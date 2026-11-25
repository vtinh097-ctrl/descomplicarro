#!/usr/bin/env python3
"""Renderiza o Reel a partir do projeto.json (EDL).

Uso: python3 scripts/render.py projeto.json [--rascunho] [--forcar]

Passos:
 1. valida legendas/gráficos (captions lint) — aborta em FALHA (salvo --forcar);
 2. gera build/legendas.ass;
 3. passo 1: monta segmentos (reenquadramento 9:16, punch-in, fades, B-roll, textos) e
    trata a voz (passa-altas, ruído, EQ, de-esser, compressor) + música autorizada → master .mov;
 4. passo 2: mede loudness do master;
 5. passo 3: ganho linear até o alvo + limitador de pico (4x) → MP4 H.264/AAC com faststart;
    mede o arquivo final e corrige o ganho uma vez se necessário.
Registra tudo em build/render_log.json. Depois rode SEMPRE scripts/qa.py.
"""
import argparse
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import (FONTS_DIR, audio_profile, die, hex_to_ffmpeg, load_project, loudness,  # noqa: E402
                       media_info, need, resolve, run, save_json, voice_recommendation)
import captions  # noqa: E402

IMG_EXT = {".png", ".jpg", ".jpeg", ".webp"}


def fps_str(fps):
    for nominal, frac in ((29.97, "30000/1001"), (23.976, "24000/1001"), (59.94, "60000/1001")):
        if abs(fps - nominal) < 0.01:
            return frac
    return f"{fps:g}"


def crop_expr(info, W, H, enq, warn, label, cfg):
    """Retorna filtro de reenquadramento para 9:16 (recortar|encaixar) + fator de ampliação."""
    v = info["video"]
    sw, sh = v["largura"], v["altura"]
    modo = enq.get("modo", "recortar")
    zoom = float(enq.get("zoom", 1.0))
    if modo == "encaixar":
        # imagem inteira, centrada sobre fundo grafite (telas, prints, B-roll horizontal)
        y = enq.get("y_px")
        scale_w = round(W * zoom / 2) * 2
        f = (f"scale={scale_w}:-2:flags=lanczos,pad={W}:{H}:(ow-iw)/2:"
             f"{y if y is not None else '(oh-ih)/2'}:color={hex_to_ffmpeg(cfg['marca']['grafite'])}")
        return f, scale_w / sw
    target = W / H
    if sw / sh > target:
        ch, cw = sh, sh * target
    else:
        cw, ch = sw, sw / target
    cw, ch = cw / zoom, ch / zoom
    cw, ch = int(cw) // 2 * 2, int(ch) // 2 * 2
    cx, cy = float(enq.get("x", 0.5)) * sw, float(enq.get("y", 0.5)) * sh
    x0 = int(min(max(0, cx - cw / 2), sw - cw))
    y0 = int(min(max(0, cy - ch / 2), sh - ch))
    amp = W / cw
    vc = cfg["video"]
    if amp > vc["ampliacao_max_bloqueio"]:
        warn(f"{label}: ampliação {amp:.2f}x acima do limite ({vc['ampliacao_max_bloqueio']}x) — "
             "perda grave de nitidez; reduzir zoom ou usar 'encaixar'")
    elif amp > vc["ampliacao_max_alerta"]:
        warn(f"{label}: ampliação {amp:.2f}x — imagem perde nitidez")
    return f"crop={cw}:{ch}:{x0}:{y0},scale={W}:{H}:flags=lanczos", amp


def color_filters(seg, info):
    f = []
    if info["video"]["hdr"]:
        f.append("zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,"
                 "zscale=t=bt709:m=bt709:r=tv")
    c = seg.get("cor") or {}
    if c:
        f.append(f"eq=brightness={c.get('brilho', 0)}:contrast={c.get('contraste', 1)}:"
                 f"saturation={c.get('saturacao', 1)}:gamma={c.get('gama', 1)}")
    return f


def prep_float(src, b, cfg, base):
    """Recorte sem fundo (PNG com alfa) → tamanho final + sombra suave abaixo (sensação de flutuar)."""
    from PIL import Image, ImageDraw, ImageFilter
    enq = b["enquadramento"]
    im = Image.open(src).convert("RGBA")
    bb = im.getbbox()
    im = im.crop(bb) if bb else im
    w = int(enq.get("largura", 300))
    h = round(w * im.height / im.width)
    im = im.resize((w, h), Image.LANCZOS)
    pad = 40
    can = Image.new("RGBA", (w + 2 * pad, h + 2 * pad), (0, 0, 0, 0))
    if enq.get("sombra", True):
        sh = Image.new("RGBA", can.size, (0, 0, 0, 0))
        ImageDraw.Draw(sh).ellipse([pad + w * 0.08, pad + h * 0.86, pad + w * 0.92, pad + h * 1.04], fill=(0, 0, 0, 120))
        can = Image.alpha_composite(can, sh.filter(ImageFilter.GaussianBlur(12)))
    can.alpha_composite(im, (pad, pad))
    out = base / "build" / f"flutuante_{Path(src).stem}_{w}.png"
    out.parent.mkdir(exist_ok=True)
    can.save(out)
    return out


def float_overlay(fc, idx, j, b, src, cur, cfg, fps, dur, base, warn):
    """Objeto flutuante: posicionado pelo centro; entra e sai piscando (liga-desliga-liga), sem fundo."""
    from PIL import Image
    enq = b["enquadramento"]
    png = src  # já preparado (prep_float) na leitura das entradas
    cw, ch = Image.open(png).size
    cx, cy = enq["centro"]
    x, y = int(cx - cw / 2), int(cy - ch / 2)
    t0, f = float(b["inicio"]), 1 / fps
    t1 = t0 + dur
    if enq.get("piscar", True):
        on = [(t0, t0 + 2 * f), (t0 + 4 * f, t1 - 4 * f), (t1 - 2 * f, t1)]
    else:
        on = [(t0, t1)]
    en = "+".join(f"between(t,{a:.4f},{c - 0.0001:.4f})" for a, c in on)
    zs = cfg["zonas_seguras"]
    if x < zs["esquerda"] or x + cw - 40 > 1080 - zs["direita"] and y + ch > zs["coluna_icones_y_inicio"]:
        warn(f"B-roll {j + 1} (flutuante): pode encostar na coluna de ícones do Instagram")
    fc.append(f"[{idx}:v:0]fps={fps_str(fps)},format=rgba,setpts=PTS-STARTPTS+{t0:.4f}/TB[fl{j}]")
    fc.append(f"[{cur}][fl{j}]overlay={x}:{y}:eof_action=pass:format=auto:enable='{en}'[vb{j}]")


def card_overlay(fc, idx, j, b, binf, cur, cfg, W, H, fps, dur, warn, dim=None):
    """Foto como card sobre o vídeo: recorte opcional, moldura de papel off-white, sombra, fundo escurecido,
    entrada com fade + leve subida (sem bounce). A voz continua por baixo."""
    cc = cfg["graficos"]["card_foto"]
    enq = b.get("enquadramento", {})
    iw, ih = binf["video"]["largura"], binf["video"]["altura"]
    rec = enq.get("recorte")  # {"x","y","w","h"} em frações da foto: mostra só o trecho que importa
    f = []
    if enq.get("girar"):  # graus, sentido horário: nivela documento fotografado torto (antes do recorte)
        f.append(f"rotate={float(enq['girar'])}*PI/180:fillcolor=black")
    if rec:
        cw, ch = int(iw * rec["w"]) // 2 * 2, int(ih * rec["h"]) // 2 * 2
        f.append(f"crop={cw}:{ch}:{int(iw * rec['x'])}:{int(ih * rec['y'])}")
        iw, ih = cw, ch
    bd = cc["borda_px"]
    maxw = int(enq.get("largura_max", cc["largura_max"])) - 2 * bd
    maxh = int(enq.get("altura_max", cc["altura_max"])) - 2 * bd
    sc = min(maxw / iw, maxh / ih)
    if sc > 1.6:
        warn(f"B-roll {j + 1} (card): foto pequena ampliada {sc:.1f}x — enviar arquivo em resolução maior")
    w, h = int(iw * sc) // 2 * 2, int(ih * sc) // 2 * 2
    cw, ch = w + 2 * bd, h + 2 * bd
    x = (W - cw) // 2
    y = int(enq.get("centro_y", cc["centro_y"]) - ch / 2)
    zs = cfg["zonas_seguras"]
    y = max(zs["topo"] + 10, y)
    if y + ch > cfg["legendas"]["base_y"] - cfg["legendas"]["tamanho"] * 2.4:
        warn(f"B-roll {j + 1} (card): card pode encostar na área da legenda — reduzir altura_max")
    t0, fd, rise, ent = float(b["inicio"]), cc["fade_s"], cc["subida_px"], cc["entrada_s"]
    nfr = round(dur * fps)
    off = hex_to_ffmpeg(cfg["marca"]["offwhite"])
    gra = hex_to_ffmpeg(cfg["marca"]["grafite"])
    fades = f"fade=t=in:st=0:d={fd}:alpha=1,fade=t=out:st={dur - fd:.3f}:d={fd}:alpha=1"
    mk = ""
    m = enq.get("marcar")  # {"x","y","w","h"} em frações da imagem já recortada: contorno laranja no trecho citado
    if m:
        lar = hex_to_ffmpeg(cfg["marca"]["laranja"])
        mk = (f"drawbox=x={int(m['x'] * w)}:y={int(m['y'] * h)}:w={int(m['w'] * w)}:h={int(m['h'] * h)}:"
              f"color={lar}:t={cc.get('marcar_px', 5)},")
    fc.append(f"[{idx}:v:0]fps={fps_str(fps)},{','.join(f + [''])}scale={w}:{h}:flags=lanczos,setsar=1,{mk}"
              f"pad={cw}:{ch}:{bd}:{bd}:color={off},trim=end_frame={nfr},setpts=PTS-STARTPTS,format=yuva420p,"
              f"{fades},setpts=PTS+{t0:.4f}/TB[card{j}]")
    # fundo escurecido: cards em sequência compartilham um único escurecimento contínuo (sem "piscar")
    if dim:
        d0, dd = dim
        dfades = f"fade=t=in:st=0:d={fd}:alpha=1,fade=t=out:st={dd - fd:.3f}:d={fd}:alpha=1"
        fc.append(f"color=c={gra}:s={W}x{H}:r={fps_str(fps)}:d={dd:.4f},format=yuva420p,"
                  f"colorchannelmixer=aa={cc['escurecer_fundo']},{dfades},setpts=PTS+{d0:.4f}/TB[dim{j}]")
    else:
        fc.append(f"color=c={gra}:s={W}x{H}:r={fps_str(fps)}:d=0.1,format=yuva420p,colorchannelmixer=aa=0,"
                  f"setpts=PTS+{t0:.4f}/TB[dim{j}]")
    fc.append(f"color=c={gra}:s={cw}x{ch}:r={fps_str(fps)}:d={dur:.4f},format=yuva420p,"
              f"colorchannelmixer=aa={cc['sombra_alfa']},boxblur=8:2,{fades},setpts=PTS+{t0:.4f}/TB[shd{j}]")
    ymove = f"{rise}*max(0,1-(t-{t0:.4f})/{ent})"
    fc.append(f"[{cur}][dim{j}]overlay=0:0:eof_action=pass:format=auto[d{j}]")
    fc.append(f"[d{j}][shd{j}]overlay={x + cc['sombra_px'] // 2}:'{y + cc['sombra_px']}+{ymove}':eval=frame:"
              f"eof_action=pass:format=auto[s{j}]")
    fc.append(f"[s{j}][card{j}]overlay={x}:'{y}+{ymove}':eval=frame:eof_action=pass:format=auto[vb{j}]")


def measure_latency(src, af, ss, rate=48000):
    """Mede o atraso (amostras) que uma cadeia de filtros introduz, usando 6 s da própria fala."""
    import subprocess
    import numpy as np

    def dec(extra):
        p = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-ss", f"{ss:.3f}", "-t", "6",
                            "-i", str(src), "-map", "0:a:0", "-ac", "1", "-ar", str(rate)] + extra +
                           ["-f", "f32le", "-"], capture_output=True)
        return np.frombuffer(p.stdout, np.float32)
    ref, out = dec([]), dec(["-af", af])
    n = min(ref.size, out.size)
    if n < rate:
        return 0
    size = 1 << (2 * n - 1).bit_length()
    corr = np.fft.irfft(np.fft.rfft(out[:n], size) * np.conj(np.fft.rfft(ref[:n], size)), size)
    maxlag = int(0.1 * rate)
    cand = np.concatenate([corr[:maxlag + 1], corr[-maxlag:]])
    k = int(np.argmax(cand))
    return k if k <= maxlag else k - len(cand)


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("projeto")
    ap.add_argument("--rascunho", action="store_true", help="encode rápido só para conferir montagem")
    ap.add_argument("--forcar", action="store_true", help="renderiza mesmo com FALHA no lint")
    args = ap.parse_args()
    need("ffmpeg")
    t0 = time.time()
    proj = load_project(args.projeto)
    cfg, base = proj["_cfg"], proj["_base"]
    vc, ac = cfg["video"], cfg["audio"]
    exp = proj.get("exportacao", {})
    W, H = int(exp.get("largura", vc["largura"])), int(exp.get("altura", vc["altura"]))
    fps = proj["_fps"]
    SR = ac["taxa"]
    build = base / "build"
    build.mkdir(exist_ok=True)
    warnings = []
    warn = warnings.append

    # 1-2. legendas e gráficos
    issues = captions.cmd_lint(proj, args, quiet=True)
    fails = [i for i in issues if i[0] == "FALHA"]
    for lvl, where, msg in issues:
        print(f"  lint {lvl:<6} {where:<14} {msg}")
    if fails and not args.forcar:
        die(f"{len(fails)} FALHA(s) no lint de legendas/gráficos — corrija ou use --forcar")
    ass_path = captions.cmd_ass(proj, argparse.Namespace(out=str(build / "legendas.ass")))

    # fontes
    infos = {k: media_info(resolve(base, p)) for k, p in proj["fontes"].items()}
    for k, inf in infos.items():
        if not inf["video"]:
            die(f"fonte {k} sem vídeo")
        if not inf["audio"]:
            die(f"fonte {k} sem áudio")
        src_fps = inf["video"]["fps"] or fps
        if abs(src_fps - fps) > 0.05 and not (abs(src_fps - 2 * fps) < 0.1):
            warn(f"fonte {k} a {src_fps:.3f} fps → saída {fps:g} fps (conversão pode gerar leve cadência)")

    inputs, fc = [], []
    idx = 0
    # 3a. segmentos
    vlabels, alabels = [], []
    tc_cfg = proj.get("transicao_cortes") or {}
    dfr = round(float(tc_cfg.get("duracao", 0.13)) * fps) if tc_cfg.get("tipo") == "dissolve" else 0
    fade_ms = ac["fade_corte_ms"] / 1000
    gra = hex_to_ffmpeg(cfg["marca"]["grafite"])
    for s in proj["segmentos"]:
        src = resolve(base, proj["fontes"][s["fonte"]])
        inf = infos[s["fonte"]]
        inputs += ["-ss", f"{s['in']:.6f}", "-t", f"{s['_dur'] + 0.5:.6f}", "-i", str(src)]
        crop, amp = crop_expr(inf, W, H, s.get("enquadramento", {}), warn, s["id"], cfg)
        s["_ampliacao"] = round(amp, 2)
        # dissolução: o trecho leva alguns quadros a mais da origem (o silêncio logo após o corte) para
        # cruzar com o próximo — a imagem do próximo trecho começa exatamente com o áudio dele
        extra = dfr if (dfr and s is not proj["segmentos"][-1]) else 0
        vf = color_filters(s, inf) + [
            f"fps={fps_str(fps)}", crop, "setsar=1", f"trim=end_frame={s['_frames'] + extra}", "setpts=PTS-STARTPTS"]
        if s.get("fade_entrada"):
            vf.append(f"fade=t=in:st=0:d={s['fade_entrada']}:color={gra}")
        if s.get("fade_saida"):
            vf.append(f"fade=t=out:st={s['_dur'] - s['fade_saida']:.4f}:d={s['fade_saida']}:color={gra}")
        vf.append("format=yuv420p")
        fc.append(f"[{idx}:v:0]{','.join(vf)}[v{idx}]")
        nsamp = round(s["_dur"] * SR)
        af = [f"aresample={SR}", "aformat=channel_layouts=mono", f"atrim=end_sample={nsamp}",
              f"apad=whole_len={nsamp}", "asetpts=PTS-STARTPTS"]
        if s.get("ganho_db"):
            af.append(f"volume={s['ganho_db']}dB")
        fin = max(fade_ms, s.get("fade_entrada") or 0)
        fout = max(fade_ms, s.get("fade_saida") or 0)
        af += [f"afade=t=in:st=0:d={fin}", f"afade=t=out:st={s['_dur'] - fout:.5f}:d={fout}"]
        fc.append(f"[{idx}:a:0]{','.join(af)}[a{idx}]")
        vlabels.append(f"[v{idx}]")
        alabels.append(f"[a{idx}]")
        idx += 1
    n = len(proj["segmentos"])
    if dfr and n > 1:
        prev = vlabels[0]
        for k, seg in enumerate(proj["segmentos"][1:], 1):
            lab = "[vcat0]" if k == n - 1 else f"[x{k}]"
            fc.append(f"{prev}{vlabels[k]}xfade=transition=fade:duration={dfr / fps:.6f}:offset={seg['_ini']:.6f}{lab}")
            prev = lab
        fc.append("".join(alabels) + f"concat=n={n}:v=0:a=1[acat0]")
    else:
        fc.append("".join(v + a for v, a in zip(vlabels, alabels)) + f"concat=n={n}:v=1:a=1[vcat0][acat0]")
    if proj["_cauda_frames"]:
        cd = proj["_cauda_frames"] / fps
        fc.append(f"[vcat0]tpad=stop_mode=add:stop_duration={cd:.6f}:color={gra}[vcat]")
        fc.append(f"[acat0]apad=pad_dur={cd:.6f}[acat]")
    else:
        fc.append("[vcat0]null[vcat]")
        fc.append("[acat0]anull[acat]")

    # 3b. B-roll
    cur = "vcat"
    amb = []
    # agrupa cards encadeados (próximo começa até 0,3 s após o fim do anterior) num só escurecimento
    brolls = proj.get("broll", [])
    is_card = [x.get("enquadramento", {}).get("modo") == "card" for x in brolls]
    dims, k = {}, 0
    while k < len(brolls):
        if not is_card[k]:
            k += 1
            continue
        g0, g1 = k, k
        while g1 + 1 < len(brolls) and is_card[g1 + 1] and \
                float(brolls[g1 + 1]["inicio"]) <= float(brolls[g1]["inicio"]) + float(brolls[g1]["duracao"]) + 0.3:
            g1 += 1
        a0 = float(brolls[g0]["inicio"])
        a1 = max(float(x["inicio"]) + float(x["duracao"]) for x in brolls[g0:g1 + 1])
        dims[g0] = (a0, a1 - a0)
        k = g1 + 1
    for j, b in enumerate(brolls):
        src = resolve(base, b["arquivo"])
        if not src.exists():
            die(f"B-roll não encontrado: {src}")
        is_img = src.suffix.lower() in IMG_EXT
        dur = float(b["duracao"])
        if b.get("enquadramento", {}).get("modo") == "flutuante":
            src = prep_float(src, b, cfg, base)  # PNG com sombra, já no tamanho final
            inputs += ["-loop", "1", "-framerate", fps_str(fps), "-t", f"{dur + 0.2:.3f}", "-i", str(src)]
            binf = None
        elif is_img:
            inputs += ["-loop", "1", "-framerate", fps_str(fps), "-t", f"{dur + 0.2:.3f}", "-i", str(src)]
            binf = media_info(src)
            binf["video"]["hdr"] = False
        else:
            inputs += ["-ss", f"{float(b.get('src_in', 0)):.3f}", "-t", f"{dur + 0.2:.3f}", "-i", str(src)]
            binf = media_info(src)
        if b.get("enquadramento", {}).get("modo") == "flutuante":
            float_overlay(fc, idx, j, b, src, cur, cfg, fps, dur, base, warn)
            cur = f"vb{j}"
            idx += 1
            continue
        if b.get("enquadramento", {}).get("modo") == "card":
            card_overlay(fc, idx, j, b, binf, cur, cfg, W, H, fps, dur, warn, dims.get(j))
            cur = f"vb{j}"
            idx += 1
            continue
        crop, amp = crop_expr(binf, W, H, b.get("enquadramento", {}), warn, f"B-roll {j + 1}", cfg)
        nfr = round(dur * fps)
        fd = float(b.get("fade", 0.15))
        vf = color_filters(b, binf) + [f"fps={fps_str(fps)}", crop, "setsar=1", f"trim=end_frame={nfr}",
                                       "setpts=PTS-STARTPTS", "format=yuva420p"]
        if fd > 0:
            vf += [f"fade=t=in:st=0:d={fd}:alpha=1", f"fade=t=out:st={dur - fd:.3f}:d={fd}:alpha=1"]
        vf.append(f"setpts=PTS+{float(b['inicio']):.4f}/TB")
        fc.append(f"[{idx}:v:0]{','.join(vf)}[b{j}]")
        fc.append(f"[{cur}][b{j}]overlay=0:0:eof_action=pass:format=auto[vb{j}]")
        cur = f"vb{j}"
        if b.get("som_ambiente_db") is not None and not is_img and binf["audio"]:
            d_ms = round(float(b["inicio"]) * 1000)
            fc.append(f"[{idx}:a:0]aresample={SR},aformat=channel_layouts=mono,atrim=duration={dur},"
                      f"afade=t=in:d=0.1,afade=t=out:st={dur - 0.15:.3f}:d=0.15,volume={b['som_ambiente_db']}dB,"
                      f"adelay={d_ms}:all=1[amb{j}]")
            amb.append(f"[amb{j}]")
        idx += 1

    # 3c. textos (ASS) — fontes da marca embarcadas
    fc.append(f"[{cur}]ass=filename='{ass_path}':fontsdir='{FONTS_DIR}',format=yuv420p[vout]")

    # 3d. voz
    au = proj.get("audio", {})
    vcfg = ac["voz"]
    main_src = resolve(base, proj["fontes"][proj["segmentos"][0]["fonte"]])
    prof = audio_profile(main_src)
    rec = voice_recommendation(prof, cfg)
    nr = au.get("reducao_ruido", vcfg["reducao_ruido"])
    nr = rec["reducao_ruido"] if nr == "auto" else nr
    chain = [f"highpass=f={au.get('passa_altas_hz', rec['passa_altas_hz'])}:p=2"]
    if au.get("declip", rec["declip"]):
        chain.append("adeclip")
    if nr and nr != "nenhuma":
        floor = min(-25, max(-80, prof.get("ruido_de_fundo_dbfs", -60)))
        chain.append(f"afftdn=nr={vcfg['reducao_ruido_niveis'][nr]}:nf={floor:.0f}:tn=1")
    eqs = au.get("eq", rec["eq"] if vcfg.get("eq_auto", True) else [])
    for e in eqs:
        chain.append(f"equalizer=f={e['f']}:t=q:w={e.get('w', 1)}:g={e['g']}")
    de = au.get("deesser", vcfg["deesser"])
    if (rec["deesser"] if de == "auto" else de):
        chain.append("deesser=i=0.4:m=0.5:f=0.5")
    if au.get("compressor", True):
        c = vcfg["compressor"]
        chain.append(f"acompressor=threshold={c['threshold_db']}dB:ratio={c['ratio']}:attack={c['attack_ms']}:"
                     f"release={c['release_ms']}:makeup={c['makeup_db']}dB")
    mid = min(max(0.0, infos[proj["segmentos"][0]["fonte"]]["duracao"] / 2 - 3), 30.0)
    lat = measure_latency(main_src, ",".join(chain), mid, SR)
    if lat > 0:
        # ex.: afftdn atrasa ~25 ms no FFmpeg 6.x — remove o atraso e repõe o fim
        chain += [f"atrim=start_sample={lat}", "asetpts=PTS-STARTPTS", f"apad=pad_len={lat}"]
    fc.append(f"[acat]{','.join(chain)}[voz]")
    voice = "voz"
    if amb:
        fc.append(f"[{voice}]{''.join(amb)}amix=inputs={1 + len(amb)}:duration=first:normalize=0[vozamb]")
        voice = "vozamb"

    # 3e. música (só autorizada)
    mus = proj.get("musica")
    total = proj["_duracao"]
    if mus and mus.get("arquivo"):
        if not mus.get("autorizada") or not mus.get("licenca"):
            die("música sem autorização/licença registrada (musica.autorizada=true e musica.licenca)")
        mc = ac["musica"]
        inputs += ["-stream_loop", "-1", "-i", str(resolve(base, mus["arquivo"]))]
        vol = mus.get("volume_db", mc["volume_db"])
        fo = mus.get("fade_out_s", mc["fade_out_s"])
        fc.append(f"[{idx}:a:0]aresample={SR},aformat=channel_layouts=mono,atrim=start={mus.get('src_in', 0)},"
                  f"asetpts=PTS-STARTPTS,atrim=duration={total:.4f},volume={vol}dB,"
                  f"afade=t=in:d={mus.get('fade_in_s', mc['fade_in_s'])},afade=t=out:st={total - fo:.3f}:d={fo}[mus]")
        if mus.get("ducking", mc["ducking"]):
            fc.append(f"[{voice}]asplit=2[vm][vsc]")
            fc.append(f"[mus][vsc]sidechaincompress=threshold={mc['ducking_threshold']}:ratio={mc['ducking_ratio']}:"
                      f"attack=30:release=450[mduck]")
            fc.append("[vm][mduck]amix=inputs=2:duration=first:normalize=0[mix]")
        else:
            fc.append(f"[{voice}][mus]amix=inputs=2:duration=first:normalize=0[mix]")
        voice = "mix"
        idx += 1
    fc.append(f"[{voice}]aformat=sample_rates={SR}:channel_layouts=stereo[aout]")

    script = build / "filtergraph.txt"
    script.write_text(";\n".join(fc) + "\n", encoding="utf-8")
    master = build / "master.mov"
    if args.rascunho:
        venc = ["-c:v", "libx264", "-preset", "veryfast", "-crf", "23"]
    else:
        venc = ["-c:v", vc["codec"], "-preset", exp.get("preset", vc["preset"]), "-crf", str(exp.get("crf", vc["crf"])),
                "-profile:v", vc["perfil"], "-level:v", vc["nivel"], "-maxrate", vc["maxrate"], "-bufsize", vc["bufsize"]]
    gop = round(fps * vc["gop_segundos"])
    cmd1 = (["ffmpeg", "-hide_banner", "-y", "-loglevel", "error", "-nostats"] + inputs +
            ["-filter_complex_script", str(script), "-map", "[vout]", "-map", "[aout]"] + venc +
            ["-pix_fmt", vc["pix_fmt"], "-g", str(gop), "-r", fps_str(fps), "-fps_mode", "cfr",
             "-colorspace", "bt709", "-color_primaries", "bt709", "-color_trc", "bt709", "-color_range", "tv",
             "-c:a", "pcm_s24le", "-t", f"{total:.6f}", str(master)])
    print(f"passo 1/3: montagem ({n} segmentos, {len(proj.get('broll', []))} B-roll) — duração {total:.2f}s")
    run(cmd1, capture=False)

    # 4-5. loudness: ganho linear até o alvo + limitador de pico (sobreamostrado 4x p/ inter-sample peaks).
    #      Mede o arquivo final e corrige o ganho uma vez se ficar fora da tolerância.
    lufs_t, tp_max = ac["lufs_alvo"], ac["true_peak_max"]
    m = loudness(master)
    if m["lufs"] is None:
        die("não foi possível medir o loudness do master (áudio vazio?)")
    print(f"passo 2/3: master {m['lufs']} LUFS / TP {m['true_peak']} dBTP")
    out = resolve(base, proj.get("saida", "saida/reel.mp4"))
    out.parent.mkdir(parents=True, exist_ok=True)
    limit = 10 ** ((tp_max - 0.5) / 20)  # margem para o overshoot do AAC
    gain = lufs_t - m["lufs"]
    final = None
    lim_af = f"aresample={SR * 4},alimiter=limit={limit:.4f}:attack=2:release=80:level=0,aresample={SR}"
    lat3 = measure_latency(main_src, lim_af, 5.0, SR)
    comp3 = f",atrim=start_sample={lat3},asetpts=PTS-STARTPTS,apad=pad_len={lat3}" if lat3 > 0 else ""
    for attempt in range(2):
        af = (f"aresample={SR * 4},volume={gain:.2f}dB,alimiter=limit={limit:.4f}:attack=2:release=80:level=0,"
              f"aresample={SR}{comp3}")
        run(["ffmpeg", "-hide_banner", "-y", "-loglevel", "error", "-i", str(master),
             "-map", "0:v", "-map", "0:a", "-c:v", "copy", "-af", af,
             "-c:a", "aac", "-b:a", ac["bitrate"], "-ar", str(SR), "-ac", str(ac["canais"]),
             "-movflags", "+faststart", "-metadata", f"title={proj.get('projeto', '')}",
             "-metadata", "comment=DESCOMPLICARRO — Editor de Reels", str(out)])
        final = loudness(out)
        delta = lufs_t - (final["lufs"] or lufs_t)
        if abs(delta) <= 0.5:
            break
        gain += delta  # o limitador comeu loudness: compensa uma vez
    limiting = gain - (lufs_t - m["lufs"])
    if gain > 12:
        warn(f"ganho de {gain:.1f} dB aplicado: gravação muito baixa — ruído de fundo sobe junto")
    if final["true_peak"] is not None and final["true_peak"] > tp_max + 0.3:
        warn(f"true peak final {final['true_peak']} dBTP acima do alvo {tp_max}")
    if limiting > 2:
        warn(f"limitador atuou forte (~{limiting:.1f} dB extra): conferir se a voz não ficou achatada")
    print(f"passo 3/3: {out}  final {final['lufs']} LUFS / TP {final['true_peak']} dBTP ({time.time() - t0:.0f}s)")

    log = {
        "projeto": str(proj["_path"]), "saida": str(out), "duracao_esperada": total, "fps": fps,
        "segmentos": [{k: s[k] for k in ("id", "fonte", "in", "out", "_ini", "_fim", "_frames", "_ampliacao")}
                      for s in proj["segmentos"]],
        "cortes": [s["_ini"] for s in proj["segmentos"][1:]],
        "broll": proj.get("broll", []), "graficos": proj.get("graficos", []),
        "voz": {"cadeia": chain, "perfil_origem": prof, "motivos": rec["motivos"],
                "latencia_compensada_amostras": {"cadeia_voz": lat, "limitador": lat3}},
        "loudness_master": m, "loudness_final": final, "ganho_db": round(gain, 2), "avisos": warnings,
        "lint": [list(i) for i in issues], "rascunho": args.rascunho,
        "comando_passo1": cmd1,
    }
    # avisos de ampliação iguais em todos os segmentos viram uma linha só
    import re as _re
    amps = [w for w in warnings if _re.match(r"S\d+: ampliação", w)]
    if len(amps) > 2 and len({w.split(": ", 1)[1] for w in amps}) == 1:
        rest = [w for w in warnings if w not in amps]
        warnings[:] = [f"todos os {len(amps)} segmentos: {amps[0].split(': ', 1)[1]}"] + rest
        log["avisos"] = warnings
    save_json(build / "render_log.json", log)
    for w in warnings:
        print(f"  AVISO: {w}")
    print("Próximo passo obrigatório: python3 scripts/qa.py", args.projeto)


if __name__ == "__main__":
    main()
