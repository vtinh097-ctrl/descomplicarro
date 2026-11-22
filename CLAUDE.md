# DESCOMPLICARRO — instruções permanentes

## Instagram (carrossel + Stories + legenda a partir do link de uma matéria)

A especificação completa está em `instagram/PROMPT-MESTRE.md` — leia antes de qualquer pedido de Instagram.

Regras que valem acima de qualquer pedido:
- Os dois modelos (`instagram/codigo/inc/editorial-render.php` = Modelo 1, `editorial-render-modelo2.php` = Modelo 2) estão **aprovados**. Nunca redesenhar, simplificar ou "melhorar" sem pedido explícito do cliente — e, mesmo assim, confirmar o escopo antes.
- O modelo é escolhido automaticamente pela editoria (Técnica/Guias → Modelo 2; o resto → Modelo 1). Nunca misturar.
- Nunca inventar dado técnico, número ou especificação. Modelo 2: escrever Entenda/Questione/Decida a partir do texto real da matéria (narrativa educativa, não resumo).
- Publicação é sempre manual, pelo cliente. Este projeto só entrega arquivos.

Comando:

    php instagram/codigo/gerar-por-link-sem-wp.php "<link>" "<pasta-de-saida>" [--destaques="a|b|c"] [--chamada=".." --entenda=".." --questione=".." --decida=".." --usarfoto=1]

Depois de gerar: abrir e conferir todos os PNGs (texto cortado, foto em branco, espaço vazio) antes de entregar.
