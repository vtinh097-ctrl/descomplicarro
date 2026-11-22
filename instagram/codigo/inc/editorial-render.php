<?php
/**
 * DESCOMPLICARRO — renderizador de imagens (carrossel + Stories) da
 * Automação Editorial (Etapa 4).
 *
 * Usa PHP GD puro — NUNCA Playwright/Node/headless-Chromium. Confirmado
 * (documentação da própria Hostinger + teste real neste ambiente) que
 * hospedagem compartilhada/Business não suporta de forma confiável um
 * navegador headless (processo persistente, timeout de 120s, sem acesso
 * root às bibliotecas de sistema que o Chromium exige) — GD, ao
 * contrário, é uma extensão PHP padrão, já disponível por padrão na
 * Hostinger, sem pedido de suporte. As fontes (Space Grotesk Bold, DM
 * Sans Regular — as duas únicas variações realmente usadas no modelo
 * aprovado) vêm junto do plugin em assets/fontes/ (licença OFL, Google
 * Fonts), porque hospedagem compartilhada não tem fontes do sistema.
 *
 * O resultado foi validado visualmente contra o modelo HTML/CSS já
 * aprovado pelo cliente (ver etapa4-modelo-aprovado/) — mesma
 * composição, cores, tipografia e hierarquia, pixel a pixel equivalente
 * no que o GD permite.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_EDITORIAL_GRAFITE', array( 0x25, 0x25, 0x25 ) );
define( 'DCO_EDITORIAL_OFFWHITE', array( 0xF4, 0xF1, 0xEA ) );
define( 'DCO_EDITORIAL_LARANJA', array( 0xC6, 0x5A, 0x32 ) );

function dco_editorial_fonte_display() {
	return DCO_PLUGIN_DIR . 'assets/fontes/SpaceGrotesk-Bold.ttf';
}

function dco_editorial_fonte_corpo() {
	return DCO_PLUGIN_DIR . 'assets/fontes/DMSans-Regular.ttf';
}

function dco_editorial_cor( $im, $rgb, $alpha = 0 ) {
	return imagecolorallocatealpha( $im, $rgb[0], $rgb[1], $rgb[2], $alpha );
}

/**
 * Redimensiona e corta a imagem de origem para preencher exatamente
 * $w x $h, igual a object-fit:cover no CSS — nunca distorce, sempre
 * preenche e centraliza o corte.
 */
function dco_editorial_compor_foto_cover( $destino, $caminho_origem, $x, $y, $w, $h ) {
	if ( ! file_exists( $caminho_origem ) ) {
		return false;
	}
	$info = getimagesize( $caminho_origem );
	if ( ! $info ) {
		return false;
	}
	list( $ow, $oh ) = $info;
	$origem = null;
	switch ( $info[2] ) {
		case IMAGETYPE_JPEG:
			$origem = @imagecreatefromjpeg( $caminho_origem );
			break;
		case IMAGETYPE_PNG:
			$origem = @imagecreatefrompng( $caminho_origem );
			break;
		case IMAGETYPE_WEBP:
			$origem = @imagecreatefromwebp( $caminho_origem );
			break;
	}
	if ( ! $origem ) {
		return false;
	}

	$escala = max( $w / $ow, $h / $oh );
	$rw     = (int) ceil( $ow * $escala );
	$rh     = (int) ceil( $oh * $escala );

	$redimensionada = imagecreatetruecolor( $rw, $rh );
	imagecopyresampled( $redimensionada, $origem, 0, 0, 0, 0, $rw, $rh, $ow, $oh );

	$corte_x = (int) ( ( $rw - $w ) / 2 );
	$corte_y = (int) ( ( $rh - $h ) / 2 );
	imagecopy( $destino, $redimensionada, $x, $y, $corte_x, $corte_y, $w, $h );

	imagedestroy( $origem );
	imagedestroy( $redimensionada );
	return true;
}

function dco_editorial_quebrar_linhas( $texto, $fonte, $tamanho, $max_width ) {
	$palavras    = preg_split( '/\s+/u', trim( (string) $texto ) );
	$linhas      = array();
	$linha_atual = '';
	foreach ( $palavras as $palavra ) {
		$teste    = '' === $linha_atual ? $palavra : $linha_atual . ' ' . $palavra;
		$bbox     = imagettfbbox( $tamanho, 0, $fonte, $teste );
		$largura  = abs( $bbox[2] - $bbox[0] );
		if ( $largura > $max_width && '' !== $linha_atual ) {
			$linhas[]    = $linha_atual;
			$linha_atual = $palavra;
		} else {
			$linha_atual = $teste;
		}
	}
	if ( '' !== $linha_atual ) {
		$linhas[] = $linha_atual;
	}
	return $linhas;
}

/** Igual a dco_editorial_desenhar_paragrafo, mas cada linha centralizada em torno de $centro_x. */
function dco_editorial_desenhar_paragrafo_centralizado( $im, $texto, $fonte, $tamanho, $cor, $centro_x, $y, $max_width, $altura_linha ) {
	$linhas = dco_editorial_quebrar_linhas( $texto, $fonte, $tamanho, $max_width );
	foreach ( $linhas as $i => $linha ) {
		$bbox    = imagettfbbox( $tamanho, 0, $fonte, $linha );
		$largura = abs( $bbox[2] - $bbox[0] );
		$x       = (int) round( $centro_x - $largura / 2 );
		imagettftext( $im, $tamanho, 0, $x, $y + $i * $altura_linha, $cor, $fonte, $linha );
	}
	return count( $linhas ) * $altura_linha;
}

/**
 * Uma linha de texto maiúsculo com espaçamento entre letras (estilo
 * "eyebrow"), centralizada em torno de $centro_x — SEM passar pela
 * quebra de linha por palavra, porque o espaço fino (U+2009) usado para
 * simular o letter-spacing é tratado como espaço em branco pelo quebrador
 * de linha, o que faz a palavra seguinte colar na anterior (bug já visto
 * e corrigido: "MATÉRIA COMPLETA" virava "MATÉRIACOMPLETA").
 */
function dco_editorial_desenhar_eyebrow_centralizado( $im, $texto, $fonte, $tamanho, $cor, $centro_x, $y ) {
	$espacado = implode( "\xE2\x80\x89", mb_str_split( mb_strtoupper( $texto ) ) );
	$bbox     = imagettfbbox( $tamanho, 0, $fonte, $espacado );
	$largura  = abs( $bbox[2] - $bbox[0] );
	imagettftext( $im, $tamanho, 0, (int) round( $centro_x - $largura / 2 ), $y, $cor, $fonte, $espacado );
}

/** Desenha um parágrafo quebrado em linhas; devolve a altura total ocupada. */
function dco_editorial_desenhar_paragrafo( $im, $texto, $fonte, $tamanho, $cor, $x, $y, $max_width, $altura_linha, $max_linhas = 0 ) {
	$linhas = dco_editorial_quebrar_linhas( $texto, $fonte, $tamanho, $max_width );
	if ( $max_linhas > 0 && count( $linhas ) > $max_linhas ) {
		$linhas = array_slice( $linhas, 0, $max_linhas );
		$ultima = $linhas[ count( $linhas ) - 1 ];
		$linhas[ count( $linhas ) - 1 ] = rtrim( $ultima ) . '…';
	}
	foreach ( $linhas as $i => $linha ) {
		imagettftext( $im, $tamanho, 0, $x, $y + $i * $altura_linha, $cor, $fonte, $linha );
	}
	return count( $linhas ) * $altura_linha;
}

/** Selo "eyebrow" (categoria): texto maiúsculo com espaçamento simulado; opcionalmente com fundo (pílula). */
function dco_editorial_desenhar_eyebrow( $im, $texto, $x, $y, $cor_texto, $cor_fundo = null, $tamanho = 15 ) {
	$espacado = implode( "\xE2\x80\x89", mb_str_split( mb_strtoupper( $texto ) ) ); // thin space U+2009
	if ( null !== $cor_fundo ) {
		$bbox  = imagettfbbox( $tamanho, 0, dco_editorial_fonte_display(), $espacado );
		$pad_x = 18;
		$w     = abs( $bbox[2] - $bbox[0] ) + $pad_x * 2;
		$h     = 34;
		imagefilledrectangle( $im, $x, $y, $x + $w, $y + $h, $cor_fundo );
		imagettftext( $im, $tamanho, 0, $x + $pad_x, $y + $h - 10, $cor_texto, dco_editorial_fonte_display(), $espacado );
		return $h;
	}
	imagettftext( $im, $tamanho, 0, $x, $y, $cor_texto, dco_editorial_fonte_display(), $espacado );
	return $tamanho;
}

/** Cantos estilo "mira de foco" sobre a foto (igual ao modelo aprovado). */
function dco_editorial_desenhar_frame_marks( $im, $x, $y, $w, $h ) {
	$cor  = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$tam  = 28;
	$esp  = 3;
	imagefilledrectangle( $im, $x + 28, $y + 28, $x + 28 + $tam, $y + 28 + $esp, $cor );
	imagefilledrectangle( $im, $x + 28, $y + 28, $x + 28 + $esp, $y + 28 + $tam, $cor );
	$bx = $x + $w - 28;
	$by = $y + $h - 28;
	imagefilledrectangle( $im, $bx - $tam, $by - $esp, $bx, $by, $cor );
	imagefilledrectangle( $im, $bx - $esp, $by - $tam, $bx, $by, $cor );
}

/** Rodapé grafite do carrossel: marca em duas cores + numeração "N/total". Sempre presente, inclusive na capa. */
function dco_editorial_desenhar_rodape( $im, $w, $h_total, $numero, $total, $altura_rodape = 92 ) {
	$y0      = $h_total - $altura_rodape;
	$grafite = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$laranja = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	$cinza_pag = dco_editorial_cor( $im, array( 244, 241, 234 ), 50 );

	imagefilledrectangle( $im, 0, $y0, $w, $h_total, $grafite );

	$tam_marca  = 15;
	$baseline_y = $y0 + (int) round( $altura_rodape / 2 ) + 8;
	imagettftext( $im, $tam_marca, 0, 44, $baseline_y, $offwhite, dco_editorial_fonte_display(), 'DESCOMPLI' );
	$bbox   = imagettfbbox( $tam_marca, 0, dco_editorial_fonte_display(), 'DESCOMPLI' );
	$avanco = abs( $bbox[2] - $bbox[0] );
	imagettftext( $im, $tam_marca, 0, 44 + $avanco, $baseline_y, $laranja, dco_editorial_fonte_display(), 'CARRO' );

	$rotulo_pag    = "$numero/$total";
	$bbox_pag      = imagettfbbox( 11, 0, dco_editorial_fonte_display(), $rotulo_pag );
	$largura_pag   = abs( $bbox_pag[2] - $bbox_pag[0] );
	imagettftext( $im, 11, 0, $w - 44 - $largura_pag, $baseline_y, $cinza_pag, dco_editorial_fonte_display(), $rotulo_pag );
}

/** Marca de rodapé simples (sem numeração), usada nos slides de conteúdo do Stories. */
function dco_editorial_desenhar_marca_simples( $im, $w, $h_total, $altura = 80 ) {
	$y0       = $h_total - $altura;
	$grafite  = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	imagefilledrectangle( $im, 0, $y0, $w, $h_total, $grafite );
	$tam = 16;
	$baseline_y = $y0 + (int) round( $altura / 2 ) + 8;
	imagettftext( $im, $tam, 0, 56, $baseline_y, $offwhite, dco_editorial_fonte_display(), 'DESCOMPLI' );
	$bbox   = imagettfbbox( $tam, 0, dco_editorial_fonte_display(), 'DESCOMPLI' );
	$avanco = abs( $bbox[2] - $bbox[0] );
	imagettftext( $im, $tam, 0, 56 + $avanco, $baseline_y, $laranja, dco_editorial_fonte_display(), 'CARRO' );
}

/**
 * Renderiza UM slide a partir de uma especificação e salva como PNG.
 *
 * $spec (todas as chaves com default seguro):
 *   formato        'carrossel' (1080x1350) | 'stories' (1080x1920)
 *   tipo           'capa' | 'conteudo' | 'cta'
 *   foto           caminho absoluto da imagem de origem (null = sem foto, só no 'cta')
 *   proporcao_foto fração da altura ocupada pela foto (ignorado em 'cta')
 *   badge_canto    texto do selo sobre a foto, canto superior direito (só 'capa')
 *   eyebrow        texto da categoria acima do título (opcional)
 *   titulo         título do slide
 *   texto          corpo do texto (opcional)
 *   numero / total numeração do carrossel (ignorado no Stories)
 *   cta_seta       true = mostra "LEIA A MATÉRIA COMPLETA" + seta laranja (Stories)
 *   dominio        texto do domínio, só no 'cta' do carrossel
 */
function dco_editorial_renderizar_slide( array $spec, $caminho_destino ) {
	$formato = $spec['formato'] ?? 'carrossel';
	$tipo    = $spec['tipo'] ?? 'conteudo';
	$w       = 'stories' === $formato ? 1080 : 1080;
	$h       = 'stories' === $formato ? 1920 : 1350;

	$im = imagecreatetruecolor( $w, $h );
	$offwhite = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
	$grafite  = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE );
	$grafite_75 = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE, 32 );
	$grafite_82 = dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE, 22 );
	$laranja  = dco_editorial_cor( $im, DCO_EDITORIAL_LARANJA );
	imagesavealpha( $im, true );
	imagefilledrectangle( $im, 0, 0, $w, $h, $offwhite );

	$fonte_display = dco_editorial_fonte_display();
	$fonte_corpo   = dco_editorial_fonte_corpo();
	$pad_x         = 'stories' === $formato ? 56 : 48;

	if ( 'cta' === $tipo ) {
		imagefilledrectangle( $im, 0, 0, $w, $h, $grafite );
		$offwhite_txt = dco_editorial_cor( $im, DCO_EDITORIAL_OFFWHITE );
		$centro_x     = (int) round( $w / 2 );
		$max_texto    = $w - $pad_x * 2 - 80;
		$cy           = (int) round( $h * 0.46 );
		dco_editorial_desenhar_eyebrow_centralizado( $im, $spec['eyebrow'] ?? 'Matéria completa', $fonte_display, 18, $laranja, $centro_x, $cy - 70 );
		$alt = dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['titulo'] ?? '', $fonte_display, 30, $offwhite_txt, $centro_x, $cy, $max_texto, 42 );
		dco_editorial_desenhar_paragrafo_centralizado( $im, $spec['dominio'] ?? 'descomplicarro.com.br', $fonte_display, 22, $laranja, $centro_x, $cy + $alt + 36, $max_texto, 30 );
		if ( 'carrossel' === $formato ) {
			dco_editorial_desenhar_rodape( $im, $w, $h, $spec['numero'] ?? 1, $spec['total'] ?? 1 );
		}
		imagepng( $im, $caminho_destino );
		imagedestroy( $im );
		return true;
	}

	$altura_rodape = 'carrossel' === $formato ? 92 : 0;
	$prop_foto = $spec['proporcao_foto'] ?? ( 'capa' === $tipo ? 0.62 : 0.68 );
	$alt_foto  = (int) round( $h * $prop_foto );

	if ( ! empty( $spec['foto'] ) ) {
		dco_editorial_compor_foto_cover( $im, $spec['foto'], 0, 0, $w, $alt_foto );
		dco_editorial_desenhar_frame_marks( $im, 0, 0, $w, $alt_foto );
	} else {
		imagefilledrectangle( $im, 0, 0, $w, $alt_foto, dco_editorial_cor( $im, DCO_EDITORIAL_GRAFITE ) );
	}

	if ( ! empty( $spec['badge_canto'] ) ) {
		$espacado_badge = implode( "\xE2\x80\x89", mb_str_split( mb_strtoupper( $spec['badge_canto'] ) ) );
		$bbox_badge     = imagettfbbox( 15, 0, $fonte_display, $espacado_badge );
		$largura_badge  = abs( $bbox_badge[2] - $bbox_badge[0] );
		dco_editorial_desenhar_eyebrow( $im, $spec['badge_canto'], $w - 36 - $largura_badge, 42, $laranja );
	}

	$y = $alt_foto + ( 'capa' === $tipo ? 56 : 44 );
	$area_texto_fim = $h - $altura_rodape;

	if ( ! empty( $spec['eyebrow'] ) ) {
		dco_editorial_desenhar_eyebrow( $im, $spec['eyebrow'], $pad_x, $y, $laranja );
		$y += 36;
	}

	$tam_titulo = 'stories' === $formato ? ( 'capa' === $tipo ? 36 : 34 ) : ( 'capa' === $tipo ? 32 : 28 );
	$y += (int) round( $tam_titulo * 1.05 );
	$alt_titulo = dco_editorial_desenhar_paragrafo( $im, $spec['titulo'] ?? '', $fonte_display, $tam_titulo, $grafite, $pad_x, $y, $w - $pad_x * 2, (int) round( $tam_titulo * 1.28 ), 3 );
	$y += $alt_titulo + 16;

	if ( ! empty( $spec['texto'] ) ) {
		$tam_corpo = 'stories' === $formato ? 22 : 19;
		$y += dco_editorial_desenhar_paragrafo( $im, $spec['texto'], $fonte_corpo, $tam_corpo, $grafite_75, $pad_x, $y, $w - $pad_x * 2, (int) round( $tam_corpo * 1.5 ), 4 );
	}

	if ( 'carrossel' === $formato ) {
		dco_editorial_desenhar_rodape( $im, $w, $h, $spec['numero'] ?? 1, $spec['total'] ?? 1 );
	} elseif ( ! empty( $spec['cta_seta'] ) ) {
		$cy = $y + 36;
		dco_editorial_desenhar_paragrafo_centralizado( $im, 'LEIA A MATÉRIA COMPLETA', $fonte_display, 24, $laranja, (int) round( $w / 2 ), $cy, $w - $pad_x * 2, 30 );
		imagettftext( $im, 34, 0, (int) round( $w / 2 ) - 12, $cy + 56, $laranja, $fonte_display, '↓' );
		if ( 'conteudo' === $tipo ) {
			dco_editorial_desenhar_marca_simples( $im, $w, $h );
		}
	}

	imagepng( $im, $caminho_destino );
	imagedestroy( $im );
	return true;
}
