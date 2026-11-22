# Automação Editorial para Instagram — DESCOMPLICARRO

Documentação permanente dos dois modelos visuais de carrossel/Stories e
de como gerar os materiais de cada matéria. Isto existe pra que qualquer
sessão futura (Claude Code ou humana) entenda o sistema sem precisar
reconstruir o histórico de decisões.

## Os dois modelos

A escolha do modelo é **automática, só pela editoria da matéria** — nunca
manual. Ver `dco_editorial_modelo_visual_da_categoria()` em
`inc/editorial-gerador.php`.

| Editoria                          | Carrossel | Stories  |
|------------------------------------|-----------|----------|
| Notícias, Reviews, Bastidores (e qualquer outra não listada) | **Modelo 1** | Modelo 1 |
| Técnica, Guias                     | **Modelo 2** | Modelo 1 |

### MODELO 1 — Editorial Fotográfico

Arquivo: `inc/editorial-render.php` (`dco_editorial_renderizar_slide()`).
**Aprovado e congelado — nunca deve ser alterado, refatorado ou
redesenhado.** Fotografia dominante, fundo off-white, CTA em grafite,
marca "DESCOMPLI" (off-white) + "CARRO" (laranja) no rodapé de todo slide
do carrossel (inclusive a capa), paginação `N/total`. Conteúdo vem de
`inc/editorial-selecao.php` (`itens` por H2 da matéria, ou "Destaques do
veículo" nas Reviews).

### MODELO 2 — Educativo e Técnico

Arquivo: `inc/editorial-render-modelo2.php`
(`dco_editorial_renderizar_slide_modelo2()`). Usado só no **carrossel**
de Técnica/Guias — visualmente distinto do Modelo 1 de propósito (fundo
grafite em todos os slides, tipografia off-white/laranja queimado,
composição tipográfica em primeiro plano, foto opcional e nunca
obrigatória).

Estrutura **fixa**, sempre 5 slides nessa ordem — nunca escolhida
automaticamente por item/matéria:

1. **Capa** — chamada forte sobre o tema.
2. **Entenda** — explicação técnica acessível.
3. **Questione** — reflexão, dúvida relevante ou quebra de percepção.
4. **Decida** — orientação prática pra decidir algo.
5. **CTA** — "Quer saber mais sobre esse assunto? / Leia a matéria
   completa em / descomplicarro.com.br".

O texto de Entenda/Questione/Decida vem de conteúdo real da matéria —
**nunca inventado** — preenchido via metabox da matéria
(`_dco_m2_chamada`/`_dco_m2_entenda`/`_dco_m2_questione`/`_dco_m2_decida`/
`_dco_m2_usar_foto`, ver `inc/editorial-campos.php`) ou pelas flags
`--chamada`/`--entenda`/`--questione`/`--decida`/`--usarfoto` do gerador
por link (ver `ferramentas/sob-demanda/README.md`). Dentro do texto,
`**trecho**` destaca esse trecho em laranja no slide.

**O Stories do Modelo 2 é igual ao do Modelo 1** (por pedido explícito do
cliente) — mesmas funções `dco_editorial_specs_stories()`/
`dco_editorial_renderizar_slide()`, mesmo em Técnica/Guias. Não existe
Stories "do Modelo 2": o dispatch em `dco_editorial_gerar_arquivos()`
(`inc/editorial-gerador.php`) só troca de modelo visual no carrossel.

## A automação completa (preservada, hoje inativa)

`inc/editorial-status-db.php`, `editorial-drive.php`, `editorial-email.php`,
`editorial-cron.php`, `editorial-painel.php` implementam a automação
completa (wp_cron no publish → Google Drive → e-mail via Brevo → painel
administrativo). Está pronta, testada e **preservada intacta** para uma
ativação futura, mas não é o fluxo usado hoje.

## Fluxo atual: geração sob demanda pelo link

O cliente publica a matéria no site e manda o link; os materiais são
gerados aqui a partir dele, sem precisar de Drive/Brevo/e-mail. Ver
`ferramentas/sob-demanda/README.md` para o passo a passo completo e as
flags disponíveis.
