# Reels local (video-use + faster-whisper + Remotion)

Fluxo de edição de Reels 100% local no Windows, sem conta nem API externa.

## Instalar

No PowerShell, dentro desta pasta:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

O script:

1. Instala o que faltar via winget: Git, ffmpeg (`Gyan.FFmpeg`), Node.js LTS e Python 3.11 (se não houver 3.11+).
2. Clona `browser-use/video-use` em `%USERPROFILE%\video-use-src` e copia (sem symlink) para `%USERPROFILE%\.claude\skills\video-use`.
3. Troca `helpers/transcribe.py` e `helpers/transcribe_batch.py` pelas versões desta pasta (faster-whisper `medium`, `pt`, CPU `int8`, `word_timestamps`, `vad_filter`) e remove a checagem de `ELEVENLABS_API_KEY` do `SKILL.md`. Baixa o modelo `medium` (~1,5 GB, uma vez).
4. Instala a skill oficial do Remotion: `npx skills add remotion-dev/skills -g -a claude-code -y --copy`.

## Testar

```powershell
py -3.11 "$env:USERPROFILE\.claude\skills\video-use\helpers\transcribe.py" C:\videos\teste.mp4
py -3.11 "$env:USERPROFILE\.claude\skills\video-use\helpers\pack_transcripts.py" --edit-dir C:\videos\edit
type C:\videos\edit\takes_packed.md
```

Ou, no Claude Code local: *"Transcreva C:\videos\teste.mp4 com a skill video-use, mostre o takes_packed.md e espere meu OK antes de cortar."*

## Formato da transcrição

`edit/transcripts/<video>.json`:

```json
{"language_code": "pt", "text": "...",
 "words": [{"text": "Olá,", "start": 0.2, "end": 0.6, "type": "word", "speaker_id": "S0"}]}
```

Sem diarização: todas as palavras são `S0` (o `takes_packed.md` mostra `SS0`, porque o `pack_transcripts.py` acrescenta um `S`). `--language auto` detecta o idioma; `--model small` é mais rápido em CPU fraca.
