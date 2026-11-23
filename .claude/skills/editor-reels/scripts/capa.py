#!/usr/bin/env python3
"""Capa do Reel (1080x1920) na identidade DESCOMPLICARRO.

Uso:
  python3 scripts/capa.py --video brutos/E.mov --t 46.8 --titulo "38 anos com o mesmo carro." \\
      --destaques "38 ANOS" --out saida/capa.jpg [--y-titulo 1250] \\
      [--card broll/foto2.jpg --card-girar 14 --card-recorte 0.245,0.42,0.53,0.34 \\
       --card-marcar 0.01,0.33,0.95,0.27 --card-pos 60,880 --card-largura 520]

Regras (references/identidade-visual.md → Capa):
- quadro real do vídeo, com rosto(s) visível(is) e o assunto em cena; nada de rosto coberto;
- título curto em Space Grotesk Bold caixa alta, caixas grafite, 1 destaque laranja, ponto final laranja;
- todo texto dentro da área que sobrevive ao recorte da grade do perfil (3:4 central: y 240–1680)
  E fora da interface do Reels (zonas seguras).
Gera também: <out>_grade.jpg (como aparece no perfil) e <out>_zonas.jpg (conferência).
"""
import argparse
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import FONTS_DIR, load_config  # noqa: E402

W, H = 1080, 1920
GRADE = (0, 240, 1080, 1680)  # recorte 3:4 central da grade do perfil


def hex_rgb(h):
    h = h.lstrip("#")
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def frame(video, t):
    from PIL import Image
    p = subprocess.run(["ffmpeg", "-hide_banner", "-loglevel", "error", "-ss", f"{t:.3f}", "-i", str(video),
                        "-frames:v", "1", "-vf",
                        f"scale={W}:{H}:force_original_aspect_ratio=increase:flags=lanczos,crop={W}:{H},"
                        "unsharp=5:5:0.6:5:5:0", "-f", "image2pipe", "-vcodec", "png", "-"],
                       capture_output=True)
    if not p.stdout:
        sys.exit(f"ERRO: não consegui extrair o quadro em {t}s")
    import io
    return Image.open(io.BytesIO(p.stdout)).convert("RGB")


def wrap(text, font, maxw):
    words, lines, cur = text.split(), [], ""
    for w in words:
        cand = (cur + " " + w).strip()
        if cur and font.getlength(cand) > maxw:
            lines.append(cur)
            cur = w
        else:
            cur = cand
    lines.append(cur)
    if len(lines) == 2:  # equilibra 2 linhas
        ws = text.split()
        weak = {"A", "O", "AS", "OS", "E", "DE", "DA", "DO", "EM", "NO", "NA", "COM", "POR", "PRA", "PARA", "QUE", "UM", "UMA"}
        best = min(range(1, len(ws)), key=lambda i: abs(font.getlength(" ".join(ws[:i])) - font.getlength(" ".join(ws[i:])))
                   + (10 ** 4 if max(font.getlength(" ".join(ws[:i])), font.getlength(" ".join(ws[i:]))) > maxw else 0)
                   + (5000 if ws[i - 1].strip(",.") in weak else 0))
        lines = [" ".join(ws[:best]), " ".join(ws[best:])]
    return lines


def draw_title(im, text, destaques, y, cfg, size):
    from PIL import ImageDraw, ImageFont
    mk = cfg["marca"]
    off, gra, lar = hex_rgb(mk["offwhite"]), hex_rgb(mk["grafite"]), hex_rgb(mk["laranja"])
    text = text.upper()
    hl = {d.upper() for d in destaques}
    weak = {"A", "O", "AS", "OS", "E", "DE", "DA", "DO", "EM", "NO", "NA", "COM", "POR", "PRA", "PARA", "QUE", "UM", "UMA"}
    # se a boa quebra (sem palavra fraca no fim da linha) não cabe, reduz o corpo em passos pequenos
    for sz in range(size, size - 26, -2):
        font = ImageFont.truetype(str(FONTS_DIR / "SpaceGrotesk-Bold.ttf"), sz)
        lines = wrap(text, font, 920)
        if all(font.getlength(ln) <= 920 for ln in lines) and \
                not any(ln.split()[-1].strip(",.") in weak for ln in lines[:-1]):
            break
    pad_x, pad_y, gap = 26, 14, 8
    asc, desc = font.getmetrics()
    lh = asc + desc
    layer = im.convert("RGBA")
    ov = __import__("PIL.Image", fromlist=["Image"]).new("RGBA", im.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(ov)
    yy = y
    boxes = []
    for ln in lines:
        w = font.getlength(ln)
        x = (W - w) / 2
        d.rectangle([x - pad_x, yy, x + w + pad_x, yy + lh + 2 * pad_y], fill=gra + (232,))
        boxes.append((x, yy + pad_y, ln))
        yy += lh + 2 * pad_y + gap
    layer = __import__("PIL.Image", fromlist=["Image"]).alpha_composite(layer, ov)
    d = ImageDraw.Draw(layer)
    for x, ty, ln in boxes:
        # destaque: trecho inteiro que coincide com um destaque fica laranja
        cx = x
        segs = [(ln, off)]
        for h in hl:
            if h in ln:
                i = ln.index(h)
                segs = [(ln[:i], off), (h, lar), (ln[i + len(h):], off)]
                break
        for s, col in segs:
            if s.endswith(".") and s is segs[-1][0]:
                d.text((cx, ty), s[:-1], font=font, fill=col)
                cx += font.getlength(s[:-1])
                d.text((cx, ty), ".", font=font, fill=lar)
                cx += font.getlength(".")
            else:
                d.text((cx, ty), s, font=font, fill=col)
                cx += font.getlength(s)
    return layer.convert("RGB"), (y, yy)


def draw_card(im, path, girar, recorte, marcar, pos, largura, cfg):
    from PIL import Image, ImageDraw, ImageFilter
    mk = cfg["marca"]
    try:
        from pillow_heif import register_heif_opener
        register_heif_opener()
    except ImportError:
        pass
    from PIL import ImageOps
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
                                    outline=hex_rgb(mk["laranja"]), width=5)
    b = 10
    framed = Image.new("RGB", (c.width + 2 * b, c.height + 2 * b), hex_rgb(mk["offwhite"]))
    framed.paste(c, (b, b))
    sh = Image.new("RGBA", (framed.width + 40, framed.height + 40), (0, 0, 0, 0))
    ImageDraw.Draw(sh).rectangle([20, 20, 20 + framed.width, 20 + framed.height], fill=hex_rgb(mk["grafite"]) + (150,))
    sh = sh.filter(ImageFilter.GaussianBlur(10))
    base = im.convert("RGBA")
    base.alpha_composite(sh, (pos[0] - 20 + 8, pos[1] - 20 + 12))
    base = base.convert("RGB")
    base.paste(framed, pos)
    return base, (pos[0], pos[1], pos[0] + framed.width, pos[1] + framed.height)


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--video", required=True)
    ap.add_argument("--t", type=float, required=True)
    ap.add_argument("--titulo", required=True)
    ap.add_argument("--destaques", default="")
    ap.add_argument("--y-titulo", type=int, default=1250)
    ap.add_argument("--tamanho", type=int, default=104)
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
    # leve escurecimento só na faixa do título (legibilidade), cor grafite — sem gradiente decorativo
    fl = lambda s: [float(x) for x in s.split(",")] if s else None  # noqa: E731
    if a.card:
        im, cbox = draw_card(im, a.card, a.card_girar, fl(a.card_recorte), fl(a.card_marcar),
                             tuple(int(x) for x in a.card_pos.split(",")), a.card_largura, cfg)
    im, (ty0, ty1) = draw_title(im, a.titulo, [d.strip() for d in a.destaques.split(",") if d.strip()],
                                a.y_titulo, cfg, a.tamanho)
    out = Path(a.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    im.save(out, quality=95)
    # conferências
    zs = cfg["zonas_seguras"]
    avisos = []
    if ty0 < GRADE[1] or ty1 > GRADE[3]:
        avisos.append("título sai do recorte da grade do perfil (3:4)")
    if ty1 > H - zs["base"] or ty0 < zs["topo"]:
        avisos.append("título entra na interface do Reels")
    if a.card and (cbox[1] < GRADE[1] or cbox[3] > GRADE[3]):
        avisos.append("card sai do recorte da grade do perfil")
    im.crop(GRADE).save(out.with_name(out.stem + "_grade.jpg"), quality=92)
    z = im.convert("RGBA")
    ov = Image.new("RGBA", z.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(ov)
    d.rectangle([0, 0, W, GRADE[1]], fill=(40, 120, 230, 90))
    d.rectangle([0, GRADE[3], W, H], fill=(40, 120, 230, 90))
    d.rectangle([0, H - zs["base"], W, H], fill=(230, 30, 30, 90))
    d.rectangle([0, 0, W, zs["topo"]], fill=(230, 30, 30, 90))
    Image.alpha_composite(z, ov).convert("RGB").save(out.with_name(out.stem + "_zonas.jpg"), quality=90)
    print(f"capa → {out}\n  grade (3:4) → {out.with_name(out.stem + '_grade.jpg')}")
    for av in avisos:
        print("  AVISO:", av)
    print("Revisão visual obrigatória: rostos livres, título legível na grade, sem nada na interface.")


if __name__ == "__main__":
    main()
