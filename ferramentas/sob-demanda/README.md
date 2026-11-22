# Geração sob demanda — a partir do link da matéria

Decisão do cliente (22/11): abandonar a automação completa (WordPress →
Google Drive → e-mail) como fluxo padrão. O fluxo atual é **sob demanda**:
o cliente publica a matéria no site e envia o link; a geração dos
materiais (carrossel + Stories + legenda) acontece aqui, manualmente, a
partir desse link — sem Drive, sem Brevo, sem notificação por e-mail.

A automação completa (`inc/editorial-cron.php`, `editorial-drive.php`,
`editorial-email.php`, `editorial-painel.php` no plugin) **continua
preservada e intacta** no plugin, para uma eventual ativação futura — só
não é usada neste fluxo.

## Como usar (`gerar-por-link.php`)

```
php ferramentas/sob-demanda/gerar-por-link.php "<link-da-materia>" "<pasta-de-saida>"
```

Opcional, para Reviews (destaques do veículo) ou Técnica/Guias (ângulo
educativo), quando o cliente já mandar esse conteúdo junto do link:

```
--destaques="0 a 100 em 7,2s|943 litros de caçamba|Consumo 8,3/13,2 km/l"
--educativo="Mito: óleo mais grosso protege mais|Curiosidade real X"
--gancho="3 mitos sobre troca de óleo que ainda enganam motorista"
```

Sem essas opções, a matéria usa o caminho comum (subtítulos H2 + parágrafo)
mesmo sendo Reviews ou Técnica/Guias — o que é seguro e nunca inventa
informação, só não aproveita o ângulo editorial mais rico.

## O que o script faz, por baixo

Reaproveita INTEGRALMENTE o gerador já aprovado do plugin
(`inc/editorial-selecao.php`, `inc/editorial-render.php`,
`inc/editorial-gerador.php`, `inc/editorial-legenda.php`) — nenhum layout
foi duplicado ou reconstruído. A única coisa nova é a ponte: buscar o HTML
da matéria pelo link, montar uma matéria-espelho **local e nunca
publicada** (status `draft`, então a automação de publicação do plugin
nunca dispara) só para poder reaproveitar essas funções (que foram
escritas para operar sobre uma matéria do WordPress), gerar os arquivos, e
apagar a matéria-espelho e as imagens importadas no final — não acumula
nada no banco local.

Precisa do WordPress local carregado (variável `DCO_WP_LOAD`, default
`/home/user/wp-local/wp-load.php`).

## Processo de cada pedido (lado do Claude)

1. Recebe o link da matéria.
2. Roda `gerar-por-link.php` com o link.
3. Confere os arquivos gerados (visualmente, antes de entregar).
4. Para Reviews ou Técnica/Guias sem campos preenchidos: se o conteúdo da
   matéria permitir, considerar extrair manualmente 3–6 destaques reais
   (nunca inventados) e rodar de novo com `--destaques`/`--educativo`,
   como já demonstrado nas entregas anteriores (Ford Maverick, guia de
   óleo de teste).
5. Entrega os arquivos (carrossel, Stories, legenda) prontos para baixar.
