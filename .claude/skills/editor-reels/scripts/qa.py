#!/usr/bin/env python3
"""Controle de qualidade obrigatório de um Reel renderizado.

Uso: python3 scripts/qa.py projeto.json [--video saida.mp4] [--out PASTA_QA]

Verificações automáticas:
  arquivo/especificação (MP4, H.264 High, 1080x1920, SAR 1, yuv420p, fps, AAC 48 kHz, faststart),
  duração e nº de quadros vs. EDL, diferença áudio/vídeo, loudness e true peak,
  telas pretas, imagem congelada, silêncios, estalos nos cortes,
  SINCRONIA por segmento (correlação do áudio de saída com a origem + casamento de quadros),
  legendas/gráficos (lint), ressalvas técnicas e cortes no meio de palavra (decupagem).
Gera quadros de prévia (início, cortes, B-roll, textos com zonas seguras, meio, fim) e folhas de contato.

IMPORTANTE: o resultado automático NÃO aprova o vídeo. A revisão visual dos quadros é obrigatória
(references/controle-qualidade.md) e o relatório lista os itens que o editor precisa confirmar.
"""
import argparse
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import (black_intervals, contact_sheet, decode_audio_mono, extract_frame,  # noqa: E402
                       ffprobe, freeze_intervals, load_json, load_project, loudness, media_info,
                       resolve, save_json, silences, tc)
import captions  # noqa: E402


class Report:
    def __init__(self):
        self.items = []

    def add(self, status, area, msg):
        self.items.append({"status": status, "area": area, "msg": msg})
        print(f"  {status:<6} {area:<12} {msg}")

    def worst(self):
        st = [i["status"] for i in self.items]
        return "FALHA" if "FALHA" in st else ("ALERTA" if "ALERTA" in st else "OK")


def faststart(path):
    with open(path, "rb") as fh:
        pos, order = 0, []
        size = Path(path).stat().st_size
        while pos < size and len(order) < 20:
            fh.seek(pos)
            hdr = fh.read(16)
            if len(hdr) < 8:
                break
            sz = int.from_bytes(hdr[:4], "big")
            typ = hdr[4:8].decode("latin1")
            if sz == 1:
                sz = int.from_bytes(hdr[8:16], "big")
            order.append(typ)
            if sz == 0:
                break
            pos += sz
    return "moov" in order and "mdat" in order and order.index("moov") < order.index("mdat")


def intentional_intervals(proj):
    """Trechos onde preto/grafite/estático é intencional (cartela, fades)."""
    iv = [(g["inicio"], g["fim"]) for g in proj.get("graficos", []) if g["tipo"] == "cartela_final"]
    if proj["_cauda_frames"]:
        iv.append((proj["_cauda_ini"], proj["_duracao"]))
    for s in proj["segmentos"]:
        if s.get("fade_entrada"):
            iv.append((s["_ini"], s["_ini"] + s["fade_entrada"] + 0.1))
        if s.get("fade_saida"):
            iv.append((s["_fim"] - s["fade_saida"] - 0.1, s["_fim"]))
    return iv


def overlaps(a, b, ivs, slack=0.15):
    return any(a < y + slack and x - slack < b for x, y in ivs)


def check_specs(rep, out, proj, cfg):
    vc, ac = cfg["video"], cfg["audio"]
    exp = proj.get("exportacao", {})
    W, H = int(exp.get("largura", vc["largura"])), int(exp.get("altura", vc["altura"]))
    data = ffprobe(out)
    info = media_info(out)
    v, a = info["video"], info["audio"]
    vs = next(s for s in data["streams"] if s["codec_type"] == "video")
    if "mp4" not in (info["container"] or ""):
        rep.add("FALHA", "formato", f"container {info['container']} (esperado MP4)")
    if not v:
        rep.add("FALHA", "formato", "sem trilha de vídeo")
        return info
    ok = (v["codec"] == "h264" and v["largura"] == W and v["altura"] == H and v["pix_fmt"] == "yuv420p"
          and vs.get("sample_aspect_ratio", "1:1") in ("1:1", "0:1", None))
    rep.add("OK" if ok else "FALHA", "formato",
            f"{v['codec']} {v['perfil']} {v['largura']}x{v['altura']} SAR {vs.get('sample_aspect_ratio')} {v['pix_fmt']}")
    if abs((v["fps"] or 0) - proj["_fps"]) > 0.01:
        rep.add("FALHA", "formato", f"fps {v['fps']:.3f} (esperado {proj['_fps']:g})")
    else:
        rep.add("OK", "formato", f"{v['fps']:.3f} fps constante" + (" (VFR?)" if v["vfr"] else ""))
    if v["cor_primarias"] not in (None, "bt709") or v["cor_transferencia"] not in (None, "", "bt709"):
        rep.add("ALERTA", "cor", f"metadados de cor {v['cor_primarias']}/{v['cor_transferencia']}")
    if not a:
        rep.add("FALHA", "formato", "sem trilha de áudio")
    else:
        ok = a["codec"] == "aac" and a["taxa"] == ac["taxa"] and a["canais"] == ac["canais"]
        rep.add("OK" if ok else "FALHA", "formato", f"áudio {a['codec']} {a['taxa']} Hz {a['canais']} canais "
                                                   f"{a['bitrate_kbps']} kbps")
    rep.add("OK" if faststart(out) else "ALERTA", "formato", "faststart (moov antes de mdat)")
    rep.add("OK", "arquivo", f"{Path(out).stat().st_size / 1e6:.1f} MB · {info['bitrate_kbps']} kbps")
    return info


def check_timing(rep, info, proj, qc):
    exp = proj["_duracao"]
    d = info["duracao"]
    st = "OK" if abs(d - exp) <= qc["tolerancia_duracao_s"] else "FALHA"
    rep.add(st, "duração", f"{d:.3f}s (EDL {exp:.3f}s)")
    v, a = info["video"], info["audio"]
    if v and a:
        diff = abs(v["duracao"] - a["duracao"])
        rep.add("OK" if diff <= qc["tolerancia_av_s"] else "FALHA", "sincronia",
                f"vídeo {v['duracao']:.3f}s × áudio {a['duracao']:.3f}s (dif {diff * 1000:.0f} ms); "
                f"início v {v['inicio']:.3f} / a {a['inicio']:.3f}")
        exp_frames = sum(s["_frames"] for s in proj["segmentos"]) + proj["_cauda_frames"]
        if v["frames"]:
            rep.add("OK" if abs(v["frames"] - exp_frames) <= 1 else "FALHA", "quadros",
                    f"{v['frames']} quadros (EDL {exp_frames})")


def envelope(x, rate, hop_ms=5):
    import numpy as np
    hop = int(rate * hop_ms / 1000)
    n = x.size // hop
    e = np.sqrt(np.mean(x[: n * hop].reshape(n, hop) ** 2, axis=1) + 1e-10)
    e = np.log(e)
    return (e - e.mean()) / (e.std() + 1e-9)


def check_sync(rep, out, proj, qc):
    """Correlaciona o áudio de cada segmento na saída com o trecho original (detecta deslocamento)."""
    import numpy as np
    rate, hop_ms, maxlag = 8000, 5, 40  # ±200 ms
    outa = decode_audio_mono(out, rate)
    lags, bad, skipped = [], [], 0
    for s in proj["segmentos"]:
        L = min(s["_dur"], 6.0)
        if L < 1.0:
            skipped += 1
            continue
        src = resolve(proj["_base"], proj["fontes"][s["fonte"]])
        sa = decode_audio_mono(src, rate, ss=s["in"], t=L)
        oa = outa[int(s["_ini"] * rate): int((s["_ini"] + L) * rate)]
        m = min(sa.size, oa.size)
        if m < rate * 0.8 or np.std(sa[:m]) < 1e-4:
            skipped += 1
            continue
        es, eo = envelope(sa[:m], rate, hop_ms), envelope(oa[:m], rate, hop_ms)
        best, bestc = 0, -9
        for k in range(-maxlag, maxlag + 1):
            a_ = es[max(0, k): len(es) + min(0, k)]
            b_ = eo[max(0, -k): len(eo) + min(0, -k)]
            c = float(np.mean(a_ * b_))
            if c > bestc:
                best, bestc = k, c
        lag_ms = best * hop_ms
        lags.append((s["id"], lag_ms, round(bestc, 2)))
        if abs(lag_ms) > qc["tolerancia_sync_ms"] and bestc > 0.3:
            bad.append(f"{s['id']} desloc. {lag_ms:+d} ms (corr {bestc:.2f})")
    if bad:
        rep.add("FALHA", "sincronia", "áudio deslocado em: " + "; ".join(bad))
    elif lags:
        worst = max(abs(x[1]) for x in lags)
        rep.add("OK", "sincronia", f"áudio de {len(lags)} segmento(s) alinhado à origem (máx {worst} ms; "
                                   f"{skipped} curto(s)/silencioso(s) não medidos)")
    return lags


def check_video_alignment(rep, out, proj):
    """Casa um quadro de saída de cada segmento com quadros vizinhos da origem (mesmo recorte)."""
    import numpy as np
    from render import crop_expr, fps_str
    cfg = proj["_cfg"]
    W, H = cfg["video"]["largura"], cfg["video"]["altura"]
    fps = proj["_fps"]
    covered = [(b["inicio"], b["inicio"] + b["duracao"]) for b in proj.get("broll", [])]
    w, h = 108, 192
    res, inconcl = [], 0

    def grab(path, t, vf):
        p = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-ss", f"{max(0, t):.4f}", "-i", str(path),
                            "-frames:v", "1", "-vf", vf + f",scale={w}:{h}:flags=area,format=gray", "-f", "rawvideo", "-"],
                           capture_output=True)
        return np.frombuffer(p.stdout, np.uint8).astype(np.float32) if len(p.stdout) == w * h else None

    for s in proj["segmentos"]:
        t_rel = min(0.6, s["_dur"] / 2)
        t_out = s["_ini"] + t_rel
        if overlaps(t_out, t_out, covered, 0.05) or s["_dur"] < 0.5:
            continue
        info = media_info(resolve(proj["_base"], proj["fontes"][s["fonte"]]))
        crop, _ = crop_expr(info, W, H, s.get("enquadramento", {}), lambda m: None, s["id"], cfg)
        o = grab(out, t_out, "null")
        if o is None:
            continue
        diffs = {}
        for k in range(-3, 4):
            src = grab(resolve(proj["_base"], proj["fontes"][s["fonte"]]), s["in"] + t_rel + k / fps,
                       f"fps={fps_str(fps)},{crop}")
            if src is not None:
                diffs[k] = float(np.mean(np.abs(src - o)))
        if len(diffs) < 5:
            continue
        best = min(diffs, key=diffs.get)
        spread = max(diffs.values()) - min(diffs.values())
        if spread < 0.6:
            inconcl += 1
            continue
        res.append((s["id"], best))
    off = [f"{i} {k:+d} quadro(s)" for i, k in res if abs(k) > 1]
    if off:
        rep.add("ALERTA", "sincronia", "imagem possivelmente deslocada: " + "; ".join(off) +
                " (confirmar nos quadros de corte)")
    elif res:
        rep.add("OK", "sincronia", f"imagem de {len(res)} segmento(s) casa com a origem (±1 quadro); "
                                   f"{inconcl} sem movimento suficiente para medir")


def check_audio(rep, out, proj, cfg):
    ac, qc = cfg["audio"], cfg["qa"]
    lo = loudness(out)
    ok_l = lo["lufs"] is not None and abs(lo["lufs"] - ac["lufs_alvo"]) <= ac["lufs_tolerancia"]
    rep.add("OK" if ok_l else "ALERTA", "loudness", f"{lo['lufs']} LUFS (alvo {ac['lufs_alvo']}) · LRA {lo['lra']} LU")
    ok_tp = lo["true_peak"] is not None and lo["true_peak"] <= ac["true_peak_max"] + 0.5
    rep.add("OK" if ok_tp else "FALHA", "loudness", f"true peak {lo['true_peak']} dBTP (máx {ac['true_peak_max']})")
    intent = intentional_intervals(proj)
    sil = [x for x in silences(out, qc["silencio_db"], qc["silencio_min_s"]) if not overlaps(x[0], x[1], intent)]
    if sil:
        rep.add("ALERTA", "áudio", "silêncios longos: " + ", ".join(f"{tc(a)}–{tc(b)}" for a, b in sil[:8]))
    else:
        rep.add("OK", "áudio", f"sem silêncio ≥{qc['silencio_min_s']}s fora de trechos intencionais")
    # estalos nos cortes
    import numpy as np
    rate = 48000
    x = decode_audio_mono(out, rate)
    clicks = []
    for t in [s["_ini"] for s in proj["segmentos"][1:]]:
        i = int(t * rate)
        if i < 0.2 * rate or i > x.size - 0.2 * rate:
            continue
        d = np.abs(np.diff(x[i - int(0.2 * rate): i + int(0.2 * rate)]))
        near = d[int(0.2 * rate) - 240: int(0.2 * rate) + 240]
        ref = np.percentile(d, 99) + 1e-6
        if near.max() > 3 * ref and near.max() > 0.05:
            clicks.append(tc(t))
    rep.add("ALERTA" if clicks else "OK", "áudio",
            ("possível estalo nos cortes: " + ", ".join(clicks)) if clicks else "sem estalos detectados nos cortes")
    # início com voz
    lpath = resolve(proj["_base"], proj.get("legendas", "legendas.json"))
    if lpath.exists():
        bl = load_json(lpath)["blocos"]
        if bl and bl[0]["ini"] > 0.6:
            rep.add("ALERTA", "gancho", f"primeira fala só em {bl[0]['ini']:.2f}s — o gancho deve começar já")


def check_image(rep, out, proj, cfg):
    qc = cfg["qa"]
    intent = intentional_intervals(proj)
    blk = black_intervals(out, qc["black_pix_th"], qc["black_min_s"])
    bad = [b for b in blk if not overlaps(b[0], b[1], intent)]
    rep.add("FALHA" if bad else "OK", "imagem",
            ("tela preta em: " + ", ".join(f"{tc(a)}–{tc(b)}" for a, b in bad)) if bad
            else f"sem tela preta não intencional ({len(blk)} intencional(is))")
    imgs = [(b["inicio"], b["inicio"] + b["duracao"]) for b in proj.get("broll", [])
            if Path(b["arquivo"]).suffix.lower() in (".png", ".jpg", ".jpeg", ".webp")]
    fr = [f for f in freeze_intervals(out, qc["freeze_ruido"], qc["freeze_min_s"])
          if not overlaps(f[0], f[1] or proj["_duracao"], intent + imgs)]
    rep.add("ALERTA" if fr else "OK", "imagem",
            ("imagem congelada em: " + ", ".join(f"{tc(a)}–{tc(b)}" for a, b in fr)) if fr
            else "sem congelamento não intencional")
    log = resolve(proj["_base"], "build/render_log.json")
    if log.exists():
        for w in load_json(log).get("avisos", []):
            rep.add("ALERTA", "render", w)


def check_text(rep, proj):
    issues = captions.cmd_lint(proj, None, quiet=True)
    nf = sum(1 for i in issues if i[0] == "FALHA")
    for lvl, where, msg in issues:
        rep.add(lvl, "legendas", f"{where}: {msg}")
    if not issues:
        rep.add("OK", "legendas", "lint sem falhas nem alertas")
    return nf


def check_ressalvas(rep, proj):
    if not proj.get("transcricoes"):
        rep.add("ALERTA", "conteúdo", "sem transcrição: ressalvas técnicas não verificadas automaticamente")
        return
    import decupagem
    for key, tpath in proj["transcricoes"].items():
        tr = load_json(resolve(proj["_base"], tpath))
        utts = decupagem.utterances(tr["palavras"])
        groups = decupagem.group_takes(utts)
        segs = [s for s in proj["segmentos"] if s["fonte"] == key]

        def inside(u):
            return any(s["in"] <= u["ini"] + 0.05 and u["fim"] - 0.05 <= s["out"] for s in segs)
        found = False
        for g in groups:
            if any(utts[i]["ressalva"] for i in g) and not any(inside(utts[i]) for i in g):
                found = True
                rep.add("ALERTA", "conteúdo", f"ressalva técnica fora da montagem ({key} {tc(utts[g[0]]['ini'])}): "
                                              f"«{utts[g[0]]['texto'][:90]}» — confirmar que o corte é intencional")
        for s in segs:
            for w in tr["palavras"]:
                for edge, name in ((s["in"], "entrada"), (s["out"], "saída")):
                    if w["ini"] + 0.03 < edge < w["fim"] - 0.03:
                        found = True
                        rep.add("ALERTA", "corte", f"{s['id']} {name} {tc(edge)} corta «{w['w']}»")
        if not found:
            rep.add("OK", "conteúdo", f"fonte {key}: ressalvas na montagem e nenhum corte no meio de palavra")


def safe_overlay(png, cfg, out_png):
    try:
        from PIL import Image, ImageDraw
    except ImportError:
        return png
    zs = cfg["zonas_seguras"]
    im = Image.open(png).convert("RGBA")
    W, H = im.size
    ov = Image.new("RGBA", im.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(ov)
    red = (230, 30, 30, 110)
    x1, y1, x2, y2 = zs["esquerda"], zs["topo"], W - zs["direita"], H - zs["base"]
    d.rectangle([0, 0, W, y1], fill=red)
    d.rectangle([0, y2, W, H], fill=red)
    d.rectangle([x2, zs["coluna_icones_y_inicio"], W, y2], fill=red)
    d.rectangle([0, y1, x1, y2], fill=red)
    # contorno nítido da área útil (visível em qualquer fundo)
    for k, col in ((0, (0, 0, 0, 255)), (4, (255, 40, 40, 255))):
        d.line([(x1 + k, y1 + k), (W - k, y1 + k)], fill=col, width=4)
        d.line([(x1 + k, y1 + k), (x1 + k, y2 - k)], fill=col, width=4)
        d.line([(x1 + k, y2 - k), (x2 - k, y2 - k)], fill=col, width=4)
        d.line([(x2 - k, zs["coluna_icones_y_inicio"]), (x2 - k, y2 - k)], fill=col, width=4)
        d.line([(x2 - k, zs["coluna_icones_y_inicio"]), (W, zs["coluna_icones_y_inicio"])], fill=col, width=4)
    Image.alpha_composite(im, ov).convert("RGB").save(out_png, quality=90)
    return out_png


def previews(out, proj, cfg, qdir):
    fdir = qdir / "quadros"
    fdir.mkdir(parents=True, exist_ok=True)
    T, fps = proj["_duracao"], proj["_fps"]
    eps = 1 / fps

    def fr(t, name, overlay=False, width=540):
        p = fdir / f"{name}.jpg"
        extract_frame(out, min(max(0, t), T - eps), p, width=None)
        if overlay:
            safe_overlay(p, cfg, fdir / f"{name}_zonas.jpg")
        return str(fdir / (f"{name}_zonas.jpg" if overlay else f"{name}.jpg"))

    sheets = {}
    ini = [(fr(t, f"inicio_{i}"), f"início {tc(t)}") for i, t in enumerate([0, 0.5, 1.0, 2.0, 3.0]) if t < T]
    meio = [(fr(T / 2, "meio"), f"meio {tc(T / 2)}")]
    fim = [(fr(T - k, f"fim_{i}"), f"fim {tc(T - k)}") for i, k in enumerate([3, 2, 1, eps]) if T - k > 0]
    sheets["inicio_meio_fim"] = contact_sheet(ini + meio + fim, qdir / "qa_inicio_meio_fim.jpg", cols=5,
                                              thumb_w=216, title="Início · meio · fim")
    cuts = []
    for i, s in enumerate(proj["segmentos"][1:], 1):
        t = s["_ini"]
        # meio quadro de margem: evita cair na fronteira e extrair o mesmo quadro duas vezes
        cuts += [(fr(t - 1.5 * eps, f"corte{i:02d}_a"), f"C{i} antes {tc(t - eps)}"),
                 (fr(t - 0.5 * eps, f"corte{i:02d}_b"), f"C{i} depois {tc(t)}")]
    if cuts:
        sheets["cortes"] = contact_sheet(cuts, qdir / "qa_cortes.jpg", cols=6, thumb_w=180, title="Cortes (antes | depois)")
    txt = []
    for j, g in enumerate(proj.get("graficos", [])):
        t = (g["inicio"] + g["fim"]) / 2
        txt.append((fr(t, f"grafico{j:02d}", overlay=True), f"{g['tipo']} {tc(t)}"))
    lpath = resolve(proj["_base"], proj.get("legendas", "legendas.json"))
    if lpath.exists():
        bl = load_json(lpath)["blocos"]
        step = max(1, len(bl) // 10)
        for b in bl[::step]:
            t = (b["ini"] + b["fim"]) / 2
            txt.append((fr(t, f"legenda_{b['id']}", overlay=True), f"{b['id']} {tc(t)}"))
        if bl:
            # recorte 100% da área da legenda para conferir nitidez/tipografia
            b = max(bl, key=lambda x: len(x["texto"]))
            p = fdir / "legenda_100pct.png"
            lc = cfg["legendas"]
            extract_frame(out, (b["ini"] + b["fim"]) / 2, p,
                          vf_extra=f"crop=1080:{int(lc['tamanho'] * 3.2)}:0:{int(lc['base_y'] - lc['tamanho'] * 2.6)}")
            sheets["legenda_100pct"] = str(p)
    for j, b in enumerate(proj.get("broll", [])):
        txt.append((fr(b["inicio"] + 0.3, f"broll{j:02d}_ini"), f"B-roll {j + 1} {tc(b['inicio'] + 0.3)}"))
    if txt:
        sheets["textos"] = contact_sheet(txt, qdir / "qa_textos_zonas.jpg", cols=5, thumb_w=216,
                                         title="Textos/B-roll (vermelho = interface do Instagram)")
    return sheets


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("projeto")
    ap.add_argument("--video")
    ap.add_argument("--out")
    args = ap.parse_args()
    proj = load_project(args.projeto)
    cfg = proj["_cfg"]
    out = Path(args.video) if args.video else resolve(proj["_base"], proj.get("saida", "saida/reel.mp4"))
    qdir = Path(args.out) if args.out else out.parent / f"qa_{out.stem}"
    qdir.mkdir(parents=True, exist_ok=True)
    rep = Report()
    if not out.exists() or out.stat().st_size < 1000:
        rep.add("FALHA", "arquivo", f"{out} inexistente ou vazio")
        sys.exit(1)
    print(f"QA de {out}")
    info = check_specs(rep, out, proj, cfg)
    check_timing(rep, info, proj, cfg["qa"])
    check_sync(rep, out, proj, cfg["qa"])
    check_video_alignment(rep, out, proj)
    check_audio(rep, out, proj, cfg)
    check_image(rep, out, proj, cfg)
    check_text(rep, proj)
    check_ressalvas(rep, proj)
    sheets = previews(out, proj, cfg, qdir)
    status = rep.worst()
    manual = [
        "Abrir qa_inicio_meio_fim.jpg: gancho visível no 1º quadro? enquadramento? fecho limpo?",
        "Abrir qa_cortes.jpg: algum corte com salto estranho, olho fechado, boca torta, mudança de luz?",
        "Abrir qa_textos_zonas.jpg: nenhum texto sobre áreas vermelhas; destaques corretos; leitura fácil?",
        "Abrir quadros/legenda_100pct.png: nitidez, contorno, acentuação e pontuação corretas?",
        "Conferir ortografia de TODAS as legendas no legendas.json revisado.",
        "Conferir correção técnica do conteúdo e presença das ressalvas.",
        "Sincronia labial: conferir quadros de corte e o relatório de sincronia.",
        "Registrar limitações que não puderam ser corrigidas.",
    ]
    md = [f"# QA — {out.name}", "", f"**Resultado automático: {status}** · revisão visual: **PENDENTE**", "",
          "| Status | Área | Verificação |", "|---|---|---|"]
    md += [f"| {i['status']} | {i['area']} | {i['msg'].replace('|', '/')} |" for i in rep.items]
    md += ["", "## Prévias geradas"] + [f"- {k}: `{v}`" for k, v in sheets.items() if v]
    md += ["", "## Revisão visual obrigatória (o editor preenche)"] + [f"- [ ] {m}" for m in manual]
    (qdir / "relatorio.md").write_text("\n".join(md) + "\n", encoding="utf-8")
    save_json(qdir / "relatorio.json", {"video": str(out), "resultado_automatico": status,
                                        "itens": rep.items, "previas": sheets, "revisao_visual": "PENDENTE"})
    print(f"\nResultado automático: {status} — relatório {qdir / 'relatorio.md'}")
    print("Revisão visual PENDENTE: abra as folhas de prévia antes de entregar.")
    sys.exit(1 if status == "FALHA" else 0)


if __name__ == "__main__":
    main()
