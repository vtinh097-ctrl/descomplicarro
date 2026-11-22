<?php
/**
 * DESCOMPLICARRO — monta a sugestão de legenda do carrossel/Stories.
 * Linguagem editorial simples, nunca entrega todo o conteúdo da matéria,
 * sempre incentiva a leitura completa. Hashtags reaproveitam as tags que
 * a própria matéria já tem no WordPress — nunca inventadas; sem tags
 * cadastradas, usa palavras relevantes do próprio título.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_editorial_texto_para_hashtag( $texto ) {
	$texto = remove_accents( $texto );
	$texto = preg_replace( '/[^A-Za-z0-9]/', '', $texto );
	return $texto;
}

function dco_editorial_hashtags_do_titulo( $titulo, $limite = 6 ) {
	$parar = array( 'de', 'da', 'do', 'das', 'dos', 'e', 'a', 'o', 'as', 'os', 'em', 'para', 'com', 'no', 'na', 'nos', 'nas', 'que', 'um', 'uma', 'por', 'ao', 'confirma', 'inaugura' );
	$palavras = preg_split( '/\s+/u', wp_strip_all_tags( $titulo ) );
	$tags     = array();
	foreach ( $palavras as $palavra ) {
		$limpa = trim( $palavra, ":;,.!?()\u{201C}\u{201D}\"'" );
		if ( mb_strlen( $limpa ) < 3 || in_array( mb_strtolower( $limpa ), $parar, true ) ) {
			continue;
		}
		$tag = dco_editorial_texto_para_hashtag( $limpa );
		if ( $tag && ! in_array( $tag, $tags, true ) ) {
			$tags[] = $tag;
		}
		if ( count( $tags ) >= $limite ) {
			break;
		}
	}
	return $tags;
}

/** Remove os marcadores **destaque** do Modelo 2, mantendo só o texto. */
function dco_editorial_remover_marcadores_destaque( $texto ) {
	return trim( preg_replace( '/\*\*(.+?)\*\*/u', '$1', (string) $texto ) );
}

function dco_editorial_montar_legenda( array $pacote ) {
	$linhas = array();

	$linhas[] = trim( $pacote['titulo'] );
	$linhas[] = '';

	if ( ! empty( $pacote['subtitulo'] ) ) {
		$linhas[] = trim( $pacote['subtitulo'] );
		$linhas[] = '';
	}

	// MODELO 2 (Técnica/Guias) usa a estrutura fixa "Entenda. Questione.
	// Decida." — a legenda resume ESSE conteúdo, nunca o `itens` genérico
	// (que pertence só ao caminho H2 do Modelo 1/comum e nem é usado na
	// geração dos slides quando o Modelo 2 está ativo).
	$usa_modelo2 = function_exists( 'dco_editorial_modelo_visual_da_categoria' )
		&& 2 === dco_editorial_modelo_visual_da_categoria( $pacote['categoria_slug'] ?? '' );

	if ( $usa_modelo2 ) {
		$rotulos = array( 'm2_entenda' => 'Entenda', 'm2_questione' => 'Questione', 'm2_decida' => 'Decida' );
		foreach ( $rotulos as $chave => $rotulo ) {
			if ( ! empty( $pacote[ $chave ] ) ) {
				$linhas[] = $rotulo . ': ' . dco_editorial_remover_marcadores_destaque( $pacote[ $chave ] );
			}
		}
	} else {
		foreach ( array_slice( $pacote['itens'], 0, 4 ) as $item ) {
			$trecho = trim( $item['titulo'] );
			if ( ! empty( $item['texto'] ) ) {
				$trecho .= ' — ' . trim( $item['texto'] );
			}
			$linhas[] = $trecho;
		}
	}
	$linhas[] = '';

	$linhas[] = 'Leia a matéria completa — link na bio.';
	$linhas[] = '';

	if ( ! empty( $pacote['tags'] ) ) {
		$hashtags = array();
		foreach ( $pacote['tags'] as $tag ) {
			$h = dco_editorial_texto_para_hashtag( $tag );
			if ( $h ) {
				$hashtags[] = '#' . $h;
			}
		}
	} else {
		$hashtags = array_map( fn( $t ) => '#' . $t, dco_editorial_hashtags_do_titulo( $pacote['titulo'] ) );
	}
	if ( ! empty( $hashtags ) ) {
		$linhas[] = implode( ' ', $hashtags );
	}

	return implode( "\n", $linhas );
}
