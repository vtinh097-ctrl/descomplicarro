# Padrão de legendagem — DESCOMPLICARRO

## Princípios (criativo)
1. **A legenda segue a fala.** Ela transcreve o que foi dito, com ortografia correta. Não "melhora" o texto: se o apresentador disse "pro seu carro", a legenda diz "pro seu carro" (o Whisper costuma trocar por "para o seu" — corrigir de volta). Pequenas limpezas permitidas: hesitações (é…, ãh) e repetições involuntárias que já foram cortadas da montagem.
2. **Leitura em bloco, não karaokê.** Blocos de 1–2 linhas que aparecem inteiros; sem palavra acendendo uma a uma, sem pulo/escala, sem emoji.
3. **Destaque seletivo:** no máximo 1–2 palavras por bloco em laranja, e só quando carregam a ideia (o número, a palavra-chave do gancho, o termo técnico, a ressalva — "atenção", "depende"). Se tudo é destaque, nada é.
4. **Quebra natural:** cada bloco é um pedaço de frase com sentido. Nunca terminar linha/bloco em artigo, preposição ou conjunção ("o", "de", "que", "para"…); não separar locuções ("tipo | de motor"); não deixar palavra órfã.
5. **Termos técnicos e marcas** com grafia oficial (ex.: "CVT", "API SP", "Ford Ka", "start-stop").
6. **Números:** algarismos para dados ("2 fatores", "R$ 18 mil", "120 mil km"), por extenso só em expressões ("uma vez").
7. **Pontuação:** vírgulas e interrogação/exclamação mantidas; ponto final no fim do bloco é removido (visual mais limpo); reticências mantidas.

## Especificação (técnico — valores em `config/padrao.json → legendas`)
- DM Sans Bold, tamanho ASS 66 (altura útil ~50 px de corpo), off-white, contorno grafite 5 px + sombra suave.
- Centralizada entre margens 90/150 px (respeita a coluna de ícones), base em y=1400.
- Máx. 2 linhas, largura máx. 820 px medida com a fonte real, ~42 caracteres por bloco.
- Duração 0,7–3,2 s por bloco; leitura ideal ≤ 17 car/s, alerta acima de 21 car/s.
- Blocos contínuos (sem "piscar" quando o intervalo é < 0,35 s); fade de entrada 60 ms.
- Quebra de bloco em pausa ≥ 0,28 s, fim de frase ou troca de segmento.

## Fluxo
1. `captions.py build projeto.json` gera `legendas.json` a partir das palavras, já mapeadas para o tempo da montagem, e sugere destaques numéricos + `destaques_globais` do projeto.
2. **Revisão do editor (obrigatória), bloco a bloco:** fala real, ortografia, acentos, pontuação, termos técnicos, destaques. Blocos com `conf_min` < 0,5 merecem atenção extra (o Whisper errou o falso início do teste: "Hum… então" virou "O mentão"). Para forçar quebra de linha, usar `\n` no texto. Tempos podem ser ajustados à mão.
3. `captions.py lint projeto.json` — FALHA bloqueia o render (sobreposição, fora da zona segura, não cabe, destaque inexistente); ALERTA exige decisão consciente.
4. Conferir no QA: `qa_textos_zonas.jpg` e `quadros/legenda_100pct.png`.

`build` não sobrescreve um `legendas.json` existente (pode estar revisado) sem `--force`.
