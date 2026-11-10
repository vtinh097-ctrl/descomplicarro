---
name: carrossel-animado
description: Cria o roteiro de timing e a especificação de motion graphics para carrosséis ANIMADOS do DESCOMPLICARRO — cada slide é um vídeo curto em loop com elementos entrando em sequência (texto principal → diagrama/mecanismo → texto de apoio), no estilo de carrosséis de motion design que circulam no Instagram. Use sempre que o usuário pedir carrossel animado, carrossel em movimento, carrossel "estilo @ocheiodegraxa" (ou referência visual parecida), post com motion graphics, ou vídeo-carrossel para redes sociais. Se o pedido for de um carrossel simples de imagem estática sem animação, use a skill carrossel-estatico em vez desta.
---

# Carrossel animado — DESCOMPLICARRO

Este skill estrutura carrosséis onde cada slide é um clipe curto em loop com elementos entrando em sequência — não um swipe de imagens paradas. Ele nasceu da análise de um formato de referência (conta `@ocheiodegraxa`): cada slide tem um "hold" escuro inicial, depois o título principal entra, depois um pequeno diagrama anima o mecanismo/conceito sendo explicado, depois entra o texto de apoio.

**Regra de ouro deste formato: copie o método, troque a pele.** A coreografia de revelação (texto → mecanismo → explicação) é ótima e deve ser reaproveitada — ela é, na prática, a mesma lógica de "camadas de informação" que já rege a marca, só que em movimento. O que NÃO deve ser copiado é o vocabulário visual da referência (fundo quase preto com glow pulsante, cards brancos arredondados estilo app, ícone de cursor, badges de SaaS) — isso é estética de explicador de produto digital, e o DESCOMPLICARRO não pode soar como um app sendo demonstrado. Veja a seção "Diagrama: o que trocar" abaixo antes de desenhar qualquer elemento.

## Base de marca (não negociável, vale para todo formato)

**Essência:** complexidade → compreensão → autonomia → decisão.

**Cores** (só estas): grafite `#252525`, off-white `#F4F1EA`, laranja queimado `#C65A32` usado como intervenção pontual — aqui, tipicamente como o ponto final depois do título, ou como o único traço/nó destacado dentro de um diagrama majoritariamente grafite/off-white.

**Tipografia:** Space Grotesk pesado e condensado para o título principal de cada slide (a peça que carrega o impacto); DM Sans para o texto de apoio que fecha o raciocínio.

**Evitar sempre:** glow/bokeh ambiente, fundo com gradiente radial, cards brancos com cantos muito arredondados estilo app, ícones de cursor/UI, badges tipo chip de produto, som de trending audio genérico — qualquer coisa que faça o slide parecer demonstração de SaaS em vez de peça editorial técnica.

## Coreografia de cada slide (4 tempos)

1. **Hold / respiro (≈0.3–0.6s).** Fundo grafite liso ou com uma textura técnica baixíssimo-contraste (grid fino, linha de construção) quase imperceptível — nunca um glow pulsante. É a pausa antes da informação, não um efeito.
2. **Título principal entra (≈0.6–1.2s).** A frase de impacto do slide, Space Grotesk, caixa alta, entrada seca (ex.: leve deslocamento vertical + fade, ou máscara revelando o texto da esquerda pra direita) — sem bounce, sem elástico, sem efeito "divertido". A marca é precisão, não fofura. Termina com um detalhe em laranja quando fizer sentido (ponto final, sublinhado curto, um traço).
3. **Diagrama do mecanismo entra (≈1.2–2.5s).** Ver seção abaixo — é aqui que o conceito abstrato vira visível.
4. **Texto de apoio entra (≈2.5–3.2s).** DM Sans, menor, a frase que fecha o raciocínio em linguagem simples. Fica em tela até o loop reiniciar ou até o usuário arrastar pro próximo slide.

Tempos são referência, não grade rígida — ajuste à complexidade do conceito, mas mantenha a ORDEM (título → mecanismo → apoio): é essa ordem que faz o conteúdo ser compreendido em camadas em vez de despejado de uma vez.

## Diagrama: o que trocar

Quando o slide precisa visualizar um mecanismo/processo (pra onde vai um custo, como uma peça se relaciona com outra, o caminho de uma decisão), resista ao impulso de desenhar um mockup de app (card branco arredondado + ícone + cursor arrastando algo). Em vez disso, use o vocabulário já aprovado pra marca:

- **Diagrama técnico/linha de construção:** elementos conectados por linhas finas, como um esquema técnico, não como uma tela de produto.
- **Vista explodida ou seta de fluxo:** mostre a peça/conceito se movendo ou se decompondo com uma seta ou linha de trajetória, em vez de um cursor "clicando".
- **Marcação de coordenada/crosshair:** para apontar o ponto exato que importa dentro de uma imagem ou esquema.
- **Paleta do diagrama:** linhas em grafite ou off-white (conforme o fundo do slide), com UM elemento ou nó em laranja — o ponto que o diagrama existe pra destacar. Nunca colorir o diagrama inteiro.

O teste rápido: se o diagrama parecesse uma tela de aplicativo sendo demonstrada, ele está errado. Se parecesse uma ilustração técnica/editorial de revista, está certo.

## Numeração e paginação

Siga o padrão de dois níveis que a referência usa bem, adaptado à marca:
- Contador do post inteiro (ex. "04/17") pode ficar discreto no canto, em DM Sans.
- Sub-contador dentro do slide usa a lógica N.0X da marca em vez de "1 DE 3" genérico — ex. `N.01` pode ser o próprio rótulo do slide, reforçando a mesma linguagem numérica da home e dos outros formatos.

## Som

Som reforça a sensação de precisão, não de empolgação. Prefira um clique/whoosh curto e seco sincronizado a cada entrada de elemento, no lugar de áudio em alta (trending sound). Se o carrossel usa narração, ela deve soar como alguém explicando com clareza, não como locução publicitária.

## Produção

Este formato é motion design (não apenas diagramação), então a entrega tem duas camadas:

1. **Roteiro de timing** (o que este skill produz sempre): a especificação completa abaixo, por slide — título, tempos de entrada, descrição do diagrama, texto de apoio, nota de som. Isso é suficiente para qualquer editor (humano ou ferramenta) produzir o motion graphics.
2. **Protótipo visual, quando possível:** para dar uma prévia real do timing e do estilo (não só descrição em texto), monte um protótipo em HTML/CSS/JS com as animações de entrada aplicadas (mesma técnica usada nas páginas do site: variáveis de cor/tipografia, transições discretas) e publique como artifact para o usuário visualizar o loop rodando antes de produzir a versão final em After Effects/Canva/CapCut. Isso é especialmente útil pra validar o tempo de cada entrada antes de qualquer produção pesada.

## Template de saída (roteiro de timing, por slide)

```
SLIDE N.0X
Fundo: [grafite liso | textura técnica sutil, especificar]

[0.0–0.5s] HOLD
[descrição do estado de repouso]

[0.5–1.2s] TÍTULO
Texto: "[título em caixa alta]"
Entrada: [tipo de movimento — ex. fade + leve deslocamento vertical]
Detalhe laranja: [onde, se houver]

[1.2–2.5s] DIAGRAMA
O que visualiza: [o mecanismo/conceito sendo tornado visível]
Elementos: [linhas/nós/setas — especificar o que é grafite, o que é off-white, o nó em laranja]
Movimento: [como o diagrama se monta/se conecta]

[2.5–3.2s] TEXTO DE APOIO
Texto: "[frase que fecha o raciocínio em linguagem simples]"

SOM: [nota de clique/whoosh por beat]
```

## Exemplo rápido

```
SLIDE N.02
Fundo: grafite liso

[0.0–0.5s] HOLD
Tela grafite, sem elementos.

[0.5–1.2s] TÍTULO
Texto: "O TORQUE NÃO É O QUE VOCÊ ACHA."
Entrada: fade + deslocamento vertical de baixo pra cima, seco, sem bounce.
Detalhe laranja: ponto final depois de "ACHA."

[1.2–2.5s] DIAGRAMA
O que visualiza: a diferença entre torque (força) e potência (velocidade de entrega
dessa força) ao longo da faixa de rotação do motor.
Elementos: duas linhas finas em off-white formando um eixo simples (rotação x força),
uma curva em grafite subindo, um ponto único em laranja marcando onde o torque
máximo acontece.
Movimento: a curva se desenha da esquerda pra direita, como uma linha de medida
sendo traçada; o ponto laranja "acende" quando a curva passa por ele.

[2.5–3.2s] TEXTO DE APOIO
Texto: "Carro com mais torque não é carro mais rápido — é carro que entrega
força mais cedo."

SOM: clique seco na entrada do título; whoosh curto acompanhando o traço da curva.
```
