# Fluxo de edição de Reels 100% local (Windows).
# Instala ffmpeg, Python 3.11, Node.js LTS e Git (winget), clona browser-use/video-use,
# troca a transcrição ElevenLabs por faster-whisper local, registra a skill em
# %USERPROFILE%\.claude\skills\video-use (cópia, sem symlink) e instala a skill do Remotion.
#
# Uso (PowerShell, a partir desta pasta):
#   powershell -ExecutionPolicy Bypass -File .\setup.ps1

$ErrorActionPreference = "Stop"
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$skillDir = Join-Path $env:USERPROFILE ".claude\skills\video-use"
$workDir = Join-Path $env:USERPROFILE "video-use-src"

function Refresh-Path {
    $env:Path = [Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
                [Environment]::GetEnvironmentVariable("Path", "User")
}

function Ensure-Tool($cmd, $wingetId) {
    if (Get-Command $cmd -ErrorAction SilentlyContinue) {
        Write-Host "ok: $cmd já instalado"
        return
    }
    Write-Host "instalando $wingetId via winget..."
    winget install -e --id $wingetId --accept-source-agreements --accept-package-agreements --silent
    Refresh-Path
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        throw "$cmd não ficou no PATH após instalar $wingetId. Feche e reabra o PowerShell e rode de novo."
    }
}

# --- 1. Dependências --------------------------------------------------------
Ensure-Tool "git" "Git.Git"
Ensure-Tool "ffmpeg" "Gyan.FFmpeg"
Ensure-Tool "node" "OpenJS.NodeJS.LTS"

# Python 3.11+ (o launcher "py" vem com o instalador oficial)
$py = $null
foreach ($v in "3.13", "3.12", "3.11") {
    if (Get-Command py -ErrorAction SilentlyContinue) {
        try { & py "-$v" -c "import sys" 2>$null } catch {}
        if ($LASTEXITCODE -eq 0) { $py = @("py", "-$v"); break }
    }
}
if (-not $py) {
    winget install -e --id Python.Python.3.11 --accept-source-agreements --accept-package-agreements --silent
    Refresh-Path
    $py = @("py", "-3.11")
}
Write-Host "python: $($py -join ' ')"
& $py[0] $py[1] --version

# --- 2. Clonar video-use -----------------------------------------------------
if (Test-Path (Join-Path $workDir ".git")) {
    git -C $workDir pull --ff-only
} else {
    git clone https://github.com/browser-use/video-use $workDir
}

# --- 3. Transcrição local (faster-whisper) no lugar do ElevenLabs ------------
Copy-Item (Join-Path $here "helpers\transcribe.py") (Join-Path $workDir "helpers\transcribe.py") -Force
Copy-Item (Join-Path $here "helpers\transcribe_batch.py") (Join-Path $workDir "helpers\transcribe_batch.py") -Force

# SKILL.md manda checar ELEVENLABS_API_KEY no início da sessão: troca pela checagem local.
$skillMd = Join-Path $workDir "SKILL.md"
$text = Get-Content $skillMd -Raw -Encoding UTF8
$text = [regex]::Replace($text, '(?m)^- `ELEVENLABS_API_KEY` resolves[^\r\n]*',
    '- Transcription is LOCAL (faster-whisper, model "medium", language "pt", CPU int8). No ElevenLabs, no API key: never ask for one. `python -c "import faster_whisper"` must succeed.')
$text = $text.Replace("single-file Scribe call", "single-file local faster-whisper transcription (pt, medium)")
$text = $text.Replace("4-worker parallel transcription", "local batch transcription (model loaded once)")
[IO.File]::WriteAllText($skillMd, $text, (New-Object Text.UTF8Encoding($false)))

& $py[0] $py[1] -m pip install --upgrade pip
& $py[0] $py[1] -m pip install faster-whisper requests librosa matplotlib pillow numpy
if ($LASTEXITCODE -ne 0) { throw "pip install falhou" }

# Baixa o modelo "medium" agora (~1.5 GB, uma vez só) para o primeiro teste não parecer travado.
& $py[0] $py[1] -c "from faster_whisper import WhisperModel; WhisperModel('medium', device='cpu', compute_type='int8'); print('modelo medium pronto')"

# --- Registrar a skill (cópia, sem symlink) ---------------------------------
if (Test-Path $skillDir) { Remove-Item $skillDir -Recurse -Force }
New-Item -ItemType Directory -Force (Split-Path $skillDir) | Out-Null
robocopy $workDir $skillDir /E /XD .git /NFL /NDL /NJH /NJS | Out-Null
if ($LASTEXITCODE -ge 8) { throw "robocopy falhou ($LASTEXITCODE)" }
Write-Host "skill registrada em $skillDir"

# --- 4. Skill oficial do Remotion -------------------------------------------
Push-Location $env:USERPROFILE
try {
    npx -y skills add remotion-dev/skills -g -a claude-code -y --copy
} finally {
    Pop-Location
}

Write-Host ""
$pyCmd = $py -join " "
Write-Host "Pronto. Teste: $pyCmd `"$skillDir\helpers\transcribe.py`" C:\caminho\do\video.mp4"
Write-Host "Depois:      $pyCmd `"$skillDir\helpers\pack_transcripts.py`" --edit-dir C:\caminho\do\edit"
