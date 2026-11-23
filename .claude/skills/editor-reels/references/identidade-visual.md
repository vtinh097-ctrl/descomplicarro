# Identidade visual em vídeo — DESCOMPLICARRO

Fonte da verdade: `assets/css/style.css` do site (cores e fontes). Parâmetros numéricos: `config/padrao.json`.

## Cores (somente estas)
| Uso | Cor | Hex |
|---|---|---|
| Texto principal, legendas | Off-white | `#F4F1EA` |
| Contorno de legenda, caixas, cartela, fundo de "encaixar" | Grafite | `#252525` |
| Destaque pontual (1–2 palavras, ponto final do título, barra/fio) | Laranja queimado | `#C65A32` |

O laranja nunca é cor dominante: não preenche caixas, não colore frases inteiras, não aparece em mais de um elemento por vez além da legenda.

## Fontes (arquivos em `assets/fonts/`, licença OFL)
| Família (nome usado no ASS) | Arquivo | Uso |
|---|---|---|
| `Space Grotesk` + negrito | SpaceGrotesk-Bold.ttf | título de gancho, termo, dado, cartela |
| `Space Grotesk Medium` | SpaceGrotesk-Medium.ttf | capítulo N.0X |
| `DM Sans` + negrito | DMSans-Bold.ttf | legendas |
| `DM Sans 14pt SemiBold` | DMSans-SemiBold.ttf | rótulo (nome), rótulo do dado, nota |
| `DM Sans 14pt Medium` | DMSans-Medium.ttf | textos de apoio, CTA |

As fontes são embarcadas no render (`fontsdir`), não dependem do sistema. Instâncias estáticas geradas das fontes variáveis oficiais do Google Fonts.

## Zonas seguras (1080×1920)
Áreas cobertas pela interface do Reels — nada de texto ou informação essencial nelas:
- topo **220 px** (cabeçalho/câmera), base **440 px** (nome, legenda do post, áudio),
- direita **150 px** a partir de y=980 (curtir, comentar, compartilhar), esquerda 60 px.

Valores iniciais conservadores. **Validar no primeiro teste real** com uma captura de tela do Reel publicado em modo privado/rascunho e ajustar em `config/padrao.json`. O QA desenha essas zonas sobre os quadros (`qa_textos_zonas.jpg`).

## Elementos gráficos disponíveis (`projeto.json → graficos`)
| Tipo | Função | Visual |
|---|---|---|
| `titulo` | Fagulha em tela (0–3 s) | Space Grotesk Bold caixa alta, caixa grafite por linha, ponto final laranja, entrada fade + 14 px de subida |
| `termo` | Nome técnico + explicação curta | Termo em caixa grafite + explicação em DM Sans, barra laranja vertical colada |
| `dado` | Número/especificação central | Número grande, fio laranja, rótulo abaixo |
| `rotulo` | Nome de quem fala / da peça | DM Sans SemiBold caixa alta + linha de apoio, fio laranja acima |
| `capitulo` | Bloco N.01, N.02… (vídeos mais longos) | Space Grotesk Medium, "N.0X" em laranja |
| `nota` | Correção ou ressalva em tela | DM Sans em caixa grafite, barra laranja |
| `cartela` | Virada de bloco ou cobrir imagem inutilizável (ex.: alguém atravessando a lente) sem perder a fala | Tela grafite, frase em Space Grotesk, fio laranja; legenda continua por cima |
| `cartela_final` | Encerramento/CTA | Tela grafite, "ENTENDA. QUESTIONE. DECIDA." empilhado com ponto final laranja, CTA |

`rotulo` aceita `"lado": "direita"` — o nome fica **do lado de quem está sendo nomeado** (em entrevistas, nunca sobre o entrevistador).

**Fotos e documentos como card** (B-roll com `enquadramento.modo: "card"`): a foto entra sobre o vídeo com moldura de papel off-white, sombra grafite e fundo escurecido, com fade + leve subida; a voz e a legenda continuam. `recorte` (frações x, y, w, h) mostra só o trecho que importa (ex.: o anúncio dentro da página do jornal). `girar` (graus, horário) nivela documento fotografado torto; `marcar` (frações x, y, w, h da imagem recortada) desenha um contorno laranja fino no trecho citado (o anúncio, a data). Use para documentos, recortes de jornal, fotos de época — tudo que é prova ou memória da história contada. Entra na palavra que o cita. Cards em sequência (até 0,3 s de intervalo) compartilham o mesmo fundo escurecido; sobrepor 0,25 s faz a troca direta de um documento para o outro. O texto mais importante do documento deve ter pelo menos ~40 px de altura no card — recorte fechado se preciso.

Regras: um gráfico por vez (além da legenda); entrada/saída com fade de 150 ms; nada de bounce, glow, gradiente, 3D, ícone de app, emoji, sticker.

## Logotipo
O repositório ainda **não tem o logotipo oficial** (só placeholder no site). Não desenhar logotipo. Se a marca enviar o arquivo, registrar aqui e criar um tipo de gráfico para ele.

## Enquadramento
- Material vertical: usar inteiro (ampliação 1,0×).
- Material horizontal: recorte 9:16 centrado na pessoa (`enquadramento.x/y`). Em 1080p, isso amplia 1,78× e perde nitidez — o render avisa; preferir gravar vertical ou em 4K.
- Telas, prints e B-roll horizontal que não pode ser cortado: `modo: "encaixar"` (imagem inteira sobre grafite). Nunca fundo desfocado.
