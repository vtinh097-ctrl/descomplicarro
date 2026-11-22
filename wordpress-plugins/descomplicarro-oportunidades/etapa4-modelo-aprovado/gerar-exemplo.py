# -*- coding: utf-8 -*-
"""
Gera os slides de exemplo (carrossel + Stories) a partir de conteúdo REAL
da matéria "Avaliação: Ford Maverick Black 2026" (descomplicarro.com.br).

Prova de conceito manual da Etapa 4 — nenhum texto aqui foi inventado:
cada frase/numero vem literalmente do corpo da matéria. Isso NÃO é a
automação (sem wp_cron, sem Drive, sem envio de e-mail) — é só o exemplo
visual pedido para avaliação do modelo de carrossel/Stories.
"""
import os

BASE = os.path.dirname(os.path.abspath(__file__))
IMGS = os.path.join(BASE, "imgs")
OUT_HTML = os.path.join(BASE, "html")
os.makedirs(OUT_HTML, exist_ok=True)

GRAFITE = "#252525"
OFFWHITE = "#F4F1EA"
LARANJA = "#C65A32"
LARANJA_DARK = "#A6472A"

CSS_BASE = f"""
@font-face {{ font-family: 'Space Grotesk'; src: local('Space Grotesk'); }}
* {{ margin:0; padding:0; box-sizing:border-box; }}
body {{
  font-family: 'DM Sans', 'Helvetica Neue', Arial, sans-serif;
  background: {OFFWHITE};
  color: {GRAFITE};
  width: 100%; height: 100%;
  overflow: hidden;
}}
.slide {{
  width: 100%; height: 100%;
  display: flex;
  flex-direction: column;
  position: relative;
}}
.eyebrow {{
  font-family: 'Space Grotesk', 'Helvetica Neue', Arial, sans-serif;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  color: {LARANJA};
}}
.frame-mark {{
  position: absolute; width: 28px; height: 28px;
  border-top: 3px solid {OFFWHITE}; border-left: 3px solid {OFFWHITE};
}}
.frame-mark.br {{
  border-top: none; border-left: none;
  border-bottom: 3px solid {OFFWHITE}; border-right: 3px solid {OFFWHITE};
}}
.foto {{
  width: 100%; object-fit: cover; display: block;
}}
.rodape {{
  display:flex; align-items:center; justify-content:space-between;
  padding: 28px 44px; background: {GRAFITE}; color: {OFFWHITE};
}}
.rodape .marca {{
  font-family: 'Space Grotesk', 'Helvetica Neue', Arial, sans-serif;
  font-weight: 700; font-size: 22px; letter-spacing: 0.01em;
}}
.rodape .marca span {{ color: {LARANJA}; }}
.rodape .pag {{ font-size: 16px; color: rgba(244,241,234,0.7); font-weight: 600; }}
.titulo-display {{
  font-family: 'Space Grotesk', 'Helvetica Neue', Arial, sans-serif;
  font-weight: 700;
}}
"""

def wrap(body, w, h, extra_css=""):
    return f"""<!doctype html><html><head><meta charset="utf-8">
<style>
html,body{{width:{w}px;height:{h}px;}}
{CSS_BASE}
{extra_css}
</style></head><body>{body}</body></html>"""

# ---------------------------------------------------------------------------
# CARROSSEL — 1080 x 1350
# ---------------------------------------------------------------------------
CW, CH = 1080, 1350

def slide_capa_carrossel(total):
    body = f"""
<div class="slide">
  <div style="position:relative;flex:1 1 62%;">
    <img class="foto" src="file://{IMGS}/capa.jpg" style="width:100%;height:100%;object-fit:cover;">
    <span class="frame-mark" style="top:28px;left:28px;"></span>
    <span class="frame-mark br" style="bottom:28px;right:28px;"></span>
    <div style="position:absolute;top:28px;right:36px;" class="eyebrow">Reviews</div>
  </div>
  <div style="flex:1 1 38%;padding:40px 48px;display:flex;flex-direction:column;justify-content:center;gap:12px;">
    <div class="eyebrow" style="font-size:20px;">Testamos ao volante</div>
    <h1 class="titulo-display" style="font-size:50px;line-height:1.08;">Avaliação: Ford Maverick Black 2026</h1>
    <p style="font-size:24px;line-height:1.4;color:rgba(37,37,37,0.75);">Motor 2.0 turbo de 253 cv, tração AWD e visual escurecido — o que mostrou na prática.</p>
  </div>
  <div class="rodape">
    <div class="marca">DESCOMPLI<span>CARRO</span></div>
    <div class="pag">1/{total}</div>
  </div>
</div>"""
    return wrap(body, CW, CH)

def slide_destaque_carrossel(numero, total, foto, eyebrow, titulo, texto):
    body = f"""
<div class="slide">
  <div style="position:relative;flex:0 0 68%;">
    <img class="foto" src="file://{IMGS}/{foto}" style="width:100%;height:100%;object-fit:cover;">
    <span class="frame-mark" style="top:28px;left:28px;"></span>
    <span class="frame-mark br" style="bottom:28px;right:28px;"></span>
  </div>
  <div style="flex:1 1 auto;padding:36px 48px 0;display:flex;flex-direction:column;gap:12px;">
    <div class="eyebrow" style="font-size:20px;">{eyebrow}</div>
    <h2 class="titulo-display" style="font-size:42px;line-height:1.12;">{titulo}</h2>
    <p style="font-size:23px;line-height:1.45;color:rgba(37,37,37,0.82);">{texto}</p>
  </div>
  <div class="rodape">
    <div class="marca">DESCOMPLI<span>CARRO</span></div>
    <div class="pag">{numero}/{total}</div>
  </div>
</div>"""
    return wrap(body, CW, CH)

def slide_cta_carrossel(numero, total):
    body = f"""
<div class="slide" style="background:{GRAFITE};color:{OFFWHITE};align-items:center;justify-content:center;text-align:center;padding:0 80px;">
  <div style="display:flex;flex-direction:column;gap:28px;align-items:center;">
    <div class="eyebrow" style="font-size:22px;">Avaliação completa</div>
    <h2 class="titulo-display" style="font-size:46px;line-height:1.15;">Leia a avaliação completa da Maverick Black no site</h2>
    <div class="titulo-display" style="font-size:30px;color:{LARANJA};">descomplicarro.com.br</div>
  </div>
  <div class="rodape" style="position:absolute;bottom:0;left:0;width:100%;background:transparent;">
    <div class="marca">DESCOMPLI<span>CARRO</span></div>
    <div class="pag">{numero}/{total}</div>
  </div>
</div>"""
    return wrap(body, CW, CH)

destaques = [
    ("motor.jpg", "Motor", "253 cv e 38,7 kgfm de torque", "Debaixo do capô, o 2.0 EcoBoost turbo entrega 253 cv e 38,7 kgfm — 0 a 100 km/h em 7,2 segundos, segundo a Ford."),
    ("cambio.jpg", "Transmissão", "Câmbio automático 8F35, 8 marchas", "Tração AWD sob demanda: a eletrônica distribui torque para o eixo traseiro quando identifica necessidade."),
    ("cacamba.jpg", "Utilidade", "943 litros de caçamba", "Suporta até 618 kg de carga e capacidade de reboque sem freio de até 499 kg."),
    ("visual.jpg", "Design", "Pacote Black escurece a identidade", "Grade dianteira, emblema e faróis em LED com DRL ganham acabamento escurecido."),
    ("interior.jpg", "Interior", "Central multimídia de 13,2″", "CarPlay e Android Auto sem fio, mais som com assinatura B&O."),
    ("volante.jpg", "Consumo", "8,3 km/l cidade / 13,2 km/l rodovia", "Médias registradas nos testes de consumo durante a avaliação."),
]

total_slides = 1 + len(destaques) + 1  # capa + destaques + cta
slides_carrossel = [("01-capa", slide_capa_carrossel(total_slides))]
for i, (foto, eyebrow, titulo, texto) in enumerate(destaques, start=2):
    slides_carrossel.append((f"{i:02d}-{eyebrow.lower()}", slide_destaque_carrossel(i, total_slides, foto, eyebrow, titulo, texto)))
slides_carrossel.append((f"{total_slides:02d}-cta", slide_cta_carrossel(total_slides, total_slides)))

for name, html in slides_carrossel:
    with open(os.path.join(OUT_HTML, f"carrossel-{name}.html"), "w", encoding="utf-8") as f:
        f.write(html)

# ---------------------------------------------------------------------------
# STORIES — 1080 x 1920 (exemplo: capa + 1 destaque, para mostrar o formato)
# ---------------------------------------------------------------------------
SW, SH = 1080, 1920

def stories_capa():
    body = f"""
<div class="slide">
  <div style="position:relative;flex:0 0 66%;">
    <img class="foto" src="file://{IMGS}/capa.jpg" style="width:100%;height:100%;object-fit:cover;">
    <div style="position:absolute;top:64px;left:40px;right:40px;display:flex;justify-content:space-between;align-items:center;">
      <div class="eyebrow" style="font-size:22px;background:rgba(244,241,234,0.9);padding:8px 18px;border-radius:999px;">Reviews</div>
    </div>
  </div>
  <div style="flex:0 0 auto;padding:40px 56px 0;display:flex;flex-direction:column;gap:14px;">
    <div class="eyebrow" style="font-size:22px;">Testamos ao volante</div>
    <h1 class="titulo-display" style="font-size:52px;line-height:1.1;">Ford Maverick Black 2026</h1>
    <p style="font-size:27px;line-height:1.4;color:rgba(37,37,37,0.75);">Motor 2.0 turbo de 253 cv, tração AWD e visual escurecido.</p>
  </div>
  <div style="flex:0 0 auto;margin:36px 56px 0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;">
    <span class="titulo-display" style="font-size:26px;color:{LARANJA};">LEIA A MATÉRIA COMPLETA</span>
    <span style="font-size:40px;color:{LARANJA};line-height:1;">&#8595;</span>
  </div>
  <div style="flex:1 1 auto;"></div>
</div>"""
    return wrap(body, SW, SH)

def stories_destaque():
    foto, eyebrow, titulo, texto = destaques[0]
    body = f"""
<div class="slide">
  <div style="position:relative;flex:0 0 62%;">
    <img class="foto" src="file://{IMGS}/{foto}" style="width:100%;height:100%;object-fit:cover;">
  </div>
  <div style="flex:0 0 auto;padding:40px 56px 0;display:flex;flex-direction:column;gap:14px;">
    <div class="eyebrow" style="font-size:22px;">{eyebrow}</div>
    <h2 class="titulo-display" style="font-size:50px;line-height:1.12;">{titulo}</h2>
    <p style="font-size:27px;line-height:1.45;color:rgba(37,37,37,0.82);">{texto}</p>
  </div>
  <div style="flex:0 0 auto;margin:32px 56px 0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;">
    <span class="titulo-display" style="font-size:22px;color:{LARANJA};">LEIA A MATÉRIA COMPLETA</span>
    <span style="font-size:34px;color:{LARANJA};line-height:1;">&#8595;</span>
  </div>
  <div style="flex:1 1 auto;"></div>
  <div class="rodape" style="padding:28px 56px;flex:0 0 auto;">
    <div class="marca" style="font-size:24px;">DESCOMPLI<span>CARRO</span></div>
  </div>
</div>"""
    return wrap(body, SW, SH)

with open(os.path.join(OUT_HTML, "stories-01-capa.html"), "w", encoding="utf-8") as f:
    f.write(stories_capa())
with open(os.path.join(OUT_HTML, "stories-02-motor.html"), "w", encoding="utf-8") as f:
    f.write(stories_destaque())

print("HTML gerado:", len(slides_carrossel), "slides de carrossel + 2 de stories")
