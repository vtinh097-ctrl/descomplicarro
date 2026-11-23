#!/usr/bin/env python3
"""Decupagem assistida a partir das transcrições (Etapa A).

Uso:
  python3 scripts/decupagem.py trans/A.json [trans/B.json ...] --out decupagem.md
  python3 scripts/decupagem.py trans/A.json --projeto projeto.json --out verificacao.md

Faz:
- divide a fala em enunciados (por pausa/pontuação) com confiança média e velocidade;
- agrupa enunciados parecidos = repetições/takes da mesma frase (e falsos inícios);
- marca hesitações, muletas, pausas longas;
- marca frases com RESSALVA TÉCNICA (não cortar sem revisar);
- sinaliza candidatos a gancho (números, contraste, pergunta);
- com --projeto: verifica se toda ressalva técnica tem pelo menos um take dentro da montagem
  e se algum segmento corta uma palavra ao meio.
A escolha final dos takes é do editor (critérios em references/direcao-editorial.md).
"""
import argparse
import re
import sys
import unicodedata
from difflib import SequenceMatcher
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from rl_common import load_json, save_json, tc  # noqa: E402

HESITACOES = {"é...", "ééé", "ãh", "ã", "ããh", "hum", "hmm", "ahn", "eh", "éh", "ah"}
MULETAS = {"né", "tipo", "então", "assim", "sabe", "enfim", "basicamente", "literalmente"}
RESSALVA = [r"\bdepende", r"\bexceto", r"a não ser", r"desde que", r"\bcuidado", r"\baten[çc][ãa]o",
            r"\bnunca\b", r"\bsempre\b", r"\bmanual\b", r"n[ãa]o (recomendo|recomendado|fa[çc]a)",
            r"cada caso", r"pode variar", r"\bmas\b", r"\bpor[ée]m\b", r"\bperigo", r"\brisco",
            r"\bgarantia\b", r"\bespecifica[çc]", r"\bprocure\b", r"\bmec[aâ]nico de confian"]
GANCHO = [r"\d", r"\bnunca\b", r"\bninguém\b", r"\bverdade\b", r"\berrad", r"\bmito\b", r"\bsegredo",
          r"\?", r"\bcaro\b", r"\bbarato\b", r"\bproblema\b", r"\bdiferen[çc]a", r"\bvale a pena"]


def norm(txt):
    t = unicodedata.normalize("NFKD", txt.lower())
    t = "".join(c for c in t if not unicodedata.combining(c))
    return re.sub(r"[^a-z0-9 ]+", " ", t).split()


def utterances(words, pause=0.6):
    out, cur = [], []
    for i, w in enumerate(words):
        cur.append(w)
        nxt = words[i + 1] if i + 1 < len(words) else None
        gap = (nxt["ini"] - w["fim"]) if nxt else 99
        if gap >= pause or (re.search(r"[.!?]$", w["w"]) and gap >= 0.25):
            out.append(cur)
            cur = []
    if cur:
        out.append(cur)
    res = []
    for u in out:
        text = " ".join(w["w"] for w in u)
        dur = max(0.01, u[-1]["fim"] - u[0]["ini"])
        toks = [w["w"].lower().strip(",.!?;:") for w in u]
        internal = [(round(a["fim"], 2), round(b["ini"] - a["fim"], 2))
                    for a, b in zip(u, u[1:]) if b["ini"] - a["fim"] >= 0.35]
        res.append({
            "ini": u[0]["ini"], "fim": u[-1]["fim"], "texto": text,
            "n": len(u), "conf": round(sum(w["prob"] for w in u) / len(u), 3),
            "ppm": round(len(u) / dur * 60), "hesitacoes": sum(t in HESITACOES for t in toks),
            "muletas": [t for k, t in enumerate(toks) if t in MULETAS
                        and not (t == "tipo" and k + 1 < len(toks) and toks[k + 1] in ("de", "do", "da", "dos", "das"))],
            "ressalva": any(re.search(p, text.lower()) for p in RESSALVA),
            "gancho": sum(bool(re.search(p, text.lower())) for p in GANCHO),
            "pausas_internas": internal,
        })
    return res


def similar(a, b):
    na, nb = norm(a["texto"]), norm(b["texto"])
    if not na or not nb:
        return 0.0
    r = SequenceMatcher(None, na, nb).ratio()
    # falso início: o menor é prefixo (aproximado) do maior
    short, long_ = (na, nb) if len(na) <= len(nb) else (nb, na)
    if len(short) >= 2 and SequenceMatcher(None, short, long_[: len(short)]).ratio() >= 0.8:
        r = max(r, 0.75)
    for k in range(len(short), 2, -1):  # "...então, o óleo mais caro" + "o óleo mais caro pode..."
        if SequenceMatcher(None, short[-k:], long_[:k]).ratio() >= 0.85:
            r = max(r, 0.7)
            break
    return r


def group_takes(utts, thr=0.6):
    groups, assigned = [], [None] * len(utts)
    for i, u in enumerate(utts):
        if assigned[i] is not None:
            continue
        g = [i]
        assigned[i] = len(groups)
        for j in range(i + 1, min(len(utts), i + 25)):  # repetições costumam estar próximas
            if assigned[j] is None and similar(u, utts[j]) >= thr:
                g.append(j)
                assigned[j] = len(groups)
        groups.append(g)
    return groups


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("transcricoes", nargs="+")
    ap.add_argument("--out", required=True)
    ap.add_argument("--projeto")
    ap.add_argument("--pausa", type=float, default=0.6)
    args = ap.parse_args()

    L, data = ["# Decupagem assistida", "",
               "Legenda: ★ candidato a gancho · ⚠ ressalva técnica (não cortar sem revisar) · "
               "↺ grupo de repetição/takes · h = hesitações · conf = confiança média da transcrição", ""], {}
    proj = load_json(args.projeto) if args.projeto else None
    alerts = []
    for tpath in args.transcricoes:
        tr = load_json(tpath)
        utts = utterances(tr["palavras"], args.pausa)
        groups = group_takes(utts)
        key = Path(tpath).stem
        data[key] = {"enunciados": utts, "grupos": groups}
        L += [f"## Fonte `{key}` — {Path(tr['arquivo']).name}", ""]
        for gi, g in enumerate(groups):
            rep = len(g) > 1
            for k, idx in enumerate(g):
                u = utts[idx]
                flags = ("★" * min(u["gancho"], 3)) + (" ⚠" if u["ressalva"] else "")
                tag = f"↺{gi:02d}.{k + 1}" if rep else f"  {gi:02d}  "
                extra = []
                if u["hesitacoes"]:
                    extra.append(f"h={u['hesitacoes']}")
                if u["muletas"]:
                    extra.append("muletas=" + ",".join(u["muletas"]))
                if u["pausas_internas"]:
                    extra.append("pausas=" + ",".join(f"{p[1]}s@{tc(p[0])}" for p in u["pausas_internas"]))
                L.append(f"- `{tag}` [{tc(u['ini'])}–{tc(u['fim'])}] conf {u['conf']:.2f} · {u['ppm']} ppm "
                         f"{flags} — {u['texto']}" + (f"  _({'; '.join(extra)})_" if extra else ""))
            if rep:
                best = max(g, key=lambda i: (utts[i]["n"] >= 0.8 * max(utts[j]["n"] for j in g),
                                             utts[i]["conf"] - 0.05 * utts[i]["hesitacoes"], i))
                L.append(f"  - ↳ sugestão automática: take {g.index(best) + 1} (mais completo/confiante; "
                         "o editor confirma pela imagem e naturalidade)")
        gaps = [(a["fim"], b["ini"] - a["fim"]) for a, b in zip(utts, utts[1:]) if b["ini"] - a["fim"] >= 1.5]
        if gaps:
            L += ["", "Intervalos longos sem fala (≥1,5 s): " +
                  ", ".join(f"{tc(t)} ({d:.1f}s)" for t, d in gaps)]
        L.append("")

        if proj:
            src_ids = [k for k, v in proj.get("transcricoes", {}).items()
                       if Path(v).stem == key or Path(v).name == Path(tpath).name]
            segs = [s for s in proj["segmentos"] if s["fonte"] in src_ids]

            def inside(u):
                return any(s["in"] <= u["ini"] + 0.05 and u["fim"] - 0.05 <= s["out"] for s in segs)

            for g in groups:
                if any(utts[i]["ressalva"] for i in g) and not any(inside(utts[i]) for i in g):
                    u = utts[g[0]]
                    alerts.append(f"⚠ RESSALVA possivelmente removida ({key} {tc(u['ini'])}): «{u['texto']}»")
            for s in segs:
                for w in tr["palavras"]:
                    for edge, name in ((s["in"], "entrada"), (s["out"], "saída")):
                        if w["ini"] + 0.03 < edge < w["fim"] - 0.03:
                            alerts.append(f"✂ Corte na {name} do segmento {s.get('id', '?')} ({tc(edge)}) "
                                          f"corta a palavra «{w['w']}» [{tc(w['ini'])}–{tc(w['fim'])}]")
    if proj:
        L += ["## Verificação da montagem", ""] + ([f"- {a}" for a in alerts] or
                                                  ["- Nenhuma ressalva removida e nenhum corte no meio de palavra."])
    Path(args.out).write_text("\n".join(L) + "\n", encoding="utf-8")
    save_json(Path(args.out).with_suffix(".json"), {"fontes": data, "alertas": alerts})
    print(f"decupagem → {args.out}" + (f" · {len(alerts)} alerta(s)" if proj else ""))
    for a in alerts:
        print("  " + a)


if __name__ == "__main__":
    main()
