<?php
/**
 * DESCOMPLICARRO — MODELO 2: Editorial Educativo e Técnico.
 *
 * Usado SOMENTE para as editorias Técnica e Guias — ver a seleção
 * automática em inc/editorial-gerador.php. O MODELO 1 (inc/editorial-
 * render.php, "Editorial Fotográfico" — Notícias/Reviews/Bastidores)
 * continua INTACTO: este arquivo não altera, nem chama para sobrepor,
 * nenhuma função dele. Reaproveita só as primitivas de desenho
 * genéricas já existentes (cor, fonte, texto, corte de foto) — nenhum
 * layout do Modelo 1 é reutilizado.
 *
 * PROPOSTA VISUAL (deliberadamente diferente do Modelo 1): a foto deixa
 * de ser protagonista — aparece pequena na capa só quando ajuda a situar
 * o assunto, nunca nos slides de conteúdo. A barra laranja grossa (10px)
 * no topo de todo slide de conteúdo é a assinatura visual do Modelo 2,
 * no lugar da foto de fundo do Modelo 1. Três composições de conteúdo,
 * escolhidas automaticamente pelo formato de cada frase (nunca à mão,
 * nunca por matéria específica):
 *   - DADO: a frase já tem um número/unidade (ex.: "31% de potência") →
 *     número gigante em destaque + a frase completa como legenda.
 *   - PERGUNTA: a frase termina em "?" → citação central, estilo cartão.
 *   - EXPLICAÇÃO: qualquer outra frase → selo numerado + texto.
 * Isso dá "composições variadas entre os slides" sem precisar de dados
 * estruturados novos — continua usando exatamente os mesmos itens
 * (título/texto) que inc/editorial-selecao.php já monta.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_EDITORIAL_M2_BARRA_ALTURA', 10 );

/** Decide a composição de um item pelo formato da própria frase — nunca por matéria específica. */
function dco_editorial_m2_tipo_do_item( $titulo ) {
	if ( preg_match( '/\d+([.,]\d+)?\s*(%|km\/l|km\/h|kgfm|cv|mm|cm|m|kg|l)\b/ui', $titulo ) ) {
		return 'dado';
	}
	if ( '?' === mb_substr( rtrim( $titulo ), -1 ) ) {
		return 'pergunta';
	}
	return 'explicacao';
}

/** Extrai o primeiro número+unidade da frase, pra exibir gigante no slide de "dado". */
function dco_editorial_m2_extrair_dado( $titulo ) {
	if ( preg_match( '/(\d+([.,]\d+)?\s*(%|km\/l|km\/h|kgfm|cv|mm|cm|kg|l|m\b))/ui', $titulo, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function dco_editorial_m2_rotulo_formato( $categoria_slug ) {
	return 'tecnica' === $categoria_slug ? 'Explicação técnica' : 'Guia rápido';
}

function dco_editorial_m2_barra_topo( $im, $w ) {
	$laranja = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	imagefilledrectangle( $im, 0, 0, $w, DCO_EDITORIAL_M2_BARRA_ALTURA, $laranja );
}

function dco_editorial_m2_circulo_numerado( $im, $numero, $cx, $cy, $raio = 34 ) {
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	imagefilledellipse( $im, $cx, $cy, $raio * 2, $raio * 2, $laranja );
	$fonte  = dco_editorial_fonte_display();
	$rotulo = sprintf( '%02d', $numero );
	$bbox   = imagettfbbox( 22, 0, $fonte, $rotulo );
	$lw     = abs( $bbox[2] - $bbox[0] );
	$lh     = abs( $bbox[1] - $bbox[7] );
	imagettftext( $im, 22, 0, (int) round( $cx - $lw / 2 ), (int) round( $cy + $lh / 2 ), $offwhite, $fonte, $rotulo );
}

function dco_editorial_specs_carrossel_modelo2( array $pacote ) {
	$itens = $pacote['itens'];
	$total = count( $itens ) + 2;
	$pool  = $pacote['pool_imagens'];
	$rotulo = dco_editorial_m2_rotulo_formato( $pacote['categoria_slug'] );

	$specs   = array();
	$specs[] = array(
		'formato'        => 'carrossel',
		'tipo'           => 'capa',
		'foto'           => dco_editorial_imagem_para_indice( $pool, 0 ),
		'badge_canto'    => $pacote['categoria_label'],
		'rotulo_formato' => $rotulo,
		'titulo'         => $pacote['titulo'],
		'texto'          => $pacote['subtitulo'],
		'numero'         => 1,
		'total'          => $total,
	);

	foreach ( $itens as $i => $item ) {
		$specs[] = array(
			'formato'     => 'carrossel',
			'tipo'        => 'conteudo',
			'eyebrow'     => $pacote['categoria_label'],
			'titulo'      => $item['titulo'],
			'texto'       => $item['texto'],
			'numero_item' => $i + 1,
			'numero'      => $i + 2,
			'total'       => $total,
		);
	}

	$specs[] = array(
		'formato' => 'carrossel',
		'tipo'    => 'cta',
		'eyebrow' => 'Matéria completa',
		'titulo'  => 'Leia a matéria completa no site',
		'dominio' => 'descomplicarro.com.br',
		'numero'  => $total,
		'total'   => $total,
	);

	return $specs;
}

function dco_editorial_specs_stories_modelo2( array $pacote ) {
	$pool  = $pacote['pool_imagens'];
	$itens = array_slice( $pacote['itens'], 0, DCO_EDITORIAL_MAX_ITENS_STORIES );
	$rotulo = dco_editorial_m2_rotulo_formato( $pacote['categoria_slug'] );

	$specs   = array();
	$specs[] = array(
		'formato'        => 'stories',
		'tipo'           => 'capa',
		'foto'           => dco_editorial_imagem_para_indice( $pool, 0 ),
		'badge_canto'    => $pacote['categoria_label'],
		'rotulo_formato' => $rotulo,
		'titulo'         => $pacote['titulo'],
		'texto'          => $pacote['subtitulo'],
		'cta_seta'       => true,
	);

	foreach ( $itens as $i => $item ) {
		$specs[] = array(
			'formato'     => 'stories',
			'tipo'        => 'conteudo',
			'eyebrow'     => $pacote['categoria_label'],
			'titulo'      => $item['titulo'],
			'texto'       => $item['texto'],
			'numero_item' => $i + 1,
			'cta_seta'    => true,
		);
	}

	return $specs;
}

/**
 * Renderiza UM slide do Modelo 2 a partir de uma especificação, no mesmo
 * espírito de dco_editorial_renderizar_slide() do Modelo 1 — mesma
 * assinatura de uso, composições totalmente diferentes.
 *
 * $spec:
 *   formato         'carrossel' (1080×1350) | 'stories' (1080×1920)
 *   tipo            'capa' | 'conteudo' | 'cta'
 *   foto            caminho local (opcional — só a capa usa)
 *   badge_canto     categoria, mostrada sobre a foto da capa
 *   rotulo_formato  "Guia rápido" / "Explicação técnica"
 *   titulo / texto  mesmo conteúdo que o Modelo 1 recebe
 *   numero_item     posição do item dentro do conjunto (pro selo numerado)
 *   numero / total  numeração do carrossel
 *   cta_seta        true = termina com "LEIA A MATÉRIA COMPLETA" + seta (Stories)
 *   dominio         domínio mostrado no CTA do carrossel
 */
function dco_editorial_renderizar_slide_modelo2( array $spec, $caminho_destino ) {
	$formato = $spec['formato'] ?? 'carrossel';
	$tipo    = $spec['tipo'] ?? 'conteudo';
	$w       = 1080;
	$h       = 'stories' === $formato ? 1920 : 1350;

	$im       = imagecreatetruecolor( $w, $h );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$grafite  = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE );
	$grafite_75 = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE, 32 );
	$offwhite_75 = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE, 32 );
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	imagesavealpha( $im, true );
	imagefilledrectangle( $im, 0, 0, $w, $h, $offwhite );

	$fonte_display = dco_editorial_fonte_display();
	$fonte_corpo   = dco_editorial_fonte_corpo();
	$pad_x         = 'stories' === $formato ? 56 : 48;
	$altura_rodape = 'carrossel' === $formato ? 92 : 0;
	$centro_x      = (int) round( $w / 2 );

	// -------------------------------------------------------------
	// CAPA
	// -------------------------------------------------------------
	if ( 'capa' === $tipo ) {
		$alt_foto = (int) round( $h * ( 'stories' === $formato ? 0.30 : 0.28 ) );
		if ( ! empty( $spec['foto'] ) ) {
			dco_editorial_compor_foto_cover( $im, $spec['foto'], 0, 0, $w, $alt_foto );
			dco_editorial_desenhar_frame_marks( $im, 0, 0, $w, $alt_foto );
			if ( ! empty( $spec['badge_canto'] ) ) {
				$esp = implode( "\xE2\x80\x89", mb_str_split( mb_strtoupper( $spec['badge_canto'] ) ) );
				$bbox = imagettfbbox( 15, 0, $fonte_display, $esp );
				$lw   = abs( $bbox[2] - $bbox[0] );
				dco_editorial_desenhar_eyebrow( $im, $spec['badge_canto'], $w - 36 - $lw, 42, $laranja );
			}
		} else {
			$alt_foto = 0;
		}

		imagefilledrectangle( $im, 0, $alt_foto, $w, $alt_foto + DCO_EDITORIAL_M2_BARRA_ALTURA, $laranja );

		$y = $alt_foto + DCO_EDITORIAL_M2_BARRA_ALTURA + 44;
		if ( ! empty( $spec['rotulo_formato'] ) ) {
			dco_editorial_desenhar_eyebrow( $im, $spec['rotulo_formato'], $pad_x, $y, $laranja );
			$y += 40;
		}
		$tam_titulo = 'stories' === $formato ? 44 : 38;
		$y += (int) round( $tam_titulo * 1.05 );
		$alt_titulo = dco_editorial_desenhar_paragrafo( $im, $spec['titulo'] ?? '', $fonte_display, $tam_titulo, $grafite, $pad_x, $y, $w - $pad_x * 2, (int) round( $tam_titulo * 1.25 ), 4 );
		$y += $alt_titulo + 20;
		if ( ! empty( $spec['texto'] ) ) {
			$tam_corpo = 'stories' === $formato ? 24 : 21;
			$y += dco_editorial_desenhar_paragrafo( $im, $spec['texto'], $fonte_corpo, $tam_corpo, $grafite_75, $pad_x, $y, $w - $pad_x * 2, (int) round( $tam_corpo * 1.5 ), 3 );
		}

		if ( 'carrossel' === $formato ) {
			dco_editorial_desenhar_rodape( $im, $w, $h, $spec['numero'] ?? 1, $spec['total'] ?? 1 );
		} elseif ( ! empty( $spec['cta_seta'] ) ) {
			$cy = $y + 36;
			dco_editorial_desenhar_paragrafo_centralizado( $im, 'LEIA A MATÉRIA COMPLETA', $fonte_display, 24, $laranja, $centro_x, $cy, $w - $pad_x * 2, 30 );
			imagettftext( $im, 34, 0, $centro_x - 12, $cy + 56, $laranja, $fonte_display, '↓' );
		}

		imagepng( $im, $caminho_destino );
		imagedestroy( $im );
		return true;
	}

	// -------------------------------------------------------------
	// CTA
	// -------------------------------------------------------------
	if ( 'cta' === $tipo ) {
		dco_editorial_m2_barra_topo( $im, $w );
		$cy = (int) round( $h * 0.44 );
		dco_editorial_desenhar_eyebrow_centralizado( $im, $spec['eyebrow'] ?? 'Matéria completa', $fonte_display, 18, $laranja, $centro_x, $cy - 70 );
		$alt = dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['titulo'] ?? 'Leia a matéria completa no site', $fonte_display, 34, $grafite, $centro_x, $cy, $w - $pad_x * 2 - 60, 44 );
		dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['dominio'] ?? 'descomplicarro.com.br', $fonte_display, 22, $laranja, $centro_x, $cy + $alt + 36, $w - $pad_x * 2, 30 );
		if ( 'carrossel' === $formato ) {
			dco_editorial_desenhar_rodape( $im, $w, $h, $spec['numero'] ?? 1, $spec['total'] ?? 1 );
		}
		imagepng( $im, $caminho_destino );
		imagedestroy( $im );
		return true;
	}

	// -------------------------------------------------------------
	// CONTEÚDO — escolhe a composição pelo formato da própria frase
	// -------------------------------------------------------------
	dco_editorial_m2_barra_topo( $im, $w );
	$area_fim = $h - $altura_rodape - ( ( 'stories' === $formato && ! empty( $spec['cta_seta'] ) ) ? 160 : 0 );
	$titulo   = $spec['titulo'] ?? '';
	$tipo_item = dco_editorial_m2_tipo_do_item( $titulo );

	if ( 'dado' === $tipo_item ) {
		imagefilledrectangle( $im, 0, DCO_EDITORIAL_M2_BARRA_ALTURA, $w, $area_fim, $grafite );
		if ( ! empty( $spec['eyebrow'] ) ) {
			dco_editorial_desenhar_eyebrow( $im, $spec['eyebrow'], $pad_x, 48, $laranja );
		}
		$dado         = dco_editorial_m2_extrair_dado( $titulo );
		$tam_numero   = 'stories' === $formato ? 150 : 120;
		$tam_legenda  = 'stories' === $formato ? 26 : 22;
		$alt_legenda  = (int) round( $tam_legenda * 1.5 );
		$linhas_legenda = dco_editorial_quebrar_linhas( $titulo, $fonte_corpo, $tam_legenda, $w - $pad_x * 2 - 40 );
		$bloco_altura = ( $dado ? $tam_numero + 36 : 0 ) + count( $linhas_legenda ) * $alt_legenda;
		$topo_zona    = 48 + 60; // abaixo do eyebrow
		$cy           = $topo_zona + (int) round( ( ( $area_fim - $topo_zona ) - $bloco_altura ) / 2 ) + $tam_numero;
		if ( $dado ) {
			dco_editorial_desenhar_paragrafo_centralizado( $im, $dado, $fonte_display, $tam_numero, $laranja, $centro_x, $cy, $w - $pad_x * 2, $tam_numero );
		}
		$y_legenda = $cy + 36;
		dco_editorial_desenhar_paragrafo_centralizado( $im, $titulo, $fonte_corpo, $tam_legenda, $offwhite_75, $centro_x, $y_legenda, $w - $pad_x * 2 - 40, $alt_legenda );
	} elseif ( 'pergunta' === $tipo_item ) {
		if ( ! empty( $spec['eyebrow'] ) ) {
			dco_editorial_desenhar_eyebrow( $im, $spec['eyebrow'], $pad_x, 48, $laranja );
		}
		$cy = (int) round( ( DCO_EDITORIAL_M2_BARRA_ALTURA + $area_fim ) / 2 ) - 40;
		$tam = 'stories' === $formato ? 46 : 38;
		$alt = dco_editorial_desenhar_paragrafo_centralizado( $im, $titulo, $fonte_display, $tam, $grafite, $centro_x, $cy, $w - $pad_x * 2 - 40, (int) round( $tam * 1.3 ) );
		$y_barra = $cy + $alt + 28;
		imagefilledrectangle( $im, $centro_x - 40, $y_barra, $centro_x + 40, $y_barra + 6, $laranja );
	} else { // explicacao
		$cx_circulo = $pad_x + 34;
		$cy_circulo = 48 + 34;
		dco_editorial_m2_circulo_numerado( $im, $spec['numero_item'] ?? 1, $cx_circulo, $cy_circulo );
		if ( ! empty( $spec['eyebrow'] ) ) {
			dco_editorial_desenhar_eyebrow( $im, $spec['eyebrow'], $cx_circulo + 50, 42, $laranja );
		}

		$tam_titulo = 'stories' === $formato ? 36 : 30;
		$alt_linha_titulo = (int) round( $tam_titulo * 1.25 );
		$linhas_titulo = dco_editorial_quebrar_linhas( $titulo, $fonte_display, $tam_titulo, $w - $pad_x * 2 );
		$bloco_altura  = count( $linhas_titulo ) * $alt_linha_titulo;

		$tam_corpo = 'stories' === $formato ? 23 : 19;
		$alt_linha_corpo = (int) round( $tam_corpo * 1.5 );
		$linhas_corpo = array();
		if ( ! empty( $spec['texto'] ) ) {
			$linhas_corpo = dco_editorial_quebrar_linhas( $spec['texto'], $fonte_corpo, $tam_corpo, $w - $pad_x * 2 );
			$bloco_altura += 20 + count( $linhas_corpo ) * $alt_linha_corpo;
		}

		$topo_zona = $cy_circulo + 34 + 40;
		$y = $topo_zona + (int) round( max( 0, ( $area_fim - $topo_zona ) - $bloco_altura ) / 2 );
		$y += dco_editorial_desenhar_paragrafo( $im, $titulo, $fonte_display, $tam_titulo, $grafite, $pad_x, $y, $w - $pad_x * 2, $alt_linha_titulo, 4 );
		if ( ! empty( $spec['texto'] ) ) {
			$y += 20;
			dco_editorial_desenhar_paragrafo( $im, $spec['texto'], $fonte_corpo, $tam_corpo, $grafite_75, $pad_x, $y, $w - $pad_x * 2, $alt_linha_corpo, 4 );
		}
	}

	if ( 'carrossel' === $formato ) {
		dco_editorial_desenhar_rodape( $im, $w, $h, $spec['numero'] ?? 1, $spec['total'] ?? 1 );
	} elseif ( ! empty( $spec['cta_seta'] ) ) {
		$cy = $area_fim + 40;
		dco_editorial_desenhar_paragrafo_centralizado( $im, 'LEIA A MATÉRIA COMPLETA', $fonte_display, 22, $laranja, $centro_x, $cy, $w - $pad_x * 2, 28 );
		imagettftext( $im, 30, 0, $centro_x - 10, $cy + 50, $laranja, $fonte_display, '↓' );
		dco_editorial_desenhar_marca_simples( $im, $w, $h );
	}

	imagepng( $im, $caminho_destino );
	imagedestroy( $im );
	return true;
}
