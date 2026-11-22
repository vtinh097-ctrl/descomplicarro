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

O modelo visual (1 ou 2) é escolhido automaticamente pela editoria da
matéria — ver `wordpress-plugins/descomplicarro-oportunidades/README.md`
para a tabela completa e o que cada modelo exige.

## Duas versões do gerador

- **`gerar-por-link-sem-wp.php`** (recomendado para um projeto
  independente deste repositório) — **não precisa de WordPress
  nenhum**. Monta o mesmo "pacote" de conteúdo direto do HTML raspado do
  link e chama as mesmas funções aprovadas de renderização/specs/legenda
  sem nenhuma alteração nelas. Testado lado a lado com a versão abaixo:
  produz os mesmos arquivos, pixel a pixel.
- **`gerar-por-link.php`** — precisa de um WordPress local (variável
  `DCO_WP_LOAD`), porque monta uma matéria-espelho de verdade antes de
  gerar. Mantido porque é o caminho mais direto se este projeto algum
  dia reativar a automação completa do WordPress (que exige um post de
  verdade para o `wp_cron` disparar).

As duas aceitam exatamente os mesmos argumentos e as mesmas flags —
tudo abaixo vale para qualquer uma das duas.

## Como usar

```
php ferramentas/sob-demanda/gerar-por-link.php "<link-da-materia>" "<pasta-de-saida>"
```

Opcional, para Reviews (Modelo 1 — destaques do veículo):

```
--destaques="0 a 100 em 7,2s|943 litros de caçamba|Consumo 8,3/13,2 km/l"
```

Opcional, para Técnica/Guias (Modelo 2 — estrutura fixa "Entenda.
Questione. Decida.", sempre 5 slides no carrossel). Cada bloco é escrito
a partir do conteúdo real da matéria, nunca inventado; `**trecho**`
destaca esse trecho em laranja no slide:

```
--chamada="chamada forte da capa"        (opcional — sem isso, usa o título real)
--entenda="explicação técnica acessível, com **destaques** pontuais"
--questione="reflexão, dúvida ou quebra de percepção relacionada ao tema"
--decida="orientação prática pra decidir algo a respeito"
--usarfoto=1                              (opcional — Modelo 2 não usa foto por padrão)
```

Sem essas opções, a matéria usa o caminho comum (subtítulos H2 + parágrafo)
mesmo sendo Reviews ou Técnica/Guias — o que é seguro e nunca inventa
informação, só não aproveita o ângulo editorial mais rico. (O Stories é
sempre igual ao Modelo 1, qualquer que seja a editoria, então não tem
flag própria.)

## O que cada script faz, por baixo

Os dois reaproveitam INTEGRALMENTE o gerador já aprovado do plugin
(`inc/editorial-selecao.php`, `inc/editorial-render.php`,
`inc/editorial-render-modelo2.php`, `inc/editorial-gerador.php`,
`inc/editorial-legenda.php`) — nenhum layout foi duplicado ou
reconstruído. Só muda a "ponte" entre o HTML raspado do link e essas
funções:

- **`gerar-por-link.php`**: monta uma matéria-espelho **local e nunca
  publicada** (status `draft`, então a automação de publicação do plugin
  nunca dispara) para poder chamar `dco_editorial_montar_pacote($post_id)`
  e `dco_editorial_gerar_arquivos($post_id, ...)` sem alterá-las, gera os
  arquivos, e apaga a matéria-espelho e as imagens importadas no final —
  não acumula nada no banco local. Precisa do WordPress local carregado
  (variável `DCO_WP_LOAD`, default `/home/user/wp-local/wp-load.php`).
- **`gerar-por-link-sem-wp.php`**: monta o mesmo array `$pacote` (mesmas
  chaves, mesmo formato) diretamente a partir do HTML raspado — sem post,
  sem banco, sem biblioteca de mídia — e chama as funções de specs/
  renderização/legenda uma por uma (sem passar por
  `dco_editorial_gerar_arquivos()`, que espera um `$post_id`). Define
  `ABSPATH` como uma constante qualquer só para satisfazer a guarda de
  segurança padrão do WordPress que cada arquivo de `inc/` tem no topo
  (`if (!defined('ABSPATH')) exit;`) — não é o WordPress de verdade, só
  o suficiente para esses arquivos carregarem fora dele. Baixa as
  imagens direto para uma pasta temporária do sistema e apaga ao final.

## Processo de cada pedido (lado do Claude)

1. Recebe o link da matéria.
2. Roda `gerar-por-link.php` com o link.
3. Confere os arquivos gerados (visualmente, antes de entregar).
4. Para Reviews ou Técnica/Guias sem campos preenchidos: se o conteúdo da
   matéria permitir, considerar curar manualmente o conteúdo real
   (destaques reais em Reviews; Entenda/Questione/Decida em Técnica/Guias
   — nunca inventados) e rodar de novo com `--destaques` ou
   `--chamada`/`--entenda`/`--questione`/`--decida`, como já demonstrado
   nas entregas anteriores (Ford Maverick, guia de altitude).
5. Entrega os arquivos (carrossel, Stories, legenda) prontos para baixar.
