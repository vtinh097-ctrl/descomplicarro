# Modelo visual aprovado — Carrossel e Stories para matérias Review

> Aprovado pelo cliente em 22/11/2026, validado com um exemplo manual real
> (matéria "Avaliação: Ford Maverick Black 2026"). Isto é só a **referência
> visual** da Etapa 4 (Automação Editorial para Instagram) — nenhuma
> automação foi implementada ainda. A implementação (wp_cron, geração
> automática de conteúdo, upload ao Google Drive, e-mail) continua
> pendente de aprovação separada, como registrado no documento de plano.

`gerar-exemplo.py` é o script Python que gerou o exemplo aprovado (usa
Playwright/Chromium para fotografar templates HTML/CSS — a mesma técnica já
usada no Checklist em PDF). Serve como referência de código para quando a
automação da Etapa 4 for construída: os templates HTML/CSS dele devem ser
reaproveitados, não recriados do zero. `exemplo-referencia/` guarda 3 PNGs
de exemplo (capa do carrossel, CTA final, capa do Stories) só para
conferência visual rápida — o conjunto completo (8 slides de carrossel +
Stories) foi entregue ao cliente pelo chat, não está versionado aqui.

## Especificação aprovada

### Identidade visual (igual à do tema/site)
- Grafite `#252525`, off-white `#F4F1EA`, laranja `#C65A32`.
- Fonte de títulos/destaques: `Space Grotesk` (bold). Fonte de corpo:
  `DM Sans`.
- Marca no rodapé: **"DESCOMPLI" em off-white + "CARRO" em laranja**
  (nunca "DESCOMPLICA" + "RRO" — erro já cometido e corrigido uma vez,
  cuidado para não repetir).

### Carrossel (1080×1350)
- Proporção **62% foto / 38% área off-white com texto** em todos os
  slides, incluindo a capa.
- **Todo slide tem o rodapé grafite** com a marca + numeração `N/total`
  (ex.: `1/8`) — inclusive a capa. Sem o rodapé, sobra espaço vazio entre
  a foto e o texto.
- Capa: imagem de destaque da matéria + selo laranja "Testamos ao
  volante" (só em Reviews — confirma que a redação dirigiu o veículo,
  não é texto de divulgação) + título + subtítulo.
- Slides de conteúdo: um por destaque, cada um com uma foto real da
  própria matéria (nunca banco de imagens) + categoria (eyebrow laranja)
  + título curto + 1 frase de apoio.
- Último slide: fundo grafite, chamada para ler a matéria completa no
  site + domínio em laranja.
- Para Reviews especificamente, como ainda não existe o campo editorial
  "Destaques do veículo" (proposto na arquitetura da Etapa 4), os
  destaques deste exemplo foram extraídos manualmente do próprio texto
  publicado — nenhuma informação foi inventada, só reorganizada.

### Stories (1080×1920)
- Proporção **66% foto / 34% área off-white com texto** (10% maior que o
  carrossel, a pedido do cliente).
- **Sem o texto "arraste para ver mais"** (isso é afford­ance de
  carrossel, não existe no Stories).
- Selo "Testamos ao volante" + título + subtítulo, igual ao carrossel.
- Chamada **"LEIA A MATÉRIA COMPLETA"** em laranja com uma seta para
  baixo (`↓`), posicionada logo abaixo do texto (não no rodapé).
- **Nenhuma caixa/contorno** demarcando onde vai o link — o espaço abaixo
  da chamada fica simplesmente livre, para não conflitar com o sticker de
  link do Instagram caso ele seja menor que uma caixa desenhada.

### Legenda do carrossel (texto do post, não é slide)
- Abre confirmando que a equipe testou o veículo (ex.: "Testamos a Ford
  Maverick Black 2026 ao volante").
- Corpo com os mesmos destaques reais dos slides.
- Fecha com chamada para o link na bio.
- **Hashtags = as próprias tags que a matéria já tem no WordPress**,
  convertidas para o formato de hashtag (sem acentos/espaços) — nunca
  hashtags inventadas. Esse é o mesmo princípio de "nunca inventar
  conteúdo" aplicado à legenda.
