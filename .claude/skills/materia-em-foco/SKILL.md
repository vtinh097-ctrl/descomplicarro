---
name: materia-em-foco
description: Escreve matérias (texto longo) para a seção Em Foco do site DESCOMPLICARRO — notícias, reviews, guias, conteúdo técnico ou bastidores — já encaixadas na estrutura real do template do site (título, linha fina, editoria, corpo com H2/H3/listas/citação/box técnico, créditos, tags). Use sempre que o usuário pedir pra escrever uma matéria, artigo, texto pro site, notícia, review escrito, guia ou conteúdo pro Em Foco — mesmo sem usar essas palavras (ex.: "preciso escrever sobre esse recall pro site", "monta um texto explicando isso pra postar no Em Foco"). Para roteiro de vídeo ou carrossel sobre o mesmo assunto, use roteiro-video ou carrossel-estatico/carrossel-animado em vez desta.
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

## Estrutura real da matéria (campos do template)

Preencha nesta ordem, usando exatamente estes elementos (todos já existem como classes/estrutura no site):

1. **Editoria** — escolha uma: `Notícias` (fato novo do setor), `Reviews` (avaliação de carro/produto), `Guias` (explicativo prático, "como fazer"), `Técnica` (aprofundamento de mecanismo/funcionamento), `Bastidores` (making-of, cobertura de evento, rotina da marca). Nunca misture duas editorias numa matéria só.
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
- Elogio funcional ao produto/marca antes de qualquer verificação — mesmo em matéria de Bastidores sobre evento a convite, a marca "QUESTIONA" antes de aprovar (ver regra de crédito em `cobertura-evento`, quando aplicável);
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
EDITORIA: Técnica

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
