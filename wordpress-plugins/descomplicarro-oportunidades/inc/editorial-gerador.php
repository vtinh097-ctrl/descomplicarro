<?php
/**
 * DESCOMPLICARRO — monta as especificações de slide (a partir do pacote de
 * conteúdo) e chama o renderizador (inc/editorial-render.php) para gerar
 * os arquivos PNG do carrossel e do Stories de uma matéria.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_editorial_specs_carrossel( array $pacote ) {
	$itens = $pacote['itens'];
	$total = count( $itens ) + 2; // capa + itens + cta
	$pool  = $pacote['pool_imagens'];

	$specs   = array();
	$specs[] = array(
		'formato'     => 'carrossel',
		'tipo'        => 'capa',
		'foto'        => dco_editorial_imagem_para_indice( $pool, 0 ),
		'badge_canto' => $pacote['categoria_label'],
		'eyebrow'     => 'review' === $pacote['tipo_conteudo'] ? 'Testamos ao volante' : '',
		'titulo'      => $pacote['titulo'],
		'texto'       => $pacote['subtitulo'],
		'numero'      => 1,
		'total'       => $total,
	);

	foreach ( $itens as $i => $item ) {
		$specs[] = array(
			'formato' => 'carrossel',
			'tipo'    => 'conteudo',
			'foto'    => dco_editorial_imagem_para_indice( $pool, $i + 1 ),
			'eyebrow' => $pacote['categoria_label'],
			'titulo'  => $item['titulo'],
			'texto'   => $item['texto'],
			'numero'  => $i + 2,
			'total'   => $total,
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

function dco_editorial_specs_stories( array $pacote ) {
	$pool  = $pacote['pool_imagens'];
	$itens = array_slice( $pacote['itens'], 0, DCO_EDITORIAL_MAX_ITENS_STORIES );

	$specs   = array();
	$specs[] = array(
		'formato'     => 'stories',
		'tipo'        => 'capa',
		'foto'        => dco_editorial_imagem_para_indice( $pool, 0 ),
		'badge_canto' => $pacote['categoria_label'],
		'eyebrow'     => 'review' === $pacote['tipo_conteudo'] ? 'Testamos ao volante' : '',
		'titulo'      => $pacote['titulo'],
		'texto'       => $pacote['subtitulo'],
		'cta_seta'    => true,
	);

	foreach ( $itens as $i => $item ) {
		$specs[] = array(
			'formato'  => 'stories',
			'tipo'     => 'conteudo',
			'foto'     => dco_editorial_imagem_para_indice( $pool, $i + 1 ),
			'eyebrow'  => $pacote['categoria_label'],
			'titulo'   => $item['titulo'],
			'texto'    => $item['texto'],
			'cta_seta' => true,
		);
	}

	return $specs;
}

/**
 * Qual modelo visual usar, SÓ pela editoria da matéria — nunca por
 * escolha manual. MODELO 1 (Editorial Fotográfico, intacto desde a
 * aprovação) para Notícias/Reviews/Bastidores/qualquer outra; MODELO 2
 * (Educativo e Técnico) só para Técnica e Guias. Ver
 * inc/editorial-render-modelo2.php para o porquê e as regras do Modelo 2.
 */
function dco_editorial_modelo_visual_da_categoria( $categoria_slug ) {
	return in_array( $categoria_slug, array( 'tecnica', 'guias' ), true ) ? 2 : 1;
}

/**
 * Gera todos os arquivos (carrossel + Stories) de uma matéria dentro de
 * $pasta_destino/{carrossel,stories}/ — pasta de trabalho temporária,
 * apagada depois do upload ao Drive (ver inc/editorial-cron.php).
 * Devolve a lista de arquivos gerados + o pacote usado (para a legenda).
 */
function dco_editorial_gerar_arquivos( $post_id, $pasta_destino ) {
	$pacote = dco_editorial_montar_pacote( $post_id );
	$modelo = dco_editorial_modelo_visual_da_categoria( $pacote['categoria_slug'] );

	// O Stories é IGUAL em Técnica/Guias e nas demais editorias — usa
	// sempre as funções do Modelo 1 (foto + "LEIA A MATÉRIA COMPLETA" +
	// seta, com espaço reservado pro link), por pedido explícito. Só o
	// carrossel muda de modelo visual por editoria.
	if ( 2 === $modelo ) {
		$specs_carrossel    = dco_editorial_specs_carrossel_modelo2( $pacote );
		$funcao_carrossel   = 'dco_editorial_renderizar_slide_modelo2';
	} else {
		$specs_carrossel    = dco_editorial_specs_carrossel( $pacote );
		$funcao_carrossel   = 'dco_editorial_renderizar_slide';
	}
	$specs_stories  = dco_editorial_specs_stories( $pacote );
	$funcao_stories = 'dco_editorial_renderizar_slide';

	$pasta_carrossel = trailingslashit( $pasta_destino ) . 'carrossel';
	$pasta_stories   = trailingslashit( $pasta_destino ) . 'stories';
	wp_mkdir_p( $pasta_carrossel );
	wp_mkdir_p( $pasta_stories );

	$arquivos_carrossel = array();
	foreach ( $specs_carrossel as $spec ) {
		$caminho = trailingslashit( $pasta_carrossel ) . sprintf( '%02d-%s.png', $spec['numero'], $spec['tipo'] );
		if ( call_user_func( $funcao_carrossel, $spec, $caminho ) ) {
			$arquivos_carrossel[] = $caminho;
		}
	}

	$arquivos_stories = array();
	foreach ( $specs_stories as $i => $spec ) {
		$caminho = trailingslashit( $pasta_stories ) . sprintf( '%02d-%s.png', $i + 1, $spec['tipo'] );
		if ( call_user_func( $funcao_stories, $spec, $caminho ) ) {
			$arquivos_stories[] = $caminho;
		}
	}

	return array(
		'pacote'    => $pacote,
		'modelo'    => $modelo,
		'carrossel' => $arquivos_carrossel,
		'stories'   => $arquivos_stories,
	);
}
