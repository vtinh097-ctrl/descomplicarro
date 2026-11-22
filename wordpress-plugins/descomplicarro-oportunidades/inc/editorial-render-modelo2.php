<?php
/**
 * DESCOMPLICARRO — MODELO 2: Editorial Educativo e Técnico.
 *
 * Usado SOMENTE para as editorias Técnica e Guias — ver a seleção
 * automática em inc/editorial-gerador.php. O MODELO 1 (inc/editorial-
 * render.php, "Editorial Fotográfico" — Notícias/Reviews/Bastidores)
 * continua INTACTO: este arquivo não altera, nem chama para sobrepor,
 * nenhuma função dele. Reaproveita só as primitivas de desenho
 * genéricas já existentes (cor, fonte, texto, corte de foto).
 *
 * ESTRUTURA FIXA — "Entenda. Questione. Decida." (a mesma assinatura
 * editorial que já é a identidade do DESCOMPLICARRO), sempre exatamente
 * 5 slides, sempre nessa ordem — nunca escolhida por item/matéria:
 *   1. CAPA      — chamada forte sobre o tema.
 *   2. ENTENDA    — explicação técnica acessível.
 *   3. QUESTIONE  — reflexão, dúvida relevante ou quebra de percepção.
 *   4. DECIDA     — orientação prática para decidir algo.
 *   5. CTA        — convite para ler a matéria completa no site.
 *
 * O texto de cada um dos três slides do meio (Entenda/Questione/Decida)
 * é escrito a partir do conteúdo real da matéria (nunca inventado) — ver
 * os campos _dco_m2_entenda/_dco_m2_questione/_dco_m2_decida
 * (inc/editorial-campos.php) e as opções --entenda/--questione/--decida
 * do gerador por link (ferramentas/sob-demanda/gerar-por-link.php).
 *
 * IDENTIDADE VISUAL: fundo grafite em TODOS os 5 slides (o que diferencia
 * à primeira vista do Modelo 1, que é dominado por fotografia/off-white) —
 * tipografia em off-white e laranja queimado, composição tipográfica em
 * primeiro plano, fotografia opcional e nunca obrigatória. Marcadores
 * **assim** dentro do texto destacam a palavra/trecho em laranja — usado
 * com moderação, só nos termos realmente importantes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Quebra um texto com marcadores **destaque** em tokens {palavra, destaque}. */
function dco_editorial_m2_tokenizar_destaques( $texto ) {
	$partes = preg_split( '/\*\*(.+?)\*\*/u', $texto, -1, PREG_SPLIT_DELIM_CAPTURE );
	$tokens = array();
	foreach ( $partes as $i => $parte ) {
		if ( '' === trim( $parte ) ) {
			continue;
		}
		$destaque = ( 1 === $i % 2 ); // índices ímpares = o que estava entre **...**
		foreach ( preg_split( '/\s+/u', trim( $parte ) ) as $palavra ) {
			if ( '' !== $palavra ) {
				$tokens[] = array( 'palavra' => $palavra, 'destaque' => $destaque );
			}
		}
	}
	return $tokens;
}

/**
 * Desenha um parágrafo com destaques inline em laranja — mesma lógica de
 * quebra de linha das primitivas do Modelo 1, mas palavra a palavra, pra
 * poder alternar de cor no meio do texto. $alinhamento: 'esquerda' | 'centro'.
 * Devolve a altura total ocupada.
 */
function dco_editorial_m2_desenhar_texto_destacado( $im, $texto, $fonte, $tamanho, $cor_normal, $cor_destaque, $x, $y, $max_width, $altura_linha, $alinhamento = 'esquerda' ) {
	$tokens = dco_editorial_m2_tokenizar_destaques( $texto );
	$linhas = array();
	$linha_atual = array();
	$largura_atual = 0;
	$espaco = abs( imagettfbbox( $tamanho, 0, $fonte, ' ' )[2] - imagettfbbox( $tamanho, 0, $fonte, ' ' )[0] );

	foreach ( $tokens as $token ) {
		$bbox = imagettfbbox( $tamanho, 0, $fonte, $token['palavra'] );
		$largura_palavra = abs( $bbox[2] - $bbox[0] );
		$largura_teste = $largura_atual + ( empty( $linha_atual ) ? 0 : $espaco ) + $largura_palavra;
		if ( $largura_teste > $max_width && ! empty( $linha_atual ) ) {
			$linhas[] = $linha_atual;
			$linha_atual = array( $token );
			$largura_atual = $largura_palavra;
		} else {
			$linha_atual[] = $token;
			$largura_atual = $largura_teste;
		}
	}
	if ( ! empty( $linha_atual ) ) {
		$linhas[] = $linha_atual;
	}

	foreach ( $linhas as $i => $linha ) {
		$largura_linha = 0;
		foreach ( $linha as $idx => $token ) {
			$bbox = imagettfbbox( $tamanho, 0, $fonte, $token['palavra'] );
			$largura_linha += abs( $bbox[2] - $bbox[0] );
			if ( $idx > 0 ) {
				$largura_linha += $espaco;
			}
		}
		$cursor_x = 'centro' === $alinhamento ? (int) round( $x - $largura_linha / 2 ) : $x;
		foreach ( $linha as $token ) {
			$cor = $token['destaque'] ? $cor_destaque : $cor_normal;
			imagettftext( $im, $tamanho, 0, $cursor_x, $y + $i * $altura_linha, $cor, $fonte, $token['palavra'] );
			$bbox = imagettfbbox( $tamanho, 0, $fonte, $token['palavra'] );
			$cursor_x += abs( $bbox[2] - $bbox[0] ) + $espaco;
		}
	}
	return count( $linhas ) * $altura_linha;
}

/** Só pra medir a altura total que um texto destacado vai ocupar, sem desenhar. */
function dco_editorial_m2_altura_texto_destacado( $im_fake, $texto, $fonte, $tamanho, $max_width, $altura_linha ) {
	$tokens = dco_editorial_m2_tokenizar_destaques( $texto );
	$linhas = 0;
	$largura_atual = 0;
	$iniciou = false;
	$espaco = abs( imagettfbbox( $tamanho, 0, $fonte, ' ' )[2] - imagettfbbox( $tamanho, 0, $fonte, ' ' )[0] );
	foreach ( $tokens as $token ) {
		$bbox = imagettfbbox( $tamanho, 0, $fonte, $token['palavra'] );
		$largura_palavra = abs( $bbox[2] - $bbox[0] );
		$largura_teste = $largura_atual + ( $iniciou ? $espaco : 0 ) + $largura_palavra;
		if ( $largura_teste > $max_width && $iniciou ) {
			++$linhas;
			$largura_atual = $largura_palavra;
		} else {
			$largura_atual = $largura_teste;
			$iniciou = true;
		}
	}
	if ( $iniciou ) {
		++$linhas;
	}
	return $linhas * $altura_linha;
}

function dco_editorial_m2_eyebrow_secao( $im, $texto, $x, $y, $cor ) {
	dco_editorial_desenhar_eyebrow( $im, $texto, $x, $y, $cor, null, 22 );
	imagefilledrectangle( $im, $x, $y + 40, $x + 56, $y + 44, $cor );
	return 44 + 36; // altura ocupada pelo selo + a régua + respiro antes do corpo
}

/**
 * Barra vertical laranja ("lombada" editorial) ao lado do bloco de texto —
 * preenche com intenção gráfica a faixa entre o rótulo de seção e o rodapé,
 * em vez de deixar o fundo grafite vazio quando o texto é mais curto.
 */
function dco_editorial_m2_barra_lateral( $im, $x, $y1, $y2, $cor, $largura = 4 ) {
	if ( $y2 > $y1 ) {
		imagefilledrectangle( $im, $x, $y1, $x + $largura - 1, $y2, $cor );
	}
}

function dco_editorial_m2_marca_rodape( $im, $w, $h, $numero = null, $total = null ) {
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE, 20 );
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	$cinza    = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE, 110 );
	$fonte    = dco_editorial_fonte_display();
	$tam      = 14;
	$y        = $h - 42;
	imagettftext( $im, $tam, 0, 56, $y, $offwhite, $fonte, 'DESCOMPLI' );
	$bbox   = imagettfbbox( $tam, 0, $fonte, 'DESCOMPLI' );
	$avanco = abs( $bbox[2] - $bbox[0] );
	imagettftext( $im, $tam, 0, 56 + $avanco, $y, $laranja, $fonte, 'CARRO' );

	if ( $numero && $total ) {
		$rotulo = "$numero/$total";
		$bbox_pag = imagettfbbox( 13, 0, $fonte, $rotulo );
		$lw = abs( $bbox_pag[2] - $bbox_pag[0] );
		imagettftext( $im, 13, 0, $w - 56 - $lw, $y, $cinza, $fonte, $rotulo );
	}
}

/**
 * Renderiza UM slide do Modelo 2. $spec:
 *   formato   'carrossel' (1080×1350) | 'stories' (1080×1920)
 *   secao     'capa' | 'entenda' | 'questione' | 'decida' | 'cta'
 *   eyebrow   categoria (só a capa)
 *   titulo    chamada da capa, ou o rótulo "Matéria completa" no CTA
 *   texto     corpo do slide (com marcadores **destaque** opcionais)
 *   foto      caminho local, opcional — só a capa pode usar, nunca obrigatório
 *   numero / total  numeração (carrossel)
 */
function dco_editorial_renderizar_slide_modelo2( array $spec, $caminho_destino ) {
	$formato = $spec['formato'] ?? 'carrossel';
	$secao   = $spec['secao'] ?? 'entenda';
	$w = 1080;
	$h = 'stories' === $formato ? 1920 : 1350;

	$im = imagecreatetruecolor( $w, $h );
	imagesavealpha( $im, true );
	$grafite  = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$offwhite_90 = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE, 12 );
	$offwhite_70 = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE, 70 );
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	imagefilledrectangle( $im, 0, 0, $w, $h, $grafite );

	$fonte_display = dco_editorial_fonte_display();
	$fonte_corpo   = dco_editorial_fonte_corpo();
	$pad_x   = 64;
	$centro_x = (int) round( $w / 2 );
	$rodape_reserva = 70;
	$area_fim = $h - $rodape_reserva;

	if ( 'capa' === $secao ) {
		$y = 72;
		if ( ! empty( $spec['eyebrow'] ) ) {
			dco_editorial_desenhar_eyebrow( $im, $spec['eyebrow'], $pad_x, $y, $laranja );
			$y += 56;
		}
		if ( ! empty( $spec['foto'] ) ) {
			$alt_foto = (int) round( $h * 0.30 );
			dco_editorial_compor_foto_cover( $im, $spec['foto'], 0, 0, $w, $alt_foto );
			imagefilledrectangle( $im, 0, $alt_foto, $w, $alt_foto + 6, $laranja );
			$y = $alt_foto + 60;
		}

		$tam_titulo = 'stories' === $formato ? 60 : 54;
		$alt_linha  = (int) round( $tam_titulo * 1.18 );
		$alt_bloco  = dco_editorial_m2_altura_texto_destacado( $im, $spec['titulo'] ?? '', $fonte_display, $tam_titulo, $w - $pad_x * 2, $alt_linha );
		$dek_existe = ! empty( $spec['texto'] );
		$tam_dek    = 'stories' === $formato ? 30 : 24;
		$alt_linha_dek = (int) round( $tam_dek * 1.5 );
		$alt_dek    = $dek_existe ? 28 + dco_editorial_m2_altura_texto_destacado( $im, $spec['texto'], $fonte_corpo, $tam_dek, $w - $pad_x * 2, $alt_linha_dek ) : 0;

		$zona_topo  = $y;
		$zona_livre = $area_fim - $y;
		$y += (int) round( max( 0, $zona_livre - $alt_bloco - $alt_dek ) / 2 );
		dco_editorial_m2_barra_lateral( $im, $pad_x - 26, $zona_topo, $area_fim, $laranja );
		$y += $alt_linha;
		$y_apos_titulo = dco_editorial_m2_desenhar_texto_destacado( $im, $spec['titulo'] ?? '', $fonte_display, $tam_titulo, $offwhite, $laranja, $pad_x, $y, $w - $pad_x * 2, $alt_linha );
		$y += $y_apos_titulo;
		if ( $dek_existe ) {
			$y += 28;
			dco_editorial_m2_desenhar_texto_destacado( $im, $spec['texto'], $fonte_corpo, $tam_dek, $offwhite_70, $laranja, $pad_x, $y, $w - $pad_x * 2, $alt_linha_dek );
		}

		dco_editorial_m2_marca_rodape( $im, $w, $h, $spec['numero'] ?? null, $spec['total'] ?? null );
		imagepng( $im, $caminho_destino );
		imagedestroy( $im );
		return true;
	}

	if ( 'cta' === $secao ) {
		$y0 = (int) round( $h * 0.38 );
		$alt_pergunta = dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['titulo'] ?? 'Quer saber mais sobre esse assunto?', $fonte_display, 38, $offwhite, $centro_x, $y0, $w - $pad_x * 2 - 40, 48 );
		$y1 = $y0 + $alt_pergunta + 34;
		$alt_leia = dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['texto'] ?? 'Leia a matéria completa em', $fonte_corpo, 26, $offwhite_70, $centro_x, $y1, $w - $pad_x * 2, 34 );
		$y2 = $y1 + $alt_leia + 18;
		dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['site'] ?? 'descomplicarro.com.br', $fonte_display, 30, $laranja, $centro_x, $y2, $w - $pad_x * 2, 36 );
		dco_editorial_m2_marca_rodape( $im, $w, $h, $spec['numero'] ?? null, $spec['total'] ?? null );
		imagepng( $im, $caminho_destino );
		imagedestroy( $im );
		return true;
	}

	// entenda | questione | decida
	$rotulos = array( 'entenda' => 'Entenda', 'questione' => 'Questione', 'decida' => 'Decida' );
	$y = 72;
	$y += dco_editorial_m2_eyebrow_secao( $im, $rotulos[ $secao ] ?? ucfirst( $secao ), $pad_x, $y, $laranja );

	$tam_corpo = 'stories' === $formato ? 38 : 34;
	$alt_linha = (int) round( $tam_corpo * 1.55 );
	$texto     = $spec['texto'] ?? '';
	$alt_bloco = dco_editorial_m2_altura_texto_destacado( $im, $texto, $fonte_corpo, $tam_corpo, $w - $pad_x * 2, $alt_linha );

	$zona_topo  = $y;
	$zona_livre = $area_fim - $y;
	$y += (int) round( max( 0, $zona_livre - $alt_bloco ) / 2 );
	dco_editorial_m2_barra_lateral( $im, $pad_x - 26, $zona_topo, $area_fim, $laranja );
	$y += $alt_linha;
	dco_editorial_m2_desenhar_texto_destacado( $im, $texto, $fonte_corpo, $tam_corpo, $offwhite_90, $laranja, $pad_x, $y, $w - $pad_x * 2, $alt_linha );

	dco_editorial_m2_marca_rodape( $im, $w, $h, $spec['numero'] ?? null, $spec['total'] ?? null );
	imagepng( $im, $caminho_destino );
	imagedestroy( $im );
	return true;
}

/**
 * Monta os 5 specs fixos (capa/entenda/questione/decida/cta). $formato
 * decide as dimensões; a chave 'tipo' é só um espelho de 'secao' (o
 * orquestrador — inc/editorial-gerador.php, compartilhado com o Modelo 1 —
 * usa 'tipo' pra nomear o arquivo; aqui o nome real de cada seção é o que
 * importa pro conteúdo).
 */
function dco_editorial_specs_modelo2( array $pacote, $formato ) {
	$foto  = dco_editorial_imagem_para_indice( $pacote['pool_imagens'], 0 );
	$total = 'carrossel' === $formato ? 5 : null;
	$specs = array(
		array( 'secao' => 'capa', 'eyebrow' => $pacote['categoria_label'], 'titulo' => $pacote['m2_chamada'] ?: $pacote['titulo'], 'texto' => $pacote['subtitulo'], 'foto' => ! empty( $pacote['m2_usar_foto'] ) ? $foto : null, 'numero' => 1 ),
		array( 'secao' => 'entenda', 'texto' => $pacote['m2_entenda'] ?? '', 'numero' => 2 ),
		array( 'secao' => 'questione', 'texto' => $pacote['m2_questione'] ?? '', 'numero' => 3 ),
		array( 'secao' => 'decida', 'texto' => $pacote['m2_decida'] ?? '', 'numero' => 4 ),
		array( 'secao' => 'cta', 'titulo' => 'Quer saber mais sobre esse assunto?', 'texto' => 'Leia a matéria completa em', 'site' => 'descomplicarro.com.br', 'numero' => 5 ),
	);
	foreach ( $specs as &$spec ) {
		$spec['formato'] = $formato;
		$spec['tipo']    = $spec['secao'];
		$spec['total']   = $total;
	}
	return $specs;
}

function dco_editorial_specs_carrossel_modelo2( array $pacote ) {
	return dco_editorial_specs_modelo2( $pacote, 'carrossel' );
}

/** Mesma estrutura de 5 partes no Stories — sem numeração de página (convenção já usada no Modelo 1). */
function dco_editorial_specs_stories_modelo2( array $pacote ) {
	return dco_editorial_specs_modelo2( $pacote, 'stories' );
}
