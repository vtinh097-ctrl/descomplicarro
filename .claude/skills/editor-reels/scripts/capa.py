#!/usr/bin/env python3
"""Capa do Reel (1080x1920) na identidade DESCOMPLICARRO.

Uso:
  python3 scripts/capa.py --video brutos/E.mov --t 46.8 --titulo "38 anos com o mesmo carro." \\
      --destaques "38 ANOS" --modelo A --out saida/capa.jpg [--y-titulo 1150]
      [--card foto.jpg --card-girar 14 --card-recorte x,y,w,h --card-marcar x,y,w,h --card-pos 60,880 --card-largura 520]

Modelos (o padrão aprovado fica registrado em references/identidade-visual.md → Capa):
  A  assinatura no topo  — símbolo + DESCOMPLICARRO numa placa grafite no alto; título em caixas grafite
  B  assinatura sob o título — título direto sobre a faixa escurecida; símbolo + assinatura logo abaixo
  C  marca mínima        — só o símbolo no canto superior; título em caixas grafite
Todos: quadro real do vídeo (HDR convertido), faixa grafite translúcida atrás do título (legibilidade),
título Space Grotesk Bold caixa alta com 1 destaque laranja e ponto final laranja, quebra sem palavra fraca.

MARCA: logotipo oficial em config/padrao.json → marca.logo (PNG transparente, versão clara). Enquanto não houver,
usa o nome em texto e avisa. O quadrado com ponto laranja do site é PLACEHOLDER — não é a marca.

Gera também <out>_grade.jpg (recorte 3:4 da grade do perfil) e <out>_zonas.jpg, e avisa se algo sair dessas áreas.
"""
import argparse
import io
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import FONTS_DIR, load_config, media_info  # noqa: E402

W, H = 1080, 1920
GRADE = (0, 240, 1080, 1680)  # recorte 3:4 central da grade do perfil
WEAK = {"A", "O", "AS", "OS", "E", "DE", "DA", "DO", "EM", "NO", "NA", "COM", "POR", "PRA", "PARA", "QUE", "UM",
        "UMA", "OU"}
HDR = ("zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,"
       "zscale=t=bt709:m=bt709:r=tv,format=yuv420p,")


def rgb(h):
    h = h.lstrip("#")
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def font(name, size):
    from PIL import ImageFont
    return ImageFont.truetype(str(FONTS_DIR / name), size)


def frame(video, t):
    from PIL import Image
    pre = HDR if (media_info(video)["video"] or {}).get("hdr") else ""
    p = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-ss", f"{t:.3f}", "-i", str(video),
                        "-frames:v", "1", "-vf",
                        f"{pre}scale={W}:{H}:force_original_aspect_ratio=increase:flags=lanczos,crop={W}:{H},"
                        "unsharp=5:5:0.6:5:5:0", "-f", "image2pipe", "-vcodec", "png", "-"], capture_output=True)
    if not p.stdout:
        sys.exit(f"ERRO: não consegui extrair o quadro em {t}s")
    return Image.open(io.BytesIO(p.stdout)).convert("RGB")


def layout_title(text, size, maxw=920):
    """Menos linhas primeiro; em cada nº de linhas, reduz o corpo até ~80% antes de passar para mais linhas.
    Nunca termina linha em palavra fraca; equilibra as larguras."""
    text = text.upper()
    words = text.split()
    for nlin in (1, 2, 3):
        if nlin == 1:
            cands = [[text]]
        elif nlin == 2:
            cands = [[" ".join(words[:i]), " ".join(words[i:])] for i in range(1, len(words))]
        else:
            cands = [[" ".join(words[:i]), " ".join(words[i:j]), " ".join(words[j:])]
                     for i in range(1, len(words)) for j in range(i + 1, len(words))]
        for sz in range(size, int(size * 0.8) - 1, -2):
            f = font("SpaceGrotesk-Bold.ttf", sz)
            best, score = None, None
            for c in cands:
                ws = [f.getlength(x) for x in c]
                if max(ws) > maxw or any(x.split()[-1].strip(",.:?") in WEAK for x in c[:-1]):
                    continue
                sc = max(ws) - min(ws)
                if score is None or sc < score:
                    best, score = c, sc
            if best:
                return f, best
    return font("SpaceGrotesk-Bold.ttf", int(size * 0.8)), [text]


def segments(line, hl, base, accent):
    for h in hl:
        if h and h in line:
            i = line.index(h)
            return [(line[:i], base), (h, accent), (line[i + len(h):], base)]
    return [(line, base)]


def draw_text_line(d, x, y, line, f, hl, base, accent):
    segs = segments(line, hl, base, accent)
    for k, (s, col) in enumerate(segs):
        if k == len(segs) - 1 and s.endswith((".", "?", "!")):
            d.text((x, y), s[:-1], font=f, fill=col)
            x += f.getlength(s[:-1])
            d.text((x, y), s[-1], font=f, fill=accent)  # pontuação final em laranja
            x += f.getlength(s[-1])
        else:
            d.text((x, y), s, font=f, fill=col)
            x += f.getlength(s)


def band(im, y0, y1, cfg, alpha=0.55, feather=70):
    """Faixa grafite translúcida (borda suave) atrás do título — legibilidade, não decoração."""
    from PIL import Image, ImageDraw, ImageFilter
    m = Image.new("L", im.size, 0)
    ImageDraw.Draw(m).rectangle([0, y0, W, y1], fill=int(255 * alpha))
    m = m.filter(ImageFilter.GaussianBlur(feather / 2))
    im.paste(Image.new("RGB", im.size, rgb(cfg["marca"]["grafite"])), (0, 0), m)
    return im


def draw_marca(im, x, y, cfg, tipo="completa", escala=1.0, placa=False, centro=False):
    """Marca na capa. Usa o LOGOTIPO OFICIAL (config/padrao.json → marca.logo: PNG com fundo transparente,
    versão clara para fundo escuro). Sem o arquivo, escreve só o nome em texto — provisório, com aviso.
    (O quadrado com ponto laranja do site é placeholder e NÃO é a marca: não usar.)"""
    from PIL import Image, ImageDraw
    from rl_common import SKILL_DIR
    mk = cfg["marca"]
    off, gra = rgb(mk["offwhite"]), rgb(mk["grafite"])
    logo = mk.get("logo")
    lp = (SKILL_DIR / logo) if logo else None
    if lp and lp.exists():
        lg = Image.open(lp).convert("RGBA")
        lg = lg.crop(lg.getbbox())
        h = int(84 * escala)  # logo 5,1:1 → ~430 px de largura
        lg = lg.resize((round(lg.width * h / lg.height), h), Image.LANCZOS)
        w = lg.width
    else:
        print("  AVISO: logotipo oficial ausente (marca.logo) — usando o nome em texto, provisório")
        lg = None
        fw = font("SpaceGrotesk-Bold.ttf", int(40 * escala))
        w, h = int(fw.getlength("DESCOMPLICARRO")), int(46 * escala)
    if centro:
        x = int((W - w) / 2)
    if placa:
        pad = int(16 * escala)
        ov = Image.new("RGBA", im.size, (0, 0, 0, 0))
        ImageDraw.Draw(ov).rectangle([x - pad, y - pad, x + w + pad, y + h + pad], fill=gra + (225,))
        im.paste(Image.alpha_composite(im.convert("RGBA"), ov).convert("RGB"))
    if lg is not None:
        base = im.convert("RGBA")
        base.alpha_composite(lg, (x, y))
        im.paste(base.convert("RGB"))
    else:
        ImageDraw.Draw(im).text((x, y - int(6 * escala)), "DESCOMPLICARRO", font=fw, fill=off)
    return (x, y, x + w, y + h)


def draw_card(im, path, girar, recorte, marcar, pos, largura, cfg):
    from PIL import Image, ImageDraw, ImageFilter, ImageOps
    mk = cfg["marca"]
    try:
        from pillow_heif import register_heif_opener
        register_heif_opener()
    except ImportError:
        pass
    c = ImageOps.exif_transpose(Image.open(path)).convert("RGB")
    if girar:
        c = c.rotate(-girar, resample=Image.BICUBIC)
    if recorte:
        x, y, w, h = recorte
        c = c.crop((int(x * c.width), int(y * c.height), int((x + w) * c.width), int((y + h) * c.height)))
    c = c.resize((largura, round(largura * c.height / c.width)), Image.LANCZOS)
    if marcar:
        x, y, w, h = marcar
        ImageDraw.Draw(c).rectangle([x * c.width, y * c.height, (x + w) * c.width, (y + h) * c.height],
                                    outline=rgb(mk["laranja"]), width=5)
    b = 10
    fr = Image.new("RGB", (c.width + 2 * b, c.height + 2 * b), rgb(mk["offwhite"]))
    fr.paste(c, (b, b))
    sh = Image.new("RGBA", (fr.width + 40, fr.height + 40), (0, 0, 0, 0))
    ImageDraw.Draw(sh).rectangle([20, 20, 20 + fr.width, 20 + fr.height], fill=rgb(mk["grafite"]) + (150,))
    base = im.convert("RGBA")
    base.alpha_composite(sh.filter(ImageFilter.GaussianBlur(10)), (pos[0] - 12, pos[1] - 8))
    base = base.convert("RGB")
    base.paste(fr, pos)
    return base, (pos[0], pos[1], pos[0] + fr.width, pos[1] + fr.height)


def compose(im, a, cfg):
    from PIL import Image, ImageDraw
    mk = cfg["marca"]
    off, lar, gra = rgb(mk["offwhite"]), rgb(mk["laranja"]), rgb(mk["grafite"])
    hl = [h.strip().upper() for h in a.destaques.split(",") if h.strip()]
    f, lines = layout_title(a.titulo, a.tamanho)
    asc, desc = f.getmetrics()
    lh = asc + desc
    boxes = a.modelo in ("A", "C")
    py, gapl = (14, 8) if boxes else (0, 6)
    block_h = len(lines) * (lh + 2 * py) + (len(lines) - 1) * gapl
    sig_h = 90 if a.modelo == "B" else 0
    bottom_limit = H - cfg["zonas_seguras"]["base"] - 40  # acima da interface e dentro da grade
    y0 = a.y_titulo if a.y_titulo else bottom_limit - block_h - sig_h
    # faixa escura atrás do bloco de título (todos os modelos)
    im = band(im, y0 - 70, y0 + block_h + sig_h + 50, cfg, alpha=0.5 if boxes else 0.62)
    if boxes:
        ov = Image.new("RGBA", im.size, (0, 0, 0, 0))
        d = ImageDraw.Draw(ov)
        y = y0
        for ln in lines:
            w = f.getlength(ln)
            x = (W - w) / 2
            d.rectangle([x - 26, y, x + w + 26, y + lh + 2 * py], fill=gra + (232,))
            y += lh + 2 * py + gapl
        im = Image.alpha_composite(im.convert("RGBA"), ov).convert("RGB")
    d = ImageDraw.Draw(im)
    y = y0
    for ln in lines:
        x = (W - f.getlength(ln)) / 2
        draw_text_line(d, x, y + py, ln, f, hl, off, lar)
        y += lh + 2 * py + gapl
    marca_box = None
    if a.modelo == "A":
        marca_box = draw_marca(im, 80, GRADE[1] + 60, cfg, "completa", 1.0, placa=True)
    elif a.modelo == "B":
        marca_box = draw_marca(im, 0, y + 18, cfg, "completa", 0.9, centro=True)
    elif a.modelo == "C":
        marca_box = draw_marca(im, W - 80 - 64, GRADE[1] + 60, cfg, "simbolo", 1.2, placa=True)
    return im, (y0, y), marca_box


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--video", required=True)
    ap.add_argument("--t", type=float, required=True)
    ap.add_argument("--titulo", required=True)
    ap.add_argument("--destaques", default="")
    ap.add_argument("--modelo", choices=["A", "B", "C"], default="A", help="A = padrão aprovado (25/11/2026)")
    ap.add_argument("--y-titulo", type=int, default=0, help="topo do título (0 = automático, terço inferior)")
    ap.add_argument("--tamanho", type=int, default=100)
    ap.add_argument("--card")
    ap.add_argument("--card-girar", type=float, default=0)
    ap.add_argument("--card-recorte")
    ap.add_argument("--card-marcar")
    ap.add_argument("--card-pos", default="60,880")
    ap.add_argument("--card-largura", type=int, default=520)
    ap.add_argument("--out", required=True)
    a = ap.parse_args()
    from PIL import Image, ImageDraw
    cfg = load_config()
    im = frame(a.video, a.t)
    fl = lambda s: [float(x) for x in s.split(",")] if s else None  # noqa: E731
    cbox = None
    if a.card:
        im, cbox = draw_card(im, a.card, a.card_girar, fl(a.card_recorte), fl(a.card_marcar),
                             tuple(int(x) for x in a.card_pos.split(",")), a.card_largura, cfg)
    im, (ty0, ty1), mbox = compose(im, a, cfg)
    out = Path(a.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    im.save(out, quality=95)
    zs = cfg["zonas_seguras"]
    avisos = []
    for nome, (y0, y1) in (("título", (ty0, ty1)), ("marca", (mbox[1], mbox[3]) if mbox else (GRADE[1], GRADE[1])),
                           ("card", (cbox[1], cbox[3]) if cbox else (GRADE[1], GRADE[1]))):
        if y0 < GRADE[1] or y1 > GRADE[3]:
            avisos.append(f"{nome} sai do recorte da grade do perfil (3:4)")
        if y0 < zs["topo"] or y1 > H - zs["base"]:
            avisos.append(f"{nome} entra na interface do Reels")
    im.crop(GRADE).save(out.with_name(out.stem + "_grade.jpg"), quality=92)
    z = im.convert("RGBA")
    ov = Image.new("RGBA", z.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(ov)
    d.rectangle([0, 0, W, GRADE[1]], fill=(40, 120, 230, 90))
    d.rectangle([0, GRADE[3], W, H], fill=(40, 120, 230, 90))
    d.rectangle([0, H - zs["base"], W, H], fill=(230, 30, 30, 90))
    d.rectangle([0, 0, W, zs["topo"]], fill=(230, 30, 30, 90))
    Image.alpha_composite(z, ov).convert("RGB").save(out.with_name(out.stem + "_zonas.jpg"), quality=90)
    print(f"capa → {out}  (modelo {a.modelo})")
    for av in avisos:
        print("  AVISO:", av)
    print("Revisão visual obrigatória: rostos livres, título legível na grade, sem nada na interface.")


if __name__ == "__main__":
    main()
