# Controle de qualidade — obrigatório antes de qualquer entrega

**Renderizar sem erro não é aprovar.** Uma versão só é entregue depois de: (1) QA automático sem FALHA, (2) revisão visual completa, (3) relato honesto das limitações.

## 1. Automático (`scripts/qa.py projeto.json`)
| Área | O que verifica | Falha quando |
|---|---|---|
| Arquivo/formato | MP4, H.264 High, 1080×1920, SAR 1:1, yuv420p, fps constante, AAC 48 kHz estéreo, faststart, metadados BT.709 | qualquer item fora |
| Duração | duração e nº de quadros = EDL | > 0,12 s ou > 1 quadro de diferença |
| Sincronia | duração áudio × vídeo; **áudio de cada segmento correlacionado com a origem**; quadro de cada segmento casado com a origem (±3 quadros, mesmo recorte) | deslocamento > 25 ms (áudio) / > 1 quadro (imagem, alerta) |
| Loudness | −14 LUFS ±1; true peak ≤ −1,5 dBTP (+0,5 de tolerância) | true peak acima |
| Áudio | silêncios ≥ 0,9 s fora de trechos intencionais; estalos nos cortes; primeira fala até 0,6 s (gancho) | alerta |
| Imagem | tela preta não intencional; imagem congelada ≥ 1,5 s; avisos de ampliação do render | tela preta = falha |
| Legendas/gráficos | lint completo (zonas seguras, linhas, duração, car/s, sobreposições, destaques, tempo de leitura dos gráficos) | regras de FALHA do lint |
| Conteúdo | ressalvas técnicas (⚠) com algum take dentro da montagem; nenhum corte no meio de palavra | alerta para decisão do editor |

Trechos intencionais (cartela, cauda grafite, fades) são excluídos das verificações de preto/congelamento/silêncio.

## 2. Revisão visual (o editor faz, sempre)
Abrir com a ferramenta de leitura de imagem:
- `qa_inicio_meio_fim.jpg` — 1º quadro já tem o gancho? Rosto/expressão bons? O fim fecha limpo (sem quadro preto, sem corte no meio da palavra)?
- `qa_cortes.jpg` — pares antes/depois de cada corte: salto estranho de cabeça, olhos fechados, boca torta, mudança de luz/cor, objeto que some?
- `qa_textos_zonas.jpg` — textos fora das áreas vermelhas, destaques certos, **nenhum card/gráfico sobre rostos** (regra do usuário), um gráfico por vez.
- `quadros/legenda_100pct.png` — nitidez do texto, contorno, acentos.
- Se algo parecer duvidoso, extrair quadros extras (`rl_common.extract_frame`) ou fazer folha de contato de um trecho.
- Reler `legendas.json` inteiro: ortografia, fala real, termos.
- Conferir que o conteúdo técnico está correto e com as ressalvas.

Lições do 1º material real (entrevista, 23/11/2026): o Whisper estica palavras sobre o silêncio — confirmar bordas pela energia do áudio (montar/decupagem já fazem); entrevista espontânea vem sem pontuação — revisar a transcrição antes de gerar legendas, pois a pontuação guia as quebras; conferir nos pares de corte se alguém atravessa a lente; rótulo de nome sempre do lado da pessoa; ao mudar a duração, recalcular a cartela final.

Lições do teste técnico (registradas para não repetir): a sobreposição das zonas seguras precisa de contorno (cor sólida some em fundos coloridos); gráficos secundários abaixo de ~44 px de altura ficam ilegíveis no celular; uma cartela final colocada antes do fim da fala esconde a última legenda — o lint agora acusa.

## 3. Relatório de entrega (mensagem ao usuário)
```
VERSÃO vN — [arquivo] · duração · 1080×1920 · 30 fps · −14 LUFS
QA automático: OK | ALERTA (lista curta) — falhas: nenhuma
Revisão visual: feita — [observações]
Decisões para você: 1… 2…
Limitações que não pude corrigir: [ex.: ruído de vento em 0:12; foco suave no take 2]
O que conferir ouvindo (fones): [ex.: naturalidade da redução de ruído]
```
Anexar o vídeo e `qa_inicio_meio_fim.jpg` (ou a folha mais relevante).
