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
