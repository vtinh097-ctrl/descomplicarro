# Padrões aprovados

Registro dos padrões de edição **validados pelo usuário**. O padrão aprovado mais recente prevalece sobre os valores iniciais das outras referências. Nunca registrar um padrão sem aprovação explícita; perguntar antes de alterar.

## Estado atual
**Nenhum padrão aprovado ainda.** Vigente: `config/padrao.json` versão 0.1 (rascunho), validado apenas em teste técnico com material sintético (voz sintética pt-BR, imagem de barras de cor) em 23/11/2026. O teste validou mecânica (cortes, sincronia, loudness, legendas, gráficos, QA); **não valida** qualidade editorial nem aparência sobre imagem real.

Pontos que o primeiro teste real deve confirmar ou ajustar:
- [ ] tamanho e posição da legenda (66, base y=1400) em imagem real e no celular;
- [ ] zonas seguras com captura de tela real do Instagram;
- [ ] estilo do título de gancho (caixa grafite por linha) vs. alternativa sem caixa;
- [ ] uso do laranja nos destaques (quantidade e critério);
- [ ] cartela final: sim/não, duração, texto do CTA;
- [ ] tratamento de voz (redução de ruído, EQ) ouvido com fones;
- [ ] duração-alvo e ritmo (pausas reduzidas a ~0,2 s);
- [ ] fluxo de aprovação (manter as 3 etapas ou simplificar).

## Capa — Modelo A com logotipo oficial — APROVADO em 26/11/2026
Versão aprovada: capas de referência "Qual eletrificado é pra você?" e "38 anos com o mesmo carro." geradas por
`scripts/capa.py --modelo A` com `assets/logo/descomplicarro-logo-claro.png` (estado do commit 6321d87).
Reproduzir exatamente este modelo; qualquer mudança no layout da capa exige nova aprovação.
Ferramenta: `scripts/capa.py --modelo A` (padrão do script).
- Quadro real do vídeo (rostos livres; HDR convertido), título do gancho no terço inferior em caixas grafite,
  Space Grotesk Bold caixa alta, 2 linhas sempre que possível, 1 destaque + pontuação final em laranja.
- Faixa grafite translúcida atrás do título (legibilidade em qualquer fundo).
- **Marca no topo à esquerda**, numa plaquinha grafite, sempre no mesmo lugar (identidade na grade do perfil).
  Logotipo oficial instalado em 25/11/2026 (`assets/logo/descomplicarro-logo-claro.png`, ~430 px de largura).
  O quadrado com ponto laranja do site é placeholder e foi rejeitado — não usar.
  PENDENTE: confirmar se as cores do logo (#33302C / #C35932 / #FAF8EC) passam a ser as oficiais da skill.
- Sem série/nome de quadro; sem elemento de apoio fixo (card/carro) na capa.
- Título visível no recorte da grade (3:4) e fora da interface do Reels — o script confere.
Rejeitados: modelo B (assinatura sob o título) e C (símbolo mínimo).

## Modelo de registro
```
## Padrão vX.Y — [nome curto] — aprovado em DD/MM/AAAA
Projeto de referência: [nome do Reel] (arquivo final, projeto.json arquivado em …)
Config: config/padrao.json versão X.Y
Decisões validadas:
- Gancho: …
- Legendas: …
- Gráficos: …
- Ritmo/cortes: …
- Áudio: …
- Encerramento/CTA: …
O que NÃO fazer (rejeitado pelo usuário):
- …
Fluxo de aprovação vigente: …
```
