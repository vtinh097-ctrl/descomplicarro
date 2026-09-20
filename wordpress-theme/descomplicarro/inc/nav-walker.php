<?php
/**
 * DESCOMPLICARRO — Walker de menu customizado.
 *
 * Reproduz exatamente o markup aprovado na V1 para cada item do menu
 * principal (<a class="site-nav__link">, com "is-current"/aria-current no
 * item ativo) usando o menu nativo do WordPress (Aparência → Menus), em vez
 * de deixar os links fixos no header.php. Sem isso, alterar o menu no
 * painel não refletiria no site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DC_Nav_Walker extends Walker_Nav_Menu {

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = array( 'site-nav__link' );
		// Links para âncora (ex.: CONTATO -> /#contato) nunca são o item "atual":
		// o WordPress às vezes marca current-menu-item para eles por compartilharem
		// a mesma URL base da página corrente, o que não deve acender o estado ativo.
		$is_anchor_link = strpos( $item->url, '#' ) !== false;
		$is_current = ! $is_anchor_link && ( in_array( 'current-menu-item', $item->classes, true ) || in_array( 'current_page_item', $item->classes, true ) );

		if ( $is_current ) {
			$classes[] = 'is-current';
		}

		$attrs  = ' href="' . esc_url( $item->url ) . '"';
		$attrs .= ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		if ( $is_current ) {
			$attrs .= ' aria-current="page"';
		}

		$output .= '<li>';
		$output .= '<a' . $attrs . '>' . esc_html( $item->title ) . '</a>';
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		$output .= '</li>';
	}
}

/**
 * Menu de fallback (usado apenas se nenhum menu tiver sido montado ainda em
 * Aparência → Menus) — reproduz exatamente os 7 itens e URLs da V1, para que
 * o site nunca fique sem navegação logo após a ativação do tema.
 */
function dc_fallback_menu() {
	$home_page       = get_page_by_path( 'home' );
	$sobre_page      = get_page_by_path( 'sobre-nos' );
	$motoristas_page = get_page_by_path( 'para-motoristas' );
	$oficinas_page   = get_page_by_path( 'para-oficinas' );
	$palestras_page  = get_page_by_path( 'palestras-workshops' );
	$emfoco_page     = get_page_by_path( 'em-foco' );

	$items = array(
		array( 'label' => 'HOME', 'url' => home_url( '/' ) ),
		array( 'label' => 'PARA MOTORISTAS', 'url' => $motoristas_page ? get_permalink( $motoristas_page ) : home_url( '/para-motoristas' ) ),
		array( 'label' => 'PARA OFICINAS', 'url' => $oficinas_page ? get_permalink( $oficinas_page ) : home_url( '/para-oficinas' ) ),
		array( 'label' => 'PALESTRAS & WORKSHOPS', 'url' => $palestras_page ? get_permalink( $palestras_page ) : home_url( '/palestras-workshops' ) ),
		array( 'label' => 'EM FOCO', 'url' => $emfoco_page ? get_permalink( $emfoco_page ) : home_url( '/em-foco' ) ),
		array( 'label' => 'SOBRE NÓS', 'url' => $sobre_page ? get_permalink( $sobre_page ) : home_url( '/sobre-nos' ) ),
		array( 'label' => 'CONTATO', 'url' => home_url( '/#contato' ) ),
	);

	echo '<ul class="site-nav__list">';
	foreach ( $items as $item ) {
		$is_current = ( untrailingslashit( $item['url'] ) === untrailingslashit( home_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) ) );
		$classes = 'site-nav__link' . ( $is_current ? ' is-current' : '' );
		printf(
			'<li><a href="%1$s" class="%2$s"%3$s>%4$s</a></li>',
			esc_url( $item['url'] ),
			esc_attr( $classes ),
			$is_current ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}
