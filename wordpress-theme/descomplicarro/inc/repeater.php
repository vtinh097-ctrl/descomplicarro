<?php
/**
 * DESCOMPLICARRO — campo repetidor nativo (sem plugin).
 *
 * Usado para as listas do site que têm número variável de itens (Formação,
 * Parceiros / "avançar", Formatos, Territórios de atuação, Galerias). Cada
 * item é um grupo de subcampos definido por $fields; o conjunto é salvo em
 * UM post meta (array de arrays). O admin usa um pouco de JS simples
 * (clonar/remover linha) — sem dependência de plugin.
 *
 * Formato de $fields: array( 'subkey' => array('label' => ..., 'type' => 'text|textarea|url|image') )
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dc_admin_repeater( $post, $key, $label, $fields, $desc = '' ) {
	$items = get_post_meta( $post->ID, $key, true );
	$items = is_array( $items ) ? $items : array();

	dc_admin_section_title( $label );
	if ( $desc ) {
		echo '<p style="color:#646970;font-size:12px;">' . esc_html( $desc ) . '</p>';
	}

	echo '<div class="dc-repeater" data-key="' . esc_attr( $key ) . '">';
	echo '<div class="dc-repeater__rows">';

	if ( empty( $items ) ) {
		$items = array( array() ); // ao menos uma linha em branco para começar
	}

	foreach ( $items as $index => $item ) {
		dc_admin_repeater_row( $key, $index, $item, $fields );
	}

	echo '</div>'; // .dc-repeater__rows

	echo '<button type="button" class="button dc-repeater__add">+ Adicionar item</button>';

	// Template oculto usado pelo JS para clonar uma nova linha em branco.
	echo '<script type="text/template" class="dc-repeater__template">';
	ob_start();
	dc_admin_repeater_row( $key, '__INDEX__', array(), $fields );
	echo str_replace( array( '<script', '</script' ), array( '&lt;script', '&lt;/script' ), ob_get_clean() );
	echo '</script>';

	echo '</div>'; // .dc-repeater
}

function dc_admin_repeater_row( $key, $index, $item, $fields ) {
	echo '<div class="dc-repeater__row" style="border:1px solid #dcdcde;padding:10px 12px;margin-bottom:8px;background:#fff;">';
	foreach ( $fields as $subkey => $field ) {
		$name  = $key . '[' . $index . '][' . $subkey . ']';
		$value = isset( $item[ $subkey ] ) ? $item[ $subkey ] : '';
		$type  = isset( $field['type'] ) ? $field['type'] : 'text';

		echo '<p style="margin:6px 0;">';
		echo '<label style="display:block;font-weight:600;font-size:12px;margin-bottom:3px;">' . esc_html( $field['label'] ) . '</label>';

		if ( $type === 'textarea' ) {
			printf( '<textarea class="widefat" rows="2" name="%1$s">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
		} elseif ( $type === 'image' ) {
			$attachment_id = (int) $value;
			$url           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'thumbnail' ) : '';
			echo '<span class="dc-image-field" data-key="' . esc_attr( $name ) . '">';
			echo '<span class="dc-image-preview" style="display:block;margin-bottom:4px;">';
			if ( $url ) {
				echo '<img src="' . esc_url( $url ) . '" style="max-width:100px;height:auto;display:block;border:1px solid #dcdcde;" />';
			}
			echo '</span>';
			printf( '<input type="hidden" class="dc-image-id" name="%1$s" value="%2$d" />', esc_attr( $name ), $attachment_id );
			echo '<button type="button" class="button button-small dc-image-select">Selecionar</button> ';
			echo '<button type="button" class="button button-small dc-image-remove">Remover</button>';
			echo '</span>';
		} else {
			printf( '<input type="text" class="widefat" name="%1$s" value="%2$s" />', esc_attr( $name ), esc_attr( $value ) );
		}
		echo '</p>';
	}
	echo '<button type="button" class="button-link-delete dc-repeater__remove" style="color:#b32d2e;">Remover este item</button>';
	echo '</div>';
}

/**
 * Sanitiza e salva um repetidor a partir de $_POST[$key] (array bruto vindo
 * do formulário). Remove linhas totalmente vazias.
 */
function dc_save_repeater( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) {
		delete_post_meta( $post_id, $key );
		return;
	}
	$clean = array();
	foreach ( $_POST[ $key ] as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$row_clean  = array();
		$has_content = false;
		foreach ( $row as $subkey => $value ) {
			$value = is_string( $value ) ? sanitize_textarea_field( wp_unslash( $value ) ) : (int) $value;
			$row_clean[ sanitize_key( $subkey ) ] = $value;
			if ( $value !== '' && $value !== 0 ) {
				$has_content = true;
			}
		}
		if ( $has_content ) {
			$clean[] = $row_clean;
		}
	}
	if ( ! empty( $clean ) ) {
		update_post_meta( $post_id, $key, $clean );
	} else {
		delete_post_meta( $post_id, $key );
	}
}
