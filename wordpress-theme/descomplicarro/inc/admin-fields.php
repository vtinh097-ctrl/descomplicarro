<?php
/**
 * DESCOMPLICARRO — renderizadores de campo reutilizáveis para os metaboxes
 * do admin. Cada função desenha UMA linha de campo (label + input) lendo o
 * valor atual do post em edição. Usadas por inc/meta-boxes.php.
 *
 * Nenhuma depende de plugin — são apenas <input>/<textarea> nativos do
 * WordPress, com a mesma aparência do admin padrão (classes "regular-text",
 * "large-text" etc.).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dc_admin_field_wrap_start( $label, $desc = '' ) {
	echo '<p class="dc-field">';
	echo '<label style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html( $label ) . '</label>';
	if ( $desc ) {
		echo '<span style="display:block;color:#646970;font-size:12px;margin-bottom:4px;">' . esc_html( $desc ) . '</span>';
	}
}

function dc_admin_field_wrap_end() {
	echo '</p>';
}

function dc_admin_text( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dc_admin_field_wrap_start( $label, $desc );
	printf(
		'<input type="text" class="widefat" name="%1$s" value="%2$s" />',
		esc_attr( $key ),
		esc_attr( $value )
	);
	dc_admin_field_wrap_end();
}

function dc_admin_textarea( $post, $key, $label, $desc = '', $rows = 3 ) {
	$value = get_post_meta( $post->ID, $key, true );
	dc_admin_field_wrap_start( $label, $desc );
	printf(
		'<textarea class="widefat" rows="%1$d" name="%2$s">%3$s</textarea>',
		(int) $rows,
		esc_attr( $key ),
		esc_textarea( $value )
	);
	dc_admin_field_wrap_end();
}

function dc_admin_url( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dc_admin_field_wrap_start( $label, $desc ? $desc : 'Deixe em branco para manter "#" (placeholder). Nunca invente uma URL real.' );
	printf(
		'<input type="text" class="widefat" name="%1$s" value="%2$s" placeholder="#" />',
		esc_attr( $key ),
		esc_attr( $value )
	);
	dc_admin_field_wrap_end();
}

function dc_admin_checkbox( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	$checked = ( $value === '' || $value ) ? 'checked' : '';
	echo '<p class="dc-field">';
	printf(
		'<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>',
		esc_attr( $key ),
		$checked,
		esc_html( $label )
	);
	if ( $desc ) {
		echo '<br><span style="color:#646970;font-size:12px;">' . esc_html( $desc ) . '</span>';
	}
	echo '</p>';
}

/** Campo de imagem: botão para abrir a Biblioteca de Mídia (wp.media), guarda o ID do anexo. */
function dc_admin_image( $post, $key, $label, $desc = '' ) {
	$attachment_id = (int) get_post_meta( $post->ID, $key, true );
	$url           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	dc_admin_field_wrap_start( $label, $desc );
	echo '<div class="dc-image-field" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="dc-image-preview" style="margin-bottom:6px;">';
	if ( $url ) {
		echo '<img src="' . esc_url( $url ) . '" style="max-width:180px;height:auto;display:block;border:1px solid #dcdcde;" />';
	}
	echo '</div>';
	printf(
		'<input type="hidden" class="dc-image-id" name="%1$s" value="%2$d" />',
		esc_attr( $key ),
		$attachment_id
	);
	echo '<button type="button" class="button dc-image-select">Selecionar imagem</button> ';
	echo '<button type="button" class="button dc-image-remove">Remover</button>';
	echo '</div>';
	dc_admin_field_wrap_end();
}

function dc_admin_section_title( $title ) {
	echo '<h4 style="margin:22px 0 8px;padding-top:16px;border-top:1px solid #dcdcde;">' . esc_html( $title ) . '</h4>';
}
