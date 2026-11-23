# DESCOMPLICARRO — Editor de Reels

Skill do Claude Code que transforma vídeo bruto em Reel vertical (9:16, 1080×1920, MP4 H.264/AAC) com a identidade do DESCOMPLICARRO, trabalhando como um editor: diagnóstico, proposta, versão de teste, ajustes e finalização — sempre com controle de qualidade.

## Como usar
Peça ao Claude, por exemplo: *"edita esse Reel — os brutos estão na pasta X do Drive"*, *"faz a decupagem dessa gravação"*, *"aplica esses ajustes na v1"*. A skill `editor-reels` é carregada automaticamente e conduz as etapas:

1. **Diagnóstico (A)** — relatório técnico do material, transcrição, decupagem e proposta de edição. Você aprova.
2. **Primeira versão (B)** — vídeo de teste + relatório de QA + decisões em aberto. Você pede ajustes.
3. **Finalização (C)** — ajustes aplicados, QA de novo, arquivo final.

## Estrutura
```
editor-reels/
├── SKILL.md                      fluxo de trabalho e princípios
├── README.md                     este arquivo
├── references/
│   ├── direcao-editorial.md      regras criativas (decupagem, gancho, montagem, B-roll, CTA)
│   ├── identidade-visual.md      cores, fontes, zonas seguras, gráficos
│   ├── legendas.md               padrão de legendagem
│   ├── audio.md                  tratamento de voz, música, masterização
│   ├── tecnico.md                scripts, formato do projeto.json, exportação
│   ├── controle-qualidade.md     QA automático + revisão visual
│   ├── padroes-aprovados.md      registro dos padrões validados
│   └── plano-de-teste.md         primeiro teste com material real
├── config/padrao.json            parâmetros numéricos do padrão vigente
├── templates/projeto.exemplo.json
├── assets/fonts/                 Space Grotesk + DM Sans (estáticas, OFL)
└── scripts/
    ├── check_env.py              auditoria do ambiente
    ├── analyze.py                diagnóstico técnico + folha de contato
    ├── transcribe.py             transcrição com tempo por palavra (faster-whisper)
    ├── decupagem.py              takes, repetições, ressalvas técnicas, ganchos
    ├── montar.py                 rascunho de EDL com cortes no silêncio real
    ├── captions.py               legendas e gráficos (build / lint / ass)
    ├── render.py                 montagem, áudio, exportação
    ├── qa.py                     controle de qualidade + prévias
    └── rl_common.py              funções compartilhadas
```

## Requisitos
- FFmpeg e FFprobe (com libx264, aac e libass) — obrigatórios.
- Python 3.9+ com numpy e Pillow (recomendados).
- `pip install faster-whisper` para transcrição (o modelo `medium`, ~1,5 GB, é baixado na primeira execução).

Verifique com `python3 scripts/check_env.py`.

## Princípios
Sentido técnico acima da duração (nenhuma ressalva técnica é cortada para encurtar) · fala natural · ritmo pela narrativa, não por intervalo fixo · nada de efeito genérico · música só autorizada · renderizar não é aprovar.

## Estado
Versão 0.1 — testada tecnicamente com material sintético; **aguardando o primeiro teste com um Reel real e a aprovação** (ver `references/plano-de-teste.md`).

Fontes Space Grotesk e DM Sans: SIL Open Font License 1.1 (arquivos `OFL-*.txt`).
