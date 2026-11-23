# Áudio — procedimento

Objetivo: voz clara, natural e constante, sem ruído que distraia e sem som "processado". Na dúvida, **menos processamento**.

## 1. Diagnóstico (Etapa A, `analyze.py`)
Mede por arquivo: loudness (LUFS, LRA, true peak), piso de ruído e SNR estimado, estouro (clipping), oscilação de volume entre trechos e balanço espectral da fala (ronco < 80 Hz, embolado 200–500 Hz, corpo, presença 2–5 kHz, sibilância 5–10 kHz). Daí sai uma recomendação com justificativa (`voice_recommendation` em `rl_common.py` — a mesma usada no render).

## 2. Cadeia de voz (render, em ordem)
1. **Passa-altas** 80 Hz (110 Hz se houver ronco/vento/manuseio).
2. **De-clip** (`adeclip`) só se houver estouro na origem.
3. **Redução de ruído** (`afftdn`, acompanha o ruído) por SNR: ≥45 dB nenhuma · 35–45 leve · 25–35 média · <25 forte. Redução forte deixa a voz metálica: testar média primeiro.
4. **EQ corretiva**, só quando o diagnóstico indicar: −2,5 dB em 300 Hz (embolado), +2 dB em 3,2 kHz (abafado).
5. **De-esser** se houver sibilância acentuada.
6. **Compressor suave** (−21 dB, 2,5:1, ataque 8 ms, release 150 ms).
7. **Compensação de latência medida**: o render mede o atraso real da cadeia em cada execução e remove-o (no FFmpeg 6.1 o `afftdn` atrasa ~25 ms — descoberto no teste técnico; sem compensação a voz sai fora de sincronia).

Tudo pode ser sobrescrito em `projeto.json → audio` (`reducao_ruido`, `passa_altas_hz`, `eq`, `deesser`, `declip`, `compressor`). Ganho por trecho: `segmentos[].ganho_db`.

## 3. Cortes
Cada segmento recebe micro-fades de 12 ms na entrada e saída (evita estalos). O QA procura estalos em cada corte.

## 4. Música de fundo — só autorizada
- Exige `musica.autorizada: true` **e** `musica.licenca` (texto da licença/origem); sem isso o render recusa.
- Volume base −22 dB, entrada 0,8 s, saída 1,5 s, **ducking** automático sob a voz (sidechain).
- Música não compete com a explicação: se o conteúdo é denso, sem música.
- Nunca trending audio sem direito de uso.

## 5. B-roll com som
Por padrão o B-roll entra mudo (a voz continua). `som_ambiente_db` (ex.: −18) mistura o som do B-roll — útil para ruído real de motor/peça quando ele é parte da explicação.

## 6. Masterização e entrega
- Alvo **−14 LUFS integrado** (±1), **true peak ≤ −1,5 dBTP**, AAC 192 kbps, 48 kHz, estéreo.
- Método: ganho linear até o alvo + limitador de pico com sobreamostragem 4× (picos entre amostras), medição do arquivo final e uma correção de ganho se necessário. Não usamos normalização dinâmica (deixa a voz "bombeando").
- Avisos automáticos: ganho > 12 dB (gravação baixa — ruído sobe junto), limitador atuando > 2 dB (voz pode achatar).

## 7. Limites honestos
Indicadores espectrais são estimativas; Claude não ouve o áudio. A versão de teste deve ser ouvida pelo usuário com fones; problemas como eco de sala, vento forte ou estouro severo **não são corrigíveis** de forma natural e devem ser informados.
