# Plano do primeiro teste com um Reel real do DESCOMPLICARRO

A skill só é considerada concluída depois deste teste e da aprovação do usuário.

## 1. Material a enviar
| Item | Obrigatório | Observação |
|---|---|---|
| Brutos de câmera de UM tema (todos os takes, inclusive os errados) | sim | arquivos originais, sem recompressão (WhatsApp recomprime; preferir Google Drive) |
| Tema/roteiro ou a ideia central | sim | se existir roteiro da skill roteiro-video, enviar |
| B-roll do assunto (peça, painel, oficina) | recomendado | com direito de uso |
| Captura de tela de um Reel do perfil aberto no celular | recomendado | para calibrar as zonas seguras |
| Música | não | só com licença; o padrão é sem música |
| Referências de Reels que vocês gostam/não gostam | opcional | ajuda a calibrar ritmo |

Ideal para o teste: 1–5 min de bruto, um apresentador falando para a câmera, tema explicativo com pelo menos uma ressalva técnica (testa a regra de não cortar ressalvas).

Entrega dos arquivos: Google Drive (o conector está disponível nesta sessão) ou upload direto. Os arquivos ficam fora do repositório.

## 2. Execução
1. **Etapa A (diagnóstico):** check_env → analyze → transcribe (modelo medium; large-v3 se houver termos difíceis) → decupagem → proposta escrita no modelo de `direcao-editorial.md`, com folhas de contato. **Parada para aprovação.**
2. **Etapa B (versão de teste v1):** montar → ajustes editoriais → legendas revisadas → render → QA + revisão visual → entrega com relatório. **Parada para ajustes.**
3. **Etapa C (final):** aplicar ajustes → render → QA completo → entrega.

## 3. Critérios de avaliação (o usuário responde)
- Editorial: gancho forte? ritmo natural (sem soar picotado)? sentido técnico preservado? fecho e CTA?
- Visual: legendas legíveis no celular? destaques no lugar certo? gráficos com cara de DESCOMPLICARRO? algo genérico demais?
- Áudio (com fones): voz natural? ruído incomoda? volume consistente com outros Reels do perfil?
- Técnico: arquivo sobe no Instagram sem perda visível? Nada coberto pela interface?

## 4. Saída do teste
- Ajustes aprovados aplicados em `config/padrao.json` (versão 1.0) e descritos em `padroes-aprovados.md`.
- Lista do que foi rejeitado ("não fazer").
- Decisão sobre o fluxo: manter 3 etapas ou simplificar para os próximos.
- Só então a skill é marcada como concluída.

## Testes técnicos já realizados
- 23/11/2026 — material sintético (voz pt-BR gerada por TTS com falso início, repetição, pausas, ruído e zumbido; vídeo de barras com movimento; B-roll horizontal e imagem). Resultado: pipeline completo funcionando, sincronia 0 ms em todos os segmentos, −14,1 LUFS / −1,9 dBTP, legendas e gráficos dentro das zonas seguras. Corrigidos durante o teste: download do modelo (Xet), decodificação de áudio, falso início não agrupado, falso positivo "tipo de", legendas quebrando no meio da frase, cartela cobrindo a última fala, loudnorm caindo para modo dinâmico, **atraso de 25 ms introduzido pela redução de ruído**, gráficos pequenos demais, sobreposição de zonas seguras invisível.
- 23/11/2026 — **1º material real**: entrevista com o Rogério (dono de um cupê dos anos 70, 38 anos com o carro), 57 s, celular vertical 720×1280 a 60 fps, áudio com estouro e ruído de evento. v1 de teste com 40 s. Novos recursos criados: card de foto, cartela intermediária, rótulo à direita, `montar.py --trechos`, verificação de corte por energia. Fotos do jornal ainda não recebidas (Drive restrito) — cards com imagem provisória.
- 23/11/2026 — entrevista Rogério: **edição aprovada pelo usuário**; final (v3) com as fotos reais do jornal em cards (anúncio do Maverick 76 + data 19/02/1989), cards abaixo dos rostos, selo "Encontro de 27 anos · Maverick Clube de Curitiba". Pendente: decidir se este acabamento vira o padrão v1.0 (`padroes-aprovados.md`).
- 23/11/2026 — entrevista Rogério **v4 final entregue**. Decisões do usuário: sem modelo do carro no rótulo/título; dado de quilometragem ligado ao anúncio ("43 mil km — eram 22 mil no anúncio de 1989"); abertura com a resposta do entrevistado e cartela "O QUE NÃO É ORIGINAL?" aprovadas; sem nota técnica; CTA de seguir ("Siga o DESCOMPLICARRO para continuar recebendo nosso conteúdo").
