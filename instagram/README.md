# Pacote de transferência — Automação Editorial para Instagram (DESCOMPLICARRO)

Este pacote contém tudo que é necessário para reproduzir, em um projeto
Claude Code **independente**, exatamente os dois modelos de carrossel/Stories
já aprovados e validados pelo cliente DESCOMPLICARRO — sem recriar, sem
redesenhar, sem inventar nada. **Não precisa de WordPress** para rodar —
ver seção abaixo.

## O que tem aqui

```
pacote-transferencia-instagram/
├── PROMPT-MESTRE.md                    ← cole isto na primeira mensagem do novo projeto
├── README.md                           ← este arquivo
├── codigo/
│   ├── gerar-por-link-sem-wp.php       ← USE ESTE — não precisa de WordPress
│   ├── gerar-por-link.php              ← alternativa — precisa de um WordPress local (ver nota)
│   ├── descomplicarro-instagram.php    ← só usado se optar pela alternativa com WordPress
│   ├── assets/fontes/
│   │   ├── SpaceGrotesk-Bold.ttf
│   │   ├── DMSans-Regular.ttf
│   │   ├── OFL-SpaceGrotesk.txt         ← licença (Open Font License)
│   │   └── OFL-DMSans.txt               ← licença (Open Font License)
│   └── inc/
│       ├── editorial-render.php        ← MODELO 1, aprovado, nunca alterar
│       ├── editorial-render-modelo2.php← MODELO 2, aprovado, nunca alterar
│       ├── editorial-selecao.php       ← seleção de conteúdo real da matéria
│       ├── editorial-gerador.php       ← escolhe o modelo por editoria e gera os PNGs
│       ├── editorial-legenda.php       ← monta a legenda sugerida
│       ├── editorial-campos.php        ← (opcional, só usado com WordPress)
│       └── admin-fields.php            ← (opcional, só usado com WordPress)
├── exemplos-aprovados/
│   ├── modelo1-bastidores-toyota/       ← saída real, gerada agora, do Modelo 1 (editoria Bastidores)
│   └── modelo2-guias-altitude/          ← saída real, gerada agora, do Modelo 2 (editoria Guias)
└── docs/
    ├── plugin-automacao-editorial-README.md  ← documentação original dos dois modelos
    └── sob-demanda-README.md                  ← documentação original dos dois geradores
```

**Todos os arquivos dentro de `codigo/inc/` são cópias byte a byte dos
arquivos já aprovados** do plugin `descomplicarro-oportunidades` — nenhuma
linha foi alterada. `editorial-render.php` e `editorial-render-modelo2.php`
(os dois modelos) são GD puro — nunca chamaram nenhuma função do
WordPress; quem precisava do WordPress era só a "cola" em volta deles.

## Como gerar o primeiro conteúdo (sem WordPress)

```
php codigo/gerar-por-link-sem-wp.php "https://descomplicarro.com.br/<slug-da-materia>" "<pasta-de-saida>"
```

Pronto — não precisa instalar nada além do PHP com a extensão GD (padrão
em qualquer instalação de PHP). Isso foi testado de verdade, exatamente
com os arquivos desta pasta: gerou os 6 slides do Modelo 1 e os 5 do
Modelo 2, pixel a pixel iguais à versão que usa WordPress.

Para Técnica/Guias (Modelo 2), normalmente vale a pena curar manualmente
os três blocos Entenda/Questione/Decida a partir do conteúdo real da
matéria:

```
php codigo/gerar-por-link-sem-wp.php "<link>" "<pasta-de-saida>" \
  --entenda="explicação técnica acessível, com **destaques** pontuais" \
  --questione="reflexão ou dúvida relacionada ao tema" \
  --decida="orientação prática pra decidir algo a respeito"
```

Ver `PROMPT-MESTRE.md` (seção 6) e `docs/sob-demanda-README.md` para a
sintaxe completa de todas as flags, dos dois modelos.

Os arquivos finais saem em `<pasta-de-saida>/carrossel/`,
`<pasta-de-saida>/stories/` e `<pasta-de-saida>/legenda.txt`.

### Por que isso funciona sem WordPress

O código em `codigo/inc/` foi escrito originalmente para rodar dentro de
um plugin WordPress (reaproveitando `wp_insert_post`, `get_post_meta`
etc. para montar o conteúdo de uma matéria). Os dois modelos visuais em
si (`editorial-render.php`/`editorial-render-modelo2.php`) nunca
dependeram disso — são só GD (a extensão de imagens do PHP). O script
`gerar-por-link-sem-wp.php` monta o mesmo pacote de conteúdo direto do
HTML raspado do link (sem post, sem banco de dados, sem biblioteca de
mídia) e chama essas mesmas funções de renderização sem alterar uma
linha delas. A única parte "falsa" é definir a constante `ABSPATH` (um
valor qualquer) antes de incluir os arquivos — eles têm uma guarda de
segurança padrão de plugin WordPress (`if (!defined('ABSPATH')) exit;`)
que só verifica se essa constante existe, não se o WordPress real está
rodando.

## Alternativa: gerar com um WordPress local (`gerar-por-link.php`)

Só faz sentido se, um dia, este projeto precisar reativar a automação
completa (Google Drive + e-mail + painel — ver `PROMPT-MESTRE.md`, seção
8), que exige uma matéria de verdade no WordPress para o `wp_cron`
disparar. Para isso:

1. Instale um WordPress local qualquer.
2. Copie `codigo/` inteira para `wp-content/plugins/descomplicarro-instagram/`.
3. Ative o plugin "DESCOMPLICARRO — Automação Editorial para Instagram"
   no wp-admin.
4. Crie 5 categorias de post com estes SLUGS exatos (o nome de exibição
   pode ser o que quiser, mas o slug tem que ser exatamente este, sem
   acento): `reviews`, `guias`, `tecnica`, `bastidores`, `noticias`.
5. Rode:
   ```
   DCO_WP_LOAD=/caminho/para/wp-load.php \
   php codigo/gerar-por-link.php "<link>" "<pasta-de-saida>"
   ```

## O que fazer com o PROMPT-MESTRE.md

Abra um projeto novo no Claude Code, cole o conteúdo de `PROMPT-MESTRE.md`
como a primeira mensagem, e anexe (ou deixe acessível no repositório desse
projeto) esta pasta inteira. O prompt já contém tudo que o Claude precisa
saber — identidade visual, os dois modelos, as regras editoriais e o
funcionamento técnico — sem depender de nenhuma conversa anterior.
