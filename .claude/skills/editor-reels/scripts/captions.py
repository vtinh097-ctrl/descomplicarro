#!/usr/bin/env python3
"""Legendas e gráficos de texto do Reel (padrão DESCOMPLICARRO).

Subcomandos:
  build  projeto.json            → cria legendas.json (rascunho a REVISAR pelo editor)
  lint   projeto.json            → valida legendas + gráficos (sincronia, leitura, zonas seguras)
  ass    projeto.json [--out f]  → gera o .ass usado pelo render (legendas + gráficos)

Fluxo: build → editor revisa legendas.json (texto, pontuação, termos, destaques) → lint → render.
O texto revisado manda; os tempos vêm das palavras. Para forçar quebra de linha use "\\n" no texto.
"""
import argparse
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import (TextMeasurer, die, hex_to_ass, load_json, load_project, resolve,  # noqa: E402
                       save_json)

NUM_RE = re.compile(r"^(R\$)?\d+([.,]\d+)?(%|km|mil|kg|cv|nm|rpm|l|litros?|anos?)?[,.!?]?$", re.I)
_meas = TextMeasurer()


# ================================================================ BUILD
def mapped_words(proj):
    """Palavras de todas as fontes, mapeadas para o tempo de saída, em ordem."""
    trans = {k: load_json(resolve(proj["_base"], v)) for k, v in proj.get("transcricoes", {}).items()}
    out = []
    for s in proj["segmentos"]:
        tr = trans.get(s["fonte"])
        if not tr or s.get("sem_legenda"):
            continue
        for w in tr["palavras"]:
            mid = (w["ini"] + w["fim"]) / 2
            if s["in"] <= mid <= s["out"]:
                ini = max(w["ini"], s["in"]) - s["in"] + s["_ini"]
                fim = min(w["fim"], s["out"]) - s["in"] + s["_ini"]
                out.append({"w": w["w"], "ini": round(ini, 3), "fim": round(fim, 3),
                            "prob": w.get("prob", 1), "seg": s["id"]})
    return out


def fits(text, lc):
    return len(wrap(text, lc)) <= lc["max_linhas"] and len(text) <= lc["max_caracteres_bloco"]


def wrap(text, lc):
    """Quebra em linhas pela largura real; equilibra 2 linhas; evita palavra fraca no fim da linha."""
    if "\n" in text:
        return [x.strip() for x in text.split("\n")]
    fam, bold, size, maxw = lc["fonte"], lc["negrito"], lc["tamanho"], lc["largura_max_px"]
    if _meas.width(text, fam, bold, size) <= maxw:
        return [text]
    words = text.split()
    weak = set(lc["palavras_fracas_fim_de_linha"])
    best, best_score = None, None
    for i in range(1, len(words)):
        a, b = " ".join(words[:i]), " ".join(words[i:])
        wa, wb = _meas.width(a, fam, bold, size), _meas.width(b, fam, bold, size)
        if max(wa, wb) > maxw:
            continue
        score = abs(wa - wb)
        if words[i - 1].lower().strip(",.") in weak:
            score += 400
        if re.search(r"[,.;:!?]$", words[i - 1]):
            score -= 150  # quebrar após pontuação é natural
        if wa < wb:
            score += 60  # preferir linha de cima um pouco maior (pirâmide invertida)
        if best_score is None or score < best_score:
            best, best_score = [a, b], score
    return best or [text] * 3  # sinaliza "não cabe" (3 linhas → lint falha)


def _txt(ws):
    return " ".join(x["w"] for x in ws)


def split_phrase(ws, lc):
    """Divide uma frase longa no ponto mais natural, recursivamente, até cada parte caber."""
    if len(ws) <= 1 or (fits(_txt(ws), lc) and ws[-1]["fim"] - ws[0]["ini"] <= lc["max_duracao_s"]):
        return [ws]
    weak = set(lc["palavras_fracas_fim_de_linha"])
    best, best_score = None, None
    for i in range(1, len(ws)):
        left, right = ws[:i], ws[i:]
        lw = left[-1]["w"].lower().strip(",.;:!?")
        score = abs(len(_txt(left)) - len(_txt(right)))
        if lw in weak:
            score += 300
        if re.search(r"[,;:]$", left[-1]["w"]):
            score -= 120
        score -= min(0.6, right[0]["ini"] - left[-1]["fim"]) * 400  # pausa natural
        if right[0]["w"].lower() in ("de", "do", "da", "dos", "das"):
            score += 150  # não separar locuções ("tipo | de motor")
        if len(left) == 1 or len(right) == 1:
            score += 500  # palavra órfã
        if best_score is None or score < best_score:
            best, best_score = i, score
    return split_phrase(ws[:best], lc) + split_phrase(ws[best:], lc)


def chunk(words, lc):
    """Frases (pausa, fim de sentença, troca de segmento com pausa) → blocos legíveis."""
    phrases, cur = [], []
    for i, w in enumerate(words):
        cur.append(w)
        nxt = words[i + 1] if i + 1 < len(words) else None
        if nxt is None:
            break
        gap = nxt["ini"] - w["fim"]
        if gap >= lc["pausa_quebra_s"] or re.search(r"[.!?…]$", w["w"]) or (nxt["seg"] != w["seg"] and gap > 0.15):
            phrases.append(cur)
            cur = []
    if cur:
        phrases.append(cur)
    blocks = []
    for ph in phrases:
        blocks += split_phrase(ph, lc)
    return blocks


def clean_text(txt, lc):
    txt = re.sub(r"\s+", " ", txt).strip()
    txt = re.sub(r"\s+([,.;:!?])", r"\1", txt)
    if lc.get("remover_pontuacao_final", True):
        txt = re.sub(r"[,;:]$", "", txt)
        txt = re.sub(r"(?<![.])\.$", "", txt)  # remove ponto final simples, mantém reticências
    return txt


def cmd_build(proj, args):
    lc = proj["_cfg"]["legendas"]
    words = mapped_words(proj)
    if not words:
        die("nenhuma palavra mapeada — confira 'transcricoes' e os tempos dos segmentos")
    blocks = chunk(words, lc)
    out = []
    glob = [g.lower() for g in proj.get("destaques_globais", [])]
    for i, b in enumerate(blocks):
        ini = b[0]["ini"] - 0.04
        fim = b[-1]["fim"] + 0.12
        if out:
            ini = max(ini, out[-1]["fim"])
        out.append({"id": f"L{i + 1:03d}", "ini": round(max(0, ini), 3), "fim": round(fim, 3),
                    "texto": clean_text(" ".join(x["w"] for x in b), lc), "destaques": [],
                    "conf_min": round(min(x["prob"] for x in b), 2)})
    for i, blk in enumerate(out):
        nxt = out[i + 1] if i + 1 < len(out) else None
        if nxt and nxt["ini"] - blk["fim"] < 0.35:
            blk["fim"] = nxt["ini"]  # legendas contínuas: sem piscar entre blocos
        if blk["fim"] - blk["ini"] < lc["min_duracao_s"]:
            limit = nxt["ini"] if nxt else proj["_duracao"]
            blk["fim"] = round(min(limit, blk["ini"] + lc["min_duracao_s"]), 3)
        toks = blk["texto"].split()
        blk["destaques"] = [t.strip(",.!?") for t in toks if NUM_RE.match(t)] + \
                           [g for g in glob if g in blk["texto"].lower()]
        blk["fim"] = round(min(blk["fim"], proj["_duracao"]), 3)
    dest = resolve(proj["_base"], proj.get("legendas", "legendas.json"))
    if dest.exists() and not args.force:
        die(f"{dest} já existe (revisado?). Use --force para sobrescrever.")
    save_json(dest, {"_instrucoes": "Revisar texto, pontuação, termos técnicos e destaques (máx. 1–2 por bloco). "
                     "Tempos podem ser ajustados manualmente. Use \\n para quebra manual.",
                     "blocos": out})
    low = [b for b in out if b["conf_min"] < 0.5]
    print(f"{len(out)} blocos → {dest}  ({len(low)} com palavra de baixa confiança: "
          f"{', '.join(b['id'] for b in low[:15])})")


# ================================================================ LINT
def caption_box(lines, lc, zs):
    size = lc["tamanho"]
    h = size * lc["entrelinha"] * len(lines)
    bottom = lc["base_y"]
    maxw = max(_meas.width(l, lc["fonte"], lc["negrito"], size) for l in lines)
    cx = (lc["margem_esquerda"] + (1080 - lc["margem_direita"])) / 2
    return cx - maxw / 2, bottom - h, cx + maxw / 2, bottom


def cmd_lint(proj, args, quiet=False):
    cfg = proj["_cfg"]
    lc, zs = cfg["legendas"], cfg["zonas_seguras"]
    W, H = cfg["video"]["largura"], cfg["video"]["altura"]
    path = resolve(proj["_base"], proj.get("legendas", "legendas.json"))
    issues = []

    def add(level, where, msg):
        issues.append((level, where, msg))

    blocks = load_json(path)["blocos"] if path.exists() else []
    if not blocks and proj.get("transcricoes"):
        add("FALHA", "legendas", f"{path} ausente — rode 'captions.py build'")
    weak = set(lc["palavras_fracas_fim_de_linha"])
    prev = None
    for b in blocks:
        d = b["fim"] - b["ini"]
        lines = wrap(b["texto"], lc)
        nchar = len(b["texto"].replace("\n", " "))
        if len(lines) > lc["max_linhas"]:
            add("FALHA", b["id"], f"não cabe em {lc['max_linhas']} linhas: «{b['texto']}»")
        for ln in lines[:-1]:
            if ln.split() and ln.split()[-1].lower().strip(",.") in weak:
                add("ALERTA", b["id"], f"linha termina em palavra fraca: «{ln}»")
        if d < lc["min_duracao_s"] - 1e-3:
            add("ALERTA", b["id"], f"duração curta ({d:.2f}s)")
        if d > lc["max_duracao_s"] + 0.5:
            add("ALERTA", b["id"], f"duração longa ({d:.2f}s) — dividir?")
        if d > 0 and nchar / d > lc["max_cps"]:
            add("ALERTA", b["id"], f"leitura rápida ({nchar / d:.0f} car/s > {lc['max_cps']})")
        if prev and b["ini"] < prev["fim"] - 1e-3:
            add("FALHA", b["id"], f"sobrepõe {prev['id']}")
        if prev and 0 < b["ini"] - prev["fim"] < 0.12:
            add("ALERTA", b["id"], "intervalo de <0,12s entre blocos (pisca)")
        if re.search(r"\s[,.;:!?]|\s{2,}", b["texto"]):
            add("ALERTA", b["id"], "espaço antes de pontuação ou espaço duplo")
        if len(b.get("destaques", [])) > 2:
            add("ALERTA", b["id"], "mais de 2 destaques — destaque perde força")
        for h in b.get("destaques", []):
            if h.lower() not in b["texto"].lower():
                add("FALHA", b["id"], f"destaque «{h}» não está no texto")
        x0, y0, x1, y1 = caption_box(lines, lc, zs)
        if y0 < zs["topo"] or y1 > H - zs["base"]:
            add("FALHA", b["id"], "legenda fora da zona segura vertical")
        if x0 < zs["esquerda"] or x1 > W - zs["direita"]:
            add("FALHA", b["id"], f"legenda invade margem lateral ({x0:.0f}–{x1:.0f}px)")
        if b["fim"] > proj["_duracao"] + 0.01:
            add("FALHA", b["id"], "termina depois do fim do vídeo")
        prev = b
    gs = proj.get("graficos", [])
    for i, g in enumerate(gs):
        gid = f"G{i + 1:02d}:{g['tipo']}"
        d = g["fim"] - g["inicio"]
        words = len(re.findall(r"\w+", " ".join(str(g.get(k, "")) for k in
                                                ("texto", "valor", "rotulo", "termo", "explicacao", "apoio", "cta"))))
        if g["tipo"] == "comparacao":
            words += len(re.findall(r"\w+", " ".join(" ".join(r) for r in g.get("linhas", []))))
            fs = g.get("tamanho", 56)
            colw = (W - 150 - 90) / 2 - 34 - 20
            for l, r in g.get("linhas", []):
                if _meas.width(l.upper(), "Space Grotesk", True, fs) > colw or \
                        _meas.width(r, "DM Sans 14pt Medium", False, fs) > colw:
                    add("FALHA", gid, f"linha «{l} = {r}» não cabe na coluna — encurtar ou reduzir 'tamanho'")
        need = max(1.5, 0.25 * words + 0.5) if g["tipo"] not in ("cartela_final", "cartela") else 1.2
        if d < need - 0.05:
            add("ALERTA", gid, f"tempo de leitura curto ({d:.1f}s < {need:.1f}s)")
        if g["fim"] > proj["_duracao"] + 0.01:
            add("FALHA", gid, "termina depois do fim do vídeo")
        for j, h in enumerate(gs[:i]):
            if h["tipo"] == g["tipo"] and h["inicio"] < g["fim"] and g["inicio"] < h["fim"]:
                add("FALHA", gid, f"sobrepõe outro gráfico do mesmo tipo (G{j + 1:02d})")
        if g["tipo"] == "cartela_final":
            for b in blocks:
                if b["ini"] < g["fim"] and g["inicio"] < b["fim"]:
                    add("ALERTA", gid, f"legenda {b['id']} sob a cartela final (fica oculta)")
        if g["tipo"] in ("titulo", "termo", "selo", "nota"):
            txt = g.get("texto") or g.get("termo", "")
            st = cfg["graficos"].get(g["tipo"], cfg["graficos"]["titulo"])
            if g["tipo"] != "nota":
                lines = wrap_title(txt.upper() if st.get("caixa_alta", True) else txt, st)
                if len(lines) > 3:
                    add("FALHA", gid, "título longo demais (>3 linhas) — encurtar")
    if not quiet:
        for lvl, where, msg in issues:
            print(f"  {lvl:<6} {where:<16} {msg}")
        nf = sum(1 for x in issues if x[0] == "FALHA")
        print(f"lint: {len(blocks)} blocos, {len(gs)} gráficos · {nf} falha(s), {len(issues) - nf} alerta(s)")
    return issues


def wrap_text(text, family, bold, size, maxw):
    words, lines, cur = text.split(), [], ""
    for w in words:
        cand = (cur + " " + w).strip()
        if cur and _meas.width(cand, family, bold, size) > maxw:
            lines.append(cur)
            cur = w
        else:
            cur = cand
    if cur:
        lines.append(cur)
    # evita linha final órfã de uma palavra curta
    if len(lines) > 1 and len(lines[-1].split()) == 1:
        prev = lines[-2].split()
        if len(prev) > 2:
            lines[-2], lines[-1] = " ".join(prev[:-1]), prev[-1] + " " + lines[-1]
    return lines


def wrap_title(text, st, maxw=840):
    """Título: menor nº de linhas; com 2+ linhas, quebra equilibrada sem palavra fraca no fim da linha."""
    size = st["tamanho"]
    lines = wrap_text(text, "Space Grotesk", True, size, maxw)
    if len(lines) != 2:
        return lines
    words = text.split()
    weak = {"A", "O", "AS", "OS", "E", "DE", "DA", "DO", "EM", "NO", "NA", "COM", "POR", "PRA", "PARA", "QUE", "UM", "UMA"}
    best, score = lines, None
    for i in range(1, len(words)):
        l1, l2 = " ".join(words[:i]), " ".join(words[i:])
        w1, w2 = _meas.width(l1, "Space Grotesk", True, size), _meas.width(l2, "Space Grotesk", True, size)
        if max(w1, w2) > maxw:
            continue
        sc = abs(w1 - w2) + (500 if words[i - 1].upper().strip(",.") in weak else 0) + (300 if i == len(words) - 1 else 0)
        if score is None or sc < score:
            best, score = [l1, l2], sc
    return best


# ================================================================ ASS
def esc(t):
    return t.replace("\\", "").replace("{", "(").replace("}", ")")


def ass_time(t):
    t = max(0, t)
    h = int(t // 3600)
    m = int(t % 3600 // 60)
    s = t - 3600 * h - 60 * m
    cs = int(round(s * 100))
    if cs >= 6000:
        cs = 5999
    return f"{h}:{m:02d}:{cs // 100:02d}.{cs % 100:02d}"


def highlight(text, terms, base, hi):
    """Colore em laranja os termos (case-insensitive, palavra inteira)."""
    out = esc(text)
    for term in sorted(set(terms), key=len, reverse=True):
        if not term:
            continue
        pat = re.compile(r"(?<![\w])(" + re.escape(esc(term)) + r")(?![\w])", re.I)
        out = pat.sub(lambda m: "{\\1c" + hi + "&}" + m.group(1) + "{\\1c" + base + "&}", out, count=1)
    return out


def rect(x, y, w, h, color, alpha=0.0, layer_tag=""):
    a = f"{round(alpha * 255):02X}"
    return (f"{{\\an7\\pos({x:.0f},{y:.0f})\\bord0\\shad0\\1c{color}&\\1a&H{a}&{layer_tag}\\p1}}"
            f"m 0 0 l {w:.0f} 0 l {w:.0f} {h:.0f} l 0 {h:.0f}{{\\p0}}")


def cmd_ass(proj, args):
    cfg = proj["_cfg"]
    lc, gc, mk = cfg["legendas"], cfg["graficos"], cfg["marca"]
    W, H = cfg["video"]["largura"], cfg["video"]["altura"]
    OFF, GRA, LAR = hex_to_ass(mk["offwhite"]), hex_to_ass(mk["grafite"]), hex_to_ass(mk["laranja"])
    base_c = "&H" + OFF[4:]
    hi_c = "&H" + LAR[4:]
    gra_c = "&H" + GRA[4:]
    shadow = hex_to_ass(mk["grafite"], lc["sombra_alfa"])
    mv = H - lc["base_y"]
    styles = [
        # Name,Font,Size,Primary,Secondary,Outline,Back,Bold,Italic,U,S,ScX,ScY,Sp,Ang,BS,Outl,Shad,Al,ML,MR,MV,Enc
        f"Legenda,{lc['fonte']},{lc['tamanho']},{OFF},{OFF},{GRA},{shadow},{-1 if lc['negrito'] else 0},0,0,0,100,100,0,0,1,"
        f"{lc['contorno_px']},{lc['sombra_px']},2,{lc['margem_esquerda']},{lc['margem_direita']},{mv},1",
        f"Titulo,Space Grotesk,{gc['titulo']['tamanho']},{OFF},{OFF},{hex_to_ass(mk['grafite'], gc['titulo']['caixa_alfa'])},"
        f"&HFF000000,-1,0,0,0,100,100,0.5,0,3,{gc['titulo']['padding']},0,8,90,150,{gc['titulo']['y_topo']},1",
        f"Dado,Space Grotesk,{gc['dado']['tamanho']},{OFF},{OFF},{GRA},{shadow},-1,0,0,0,100,100,0,0,1,6,3,5,90,150,0,1",
        f"DadoRotulo,DM Sans 14pt SemiBold,{gc['dado']['tamanho_rotulo']},{OFF},{OFF},{GRA},{shadow},0,0,0,0,100,100,0,0,1,4,2,8,90,150,0,1",
        f"Termo,Space Grotesk,{gc['termo']['tamanho']},{OFF},{OFF},{hex_to_ass(mk['grafite'], 0.1)},&HFF000000,-1,0,0,0,100,100,0.5,0,3,18,0,7,90,150,0,1",
        f"TermoApoio,DM Sans 14pt Medium,{gc['termo']['tamanho_apoio']},{OFF},{OFF},{hex_to_ass(mk['grafite'], 0.1)},&HFF000000,0,0,0,0,100,100,0,0,3,14,0,7,90,150,0,1",
        f"Rotulo,{gc['rotulo']['fonte']},{gc['rotulo']['tamanho']},{OFF},{OFF},{GRA},{shadow},0,0,0,0,100,100,1,0,1,4,2,1,90,150,0,1",
        f"RotuloApoio,DM Sans 14pt Medium,{gc['rotulo']['tamanho_apoio']},{OFF},{OFF},{GRA},{shadow},0,0,0,0,100,100,1,0,1,3,2,7,90,150,0,1",
        f"Capitulo,Space Grotesk Medium,{gc['capitulo']['tamanho']},{OFF},{OFF},{GRA},{shadow},0,0,0,0,100,100,2,0,1,3,1,7,90,150,{gc['capitulo']['y_topo']},1",
        f"Nota,DM Sans 14pt SemiBold,40,{OFF},{OFF},{hex_to_ass(mk['grafite'], 0.08)},&HFF000000,0,0,0,0,100,100,0,0,3,18,0,8,110,170,{gc['titulo']['y_topo']},1",
        f"Cartela,Space Grotesk,{gc['cartela_final']['tamanho']},{OFF},{OFF},{GRA},&HFF000000,-1,0,0,0,100,100,1,0,1,0,0,5,90,90,0,1",
        f"CartelaCTA,DM Sans 14pt Medium,{gc['cartela_final']['tamanho_cta']},{OFF},{OFF},{GRA},&HFF000000,0,0,0,0,100,100,0,0,1,0,0,5,110,110,0,1",
        f"Forma,DM Sans,10,{OFF},{OFF},{GRA},&HFF000000,0,0,0,0,100,100,0,0,1,0,0,7,0,0,0,1",
    ]
    ev = []

    def E(layer, a, b, style, text):
        ev.append(f"Dialogue: {layer},{ass_time(a)},{ass_time(b)},{style},,0,0,0,,{text}")

    fi, fo = gc["fade_ms"], gc["fade_ms"]
    cx = (90 + (W - 150)) / 2
    # ---- legendas
    lpath = resolve(proj["_base"], proj.get("legendas", "legendas.json"))
    if lpath.exists() and not proj.get("sem_legendas"):
        for b in load_json(lpath)["blocos"]:
            txt = b["texto"].upper() if lc.get("caixa_alta") else b["texto"]
            lines = wrap(txt, lc)
            body = "\\N".join(highlight(l, b.get("destaques", []), base_c, hi_c) for l in lines)
            E(5, b["ini"], b["fim"], "Legenda", f"{{\\fad({lc['fade_entrada_ms']},{lc['fade_saida_ms']})}}{body}")
    # ---- gráficos
    for g in proj.get("graficos", []):
        a, b, t = g["inicio"], g["fim"], g["tipo"]
        hl = g.get("destaques", [])
        if t == "titulo":
            st = gc["titulo"]
            txt = g["texto"].upper() if st.get("caixa_alta", True) else g["texto"]
            lines = wrap_title(txt, st)
            body = "\\N".join(highlight(l, hl, base_c, hi_c) for l in lines)
            if g.get("ponto_laranja", True) and body.endswith("."):
                body = body[:-1] + "{\\1c" + hi_c + "&}.{\\1c" + base_c + "&}"
            y = g.get("y", st["y_topo"])  # subir/descer para não cobrir rostos
            E(3, a, b, "Titulo", f"{{\\fad({fi},{fo})\\move({cx:.0f},{y + 14},{cx:.0f},{y},0,220)}}{body}")
        elif t == "dado":
            st = gc["dado"]
            y = g.get("y", st["y_centro"])
            E(3, a, b, "Dado", f"{{\\fad({fi},{fo})\\pos({cx:.0f},{y})}}{highlight(g['valor'], hl, base_c, hi_c)}")
            E(3, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(cx - 60, y + st["tamanho"] * 0.5, 120, 6, hi_c))
            if g.get("rotulo"):
                E(3, a, b, "DadoRotulo", f"{{\\fad({fi},{fo})\\pos({cx:.0f},{y + st['tamanho'] * 0.5 + 26:.0f})}}"
                                         f"{esc(g['rotulo'])}")
        elif t in ("termo", "selo"):
            # selo = mesmo desenho do termo, para contexto (evento, local, data): "termo" + "explicacao"
            st = gc["termo"]
            y = g.get("y", st["y_topo"])
            y2 = y + st["tamanho"] + 40
            bar_h = (y2 + st["tamanho_apoio"] + 14 if g.get("explicacao") else y + st["tamanho"] + 18) - (y - 18)
            E(4, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(100, y - 18, 8, bar_h, hi_c))
            E(3, a, b, "Termo", f"{{\\fad({fi},{fo})\\pos(126,{y})}}{esc(g['termo'].upper())}")
            if g.get("explicacao"):
                E(3, a, b, "TermoApoio", f"{{\\fad({fi},{fo})\\pos(126,{y2})}}"
                                         f"{highlight(g['explicacao'], hl, base_c, hi_c)}")
        elif t == "rotulo":
            # lado: "esquerda" (padrão) ou "direita" — o rótulo fica do lado de QUEM está sendo nomeado
            st = gc["rotulo"]
            zs = cfg["zonas_seguras"]
            if g.get("lado") == "direita":
                xr = W - zs["direita"] - 20
                y = g.get("y", zs["coluna_icones_y_inicio"] - 40)
                E(3, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(xr - 64, y - st["tamanho"] - 26, 64, 5, hi_c))
                E(3, a, b, "Rotulo", f"{{\\fad({fi},{fo})\\an3\\pos({xr},{y})}}{esc(g['texto'].upper())}")
                if g.get("apoio"):
                    E(3, a, b, "RotuloApoio", f"{{\\fad({fi},{fo})\\an9\\pos({xr},{y + 12})}}{esc(g['apoio'])}")
            else:
                y = g.get("y", st["y_base"])
                E(3, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(90, y - st["tamanho"] - 26, 64, 5, hi_c))
                E(3, a, b, "Rotulo", f"{{\\fad({fi},{fo})\\pos(90,{y})}}{esc(g['texto'].upper())}")
                if g.get("apoio"):
                    E(3, a, b, "RotuloApoio", f"{{\\fad({fi},{fo})\\pos(90,{y + 12})}}{esc(g['apoio'])}")
        elif t == "comparacao":
            # tabela curta "A = B" (analogias, equivalências): caixa grafite, esquerda Space Grotesk caixa alta,
            # direita DM Sans, "=" laranja; linhas entram em sequência (escalonar, s)
            rows = g["linhas"]
            head = g.get("cabecalho")
            fs = g.get("tamanho", 56)  # ≥ ~50: legível no celular (40 ficou pequeno demais no 1º uso)
            rh = int(fs * 1.4)
            top = g.get("y", 720)
            x0, x1 = 90, W - 150
            mid = (x0 + x1) / 2
            nrow = len(rows) + (1 if head else 0)
            box_h = nrow * rh + 48
            E(2, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(x0, top, x1 - x0, box_h, gra_c, 0.08))
            E(2, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(x0, top, 8, box_h, hi_c))
            y = top + 24 + rh / 2
            st_ = g.get("escalonar", 0.3)
            if head:
                E(3, a, b, "TermoApoio", f"{{\\fad({fi},{fo})\\an6\\bord0\\shad0\\fs{int(fs * 0.65)}\\1c{hi_c}&\\pos({mid - 34:.0f},{y:.0f})}}{esc(head[0].upper())}")
                E(3, a, b, "TermoApoio", f"{{\\fad({fi},{fo})\\an4\\bord0\\shad0\\fs{int(fs * 0.65)}\\1c{hi_c}&\\pos({mid + 34:.0f},{y:.0f})}}{esc(head[1].upper())}")
                y += rh
            for k, (l, r) in enumerate(rows):
                a_k = a + st_ * (k + 1)
                if a_k >= b:
                    break
                E(3, a_k, b, "Termo", f"{{\\fad({fi},{fo})\\an6\\bord0\\fs{fs}\\pos({mid - 34:.0f},{y:.0f})}}{esc(l.upper())}")
                E(3, a_k, b, "Termo", f"{{\\fad({fi},{fo})\\an5\\bord0\\fs{fs}\\1c{hi_c}&\\pos({mid:.0f},{y:.0f})}}=")
                E(3, a_k, b, "TermoApoio", f"{{\\fad({fi},{fo})\\an4\\bord0\\fs{fs}\\pos({mid + 34:.0f},{y:.0f})}}{highlight(r, hl, base_c, hi_c)}")
                y += rh
        elif t == "cartela":
            # cartela intermediária: tela grafite com uma frase (virada de bloco ou para cobrir imagem
            # inutilizável). Fica ABAIXO das legendas — a fala continua legendada.
            st = gc["cartela_final"]
            E(4, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(0, 0, W, H, gra_c))
            E(4, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(W / 2 - 60, H / 2 - 230, 120, 8, hi_c))
            lines = wrap_title(g["texto"].upper(), {"tamanho": st["tamanho"]}, 840)
            body = "\\N".join(highlight(l, hl, base_c, hi_c) for l in lines)
            E(4, a, b, "Cartela", f"{{\\fad({fi},{fo})\\an8\\pos({W / 2:.0f},{H / 2 - 180:.0f})}}{body}")
        elif t == "capitulo":
            E(3, a, b, "Capitulo", f"{{\\fad({fi},{fo})}}" + highlight(g["texto"], hl or
                                   re.findall(r"N\.\d+", g["texto"]), base_c, hi_c))
        elif t == "nota":
            E(3, a, b, "Forma", f"{{\\fad({fi},{fo})}}" + rect(110 - 18 - 8, gc["titulo"]["y_topo"] - 18, 8, 76, hi_c))
            E(3, a, b, "Nota", f"{{\\fad({fi},{fo})}}{highlight(g['texto'], hl, base_c, hi_c)}")
        elif t == "cartela_final":
            st = gc["cartela_final"]
            E(10, a, b, "Forma", f"{{\\fad({fi * 2},0)}}" + rect(0, 0, W, H, gra_c))
            E(11, a, b, "Forma", f"{{\\fad({fi * 2},0)}}" + rect(W / 2 - 60, H / 2 - 110, 120, 8, hi_c))
            sig = esc(g.get("texto", st["assinatura"]))
            sig_lines = "\\N".join(x.strip() + "." for x in sig.split(".") if x.strip()) if g.get("empilhar", True) else sig
            sig_lines = sig_lines[:-1] + "{\\1c" + hi_c + "&}.{\\1c" + base_c + "&}" if sig_lines.endswith(".") else sig_lines
            E(11, a, b, "Cartela", f"{{\\fad({fi * 2},0)\\an8\\pos({W / 2:.0f},{H / 2 - 50:.0f})}}{sig_lines}")
            if g.get("cta"):
                cta = "\\N".join(highlight(l, hl, base_c, hi_c) for l in
                                   wrap_text(g["cta"], "DM Sans 14pt Medium", False, st["tamanho_cta"], 820))
                E(11, a, b, "CartelaCTA", f"{{\\fad({fi * 2},0)\\an8\\pos({W / 2:.0f},{H / 2 + 300:.0f})}}{cta}")
        else:
            die(f"tipo de gráfico desconhecido: {t}")

    header = (
        "[Script Info]\nScriptType: v4.00+\nPlayResX: %d\nPlayResY: %d\nWrapStyle: 2\n"
        "ScaledBorderAndShadow: yes\nYCbCr Matrix: TV.709\n\n[V4+ Styles]\n"
        "Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, "
        "Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, "
        "MarginR, MarginV, Encoding\n" % (W, H)
    )
    body = header + "\n".join("Style: " + s for s in styles) + \
        "\n\n[Events]\nFormat: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text\n" + \
        "\n".join(ev) + "\n"
    out = Path(args.out) if getattr(args, "out", None) else resolve(proj["_base"], "build/legendas.ass")
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(body, encoding="utf-8")
    print(f"ASS → {out} ({len(ev)} eventos)")
    return out


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("cmd", choices=["build", "lint", "ass"])
    ap.add_argument("projeto")
    ap.add_argument("--out")
    ap.add_argument("--force", action="store_true")
    args = ap.parse_args()
    proj = load_project(args.projeto)
    if args.cmd == "build":
        cmd_build(proj, args)
    elif args.cmd == "lint":
        issues = cmd_lint(proj, args)
        sys.exit(1 if any(i[0] == "FALHA" for i in issues) else 0)
    else:
        cmd_ass(proj, args)


if __name__ == "__main__":
    main()
