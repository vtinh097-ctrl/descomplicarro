#!/usr/bin/env python3
"""Monta o rascunho do projeto.json a partir dos takes escolhidos na decupagem.

Uso:
  python3 scripts/montar.py --decupagem decupagem.json --takes "A:01.2,02,03,04" --out projeto.json
  (vários arquivos: --takes "A:01.2,02;B:00,03")

Notação: GG = grupo da decupagem (take 1); GG.k = take k do grupo GG. A ordem da lista é a ordem
de montagem (pode reordenar falas — registre o motivo na proposta).

Os pontos de corte NÃO usam o tempo cru do Whisper: cada borda é ajustada à energia real do áudio
(início da fala/fim da cauda da última sílaba), com respiro configurável. Enunciados vizinhos na
origem com pausa curta viram um único segmento. O resultado é um rascunho: o editor ajusta
enquadramento, B-roll, gráficos e revisa cada corte.
"""
import argparse
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import decode_audio_mono, die, load_json, save_json, tc  # noqa: E402

RATE, HOP = 16000, 160  # 10 ms


def envelope_db(path):
    import numpy as np
    x = decode_audio_mono(path, RATE)
    n = x.size // HOP
    db = 20 * np.log10(np.sqrt(np.mean(x[: n * HOP].reshape(n, HOP) ** 2, axis=1) + 1e-10))
    return db, float(np.percentile(db, 10)), float(np.percentile(db, 90))


def refine(db, thr, t_ini, t_fim, lo_lim, hi_lim, pre, post):
    """Expande as bordas até o silêncio real (sem invadir a fala vizinha)."""
    i = int(t_ini * 100)
    lo = max(int(lo_lim * 100), i - 30)
    while i > lo and db[i - 1] > thr:
        i -= 1
    j = int(t_fim * 100)
    hi = min(int(hi_lim * 100), j + 40, len(db) - 1)
    while j < hi and db[j] > thr:
        j += 1
    ini = max(lo_lim, i / 100 - pre)
    fim = min(hi_lim, j / 100 + post)
    return round(ini, 3), round(fim, 3)


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("--decupagem", required=True)
    ap.add_argument("--takes", required=True)
    ap.add_argument("--out", required=True)
    ap.add_argument("--respiro-antes", type=float, default=0.06)
    ap.add_argument("--respiro-depois", type=float, default=0.14)
    ap.add_argument("--juntar-pausa", type=float, default=0.45,
                    help="enunciados consecutivos na origem com pausa ≤ isto ficam no mesmo segmento")
    args = ap.parse_args()
    dec = load_json(args.decupagem)["fontes"]
    out_path = Path(args.out).resolve()
    proj = load_json(out_path) if out_path.exists() else {}
    fontes, trans, segs = dict(proj.get("fontes", {})), dict(proj.get("transcricoes", {})), []
    trans_dir = Path(args.decupagem).resolve().parent

    for part in args.takes.split(";"):
        key, picks = part.split(":")
        key = key.strip()
        if key not in dec:
            die(f"fonte '{key}' não está na decupagem (disponíveis: {', '.join(dec)})")
        utts, groups = dec[key]["enunciados"], dec[key]["grupos"]
        tr_path = next((p for p in trans_dir.rglob(f"{key}.json") if "palavras" in load_json(p)), None)
        if not tr_path:
            die(f"transcrição {key}.json não encontrada sob {trans_dir}")
        src = Path(load_json(tr_path)["arquivo"])
        src = src if src.is_absolute() else (Path.cwd() / src)
        fontes[key] = str(src.resolve())
        trans[key] = str(tr_path.resolve())
        db, floor, speech = envelope_db(src)
        thr = floor + max(6.0, (speech - floor) * 0.25)
        chosen = []
        for p in picks.split(","):
            p = p.strip()
            if not p:
                continue
            g, k = (p.split(".") + ["1"])[:2]
            g, k = int(g), int(k)
            if g >= len(groups) or k < 1 or k > len(groups[g]):
                die(f"take inválido {key}:{p}")
            chosen.append(groups[g][k - 1])
        all_sorted = sorted(range(len(utts)), key=lambda i: utts[i]["ini"])
        for ui in chosen:
            u = utts[ui]
            pos = all_sorted.index(ui)
            prev_end = utts[all_sorted[pos - 1]]["fim"] + 0.02 if pos > 0 else 0.0
            next_ini = utts[all_sorted[pos + 1]]["ini"] - 0.02 if pos + 1 < len(all_sorted) else len(db) / 100
            ini, fim = refine(db, thr, u["ini"], u["fim"], prev_end, next_ini, args.respiro_antes, args.respiro_depois)
            last = segs[-1] if segs else None
            if last and last["fonte"] == key and 0 <= ini - last["out"] <= args.juntar_pausa:
                last["out"] = fim
                last["nota"] += " + " + u["texto"][:40]
            else:
                segs.append({"fonte": key, "in": ini, "out": fim, "nota": u["texto"][:60],
                             "enquadramento": {"zoom": 1.0, "x": 0.5, "y": 0.5}})
    for i, s in enumerate(segs):
        s["id"] = f"S{i + 1:02d}"
    proj.update({"fontes": fontes, "transcricoes": trans, "segmentos": segs})
    proj.setdefault("projeto", out_path.parent.name)
    proj.setdefault("saida", "saida/v1.mp4")
    proj.setdefault("legendas", "legendas.json")
    proj.setdefault("graficos", [])
    proj.setdefault("broll", [])
    proj.setdefault("audio", {"reducao_ruido": "auto"})
    proj.setdefault("historico", [])
    save_json(out_path, proj)
    total = sum(s["out"] - s["in"] for s in segs)
    print(f"{len(segs)} segmento(s), {total:.2f}s → {out_path}")
    for s in segs:
        print(f"  {s['id']} {s['fonte']} {tc(s['in'])}–{tc(s['out'])} ({s['out'] - s['in']:.2f}s) {s['nota']}")


if __name__ == "__main__":
    main()
