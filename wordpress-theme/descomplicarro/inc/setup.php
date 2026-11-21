<?php
/**
 * DESCOMPLICARRO — configuração base do tema (theme supports, menus,
 * enfileiramento de estilos/scripts). Usa exclusivamente recursos nativos
 * do WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_setup_theme', 'dc_theme_setup' );
function dc_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'automatic-feed-links' );
	// O logotipo do site é gerenciado em Configurações Descomplicarro →
	// Identidade visual (ver header.php), não pelo "Logo" nativo do
	// Personalizar do WordPress — por isso 'custom-logo' não é declarado aqui.

	register_nav_menus( array(
		'principal' => 'Navegação Principal (header)',
	) );

	/*
	 * Tamanho dedicado para banners publicitários (padrão oficial: 1600×400,
	 * proporção 4:1). O tamanho nativo "large" do WordPress é limitado a
	 * 1024×1024 por padrão — um banner 1600×400 virava um arquivo de
	 * 1024×256, menor que o espaço onde é exibido (até ~1200px de largura
	 * no desktop), e o navegador esticava essa imagem pequena para caber,
	 * perdendo nitidez. "dc_anuncio" nunca aumenta uma imagem menor que
	 * 1600px (o terceiro parâmetro, false, só permite reduzir, nunca
	 * cortar/ampliar) — usado exclusivamente pelo Gerenciador de
	 * Publicidade (ver inc/cpt-campanhas.php e dc_ad_slot_unit() em
	 * inc/helpers.php); nenhuma outra imagem do site usa este tamanho.
	 */
	add_image_size( 'dc_anuncio', 1600, 400, false );
}

/**
 * Páginas de editoria (category.php) exibem 1 matéria em destaque + 3 na
 * grade "Últimas" por página (4 no total) — este filtro ajusta a consulta
 * principal do arquivo de categoria para respeitar esse layout, para que a
 * paginação nativa do WordPress ("CARREGAR MAIS") avance corretamente de
 * 4 em 4 matérias em vez do padrão do site inteiro.
 */
add_action( 'pre_get_posts', 'dc_ajustar_consulta_categoria' );
function dc_ajustar_consulta_categoria( $query ) {
	if ( ! is_admin() && $query->is_main_query() && is_category() ) {
		$query->set( 'posts_per_page', 4 );
	}
}

/**
 * Enquanto a página Para Oficinas estiver temporariamente fora do ar (ver
 * Configurações Descomplicarro → GI CONECTA), seu item some do menu
 * principal — sem remover o item de verdade: ele volta sozinho assim que a
 * página for reativada, sem precisar editar o menu de novo.
 */
add_filter( 'wp_nav_menu_objects', 'dc_ocultar_item_oficinas_no_menu' );
function dc_ocultar_item_oficinas_no_menu( $items ) {
	if ( dc_oficinas_pagina_ativa() ) {
		return $items;
	}
	$pagina_oficinas = get_page_by_path( 'para-oficinas' );
	if ( ! $pagina_oficinas ) {
		return $items;
	}
	return array_values( array_filter( $items, function ( $item ) use ( $pagina_oficinas ) {
		return ! ( (int) $item->object_id === $pagina_oficinas->ID && $item->object === 'page' );
	} ) );
}

add_action( 'wp_enqueue_scripts', 'dc_enqueue_front_assets' );
function dc_enqueue_front_assets() {
	wp_enqueue_style(
		'google-fonts-descomplicarro',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=DM+Sans:wght@400;500;700&display=swap',
		array(),
		null
	);

	// style.css do tema = o mesmo CSS da V1, sem alterações (ver cabeçalho do arquivo).
	wp_enqueue_style( 'descomplicarro-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );

	// main.js da V1, copiado sem alterações (comportamento do menu mobile).
	wp_enqueue_script(
		'descomplicarro-main',
		get_template_directory_uri() . '/assets/js/main.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	// Rastreamento (dataLayer/GTM) — ver inc/analytics.php e assets/js/dc-tracking.js.
	// Carrega sempre (não só quando GA4/GTM estão ativos): mesmo sem nenhuma
	// ferramenta ligada, os cliques já alimentam window.dataLayer, pronto
	// para quando as integrações forem ativadas, sem precisar reativar o tema.
	//
	// Os mesmos cliques já classificados por este script também alimentam o
	// painel nativo "Métricas" (Etapa 3 — ver plugin descomplicarro-
	// oportunidades), que funciona independente de GA4/GTM estarem ativos ou
	// não. admin-ajax.php é sempre o mesmo endereço do núcleo do WordPress —
	// localizado aqui só porque dc-tracking.js é um arquivo estático, sem
	// acesso direto a admin_url().
	wp_enqueue_script(
		'descomplicarro-tracking',
		get_template_directory_uri() . '/assets/js/dc-tracking.js',
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);
	wp_localize_script( 'descomplicarro-tracking', 'dcoMetricas', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	) );
}

add_action( 'admin_enqueue_scripts', 'dc_enqueue_admin_assets' );
function dc_enqueue_admin_assets( $hook ) {
	// Necessário nas telas de edição de página/post e na página de opções.
	if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || strpos( $hook, 'dc-configuracoes' ) !== false ) {
		wp_enqueue_media();
		wp_enqueue_script(
			'descomplicarro-admin',
			get_template_directory_uri() . '/assets/js/admin.js',
			array( 'jquery' ),
			wp_get_theme()->get( 'Version' ),
			true
		);
	}
}
