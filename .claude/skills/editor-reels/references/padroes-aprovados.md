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
