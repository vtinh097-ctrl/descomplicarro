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

function dco_editorial_montar_legenda( array $pacote ) {
	$linhas = array();

	$linhas[] = trim( $pacote['titulo'] );
	$linhas[] = '';

	if ( ! empty( $pacote['subtitulo'] ) ) {
		$linhas[] = trim( $pacote['subtitulo'] );
		$linhas[] = '';
	}

	foreach ( array_slice( $pacote['itens'], 0, 4 ) as $item ) {
		$trecho = trim( $item['titulo'] );
		if ( ! empty( $item['texto'] ) ) {
			$trecho .= ' — ' . trim( $item['texto'] );
		}
		$linhas[] = $trecho;
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
