<?php
/**
 * DESCOMPLICARRO — provisionamento automático na ativação do tema.
 *
 * Ao ativar o tema pela primeira vez, cria as 6 páginas institucionais
 * (se ainda não existirem), atribui o modelo de página correto a cada uma,
 * define a Home como página inicial estática, ativa permalinks amigáveis
 * (/slug/) e monta o menu principal — para que o site já apareça
 * corretamente reproduzido assim que o tema for ativado, sem passos
 * manuais extras de configuração de estrutura.
 *
 * Conteúdo (textos/imagens) NÃO é sobrescrito em ativações futuras: os
 * campos só são criados/alterados se a página ainda não existir.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'after_switch_theme', 'dc_provision_site' );
function dc_provision_site() {
	$pages = array(
		'home'                 => array( 'title' => 'Home', 'template' => 'front-page.php' ),
		'sobre-nos'            => array( 'title' => 'Sobre Nós', 'template' => 'template-sobre-nos.php' ),
		'para-motoristas'      => array( 'title' => 'Para Motoristas', 'template' => 'template-para-motoristas.php' ),
		'para-oficinas'        => array( 'title' => 'Para Oficinas', 'template' => 'template-para-oficinas.php' ),
		'palestras-workshops'  => array( 'title' => 'Palestras & Workshops', 'template' => 'template-palestras-workshops.php' ),
		'em-foco'              => array( 'title' => 'Em Foco', 'template' => 'template-em-foco.php' ),
	);

	$ids = array();

	foreach ( $pages as $slug => $data ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$post_id = wp_insert_post( array(
			'post_title'   => $data['title'],
			'post_name'    => $slug,
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
		) );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_wp_page_template', $data['template'] );
			$ids[ $slug ] = $post_id;
		}
	}

	// Home como página inicial estática.
	if ( isset( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	// Permalinks amigáveis — necessário para que /sobre-nos/, /para-motoristas/ etc. funcionem.
	global $wp_rewrite;
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'category_base', 'em-foco' );
	if ( $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		$wp_rewrite->flush_rules();
	}

	dc_provision_menu( $ids );
}

/**
 * AUTOCORREÇÃO — garante que o prefixo das categorias seja "em-foco" mesmo em
 * sites onde o tema já estava ativo antes desta correção (nesses casos,
 * after_switch_theme não roda de novo sozinho). Sem isso, os botões
 * editoriais da página Em Foco (que já apontam para /em-foco/<categoria>/)
 * continuariam levando a um endereço inexistente, porque o arquivo de
 * categoria do WordPress ficaria em /category/<categoria>/ por padrão.
 * Roda uma única vez por instalação (grava a opção dc_category_base_fixed)
 * e libera novamente as regras de rewrite.
 */
add_action( 'init', 'dc_ensure_category_base', 20 );
function dc_ensure_category_base() {
	if ( get_option( 'category_base' ) === 'em-foco' ) {
		return;
	}
	update_option( 'category_base', 'em-foco' );
	global $wp_rewrite;
	if ( $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->init();
		flush_rewrite_rules();
	}
}

function dc_provision_menu( $ids ) {
	$menu_name = 'Navegação Principal';
	$menu_id   = 0;
	$existing_menu = wp_get_nav_menu_object( $menu_name );
	if ( $existing_menu ) {
		$menu_id = $existing_menu->term_id;
	} else {
		$menu_id = wp_create_nav_menu( $menu_name );
	}
	if ( is_wp_error( $menu_id ) || ! $menu_id ) {
		return;
	}

	// Evita duplicar itens se o tema for reativado.
	$current_items = wp_get_nav_menu_items( $menu_id );
	if ( ! empty( $current_items ) ) {
		$locations              = get_theme_mod( 'nav_menu_locations', array() );
		$locations['principal'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		return;
	}

	$items = array(
		array( 'title' => 'HOME', 'page' => 'home' ),
		array( 'title' => 'PARA MOTORISTAS', 'page' => 'para-motoristas' ),
		array( 'title' => 'PARA OFICINAS', 'page' => 'para-oficinas' ),
		array( 'title' => 'PALESTRAS & WORKSHOPS', 'page' => 'palestras-workshops' ),
		array( 'title' => 'EM FOCO', 'page' => 'em-foco' ),
		array( 'title' => 'SOBRE NÓS', 'page' => 'sobre-nos' ),
	);

	foreach ( $items as $item ) {
		if ( ! isset( $ids[ $item['page'] ] ) ) {
			continue;
		}
		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-title'     => $item['title'],
			'menu-item-object-id' => $ids[ $item['page'] ],
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		) );
	}

	// CONTATO: não é uma página — é uma âncora até a área de contato do footer.
	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-title'  => 'CONTATO',
		'menu-item-url'    => home_url( '/#contato' ),
		'menu-item-type'   => 'custom',
		'menu-item-status' => 'publish',
	) );

	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations['principal'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}
