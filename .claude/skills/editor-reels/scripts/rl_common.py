"""Funções compartilhadas pelos scripts do Editor de Reels DESCOMPLICARRO.

Somente biblioteca padrão. numpy/Pillow são opcionais e importados onde usados.
"""
from __future__ import annotations

import json
import re
import shutil
import subprocess
import sys
from fractions import Fraction
from pathlib import Path

SKILL_DIR = Path(__file__).resolve().parent.parent
FONTS_DIR = SKILL_DIR / "assets" / "fonts"
DEFAULT_CONFIG = SKILL_DIR / "config" / "padrao.json"


# ---------------------------------------------------------------- utilidades
def die(msg: str, code: int = 1):
    print(f"ERRO: {msg}", file=sys.stderr)
    sys.exit(code)


def run(cmd: list[str], check: bool = True, capture: bool = True) -> subprocess.CompletedProcess:
    """Executa um comando sem shell. Em falha, mostra o fim do stderr."""
    proc = subprocess.run(cmd, capture_output=capture, text=True)
    if check and proc.returncode != 0:
        tail = (proc.stderr or "")[-3000:]
        die(f"comando falhou ({proc.returncode}): {' '.join(cmd[:6])} ...\n{tail}")
    return proc


def need(binary: str):
    if not shutil.which(binary):
        die(f"'{binary}' não encontrado. Rode scripts/check_env.py.")


def load_json(path) -> dict:
    with open(path, encoding="utf-8") as fh:
        return json.load(fh)


def save_json(path, data):
    Path(path).parent.mkdir(parents=True, exist_ok=True)
    with open(path, "w", encoding="utf-8") as fh:
        json.dump(data, fh, ensure_ascii=False, indent=2, default=str)


def load_config(path=None) -> dict:
    cfg = load_json(path or DEFAULT_CONFIG)
    return cfg


def tc(seconds: float) -> str:
    """Timecode legível mm:ss.cc"""
    if seconds is None:
        return "--"
    sign = "-" if seconds < 0 else ""
    s = abs(seconds)
    m = int(s // 60)
    return f"{sign}{m:02d}:{s - 60 * m:05.2f}"


def hex_to_ass(hexcolor: str, alpha: float = 0.0) -> str:
    """'#RRGGBB' -> '&HAABBGGRR' (alpha 0 = opaco)."""
    h = hexcolor.lstrip("#")
    r, g, b = h[0:2], h[2:4], h[4:6]
    a = max(0, min(255, round(alpha * 255)))
    return f"&H{a:02X}{b}{g}{r}".upper()


def hex_to_ffmpeg(hexcolor: str) -> str:
    return "0x" + hexcolor.lstrip("#")


# ---------------------------------------------------------------- ffprobe
def ffprobe(path) -> dict:
    need("ffprobe")
    p = run(["ffprobe", "-v", "error", "-print_format", "json", "-show_format",
             "-show_streams", str(path)])
    return json.loads(p.stdout)


def _rate(txt: str | None) -> float | None:
    if not txt or txt in ("0/0", "0"):
        return None
    try:
        return float(Fraction(txt))
    except (ValueError, ZeroDivisionError):
        return None


def media_info(path) -> dict:
    """Resumo normalizado de um arquivo de mídia (considera rotação)."""
    data = ffprobe(path)
    fmt = data.get("format", {})
    v = next((s for s in data["streams"] if s.get("codec_type") == "video"
              and not s.get("disposition", {}).get("attached_pic")), None)
    a = next((s for s in data["streams"] if s.get("codec_type") == "audio"), None)
    info = {
        "arquivo": str(path),
        "container": fmt.get("format_name"),
        "duracao": float(fmt.get("duration", 0) or 0),
        "bitrate_kbps": round(int(fmt.get("bit_rate", 0) or 0) / 1000),
        "video": None,
        "audio": None,
    }
    if v:
        rot = 0
        for sd in v.get("side_data_list", []) or []:
            if "rotation" in sd:
                rot = int(sd["rotation"])
        if "rotate" in (v.get("tags") or {}):
            rot = int(v["tags"]["rotate"])
        w, h = int(v["width"]), int(v["height"])
        if abs(rot) % 180 == 90:
            w, h = h, w
        r_fps, avg_fps = _rate(v.get("r_frame_rate")), _rate(v.get("avg_frame_rate"))
        trc = v.get("color_transfer") or ""
        info["video"] = {
            "codec": v.get("codec_name"),
            "perfil": v.get("profile"),
            "largura": w,
            "altura": h,
            "rotacao": rot,
            "fps": avg_fps or r_fps,
            "fps_nominal": r_fps,
            "vfr": bool(r_fps and avg_fps and abs(r_fps - avg_fps) > 0.05),
            "pix_fmt": v.get("pix_fmt"),
            "sar": v.get("sample_aspect_ratio", "1:1"),
            "cor_primarias": v.get("color_primaries"),
            "cor_transferencia": trc,
            "cor_espaco": v.get("color_space"),
            "hdr": trc in ("arib-std-b67", "smpte2084"),
            "duracao": float(v.get("duration", 0) or 0) or info["duracao"],
            "inicio": float(v.get("start_time", 0) or 0),
            "frames": int(v.get("nb_frames", 0) or 0),
            "orientacao": "vertical" if h > w else ("quadrado" if h == w else "horizontal"),
        }
    if a:
        info["audio"] = {
            "codec": a.get("codec_name"),
            "taxa": int(a.get("sample_rate", 0) or 0),
            "canais": int(a.get("channels", 0) or 0),
            "layout": a.get("channel_layout"),
            "duracao": float(a.get("duration", 0) or 0) or info["duracao"],
            "inicio": float(a.get("start_time", 0) or 0),
            "bitrate_kbps": round(int(a.get("bit_rate", 0) or 0) / 1000),
        }
    return info


# ---------------------------------------------------------------- análises ffmpeg
def ffmpeg_filter_log(path, vf=None, af=None, ss=None, t=None, extra_in=None) -> str:
    """Roda um filtro de análise e devolve o stderr (onde ffmpeg imprime os resultados)."""
    need("ffmpeg")
    cmd = ["ffmpeg", "-hide_banner", "-nostats"]
    if ss is not None:
        cmd += ["-ss", f"{ss:.3f}"]
    if t is not None:
        cmd += ["-t", f"{t:.3f}"]
    cmd += (extra_in or []) + ["-i", str(path)]
    if vf:
        cmd += ["-map", "0:v:0", "-vf", vf]
    if af:
        cmd += ["-map", "0:a:0", "-af", af]
    cmd += ["-f", "null", "-"]
    return run(cmd, check=False).stderr


def loudness(path, ss=None, t=None) -> dict:
    """Integrated LUFS, LRA e true peak via filtro ebur128."""
    log = ffmpeg_filter_log(path, af="ebur128=peak=true", ss=ss, t=t)
    summary = log[log.rfind("Summary:"):] if "Summary:" in log else ""
    def grab(pat):
        m = re.search(pat, summary)
        return float(m.group(1)) if m else None
    return {
        "lufs": grab(r"I:\s+(-?[\d.]+|-inf) LUFS"),
        "lra": grab(r"LRA:\s+(-?[\d.]+) LU"),
        "true_peak": grab(r"Peak:\s+(-?[\d.]+|-inf) dBFS"),
    }


def silences(path, noise_db=-38, min_d=0.35) -> list[tuple[float, float]]:
    log = ffmpeg_filter_log(path, af=f"silencedetect=noise={noise_db}dB:d={min_d}")
    out, start = [], None
    for line in log.splitlines():
        m = re.search(r"silence_start: (-?[\d.]+)", line)
        if m:
            start = max(0.0, float(m.group(1)))
        m = re.search(r"silence_end: (-?[\d.]+)", line)
        if m and start is not None:
            out.append((start, float(m.group(1))))
            start = None
    return out


def black_intervals(path, pix_th=0.05, min_d=0.12, pic_th=0.98):
    log = ffmpeg_filter_log(path, vf=f"blackdetect=d={min_d}:pix_th={pix_th}:pic_th={pic_th}")
    return [(float(a), float(b)) for a, b in
            re.findall(r"black_start:(-?[\d.]+) black_end:(-?[\d.]+)", log)]


def freeze_intervals(path, noise=0.0008, min_d=1.5):
    log = ffmpeg_filter_log(path, vf=f"freezedetect=n={noise}:d={min_d}")
    starts = [float(x) for x in re.findall(r"freeze_start: (-?[\d.]+)", log)]
    ends = [float(x) for x in re.findall(r"freeze_end: (-?[\d.]+)", log)]
    if len(ends) < len(starts):
        ends.append(None)
    return list(zip(starts, ends))


def extract_frame(path, t: float, out_png, width=None, vf_extra=None):
    """Extrai um quadro exato em t (segundos)."""
    vf = []
    if vf_extra:
        vf.append(vf_extra)
    if width:
        vf.append(f"scale={width}:-2:flags=lanczos")
    cmd = ["ffmpeg", "-hide_banner", "-loglevel", "error", "-y", "-ss", f"{max(0, t):.3f}",
           "-i", str(path), "-frames:v", "1"]
    if vf:
        cmd += ["-vf", ",".join(vf)]
    cmd += [str(out_png)]
    run(cmd)
    return out_png


def decode_audio_mono(path, rate=16000, ss=None, t=None):
    """Decodifica áudio para numpy float32 mono."""
    import numpy as np
    cmd = ["ffmpeg", "-hide_banner", "-loglevel", "error"]
    if ss is not None:
        cmd += ["-ss", f"{ss:.3f}"]
    if t is not None:
        cmd += ["-t", f"{t:.3f}"]
    cmd += ["-i", str(path), "-map", "0:a:0", "-ac", "1", "-ar", str(rate), "-f", "f32le", "-"]
    proc = subprocess.run(cmd, capture_output=True)
    if proc.returncode != 0:
        die(f"falha ao decodificar áudio de {path}: {proc.stderr.decode()[-500:]}")
    return np.frombuffer(proc.stdout, dtype=np.float32)


def contact_sheet(images: list[tuple[str, str]], out_path, cols=4, thumb_w=270, title=None):
    """Monta folha de contato com rótulos. images = [(png, rotulo), ...]."""
    try:
        from PIL import Image, ImageDraw, ImageFont
    except ImportError:
        print("AVISO: Pillow ausente — folha de contato não gerada (quadros individuais mantidos).")
        return None
    if not images:
        return None
    thumbs = []
    for p, label in images:
        im = Image.open(p).convert("RGB")
        ratio = thumb_w / im.width
        thumbs.append((im.resize((thumb_w, round(im.height * ratio)), Image.LANCZOS), label))
    th = max(t.height for t, _ in thumbs)
    label_h, pad = 34, 10
    head = 50 if title else 0
    rows = (len(thumbs) + cols - 1) // cols
    sheet = Image.new("RGB", (cols * (thumb_w + pad) + pad, head + rows * (th + label_h + pad) + pad),
                      (37, 37, 37))
    draw = ImageDraw.Draw(sheet)
    try:
        font = ImageFont.truetype(str(FONTS_DIR / "DMSans-Medium.ttf"), 18)
        tfont = ImageFont.truetype(str(FONTS_DIR / "SpaceGrotesk-Bold.ttf"), 24)
    except OSError:
        font = tfont = ImageFont.load_default()
    if title:
        draw.text((pad, 12), title, fill=(244, 241, 234), font=tfont)
    for i, (im, label) in enumerate(thumbs):
        r, c = divmod(i, cols)
        x = pad + c * (thumb_w + pad)
        y = head + pad + r * (th + label_h + pad)
        sheet.paste(im, (x, y))
        draw.text((x, y + th + 6), label[:34], fill=(244, 241, 234), font=font)
    sheet.save(out_path, quality=90)
    return out_path


# ---------------------------------------------------------------- projeto / linha do tempo
def resolve(base: Path, p) -> Path:
    p = Path(p)
    return p if p.is_absolute() else (base / p)


def load_project(path):
    """Carrega projeto.json, resolve caminhos relativos à pasta do projeto e monta a linha do tempo."""
    path = Path(path).resolve()
    proj = load_json(path)
    proj["_base"] = path.parent
    proj["_path"] = path
    cfg = load_config(resolve(path.parent, proj["padrao"]) if proj.get("padrao") else None)
    proj["_cfg"] = cfg
    fps = float(proj.get("exportacao", {}).get("fps") or cfg["video"]["fps"])
    proj["_fps"] = fps
    t = 0.0
    for i, s in enumerate(proj["segmentos"]):
        s.setdefault("id", f"S{i + 1:02d}")
        if s["out"] <= s["in"]:
            die(f"segmento {s['id']}: out ({s['out']}) <= in ({s['in']})")
        if s["fonte"] not in proj["fontes"]:
            die(f"segmento {s['id']}: fonte '{s['fonte']}' não declarada em 'fontes'")
        # duração quantizada em quadros inteiros: áudio e vídeo cortados no MESMO ponto (sem deriva)
        s["_frames"] = max(1, round((s["out"] - s["in"]) * fps))
        s["_dur"] = round(s["_frames"] / fps, 6)
        s["out"] = round(s["in"] + s["_dur"], 6)
        s["_ini"] = round(t, 6)
        t += s["_dur"]
        s["_fim"] = round(t, 6)
    # cauda: segundos extras em grafite ao final (cartela/respiro depois da última palavra)
    cauda = float(proj.get("cauda_s", 0) or 0)
    proj["_cauda_frames"] = round(cauda * fps)
    proj["_cauda_ini"] = round(t, 6)
    proj["_duracao"] = round(t + proj["_cauda_frames"] / fps, 6)
    return proj


def src_to_out(proj, fonte, t_src):
    """Tempo na origem -> tempo na saída (None se o instante não está na montagem)."""
    for s in proj["segmentos"]:
        if s["fonte"] == fonte and s["in"] <= t_src <= s["out"]:
            return s["_ini"] + (t_src - s["in"])
    return None


def cut_points(proj):
    return [s["_ini"] for s in proj["segmentos"][1:]]


class TextMeasurer:
    """Mede largura de texto como o libass renderiza (tamanho ASS = altura asc+desc)."""

    def __init__(self):
        self._cache = {}
        try:
            from PIL import ImageFont  # noqa: F401
            self.ok = True
        except ImportError:
            self.ok = False

    FILES = {
        ("Space Grotesk", True): "SpaceGrotesk-Bold.ttf",
        ("Space Grotesk", False): "SpaceGrotesk-Medium.ttf",
        ("Space Grotesk Medium", False): "SpaceGrotesk-Medium.ttf",
        ("DM Sans", True): "DMSans-Bold.ttf",
        ("DM Sans", False): "DMSans-Medium.ttf",
        ("DM Sans 14pt SemiBold", False): "DMSans-SemiBold.ttf",
        ("DM Sans 14pt Medium", False): "DMSans-Medium.ttf",
    }

    def _font(self, family, bold, size):
        from PIL import ImageFont
        key = (family, bold, size)
        if key not in self._cache:
            fname = self.FILES.get((family, bold)) or self.FILES.get((family, not bold)) or "DMSans-Bold.ttf"
            probe = ImageFont.truetype(str(FONTS_DIR / fname), 1000)
            asc, desc = probe.getmetrics()
            em = size * 1000 / (asc + desc)
            self._cache[key] = ImageFont.truetype(str(FONTS_DIR / fname), max(1, round(em)))
        return self._cache[key]

    def width(self, text, family, bold, size, spacing=0.0):
        if not self.ok:
            return len(text) * size * 0.43  # estimativa conservadora
        return self._font(family, bold, size).getlength(text) + spacing * max(0, len(text) - 1)


# ---------------------------------------------------------------- perfil de voz (análise e render usam o mesmo)
VOICE_BANDS = {
    "ronco_20_80": (20, 80),
    "grave_80_200": (80, 200),
    "embolado_200_500": (200, 500),
    "corpo_500_2k": (500, 2000),
    "presenca_2k_5k": (2000, 5000),
    "sibilancia_5k_10k": (5000, 10000),
}


def audio_profile(path, ss=None, t=None) -> dict:
    """Piso de ruído, nível de fala, SNR, clipping, oscilação e balanço espectral da fala."""
    import math
    import numpy as np
    x = decode_audio_mono(path, rate=48000, ss=ss, t=t)
    if x.size < 48000:
        return {"erro": "áudio curto ou ausente"}
    hop = 2400  # 50 ms
    n = x.size // hop
    frames = x[: n * hop].reshape(n, hop)
    db = 20 * np.log10(np.sqrt(np.mean(frames ** 2, axis=1) + 1e-12))
    floor, speech = float(np.percentile(db, 10)), float(np.percentile(db, 90))
    win = 60
    levels = [float(np.percentile(db[i:i + win], 90)) for i in range(0, n - win, win)]
    prof = {
        "clipping_pct": round(float(np.mean(np.abs(x) >= 0.999)) * 100, 4),
        "pico_dbfs": round(20 * math.log10(max(float(np.max(np.abs(x))), 1e-9)), 2),
        "ruido_de_fundo_dbfs": round(floor, 1),
        "nivel_fala_dbfs": round(speech, 1),
        "snr_estimado_db": round(speech - floor, 1),
        "oscilacao_volume_db": round(max(levels) - min(levels), 1) if len(levels) > 1 else 0.0,
    }
    idx = np.where(db > (floor + speech) / 2)[0]
    if idx.size > 10:
        sel = frames[idx[:: max(1, idx.size // 400)]]
        spec = np.mean(np.abs(np.fft.rfft(sel * np.hanning(hop), axis=1)) ** 2, axis=0)
        freqs = np.fft.rfftfreq(hop, 1 / 48000)
        total = float(np.sum(spec[(freqs >= 20) & (freqs <= 10000)])) + 1e-12
        prof["bandas_db_rel"] = {k: round(10 * math.log10(float(np.sum(spec[(freqs >= lo) & (freqs < hi)])) / total + 1e-12), 1)
                                 for k, (lo, hi) in VOICE_BANDS.items()}
    return prof


def voice_recommendation(prof: dict, cfg: dict) -> dict:
    """Converte o perfil em parâmetros de tratamento + justificativas legíveis."""
    vc = cfg["audio"]["voz"]
    rec = {"passa_altas_hz": vc["passa_altas_hz"], "reducao_ruido": "nenhuma", "eq": [],
           "deesser": False, "declip": False, "motivos": []}
    if "erro" in prof:
        rec["motivos"].append(prof["erro"])
        return rec
    m = rec["motivos"]
    if prof["clipping_pct"] > 0.01:
        rec["declip"] = True
        m.append(f"ESTOURO na origem ({prof['clipping_pct']}% das amostras no limite): adeclip; "
                 "se ainda distorcer, é limitação do material.")
    snr = prof["snr_estimado_db"]
    if snr < 25:
        rec["reducao_ruido"] = "forte"
        m.append(f"Ruído alto (SNR ~{snr} dB, piso {prof['ruido_de_fundo_dbfs']} dBFS): redução forte — "
                 "conferir se a voz não ficou metálica; se ficar, voltar para 'media'.")
    elif snr < 35:
        rec["reducao_ruido"] = "media"
        m.append(f"Ruído moderado (SNR ~{snr} dB): redução média.")
    elif snr < 45:
        rec["reducao_ruido"] = "leve"
        m.append(f"Ruído baixo (SNR ~{snr} dB): redução leve.")
    else:
        m.append(f"Ruído desprezível (SNR ~{snr} dB): sem redução de ruído (preserva naturalidade).")
    if prof["oscilacao_volume_db"] > 6:
        m.append(f"Volume da fala oscila {prof['oscilacao_volume_db']} dB entre trechos: compressor + "
                 "'ganho_db' por segmento onde necessário.")
    b = prof.get("bandas_db_rel")
    if b:
        if b["ronco_20_80"] > -18:
            rec["passa_altas_hz"] = max(rec["passa_altas_hz"], 110)
            m.append("Energia forte abaixo de 80 Hz (ronco/vento/manuseio): passa-altas em 110 Hz.")
        if b["embolado_200_500"] - b["corpo_500_2k"] > 3:
            rec["eq"].append({"f": 300, "g": -2.5, "w": 1.2})
            m.append("Voz embolada (excesso 200–500 Hz): EQ −2,5 dB em 300 Hz.")
        if b["presenca_2k_5k"] - b["corpo_500_2k"] < -16:
            rec["eq"].append({"f": 3200, "g": 2.0, "w": 1.0})
            m.append("Voz abafada (pouca presença 2–5 kHz): EQ +2 dB em 3,2 kHz.")
        if b["sibilancia_5k_10k"] - b["presenca_2k_5k"] > -2:
            rec["deesser"] = True
            m.append("Sibilância acentuada: de-esser.")
    m.append("Indicadores automáticos são estimativas: confirmar ouvindo a versão de teste (fones).")
    return rec
