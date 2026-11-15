---
name: materia-em-foco
description: Escreve matérias (texto longo) para a seção Em Foco do site DESCOMPLICARRO, já na editoria certa dentre as 5 oficiais — Notícias (fato do setor com contexto/análise), Reviews (avaliação de veículo/produto/tecnologia), Guias (orientação prática pro motorista/consumidor), Técnica (aprofundamento pra mecânico/oficina) e Bastidores (histórias, eventos, conteúdo institucional/de parceria) — e na estrutura real do template do site (título, linha fina, corpo em H2/H3, créditos, tags). Use sempre que o usuário pedir pra escrever uma matéria, artigo, texto pro site, notícia, review escrito, guia, conteúdo técnico pra oficina ou cobertura institucional pro Em Foco — mesmo sem usar essas palavras (ex.: "preciso escrever sobre esse recall pro site", "monta um texto explicando isso pra postar no Em Foco", "escreve a matéria do evento da Borg pro Bastidores"). Para roteiro de vídeo ou carrossel sobre o mesmo assunto, use roteiro-video ou carrossel-estatico/carrossel-animado em vez desta.
---

# Matéria Em Foco — DESCOMPLICARRO

Este skill escreve matérias pra seção Em Foco do site, encaixadas na estrutura real do template (`em-foco/noticias/materia-modelo/index.html`) — não um texto solto que alguém vai ter que reformatar depois.

## Base de marca (não negociável, vale para todo formato)

**Essência:** complexidade → compreensão → autonomia → decisão. A matéria existe pra alguém sair sabendo mais do que entrou, nunca só informado "que algo aconteceu".

**Tom:** editorial e avaliativo/crítico — a matéria questiona, não só relata ou elogia. Linguagem acessível, nunca condescendente nem cheia de jargão não explicado. Nunca soa como release de assessoria reescrito, nem como post de blog genérico de carro.

**Conteúdo em parceria:** quando houver relação comercial com a marca citada, isso é sempre declarado de forma visível (campo `materia-parceria`, já existe no template) — nunca omitido ou escondido no fim do texto. Isso não muda o tom crítico da matéria.

**Cores/tipografia em elementos visuais da matéria** (imagem de capa, gráficos internos, se houver): grafite `#252525`, off-white `#F4F1EA`, laranja queimado `#C65A32` como destaque pontual; Space Grotesk em títulos, DM Sans no corpo — mesma identidade do resto do site.

## Esqueleto retórico — método FALAR

Toda matéria segue o método FALAR, de Fabiana Bertotti, mapeado direto pros campos do template:

- **F — Fagulha:** o primeiro parágrafo do corpo. Nunca abre com "No dia X, a empresa Y anunciou..." — abre com o que torna aquilo relevante pra quem lê.
- **A — Afirmação:** a **linha fina/subtítulo** (campo `materia-subtitulo`, logo abaixo do H1) é literalmente o campo certo pra isso — uma frase que resume a ideia central da matéria. Reforce a mesma ideia no fechamento do primeiro parágrafo do corpo.
- **L — Linha do tempo:** o primeiro bloco H2 — contexto, o que já existia, o que mudou, de onde vem o assunto.
- **A — Argumento:** o dado, caso ou razão que sustenta a matéria — normalmente o segundo H2, apoiado por box de destaque técnico, tabela, citação ou lista.
- **R — Reforço:** o parágrafo de fechamento — o que fica como conclusão e o que fazer com essa informação (decisão, cuidado, próximo passo).

## As 5 editorias (definição oficial — não são palpite, confirmadas pelo time)

Escolha sempre UMA editoria por matéria — nunca misture duas. Elas não são só "assunto diferente", têm público e critério editorial diferentes entre si:

- **NOTÍCIAS** — "O que acontece no universo automotivo." Cobertura dos acontecimentos mais relevantes do setor: lançamentos, legislação, indústria, mercado, mobilidade, novas tecnologias e mudanças que impactam o universo automotivo. Informar com contexto, análise e relevância — nunca reproduzir comunicado puro. Público: geral.
- **REVIEWS** — "Além da aparência e da ficha técnica." Avaliação de veículos, produtos, equipamentos e tecnologias automotivas: desempenho, funcionamento, confiabilidade, manutenção, custos, experiência de uso. É a versão escrita da mesma régua do review em vídeo (`roteiro-video`) — mesmas perguntas (o que muda, no dia a dia, ponto crítico, vale a pena), em texto. Público: geral, orientado a decisão de compra/uso.
- **GUIAS** — "Conhecimento para entender, cuidar e decidir." Conteúdo educativo e orientação prática pra **motoristas e consumidores** (a mesma audiência da porta "Para Motoristas" da home): compra e venda, manutenção preventiva, sintomas/problemas, funcionamento dos sistemas, cuidados do dia a dia, direitos do consumidor, relação com oficina. Inclui explicação técnica traduzida pra quem não é profissional. Objetivo: ajudar o motorista a decidir com mais consciência.
- **TÉCNICA** — "Conhecimento para quem vive a reparação automotiva." Conteúdo técnico aprofundado pra **mecânicos, reparadores e profissionais do setor** (a audiência da porta "Para Oficinas" da home): diagnóstico, procedimento de manutenção/reparo, funcionamento de sistema, análise de falha, ferramenta, equipamento, metodologia de trabalho, nova tecnologia aplicada à oficina. Prioriza precisão e aplicação prática na bancada.
- **BASTIDORES** — "Pessoas, empresas e acontecimentos que movimentam o setor." Histórias, entrevistas, eventos, iniciativas, projetos, experiências — o universo automotivo além do produto e da notícia. É também onde entra conteúdo institucional, release de empresa, ação de parceiro, campanha e participação em evento (ex.: a cobertura escrita de um evento planejado com `cobertura-evento` vira matéria aqui) — coisas de menor peso jornalístico mas importantes pro relacionamento com o mercado. Única editoria onde esse tipo de conteúdo institucional é aceito; nas outras 4, critério editorial pleno sempre.

**Régua rápida Guias x Técnica** (a distinção mais fácil de confundir): mesmo assunto pode virar matéria nas duas — o que muda é o público e a profundidade. "Por que o carro perde força na subida" explicado pro motorista = Guias. O mesmo tema com procedimento de diagnóstico, especificação e método de bancada pro mecânico = Técnica.
2. **Título (H1)** — direto, sem jargão, sem clickbait.
3. **Linha fina/subtítulo** — a Afirmação (ver acima).
4. **Autor e data** — preencher quando souber quem assina; "Atualizado em" só aparece se a matéria for revisada depois (recall, mudança de legislação etc.); "tempo de leitura" só se for calculado.
5. **Imagem principal + alt text** — descreva de verdade a imagem (acessibilidade), nunca deixe o alt genérico tipo "foto do carro".
6. **Corpo:**
   - Parágrafo de abertura = Fagulha (+ reforça a Afirmação)
   - H2 "o que mudou/contexto" = Linha do tempo
   - H2/H3 seguintes, com apoio de lista, citação, box de destaque técnico, imagem interna ou tabela quando fizer sentido = Argumento
   - Parágrafo de fechamento = Reforço
7. **Créditos/fontes** — preencher sempre que houver apuração externa (entrevista, documento, nota oficial).
8. **Tags** — termos de busca reais (marca, modelo, tema), não hashtag de rede social.

## O que evitar

- Abrir com data/lugar/evento como se fosse um clipping de assessoria;
- Elogio funcional ao produto/marca antes de qualquer verificação em Notícias, Reviews, Guias ou Técnica — nessas 4, a marca "QUESTIONA" antes de aprovar, sempre. Em Bastidores, conteúdo institucional/de parceria é aceito (é a função da editoria), mas ainda precisa da etiqueta de crédito honesta (ver `cobertura-evento`) — aceitar o convite não é o mesmo que fingir neutralidade;
- Parágrafos de mais de 4-5 linhas sem quebra — prejudica leitura em tela;
- Jargão técnico sem explicação na primeira aparição;
- Matéria "em parceria" sem o aviso visível.

## Template de saída

```
EDITORIA: [Notícias | Reviews | Guias | Técnica | Bastidores]

TÍTULO: [título direto]

LINHA FINA (Afirmação): [uma frase — a ideia central da matéria]

AUTOR: [nome, se já souber] | DATA: [data de publicação]
CONTEÚDO EM PARCERIA COM: [marca, só se aplicável — remover linha se não houver]

IMAGEM PRINCIPAL: [descrição real da imagem] | ALT: [texto alternativo]

CORPO

[Parágrafo de abertura — Fagulha, reforçando a Afirmação]

## [H2 — Linha do tempo: contexto/trajetória]
[parágrafo(s)]

## [H2 — Argumento: o dado/caso/razão central]
[parágrafo(s), com lista/citação/box técnico/tabela quando fizer sentido]

[Parágrafo de fechamento — Reforço]

CRÉDITOS/FONTES: [quando aplicável]
TAGS: [tag1, tag2, tag3]
```

## Exemplo rápido

```
EDITORIA: Guias

TÍTULO: Por que seu carro perde potência em cidade alta

LINHA FINA (Afirmação): A perda de potência em altitude não é defeito —
é física, e hoje a gente mostra o que isso muda na prática.

AUTOR: [a definir] | DATA: [a definir]

IMAGEM PRINCIPAL: motor visto de cima, com destaque no duto de admissão de ar.
ALT: Vista superior do compartimento do motor, com o duto de admissão de ar em destaque.

CORPO

Se você mora em cidade de altitude elevada, provavelmente já notou: o carro
"não puxa" como puxava na praia. Isso não é sinal de problema mecânico —
é a física do ar rarefeito agindo sobre o motor.

## Por que isso acontece
Motores a combustão dependem de oxigênio pra queimar o combustível. Em
altitude, o ar é menos denso — menos oxigênio por litro de ar admitido,
logo menos potência na queima, mesmo com o motor funcionando perfeitamente.

## O que isso muda na prática
- Carros aspirados perdem mais potência proporcional que turbinados
  (o turbo compensa parte da perda comprimindo mais ar);
- Isso é mais perceptível em ultrapassagem e subida, não em velocidade
  de cruzeiro;
- Não é motivo pra revisão — é comportamento esperado do motor.

Na prática, se o carro perde fôlego subindo a serra mas volta ao normal
na baixada, isso é física, não defeito. Guarde essa informação antes de
levar o carro pra revisão por um sintoma que não é problema nenhum.

TAGS: motor, altitude, potência, aspirado, turbo
```
