---
name: carrossel-estatico
description: Cria o conteúdo e a especificação visual de carrosséis estáticos (imagem por slide, sem motion) para o Instagram/LinkedIn do DESCOMPLICARRO. Use sempre que o usuário pedir um carrossel, post de carrossel, "slides pro Instagram", material pra divulgar uma matéria do Em Foco em formato de post, ou pedir pra transformar um texto/vídeo em posts de feed — mesmo sem mencionar "carrossel" explicitamente (ex.: "preciso de um post pra falar sobre isso no Instagram", "resume isso em slides pra postar"). Se o usuário pedir explicitamente movimento/animação por slide, use a skill carrossel-animado em vez desta.
---

# Carrossel estático — DESCOMPLICARRO

Este skill gera carrosséis de imagem estática (swipe simples, sem animação por slide) para Instagram/LinkedIn. Para o formato com motion graphics por slide, use a skill `carrossel-animado`.

## Base de marca (não negociável, vale para todo formato)

**Essência:** complexidade → compreensão → autonomia → decisão. Cada slide é uma camada de informação, não um pedaço de um parágrafo cortado — se um slide não faz sentido sozinho, ele está mal dividido.

**A marca nunca pode soar como:** carrossel genérico de dicas numeradas sem fio condutor, meme automotivo, post de concessionária/oficina vendendo serviço.

**Cores** (só estas): grafite `#252525` (estrutura/fundo), off-white `#F4F1EA` (respiro/fundo alternado), laranja queimado `#C65A32` (intervenção pontual — um grifo, uma palavra, uma linha, nunca o fundo do slide inteiro).

**Tipografia:** Space Grotesk para o título de cada slide (pesado, caixa alta quando for a frase de impacto); DM Sans para texto de apoio/legenda.

**Elementos gráficos:** linha técnica, moldura fina, seta, marca de coordenada, numeração — organizam ou destacam, nunca decoram. Evite ícone genérico de app, emoji como marcador de seção, gradiente, 3D.

**Numeração:** cada slide carrega sua posição como uma "camada" (`N.01`, `N.02`...), ecoando o sistema já usado na home — isso também ajuda quem está lendo a saber quanto falta.

## Estrutura do carrossel

Um carrossel bom tem uma única ideia central — ele não é um resumo de vários tópicos, é a decomposição de UM conceito em camadas que se constroem.

**Slide 1 — gancho.** Uma pergunta ou afirmação que cria a lacuna de curiosidade (a mesma lógica do gancho de vídeo). Não é o título de um artigo, é a pergunta que a pessoa já tem na cabeça mas não sabia como formular. Ex.: "Por que a peça original custa o triplo da paralela — e quando isso realmente importa?"

**Slides 2 a N-2 — as camadas.** Cada slide avança UM passo do raciocínio, numerado (N.01, N.02...). Regra prática: se você tirar um slide do meio e o próximo ainda fizer sentido sozinho sem ele, o slide do meio não estava carregando informação de verdade — reescreva.

**Penúltimo slide — a virada/resposta.** O ponto em que o conceito complicado vira claro. Normalmente o slide com a frase mais forte do carrossel inteiro — é aqui que o laranja pode aparecer com mais intenção (grifo, sublinhado, palavra isolada).

**Último slide — fechamento + CTA.** Resume a resposta em uma frase e aponta pra ação (seguir, comentar, ir pro perfil/link, assistir ao vídeo completo se o carrossel for derivado de um). CTA claro, nunca genérico tipo só "siga pra mais".

## Regras de texto por slide

- Título do slide: curto o bastante pra ler em 1-2 segundos de swipe (pense em 6-10 palavras no máximo para a linha de impacto).
- Nunca lotar um slide com parágrafo corrido — se precisar de mais de 2-3 linhas de apoio, o conceito provavelmente precisa de outro slide.
- O laranja pontua, não narra: use em UMA palavra, UM número ou UM traço por slide, não em blocos de texto inteiros.

## Produção da arte

Depois de fechar o roteiro de slides (conteúdo), monte a peça visual. Duas rotas possíveis — escolha com base no que está disponível:

1. **Se houver um brand kit/template do DESCOMPLICARRO no Canva** (confira com as ferramentas `list-brand-kits` / `search-brand-templates` do Canva, se disponíveis): use `create-design-from-brand-template` ou `autofill-design` preenchendo o template com o texto de cada slide, mantendo a paleta e tipografia da marca. Isso garante consistência entre posts sem redesenhar do zero.
2. **Se não houver template pronto:** monte os slides como páginas HTML/CSS simples (mesmo princípio usado no site — variáveis de cor/tipografia, grid editorial, laranja pontual) e exporte cada slide como imagem. Use proporção `4:5` (1080×1350) como padrão de feed, a menos que o usuário peça outra.

Em qualquer rota, releia a especificação de slides contra a Base de marca antes de finalizar — principalmente o teste do laranja (ele está pontuando ou dominando?) e o teste do slide-sozinho (cada slide se sustenta como camada de informação?).

## Template de saída (roteiro de slides, antes da arte)

```
TEMA / IDEIA CENTRAL: [uma frase]

SLIDE 1 — GANCHO
[texto do slide]

N.01 — [texto do slide]
N.02 — [texto do slide]
N.03 — [texto do slide]
...

SLIDE [N-1] — A VIRADA
[a frase mais forte do carrossel — candidata a grifo laranja]

SLIDE N — FECHAMENTO
[resposta em uma frase] + [CTA]

LEGENDA DO POST: [texto pra acompanhar o post, não repete os slides, dá contexto/convite]
```

## Exemplo rápido

```
TEMA: Por que a peça original custa mais caro que a paralela — e quando isso importa de verdade.

SLIDE 1 — GANCHO
"A peça original custa 3x mais que a paralela.
Isso é só margem de loja ou é outra coisa?"

N.01 — Nem toda peça paralela é igual. Existe fabricante homologado e existe o resto.
N.02 — A diferença de preço paga certificação e controle de qualidade — na maioria das vezes.
N.03 — Mas em peça de desgaste simples (filtro, pastilha básica), a diferença técnica é quase zero.

SLIDE 4 — A VIRADA
"Pague original onde a segurança depende disso.
Economize onde a física é a mesma."

SLIDE 5 — FECHAMENTO
Resposta: não existe regra única — existe peça por peça.
CTA: "Salva esse post pra consultar antes do próximo orçamento."

LEGENDA: Antes de brigar com o mecânico sobre peça original x paralela, vale
entender onde a diferença é real e onde é só etiqueta. 👇
```
