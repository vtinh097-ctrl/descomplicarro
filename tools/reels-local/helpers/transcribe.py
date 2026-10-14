"""Transcribe a video locally with faster-whisper (no account, no API).

Extracts mono 16kHz audio via ffmpeg, runs faster-whisper on CPU (int8) with
word-level timestamps + VAD, and writes a Scribe-compatible JSON to
<edit_dir>/transcripts/<video_stem>.json:

    {"language_code": "pt", "text": "...",
     "words": [{"text", "start", "end", "type": "word", "speaker_id": "S0"}, ...]}

That is the shape pack_transcripts.py reads. There is no diarization, so every
word gets speaker S0, and no audio events.

Cached: if the output file already exists, transcription is skipped.

Usage:
    python helpers/transcribe.py <video_path>
    python helpers/transcribe.py <video_path> --edit-dir /custom/edit
    python helpers/transcribe.py <video_path> --language en
    python helpers/transcribe.py <video_path> --model small
"""

from __future__ import annotations

import argparse
import array
import json
import math
import os
import subprocess
import sys
import tempfile
import threading
import time
import wave
from pathlib import Path


DEFAULT_MODEL = "medium"
DEFAULT_LANGUAGE = "pt"
SPEAKER_ID = "S0"

_models: dict[str, object] = {}
_model_lock = threading.Lock()


def load_model(name: str = DEFAULT_MODEL):
    """Load a faster-whisper model once per process (batch mode shares it)."""
    with _model_lock:
        if name not in _models:
            from faster_whisper import WhisperModel

            _models[name] = WhisperModel(
                name,
                device="cpu",
                compute_type="int8",
                cpu_threads=os.cpu_count() or 4,
            )
        return _models[name]


def count_audio_tracks(video_path: Path) -> int:
    """How many audio streams the container holds."""
    out = subprocess.run(
        ["ffprobe", "-v", "error", "-select_streams", "a",
         "-show_entries", "stream=index", "-of", "csv=p=0", str(video_path)],
        capture_output=True, text=True,
    )
    return len([ln for ln in out.stdout.splitlines() if ln.strip()])


def peak_dbfs(wav_path: Path) -> float:
    """Peak level of a 16-bit PCM wav, in dBFS. -inf for digital silence."""
    peak = 0
    with wave.open(str(wav_path), "rb") as w:
        # A chunk at a time: a two-hour take is 230 MB of 16 kHz mono.
        while frames := w.readframes(1 << 16):
            samples = array.array("h", frames)
            peak = max(peak, max(samples), -min(samples))
    return 20 * math.log10(peak / 32768) if peak > 0 else float("-inf")


def extract_audio(video_path: Path, dest: Path, audio_track: int = 0) -> None:
    cmd = [
        "ffmpeg", "-y", "-i", str(video_path),
        "-map", f"0:a:{audio_track}",
        "-vn", "-ac", "1", "-ar", "16000", "-c:a", "pcm_s16le",
        str(dest),
    ]
    subprocess.run(cmd, check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)


def run_whisper(
    audio_path: Path,
    model_name: str = DEFAULT_MODEL,
    language: str | None = DEFAULT_LANGUAGE,
    verbose: bool = True,
) -> dict:
    model = load_model(model_name)
    segments, info = model.transcribe(
        str(audio_path),
        language=language,
        word_timestamps=True,
        vad_filter=True,
    )

    words: list[dict] = []
    texts: list[str] = []
    # segments is a generator: transcription happens while we iterate.
    for seg in segments:
        texts.append(seg.text.strip())
        for w in seg.words or []:
            text = w.word.strip()
            if not text:
                continue
            words.append({
                "text": text,
                "start": round(float(w.start), 3),
                "end": round(float(w.end), 3),
                "type": "word",
                "speaker_id": SPEAKER_ID,
            })
        if verbose:
            print(f"    [{seg.start:7.2f}] {seg.text.strip()[:70]}", flush=True)

    return {
        "language_code": info.language,
        "language_probability": round(float(info.language_probability), 3),
        "text": " ".join(t for t in texts if t),
        "words": words,
    }


def transcript_path(edit_dir: Path, video: Path, audio_track: int = 0) -> Path:
    """Where a video's transcript lands.

    The track belongs in the name, or a rerun with --audio-track hands back the transcript of
    the track it is meant to replace. Batch mode tests its cache with this too.
    """
    suffix = "" if audio_track == 0 else f".track{audio_track}"
    return edit_dir / "transcripts" / f"{video.stem}{suffix}.json"


def transcribe_one(
    video: Path,
    edit_dir: Path,
    language: str | None = DEFAULT_LANGUAGE,
    model_name: str = DEFAULT_MODEL,
    verbose: bool = True,
    audio_track: int = 0,
) -> Path:
    """Transcribe a single video. Returns path to transcript JSON.

    Cached: returns existing path immediately if the transcript already exists.
    """
    transcripts_dir = edit_dir / "transcripts"
    transcripts_dir.mkdir(parents=True, exist_ok=True)
    out_path = transcript_path(edit_dir, video, audio_track)

    if out_path.exists():
        if verbose:
            print(f"cached: {out_path.name}")
        return out_path

    if verbose:
        print(f"  extracting audio from {video.name}", flush=True)

    n_tracks = count_audio_tracks(video)
    if n_tracks > 1 and verbose:
        print(f"  note: {video.name} has {n_tracks} audio tracks, using track "
              f"{audio_track + 1} (--audio-track to change)", flush=True)

    t0 = time.time()
    with tempfile.TemporaryDirectory() as tmp:
        audio = Path(tmp) / f"{video.stem}.wav"
        extract_audio(video, audio, audio_track)

        # Whisper hallucinates text on silence, so catch the wrong-track case first.
        peak = peak_dbfs(audio)
        if peak < -60.0:
            raise RuntimeError(
                f"track {audio_track + 1} of {video.name} is silent "
                f"(peak {peak:.1f} dBFS) - not transcribing. "
                + (f"The file has {n_tracks} audio tracks; try --audio-track "
                   + " or ".join(str(i) for i in range(n_tracks) if i != audio_track) + "."
                   if n_tracks > 1 else "Check the source audio.")
            )

        if verbose:
            print(f"  transcribing with faster-whisper '{model_name}' (cpu/int8, "
                  f"language={language or 'auto'})", flush=True)
        payload = run_whisper(audio, model_name, language, verbose)

    out_path.write_text(json.dumps(payload, indent=2), encoding="utf-8")
    dt = time.time() - t0

    if verbose:
        kb = out_path.stat().st_size / 1024
        print(f"  saved: {out_path.name} ({kb:.1f} KB) in {dt:.1f}s")
        print(f"    words: {len(payload['words'])}")

    return out_path


def main() -> None:
    ap = argparse.ArgumentParser(description="Transcribe a video locally with faster-whisper")
    ap.add_argument("video", type=Path, help="Path to video file")
    ap.add_argument(
        "--edit-dir",
        type=Path,
        default=None,
        help="Edit output directory (default: <video_parent>/edit)",
    )
    ap.add_argument(
        "--language",
        type=str,
        default=DEFAULT_LANGUAGE,
        help=f"ISO language code (default: {DEFAULT_LANGUAGE}). Pass 'auto' to auto-detect.",
    )
    ap.add_argument(
        "--model",
        type=str,
        default=DEFAULT_MODEL,
        help=f"faster-whisper model size (default: {DEFAULT_MODEL}).",
    )
    ap.add_argument(
        "--num-speakers",
        type=int,
        default=None,
        help="Accepted for compatibility; ignored (no diarization, every word is S0).",
    )
    ap.add_argument(
        "--audio-track",
        type=int,
        default=0,
        help="Zero-based audio track to transcribe. OBS writes the game on track 0 "
             "and the mic on track 1.",
    )
    args = ap.parse_args()

    video = args.video.resolve()
    if not video.exists():
        sys.exit(f"video not found: {video}")

    edit_dir = (args.edit_dir or (video.parent / "edit")).resolve()

    transcribe_one(
        video=video,
        edit_dir=edit_dir,
        language=None if args.language == "auto" else args.language,
        model_name=args.model,
        audio_track=args.audio_track,
    )


if __name__ == "__main__":
    main()
