# Procedimentos técnicos

Todos os comandos a partir da pasta da skill (`.claude/skills/editor-reels/`) ou com caminho completo dos scripts. A **pasta de trabalho do projeto fica fora do repositório** (ex.: scratchpad):

```
<projeto>/
  brutos/        arquivos de câmera (A.mp4, B.mov…)
  broll/         imagens/vídeos complementares (com origem/direito registrados)
  musica/        só música autorizada
  analise/       saída do analyze.py
  trans/         transcrições + decupagem
  projeto.json   EDL (lista de edição)
  legendas.json  legendas revisadas
  build/         intermediários (legendas.ass, filtergraph, master.mov, render_log.json)
  saida/         v1.mp4, v2.mp4… + qa_v1/, qa_v2/…
```

## Dependências
Obrigatórias: FFmpeg/FFprobe (com libx264, aac, libass), Python ≥ 3.9. Recomendadas: numpy, Pillow (folhas de contato, medição de texto, análises). Transcrição: `pip install faster-whisper` (modelo baixado do Hugging Face na primeira vez; o script força download HTTP clássico porque o protocolo Xet costuma ser barrado por proxies). `scripts/check_env.py` audita tudo.

## Sequência de scripts
| Etapa | Comando | Saída |
|---|---|---|
| 0 | `python3 scripts/check_env.py` | tabela OK/FALTA |
| A | `python3 scripts/analyze.py brutos/*.mp4 --out analise` | `analise/<arq>/analise.md`, `contato.jpg` |
| A | `python3 scripts/transcribe.py brutos/A.mp4 --out trans/A.json --vocab "termos, marcas"` | palavras com tempo + `.txt` |
| A | `python3 scripts/decupagem.py trans/A.json [trans/B.json] --out trans/decupagem.md` | takes, repetições, ressalvas ⚠, ganchos ★ |
| B | `python3 scripts/montar.py --decupagem trans/decupagem.json --takes "A:00.3,01,02" --out projeto.json` | rascunho da EDL com cortes no silêncio real |
| B | ou `montar.py ... --trechos "E:12.42-15.0,15.4-19.9"` (cortes dentro de fala contínua, tempos de palavra) | idem |
| B | editar `projeto.json` (enquadramento, B-roll, gráficos, áudio) | |
| B | `python3 scripts/captions.py build projeto.json` → revisar → `captions.py lint projeto.json` | `legendas.json` |
| B | `python3 scripts/render.py projeto.json` (`--rascunho` para checar montagem rápido) | `saida/vN.mp4` |
| B/C | `python3 scripts/qa.py projeto.json` | `saida/qa_vN/relatorio.md` + prévias |

Importante: o identificador da fonte (`A`) é o nome do arquivo de transcrição (`trans/A.json`); mantenha-os iguais.

## `projeto.json` (EDL) — campos
Modelo completo: `templates/projeto.exemplo.json`. Tempos de `segmentos` são **da origem**; tempos de `broll` e `graficos` são **da saída** (linha do tempo montada). `montar.py` e `captions.py build` imprimem/calculam esses tempos.

- `fontes` `{id: caminho}` · `transcricoes` `{id: caminho}` · `saida` · `legendas` · `padrao` (outro config, opcional)
- `segmentos[]`: `fonte`, `in`, `out`, `enquadramento {modo: recortar|encaixar, zoom, x, y}`, `ganho_db`, `cor {brilho, contraste, saturacao, gama}`, `fade_entrada`, `fade_saida` (para grafite), `sem_legenda`, `nota`
- `broll[]`: `arquivo`, `inicio`, `duracao`, `src_in`, `enquadramento` (`modo`: recortar | encaixar | card; no card: `girar`, `recorte`, `marcar`, `largura_max`, `altura_max`, `centro_y`), `fade` (0,15 s), `som_ambiente_db`, `origem` (direito de uso)
- `graficos[]`: `tipo` (titulo, termo, dado, rotulo [`lado`], capitulo, nota, cartela, cartela_final), `inicio`, `fim`, textos do tipo, `destaques`
- `destaques_globais`: termos destacados em laranja em qualquer bloco de legenda
- `cauda_s`: segundos extras em grafite no final (para a cartela entrar depois da última palavra)
- `audio`: sobrescritas da cadeia de voz · `musica`: `arquivo`, `autorizada`, `licenca`, `volume_db`, `ducking`
- `exportacao`: `fps`, `largura`, `altura`, `crf`, `preset` (alternativas)
- `historico[]`: versões e ajustes aprovados

## Como o render garante corte e sincronia
- Cada segmento é lido com busca precisa (`-ss/-t` na entrada) e cortado em **número inteiro de quadros**; o áudio é cortado no mesmo número exato de amostras (1600 amostras/quadro a 30 fps). Sem deriva acumulada.
- Reenquadramento: maior área 9:16 da origem ÷ zoom, centrada em (x, y), escalada com lanczos. Ampliação > 1,25× gera aviso; > 2× é bloqueio editorial (aviso forte).
- HDR (HLG/PQ de celular) → tonemap para SDR BT.709. VFR → fps constante. Rotação de metadado respeitada.
- Textos (legendas + gráficos) num único ASS, renderizados pela libass com as fontes da marca.
- Latência da cadeia de áudio medida e compensada em toda renderização.

## Exportação (padrão)
MP4 · H.264 High 4.1 · 1080×1920 · yuv420p · BT.709 · CRF 17 preset slow (maxrate 16 Mb/s) · GOP 2 s · fps 30 (ou o da origem quando 23,976/24/25/29,97) · AAC 192 kbps 48 kHz estéreo · faststart.

Alternativas via `exportacao`: 4:5 para feed (1080×1350), 1:1, 60 fps (só se a origem for 60 e o conteúdo pedir movimento), CRF maior para arquivo menor. Ao mudar dimensões, rever zonas seguras e posições dos gráficos.

## Fotos de celular (HEIC)
Fotos de iPhone chegam em HEIC, que o FFmpeg 6.1 não lê. Converter antes, respeitando a orientação:
`pip install pillow-heif` e `ImageOps.exif_transpose(Image.open(f)).convert("RGB").save("foto.jpg", quality=95)` (com `pillow_heif.register_heif_opener()`).

## Limitações conhecidas (v0.1)
- **Multicâmera** (duas câmeras gravando a mesma fala): ainda não há sincronização automática entre câmeras nem transcrição compartilhada; a verificação de ressalvas trata cada câmera separadamente e gera alertas falsos. Implementar quando houver material multicâmera real (sincronizar por correlação de áudio, como o QA já faz).
- Sem detecção automática de rosto: o enquadramento (`x`, `y`) é decidido pelo editor olhando a folha de contato.
- Sem estabilização automática (o FFmpeg tem `vidstab`; adicionar se o material exigir).
- Claude não ouve o áudio: decisões de tratamento são por medição; a validação final é do usuário com fones.
