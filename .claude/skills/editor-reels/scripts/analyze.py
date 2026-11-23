#!/usr/bin/env python3
"""Diagnóstico técnico de um arquivo bruto (Etapa A).

Uso: python3 scripts/analyze.py BRUTO.mp4 [BRUTO2.mov ...] --out PASTA [--quadros 24]

Gera, por arquivo, em PASTA/<nome>/:
  analise.json  dados completos
  analise.md    relatório legível (problemas + sugestões de tratamento)
  contato.jpg   folha de contato com timecodes (OLHE esta imagem)
O relatório aponta problemas; a decisão editorial é do editor.
"""
import argparse
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import (audio_profile, black_intervals, contact_sheet, extract_frame,  # noqa: E402
                       ffmpeg_filter_log, freeze_intervals, load_config, loudness, media_info,
                       run, save_json, silences, tc, voice_recommendation)

def audio_analysis(path, info, cfg):
    res = audio_profile(path)
    if "erro" in res:
        return res
    res["loudness"] = loudness(path)
    sil_th = int(max(-50, min(-30, res["ruido_de_fundo_dbfs"] + 8)))
    res["limiar_silencio_db"] = sil_th
    res["pausas"] = [(round(x, 2), round(y, 2)) for x, y in silences(path, sil_th, 0.4)]
    res["tratamento_sugerido"] = voice_recommendation(res, cfg)
    return res


def audio_suggestions(a):
    if "erro" in a:
        return [f"Áudio: {a['erro']}"]
    return a["tratamento_sugerido"]["motivos"]


def video_analysis(path, info, cfg, sample_fps=2):
    import numpy as np
    v = info["video"]
    res = {}
    # exposição (signalstats amostrado; metadata=print escreve no stdout)
    p = run(["ffmpeg", "-hide_banner", "-nostats", "-loglevel", "error", "-i", str(path), "-map", "0:v:0",
             "-vf", f"fps={sample_fps},signalstats,metadata=print:file=-", "-f", "null", "-"], check=False)
    vals = {k: [float(x) for x in re.findall(rf"lavfi\.signalstats\.{k}=([\d.]+)", p.stdout)]
            for k in ("YAVG", "YLOW", "YHIGH", "SATAVG")}
    if vals["YAVG"]:
        yavg = np.array(vals["YAVG"])
        yhigh = np.array(vals["YHIGH"])
        ylow = np.array(vals["YLOW"])
        res["luma_media"] = round(float(np.mean(yavg)), 1)
        res["luma_min_max"] = [round(float(yavg.min()), 1), round(float(yavg.max()), 1)]
        res["pct_quadros_escuros"] = round(float(np.mean(yavg < 45)) * 100, 1)
        res["pct_altas_estouradas"] = round(float(np.mean(yhigh >= 233)) * 100, 1)
        res["pct_sombras_esmagadas"] = round(float(np.mean(ylow <= 17)) * 100, 1)
        res["saturacao_media"] = round(float(np.mean(vals["SATAVG"])), 1) if vals["SATAVG"] else None
        res["variacao_exposicao"] = round(float(np.percentile(yavg, 95) - np.percentile(yavg, 5)), 1)

    # foco: variância do laplaciano em quadros cinza reduzidos (1 fps)
    w, h = 270, int(270 * v["altura"] / v["largura"]) // 2 * 2
    p = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-i", str(path), "-map", "0:v:0",
                        "-vf", f"fps=1,scale={w}:{h}:flags=area,format=gray", "-f", "rawvideo", "-"],
                       capture_output=True)
    raw = np.frombuffer(p.stdout, np.uint8)
    nfr = raw.size // (w * h)
    sharp = []
    if nfr:
        fr = raw[: nfr * w * h].reshape(nfr, h, w).astype(np.float32)
        lap = (fr[:, 1:-1, :-2] + fr[:, 1:-1, 2:] + fr[:, :-2, 1:-1] + fr[:, 2:, 1:-1] - 4 * fr[:, 1:-1, 1:-1])
        sharp = lap.reshape(nfr, -1).var(axis=1)
        med = float(np.median(sharp))
        soft = [i for i, s in enumerate(sharp) if s < 0.45 * med]
        res["nitidez_mediana"] = round(med, 1)
        res["seg_possivel_perda_de_foco"] = _group_seconds(soft)
    res["telas_pretas"] = black_intervals(path, cfg["qa"]["black_pix_th"], 0.1)
    res["congelamentos"] = freeze_intervals(path, cfg["qa"]["freeze_ruido"], 1.0)
    log = ffmpeg_filter_log(path, vf="scdet=threshold=12")
    res["mudancas_de_cena"] = [round(float(t), 2) for t in re.findall(r"lavfi\.scd\.time: ([\d.]+)", log)]

    # custo de reenquadrar para 9:16
    W, H = v["largura"], v["altura"]
    tw, th = cfg["video"]["largura"], cfg["video"]["altura"]
    if W / H > tw / th:
        cw, ch = H * tw / th, H
    else:
        cw, ch = W, W * th / tw
    res["recorte_9x16_px"] = [round(cw), round(ch)]
    res["ampliacao_9x16"] = round(tw / cw, 2)
    res["ampliacao_com_punch_in_1_15"] = round(tw / (cw / 1.15), 2)
    return res


def _group_seconds(idx):
    out, start, prev = [], None, None
    for i in idx:
        if start is None:
            start = prev = i
        elif i == prev + 1:
            prev = i
        else:
            out.append([start, prev + 1])
            start = prev = i
    if start is not None:
        out.append([start, prev + 1])
    return out


def video_suggestions(vr, info, cfg):
    s = []
    v = info["video"]
    if v["hdr"]:
        s.append(f"Gravação em HDR ({v['cor_transferencia']}): o render converte para SDR BT.709 "
                 "(tonemap). Conferir pele e céu na prévia.")
    if v["vfr"]:
        s.append("Taxa de quadros variável (típico de celular): o render normaliza para fps constante.")
    if v["rotacao"]:
        s.append(f"Metadado de rotação {v['rotacao']}°: tratado automaticamente; conferir orientação na prévia.")
    amp = vr.get("ampliacao_9x16", 1)
    lim, blk = cfg["video"]["ampliacao_max_alerta"], cfg["video"]["ampliacao_max_bloqueio"]
    if amp > blk:
        s.append(f"Recorte 9:16 exige ampliar {amp}x: perda de nitidez inaceitável. Usar modo 'encaixar' "
                 "ou pedir material em resolução maior/vertical.")
    elif amp > lim:
        s.append(f"Recorte 9:16 exige ampliar {amp}x (punch-in 1,15 → {vr['ampliacao_com_punch_in_1_15']}x): "
                 "imagem perde nitidez. Limitar punch-ins e avisar o usuário.")
    if vr.get("pct_quadros_escuros", 0) > 20:
        s.append(f"{vr['pct_quadros_escuros']}% dos quadros escuros: avaliar correção leve de exposição "
                 "(segmento.cor.brilho/contraste); ruído de imagem vai aparecer.")
    if vr.get("pct_altas_estouradas", 0) > 25:
        s.append(f"Altas luzes estouradas em {vr['pct_altas_estouradas']}% dos quadros (janela/céu/reflexo): "
                 "não recuperável; evitar punch-in nessas áreas.")
    if vr.get("variacao_exposicao", 0) > 40:
        s.append("Exposição varia muito ao longo do arquivo (auto-exposição?): cuidado ao emendar takes distantes.")
    if vr.get("seg_possivel_perda_de_foco"):
        segs = ", ".join(f"{tc(a)}–{tc(b)}" for a, b in vr["seg_possivel_perda_de_foco"][:8])
        s.append(f"Possível perda de foco/borrado em: {segs} (confirmar na folha de contato).")
    if vr.get("telas_pretas"):
        s.append(f"Telas pretas na origem: {vr['telas_pretas'][:5]}")
    if vr.get("congelamentos"):
        s.append(f"Imagem congelada ≥1 s (quadros duplicados/travamento?): {vr['congelamentos'][:5]}")
    return s


def build_contact(path, outdir, info, n):
    dur = info["duracao"]
    frames_dir = outdir / "quadros"
    frames_dir.mkdir(parents=True, exist_ok=True)
    imgs = []
    for i in range(n):
        t = dur * (i + 0.5) / n
        png = frames_dir / f"q{i:02d}.jpg"
        extract_frame(path, t, png, width=360)
        imgs.append((str(png), tc(t)))
    cols = 6 if info["video"]["orientacao"] == "vertical" else 4
    return contact_sheet(imgs, outdir / "contato.jpg", cols=cols,
                         thumb_w=200 if cols == 6 else 300, title=Path(path).name)


def write_md(path, outdir, info, ar, vr, sa, sv):
    v, a = info["video"], info["audio"]
    L = [f"# Análise técnica — {Path(path).name}", ""]
    L.append(f"- Duração: {tc(info['duracao'])} · container {info['container']} · {info['bitrate_kbps']} kbps")
    if v:
        L.append(f"- Vídeo: {v['codec']} {v['largura']}x{v['altura']} ({v['orientacao']}) · "
                 f"{v['fps']:.3f} fps{' VFR' if v['vfr'] else ''} · {v['pix_fmt']} · "
                 f"transferência {v['cor_transferencia'] or 'n/d'}{' (HDR)' if v['hdr'] else ''}")
    if a:
        L.append(f"- Áudio: {a['codec']} {a['taxa']} Hz · {a['canais']} canal(is)")
    if ar and "loudness" in ar:
        lo = ar["loudness"]
        L.append(f"- Loudness: {lo['lufs']} LUFS · LRA {lo['lra']} LU · true peak {lo['true_peak']} dBFS · "
                 f"ruído {ar['ruido_de_fundo_dbfs']} dBFS · SNR ~{ar['snr_estimado_db']} dB")
    if vr:
        L.append(f"- Imagem: luma média {vr.get('luma_media')} · nitidez mediana {vr.get('nitidez_mediana')} · "
                 f"recorte 9:16 {vr['recorte_9x16_px']} → ampliação {vr['ampliacao_9x16']}x")
        L.append(f"- Mudanças de cena detectadas: {len(vr['mudancas_de_cena'])}")
    L += ["", "## Áudio — achados e tratamento sugerido", *[f"- {x}" for x in sa]]
    L += ["", "## Imagem — achados", *([f"- {x}" for x in sv] or ["- Nenhum problema automático detectado."])]
    if ar and ar.get("pausas"):
        longas = [p for p in ar["pausas"] if p[1] - p[0] >= 0.6]
        L += ["", f"## Pausas ≥ 0,6 s ({len(longas)})",
              ", ".join(f"{tc(p[0])}({p[1]-p[0]:.1f}s)" for p in longas[:60])]
    L += ["", "## Revisão visual obrigatória",
          "Abrir `contato.jpg` e verificar: enquadramento, olhar, foco, luz, objetos/pessoas invadindo, "
          "continuidade entre takes, oportunidades de B-roll."]
    (outdir / "analise.md").write_text("\n".join(L) + "\n", encoding="utf-8")


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("arquivos", nargs="+")
    ap.add_argument("--out", required=True)
    ap.add_argument("--quadros", type=int, default=24)
    ap.add_argument("--config")
    args = ap.parse_args()
    cfg = load_config(args.config)
    for f in args.arquivos:
        path = Path(f)
        outdir = Path(args.out) / path.stem
        outdir.mkdir(parents=True, exist_ok=True)
        print(f"→ {path.name}")
        info = media_info(path)
        ar = audio_analysis(path, info, cfg) if info["audio"] else None
        vr = video_analysis(path, info, cfg) if info["video"] else None
        sa = audio_suggestions(ar) if ar else ["SEM ÁUDIO no arquivo."]
        sv = video_suggestions(vr, info, cfg) if vr else []
        if info["video"]:
            build_contact(path, outdir, info, args.quadros)
        save_json(outdir / "analise.json", {"info": info, "audio": ar, "video": vr,
                                             "sugestoes_audio": sa, "sugestoes_video": sv})
        write_md(path, outdir, info, ar, vr, sa, sv)
        print(f"  relatório: {outdir / 'analise.md'}\n  contato:   {outdir / 'contato.jpg'}")


if __name__ == "__main__":
    main()
