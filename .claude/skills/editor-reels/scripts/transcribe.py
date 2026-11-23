#!/usr/bin/env python3
"""Transcrição com tempo por palavra (faster-whisper), em português.

Uso:
  python3 scripts/transcribe.py BRUTO.mp4 --out trans/A.json [--modelo medium] [--vocab "turbo, CVT, Ford Ka"]

Saídas: A.json (palavras com início/fim/confiança + segmentos) e A.txt (leitura com timecodes).

Notas de qualidade:
- Modelo padrão 'medium' (bom em PT-BR em CPU). 'large-v3' é mais preciso e bem mais lento —
  use na finalização quando houver termos difíceis. 'small' só para rascunho.
- --vocab ajuda a acertar nomes técnicos/marcas (vira prompt inicial).
- Whisper tende a "limpar" hesitações (é..., ããh). O prompt inicial pede transcrição literal,
  mas confirme falsos inícios pela forma de onda/pausas e pela revisão visual.
- A transcrição é rascunho: o editor revisa ortografia/termos ANTES de gerar legendas.
"""
import argparse
import os
import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import decode_audio_mono, die, save_json, tc  # noqa: E402

LITERAL_PROMPT = ("Transcrição literal em português do Brasil, com hesitações: é... ãh... hum... "
                  "então, tipo, né. Vocabulário automotivo.")


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("arquivo")
    ap.add_argument("--out", required=True)
    ap.add_argument("--modelo", default="medium")
    ap.add_argument("--idioma", default="pt")
    ap.add_argument("--vocab", default="", help="termos técnicos/nomes separados por vírgula")
    ap.add_argument("--threads", type=int, default=0)
    args = ap.parse_args()
    # download HTTP clássico: o protocolo Xet do Hugging Face costuma ser barrado por proxies
    os.environ.setdefault("HF_HUB_DISABLE_XET", "1")
    try:
        from faster_whisper import WhisperModel
    except ImportError:
        die("faster-whisper não instalado: pip install faster-whisper")

    prompt = LITERAL_PROMPT + (f" Termos: {args.vocab}." if args.vocab else "")
    t0 = time.time()
    model = WhisperModel(args.modelo, device="cpu", compute_type="int8", cpu_threads=args.threads)
    # decodifica com o FFmpeg (mais robusto que o decodificador interno do faster-whisper)
    audio = decode_audio_mono(args.arquivo, rate=16000)
    segments, info = model.transcribe(
        audio, language=args.idioma, word_timestamps=True, beam_size=5,
        initial_prompt=prompt, condition_on_previous_text=False,
        vad_filter=True, vad_parameters={"min_silence_duration_ms": 400, "speech_pad_ms": 200},
    )
    words, segs = [], []
    for s in segments:
        segs.append({"ini": round(s.start, 3), "fim": round(s.end, 3), "texto": s.text.strip(),
                     "no_speech": round(s.no_speech_prob, 3)})
        for w in s.words or []:
            txt = w.word.strip()
            if txt:
                words.append({"w": txt, "ini": round(w.start, 3), "fim": round(w.end, 3),
                              "prob": round(w.probability, 3)})
    out = Path(args.out)
    save_json(out, {"arquivo": str(args.arquivo), "modelo": args.modelo, "idioma": info.language,
                    "duracao": round(info.duration, 3), "vocab": args.vocab,
                    "palavras": words, "segmentos": segs})
    lines = []
    for s in segs:
        lines.append(f"[{tc(s['ini'])} → {tc(s['fim'])}] {s['texto']}")
    low = [w for w in words if w["prob"] < 0.5]
    if low:
        lines += ["", "PALAVRAS DE BAIXA CONFIANÇA (revisar):",
                  ", ".join(f"{w['w']}@{tc(w['ini'])}" for w in low)]
    out.with_suffix(".txt").write_text("\n".join(lines) + "\n", encoding="utf-8")
    print(f"{len(words)} palavras, {len(segs)} segmentos em {time.time() - t0:.0f}s → {out}")
    print(f"leitura: {out.with_suffix('.txt')}  ({len(low)} palavras de baixa confiança)")


if __name__ == "__main__":
    main()
