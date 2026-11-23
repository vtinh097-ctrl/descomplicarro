#!/usr/bin/env python3
"""Audita o ambiente: binários, codecs, filtros, módulos Python e fontes da marca.

Uso: python3 scripts/check_env.py [--json]
Saída: tabela OK/FALTA. Código de saída 1 se faltar algo OBRIGATÓRIO.
"""
import importlib.util
import json
import re
import shutil
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import FONTS_DIR  # noqa: E402

REQ_FILTERS = {
    # filtro: para que serve
    "ass": "queimar legendas/gráficos (libass)",
    "loudnorm": "normalização de loudness EBU R128",
    "ebur128": "medição de loudness",
    "highpass": "limpeza de graves na voz",
    "acompressor": "controle dinâmico da voz",
    "afftdn": "redução de ruído",
    "afade": "micro-fades nos cortes",
    "silencedetect": "pausas e silêncios",
    "blackdetect": "telas pretas (QA)",
    "freezedetect": "imagem congelada (QA)",
    "overlay": "B-roll",
    "crop": "reenquadramento 9:16",
    "scale": "redimensionamento",
    "fade": "fade para grafite",
    "astats": "picos/clipping",
}
OPT_FILTERS = {
    "zscale": "conversão HDR→SDR (celulares em HDR)",
    "tonemap": "conversão HDR→SDR",
    "deesser": "redução de sibilância",
    "sidechaincompress": "ducking da música sob a voz",
    "adeclip": "recuperação de áudio estourado",
    "anlmdn": "redução de ruído alternativa",
    "equalizer": "equalização da voz",
    "scdet": "detecção de mudança de cena",
    "signalstats": "exposição/luminância",
}
REQ_ENC = {"libx264": "vídeo H.264", "aac": "áudio AAC"}
PY_MODS = {
    "numpy": ("recomendado", "análise espectral, verificação de sincronia"),
    "PIL": ("recomendado", "folhas de contato e quebra de linha por largura real"),
    "faster_whisper": ("necessário p/ transcrição", "legendas sincronizadas e decupagem"),
}
FONTS = {
    "SpaceGrotesk-Bold.ttf": "Space Grotesk (Bold)",
    "SpaceGrotesk-Medium.ttf": "Space Grotesk Medium",
    "DMSans-Bold.ttf": "DM Sans (Bold)",
    "DMSans-SemiBold.ttf": "DM Sans 14pt SemiBold",
    "DMSans-Medium.ttf": "DM Sans 14pt Medium",
}


def main():
    as_json = "--json" in sys.argv
    rows, missing_req = [], []

    def add(cat, name, ok, req, note=""):
        rows.append({"categoria": cat, "item": name, "ok": ok, "obrigatorio": req, "nota": note})
        if req and not ok:
            missing_req.append(name)

    for b in ("ffmpeg", "ffprobe"):
        path = shutil.which(b)
        ver = ""
        if path:
            ver = subprocess.run([b, "-version"], capture_output=True, text=True).stdout.split("\n")[0]
        add("binário", b, bool(path), True, ver[:60])
    for b in ("fc-scan",):
        add("binário", b, bool(shutil.which(b)), False, "inspeção de fontes")

    if shutil.which("ffmpeg"):
        flt = subprocess.run(["ffmpeg", "-hide_banner", "-filters"], capture_output=True, text=True).stdout
        names = set(re.findall(r"^\s*[TSC.]{3}\s+(\w+)", flt, re.M))
        for f, why in REQ_FILTERS.items():
            add("filtro", f, f in names, True, why)
        for f, why in OPT_FILTERS.items():
            add("filtro", f, f in names, False, why)
        enc = subprocess.run(["ffmpeg", "-hide_banner", "-encoders"], capture_output=True, text=True).stdout
        for e, why in REQ_ENC.items():
            add("encoder", e, re.search(rf"\s{e}\s", enc) is not None, True, why)

    py_ok = sys.version_info >= (3, 9)
    add("python", f"python {sys.version.split()[0]}", py_ok, True, ">= 3.9")
    for mod, (level, why) in PY_MODS.items():
        ok = importlib.util.find_spec(mod) is not None
        note = f"{level}: {why}"
        if mod == "faster_whisper" and not ok:
            note += " — instalar: pip install faster-whisper"
        add("python", mod, ok, False, note)

    for fname, fam in FONTS.items():
        p = FONTS_DIR / fname
        add("fonte", fname, p.exists(), True, fam)
    if not (FONTS_DIR / "OFL-SpaceGrotesk.txt").exists():
        add("fonte", "licenças OFL", False, False, "manter OFL junto das fontes")

    if as_json:
        print(json.dumps({"itens": rows, "faltando_obrigatorio": missing_req}, ensure_ascii=False, indent=2))
    else:
        cat = None
        for r in rows:
            if r["categoria"] != cat:
                cat = r["categoria"]
                print(f"\n[{cat.upper()}]")
            flag = "OK   " if r["ok"] else ("FALTA" if r["obrigatorio"] else "aus. ")
            print(f"  {flag} {r['item']:<26} {r['nota']}")
        print()
        if missing_req:
            print("RESULTADO: faltam itens obrigatórios:", ", ".join(missing_req))
        else:
            print("RESULTADO: ambiente apto (itens 'aus.' são opcionais; veja a nota de cada um).")
    sys.exit(1 if missing_req else 0)


if __name__ == "__main__":
    main()
