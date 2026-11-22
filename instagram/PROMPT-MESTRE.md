# PROMPT MESTRE — Automação Editorial para Instagram (DESCOMPLICARRO)

Cole este texto inteiro como a primeira mensagem de um projeto Claude Code
novo, dedicado exclusivamente à produção de conteúdos para o Instagram do
DESCOMPLICARRO. Você (Claude) não tem acesso a nenhuma conversa anterior —
este documento é autossuficiente. Junto dele vem uma pasta com todo o
código, fontes e exemplos citados aqui; anexe-a ou deixe-a acessível no
repositório deste projeto antes de começar.

**Regra permanente, acima de qualquer pedido futuro:** os dois modelos
abaixo estão **aprovados e validados pelo cliente**. Nunca os redesenhe,
simplifique, "melhore" ou proponha alternativas por conta própria — nem
quando parecer uma boa ideia. Qualquer mudança de modelo só acontece se o
cliente pedir explicitamente, e mesmo assim, confirme o escopo exato antes
de tocar em qualquer arquivo.

---

## 1. O que este projeto faz

O cliente publica uma matéria em `descomplicarro.com.br`, manda o link, e
você gera e entrega:
- um carrossel (PNG, 1080×1350),
- um Stories (PNG, 1080×1920),
- uma legenda sugerida (texto),

prontos para ele baixar e publicar manualmente no Instagram. Você nunca
publica nada sozinho — a publicação é sempre manual, do lado do cliente.

O modelo visual usado (ver seções 2 e 3) é escolhido **automaticamente**,
só pela editoria da matéria — nunca por escolha manual sua ou do cliente.

---

## 2. Identidade visual (valores reais, extraídos do código aprovado)

**Paleta (hex):**
| Nome               | Hex       | Uso |
|---------------------|-----------|-----|
| Grafite             | `#252525` | fundo do CTA e do rodapé (Modelo 1); fundo de TODOS os slides do Modelo 2 |
| Off-white           | `#F4F1EA` | fundo principal do Modelo 1; texto principal sobre grafite |
| Laranja queimado    | `#C65A32` | destaque, categoria, CTA, marca "CARRO", acentos gráficos |

**Tipografia:**
- **Space Grotesk Bold** (`SpaceGrotesk-Bold.ttf`) — títulos, selos de
  categoria ("eyebrows"), marca DESCOMPLICARRO, domínio no CTA, rótulos de
  seção do Modelo 2. É a ÚNICA variação de peso realmente usada (sem
  Regular/Medium/Light deste tipo no sistema).
- **DM Sans Regular** (`DMSans-Regular.ttf`) — todo o corpo de texto
  (parágrafos, deks/subtítulos). Única variação usada.
- Licença: Open Font License (Google Fonts), arquivos `OFL-*.txt`
  incluídos na pasta `codigo/assets/fontes/`. Uso livre, inclusive comercial, desde que
  a licença acompanhe os arquivos de fonte.
- Não existe nenhuma outra fonte ou peso em uso — não introduza
  variações novas.

**Logotipo:** não existe um arquivo de logo (PNG/SVG) em nenhum lugar do
projeto — sinalizando a ausência, como pedido. A "marca" DESCOMPLICARRO é
puramente tipográfica: a palavra escrita em duas cores na mesma fonte
(Space Grotesk Bold) — **"DESCOMPLI" em off-white + "CARRO" em laranja
queimado**, sempre juntas, sempre nessa divisão exata de letras, nunca
"DESCOMPLICA"+"RRO" ou qualquer outro corte. Aparece no rodapé de (quase)
todo slide.

**Dimensões e formatos:**
- Carrossel: 1080 × 1350 px (proporção 4:5), um arquivo PNG por slide.
- Stories: 1080 × 1920 px (proporção 9:16), um arquivo PNG por slide.
- Os dois modelos usam exatamente essas mesmas dimensões.

**Margens e alinhamento:**
- Modelo 1: margem lateral de 48px (carrossel) / 56px (Stories).
- Modelo 2: margem lateral fixa de 64px nos dois formatos.
- Textos de título/corpo sempre alinhados à esquerda (exceto o CTA de
  ambos os modelos, que é centralizado).

**Rodapés e numeração — diferentes por contexto, de propósito:**
- **Carrossel do Modelo 1:** barra grafite de 92px na base de TODO slide,
  inclusive a capa — marca DESCOMPLICARRO à esquerda (44px de margem) +
  numeração "N/total" à direita (44px de margem, off-white bem translúcido).
- **Carrossel do Modelo 2:** sem barra de fundo (o slide inteiro já é
  grafite) — só a marca pequena (14px) a 42px da base + "N/5" à direita,
  mais discreta que o Modelo 1 de propósito.
- **Stories (igual nos dois modelos, porque o Stories É o do Modelo 1):**
  o frame de **capa** termina no convite "LEIA A MATÉRIA COMPLETA" + seta
  ↓ e **não tem nenhuma marca abaixo** — esse espaço vazio é proposital,
  reservado para colar a figurinha de link do Instagram ao postar. Os
  frames de **conteúdo** (não-capa) têm uma marca simples (80px, só o
  nome, sem numeração) depois do mesmo convite + seta.
- Nenhum slide de Stories mostra numeração "N/total" — só o carrossel
  numera.

**Identificação da editoria:** um selo/"eyebrow" em maiúsculas,
letter-spaced (caractere de espaço fino entre as letras, para imitar
letter-spacing em uma fonte que não suporta isso nativamente), sempre em
laranja. No Modelo 1 aparece como selo sobre a foto (canto superior
direito, só na capa) E/OU como selo acima do título. No Modelo 2 aparece
só acima do título da capa.

---

## 3. MODELO 1 — Editorial Fotográfico (Notícias, Reviews, Bastidores)

**Status: aprovado, em uso, publicado. Não alterar.**
Arquivo: `codigo/inc/editorial-render.php`.

### Estrutura do carrossel
1. **Capa** — foto de capa da matéria (cover-fit, cantos com "mira de
   foco" off-white sobre a foto), selo da categoria no canto superior
   direito, eyebrow opcional ("Testamos ao volante" — exclusivo de
   Reviews), título da matéria, subtítulo/resumo.
2. **Conteúdo** — um slide por cada subtítulo (H2) real da matéria, com o
   primeiro parágrafo daquela seção (recortado numa fronteira de frase ou
   palavra, nunca reescrito), até 4 slides. Cada um usa uma foto diferente
   do corpo da matéria, em sequência.
3. **CTA** — fundo grafite (o único slide do Modelo 1 que não é
   off-white), "Leia a matéria completa no site" + "descomplicarro.com.br",
   centralizado.

Total de slides: variável, de 3 (capa + 1 conteúdo + CTA, se a matéria só
tiver 1 H2) até 6 (capa + 4 conteúdo + CTA). Já publicado com até 6
slides.

### Seleção de conteúdo (nunca inventa, nunca resume com IA)
- **Reviews:** se o campo "Destaques do veículo" estiver preenchido
  (fatos reais e verificados, um por linha — ex. "0–100 km/h em 7,2s"),
  um slide por destaque, frase literal. Sem o campo preenchido, cai no
  caminho comum abaixo.
- **Qualquer outra editoria (e Reviews sem destaques):** um slide por H2
  existente no corpo + o primeiro parágrafo daquela seção. Sem H2 nenhum,
  usa o resumo (excerpt) da matéria como único slide de conteúdo.
- Fotos vêm só do pool real da matéria (destacada + imagens do corpo, na
  ordem em que aparecem) — nunca um banco de imagens genérico.

### Stories
Capa + até 3 slides de conteúdo (mesmo critério de seleção acima, máximo
3 em vez de 4). Foto ocupando 68% da altura (62% na capa), painel
off-white com título + texto abaixo, convite "LEIA A MATÉRIA COMPLETA" +
seta em todos os frames.

---

## 4. MODELO 2 — Educativo e Técnico (Técnica, Guias)

**Status: aprovado, na versão final abaixo — ignore qualquer versão
anterior que mencione "composições automáticas por item" (dado/pergunta/
explicação): essa primeira versão foi EXPLICITAMENTE REJEITADA pelo
cliente e não deve ser usada como referência.**
Arquivo: `codigo/inc/editorial-render-modelo2.php`.

### Estrutura fixa do carrossel — sempre exatamente 5 slides, nessa ordem
1. **CAPA** — chamada forte sobre o tema (usa a chamada curada, ou o
   título real da matéria se nenhuma chamada foi escrita). Fundo grafite;
   foto é OPCIONAL e NUNCA aparece por padrão — só se pedido
   explicitamente (campo/flag `usarfoto`), ocupando uma faixa de 30% da
   altura no topo.
2. **ENTENDA** — explicação técnica acessível do assunto.
3. **QUESTIONE** — uma reflexão, dúvida relevante ou quebra de percepção
   equivocada.
4. **DECIDA** — orientação prática que ajuda o leitor a decidir algo.
5. **CTA** — "Quer saber mais sobre esse assunto?" / "Leia a matéria
   completa em" / "descomplicarro.com.br" (laranja, em destaque).

Cada um dos slides 2–4 tem um rótulo de seção fixo no topo (ENTENDA /
QUESTIONE / DECIDA, maiúsculo, letter-spaced, laranja) com uma pequena
régua laranja embaixo, e o corpo do texto centralizado verticalmente no
espaço restante. Uma barra vertical laranja fina ("lombada editorial") ao
lado esquerdo do bloco de texto preenche com intenção gráfica o espaço
entre o rótulo e o rodapé — isso evita que slides com texto mais curto
pareçam vazios.

### Regra de conteúdo — a mais importante deste modelo
O texto de Entenda/Questione/Decida **não é um resumo nem uma cópia de
frases/subtítulos da matéria**. É uma narrativa educativa com começo,
desenvolvimento e conclusão, construída a partir do assunto da matéria
como ponto de partida — mas usando exclusivamente informações
tecnicamente sustentadas pelo texto real da matéria. **Nunca invente
dado técnico, número, estatística ou recomendação.** Se a matéria não
tiver informação suficiente para um dos três blocos, é melhor escrever
um texto mais simples e honesto do que inventar.

Dentro do texto de qualquer slide, marcar `**um trecho**` com asteriscos
duplos faz esse trecho (uma ou mais palavras) aparecer em laranja no
slide — use com moderação, só nos termos realmente importantes (um dado,
uma condição, uma ação). O resto do texto permanece off-white.

### Identidade visual deste modelo (por que é diferente do Modelo 1 de propósito)
Fundo grafite em TODOS os 5 slides (não só no CTA, como no Modelo 1) —
essa é a diferença visual mais imediata entre os dois modelos.
Composição 100% tipográfica em primeiro plano; fotografia nunca é
obrigatória, e a prioridade é sempre a composição do texto.

### Stories
**O Stories do Modelo 2 é IGUAL ao Stories do Modelo 1** — por pedido
explícito do cliente. Usa exatamente as mesmas funções do Modelo 1 (ver
seção 3), inclusive para matérias de Técnica/Guias. Não existe um
"Stories do Modelo 2" separado — foi testado e removido depois de o
cliente pedir essa unificação.

---

## 5. Legenda sugerida

Sempre: título da matéria, subtítulo/resumo, depois o conteúdo (ver
abaixo), depois "Leia a matéria completa — link na bio.", depois
hashtags.

- **Matérias no Modelo 2 (Técnica/Guias):** a legenda resume o conteúdo
  REAL dos blocos Entenda/Questione/Decida (prefixados "Entenda:",
  "Questione:", "Decida:", com os marcadores `**` removidos) — nunca os
  subtítulos genéricos da matéria.
- **Matérias no Modelo 1 (as outras editorias):** a legenda lista até 4
  linhas "título — texto" dos itens de conteúdo usados no carrossel.
- **Hashtags:** reaproveitam as tags reais que a matéria já tem no
  WordPress. Sem tags cadastradas, usa palavras relevantes do próprio
  título (nunca termos inventados ou genéricos de mercado).

---

## 6. Funcionamento técnico

### Arquivos (todos em `codigo/`, dentro da pasta deste pacote)
- `inc/editorial-render.php` — Modelo 1 (renderização GD pura, sem
  nenhuma chamada ao WordPress).
- `inc/editorial-render-modelo2.php` — Modelo 2 (idem).
- `inc/editorial-selecao.php` — helpers de seleção/recorte de texto
  reaproveitados pelos dois scripts abaixo.
- `inc/editorial-gerador.php` — decide o modelo pela editoria
  (`dco_editorial_modelo_visual_da_categoria()`) e monta as specs de
  cada slide.
- `inc/editorial-legenda.php` — monta a legenda.
- `inc/editorial-campos.php` + `inc/admin-fields.php` — **opcionais**,
  só fazem sentido se você optar pela alternativa com WordPress (abaixo).
- **`gerar-por-link-sem-wp.php`** — **USE ESTE.** Não precisa de
  WordPress: baixa o HTML da matéria, baixa as imagens direto para
  arquivos locais temporários, monta o mesmo "pacote" de conteúdo que o
  WordPress monta, e chama as mesmas funções de render/specs/legenda
  acima sem alterar nelas uma linha. Já testado lado a lado com a versão
  WordPress: produz os mesmos arquivos, pixel a pixel.
- `gerar-por-link.php` + `descomplicarro-instagram.php` — alternativa
  que precisa de um WordPress local (ver seção "Alternativa" abaixo) —
  só vale a pena se um dia você for reativar a automação completa
  (Drive/e-mail/painel, ver seção 8), que exige uma matéria de verdade
  no WordPress.

### Rodando sem WordPress (caminho recomendado)
```
php codigo/gerar-por-link-sem-wp.php "<link-da-materia>" "<pasta-de-saida>" [flags]
```
Só precisa de PHP com a extensão GD (padrão em qualquer instalação PHP).
Os arquivos de fontes já estão em `codigo/assets/fontes/` — não precisa
mover nada, só rodar o comando acima a partir de onde quer que você
coloque a pasta `codigo/`.

### Alternativa: rodando com um WordPress local
Só use isto se precisar da automação completa do WordPress (seção 8).
Copie `codigo/` inteira para `wp-content/plugins/descomplicarro-instagram/`,
ative o plugin no wp-admin, crie as 5 categorias com os slugs exatos
`reviews`/`guias`/`tecnica`/`bastidores`/`noticias`, e rode:
```
DCO_WP_LOAD=/caminho/para/wp-load.php php codigo/gerar-por-link.php "<link>" "<pasta-de-saida>" [flags]
```

### Como identificar a editoria e escolher o modelo
O script lê o texto dentro de `<span class="materia-editoria">` na
página e mapeia pelo nome: `Reviews→reviews`, `Guias→guias`,
`Técnica→tecnica`, `Bastidores→bastidores`, `Notícias→noticias` (qualquer
editoria não reconhecida cai em `noticias`, Modelo 1). No caminho sem
WordPress, esse slug alimenta `dco_editorial_modelo_visual_da_categoria()`
diretamente; na alternativa com WordPress, precisa bater com o slug real
de uma categoria do WordPress local (ver `README.md`).

### Como extrair textos e imagens
`gerar-por-link-sem-wp.php` baixa o HTML da URL informada e lê, via
`DOMDocument`/`DOMXPath`, os seguintes seletores (dependem da estrutura
HTML real do site — se o tema do site mudar essas classes, a extração
quebra silenciosamente, retornando "não encontrei o título"):
- Título: `h1.materia-titulo`
- Subtítulo: `p.materia-subtitulo`
- Editoria: `span.materia-editoria`
- Tags: `a.tag-chip`
- Imagem de capa: `figure.materia-imagem-principal img`
- Corpo: `div.materia-corpo` (preserva as tags `<h2>`, `<p>`, `<img>`)

As imagens (capa + do corpo) são baixadas direto para arquivos locais
temporários (sem biblioteca de mídia nenhuma) — e apagadas ao final,
depois que os PNGs já foram gerados.

### Comando (e flags — as mesmas nos dois scripts)
```
php gerar-por-link-sem-wp.php "<link-da-materia>" "<pasta-de-saida>" [flags opcionais]
```

Flags (Modelo 1 — Reviews):
```
--destaques="0 a 100 em 7,2s|943 litros de caçamba|Consumo 8,3/13,2 km/l"
```

Flags (Modelo 2 — Técnica/Guias; cada uma aceita `**trecho**` para
destacar em laranja):
```
--chamada="chamada forte da capa"     (opcional — sem isso, usa o título real)
--entenda="explicação técnica acessível, com **destaques** pontuais"
--questione="reflexão, dúvida ou quebra de percepção relacionada ao tema"
--decida="orientação prática pra decidir algo a respeito"
--usarfoto=1                           (opcional — Modelo 2 não usa foto por padrão)
```

Sem as flags do Modelo 2 preenchidas, a matéria ainda gera um carrossel de
5 slides, mas os blocos Entenda/Questione/Decida saem vazios — **sempre
curar esse conteúdo manualmente a partir da matéria real antes de
entregar**, lendo a matéria e escrevendo os três blocos você mesmo (nunca
inventando dado técnico).

**Atenção:** o analisador dessas flags não aceita hífen no nome
(`--usar-foto` falha silenciosamente) — por isso é `usarfoto`, sem hífen.

### Onde os arquivos são salvos
Dentro de `<pasta-de-saida>/carrossel/*.png`, `<pasta-de-saida>/stories/*.png`
e `<pasta-de-saida>/legenda.txt` (a legenda já inclui o link da matéria no
final).

### Processo sugerido a cada pedido
1. Recebe o link da matéria.
2. Lê a matéria real (abra o link) para entender editoria e conteúdo.
3. Se for Técnica/Guias: escreva Entenda/Questione/Decida você mesmo, a
   partir do que a matéria realmente diz — nunca invente.
4. Roda `gerar-por-link-sem-wp.php` com as flags apropriadas.
5. **Confere os arquivos gerados visualmente antes de entregar** — abra
   os PNGs, cheque espaço vazio, texto cortado, fotos em branco.
6. Entrega carrossel + Stories + legenda prontos para download.

### Problemas comuns (sem alterar os modelos aprovados)
- **Fotos em branco:** o HTML importado ainda aponta para a URL remota
  original em vez da cópia local — reescreva o `post_content` antes de
  gerar (o script já faz isso automaticamente; se portar para outro
  ambiente, mantenha essa etapa).
- **Texto de selo colado ("MATÉRIACOMPLETA" em vez de "MATÉRIA
  COMPLETA"):** nunca passe um texto já "letter-spaced" (com espaços
  finos U+2009) pela função genérica de quebra de linha por palavra —
  use sempre as funções dedicadas de desenho de eyebrow centralizado, que
  já existem no código.
- **Sem nenhuma saída no terminal e nenhum arquivo gerado, mas sem
  erro:** quase sempre é a guarda `if (!defined('ABSPATH')) exit;` que
  cada arquivo de `inc/` tem no topo — confira se `gerar-por-link-sem-wp.php`
  está mesmo definindo essa constante antes de incluir os arquivos.
- **(Só na alternativa com WordPress) Script parado no meio:** confira
  se o MySQL/MariaDB e o `wp-load.php` do WordPress local estão
  realmente no ar antes de rodar.
- **Editoria caiu no modelo errado:** no caminho sem WordPress, confira
  o texto que está sendo raspado em `<span class="materia-editoria">`
  contra o `$mapa_editoria_slug` do script — tem que bater exatamente
  (`Reviews`, `Guias`, `Técnica`, `Bastidores`, `Notícias`). Na
  alternativa com WordPress, confira o slug da categoria da matéria
  (`reviews`/`guias`/`tecnica`/`bastidores`/`noticias`).

---

## 7. Regras editoriais permanentes

- Nunca inventar informações técnicas, números ou especificações — só
  usar o que a matéria realmente diz.
- Nunca misturar os dois modelos (a escolha é sempre automática, por
  editoria).
- Nunca criar slides com informações desconexas ou aleatórias — todo
  carrossel precisa ter começo, desenvolvimento e conclusão.
- Nunca transformar Técnica/Guias num resumo da matéria — é uma narrativa
  educativa própria, construída a partir do assunto.
- Preservar a identidade editorial do DESCOMPLICARRO (grafite/off-white/
  laranja queimado, Space Grotesk + DM Sans) em tudo que for gerado.
- Produzir conteúdo que desperte interesse em acessar o site — nunca
  entregar a matéria inteira no carrossel/Stories.
- A publicação no Instagram é sempre manual, do lado do cliente — este
  projeto só entrega arquivos.
- Nunca alterar os modelos aprovados sem pedido explícito e confirmado.

---

## 8. O que NÃO está incluído neste pacote (sinalizando, não inventando)

- **A automação completa via WordPress** (gatilho automático no publish
  real → upload para Google Drive → e-mail via Brevo → painel
  administrativo de status) existe no projeto original
  (`editorial-status-db.php`, `editorial-drive.php`, `editorial-email.php`,
  `editorial-cron.php`, `editorial-painel.php`), mas **não foi trazida
  para cá** — o fluxo atual do cliente é 100% sob demanda pelo link, sem
  Drive/Brevo/e-mail, e essa automação dependeria de credenciais que não
  fazem sentido neste projeto independente. Se um dia for necessária,
  precisa ser buscada no projeto original.
- **Nenhum arquivo de logotipo** (imagem) existe — a marca é só
  tipográfica, como descrito na seção 2.
- **Nenhuma suíte de testes automatizados** existe para este gerador —
  toda validação até aqui foi visual/manual, slide por slide.
- A extração de conteúdo depende da estrutura HTML atual do site
  (seção 6) — se o tema do site oficial mudar essas classes CSS, a
  extração para de funcionar e isso precisará ser ajustado aqui.

---

## Exemplos de referência (pasta `exemplos-aprovados/`)

Duas saídas reais, geradas com este exato código antes de fechar o
pacote — use como referência visual de "como deve ficar", não como texto
para copiar:
- `modelo1-bastidores-toyota/` — Modelo 1, matéria real de Bastidores.
- `modelo2-guias-altitude/` — Modelo 2, matéria real de Guias (a mesma
  "perda de potência em altitude" usada na aprovação original).
