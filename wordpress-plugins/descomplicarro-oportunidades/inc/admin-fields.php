<?php
/**
 * DESCOMPLICARRO Oportunidades — renderizadores de campo para os metaboxes
 * do admin (Currículos e Vagas).
 *
 * Deliberadamente NÃO reaproveita as funções dc_admin_*() do tema: este
 * plugin precisa continuar funcionando mesmo se o tema for trocado ou
 * atualizado de forma incompatível (é exatamente por isso que esta feature
 * virou plugin em vez de ficar dentro do tema) — por isso tem seu próprio
 * conjunto mínimo de helpers, com prefixo "dco_" (Descomplicarro
 * Oportunidades) para nunca colidir com os "dc_" do tema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_admin_field_wrap_start( $label, $desc = '' ) {
	echo '<p class="dco-field">';
	echo '<label style="display:block;font-weight:600;margin-bottom:4px;">' . esc_html( $label ) . '</label>';
	if ( $desc ) {
		echo '<span style="display:block;color:#646970;font-size:12px;margin-bottom:4px;">' . esc_html( $desc ) . '</span>';
	}
}

function dco_admin_field_wrap_end() {
	echo '</p>';
}

function dco_admin_text( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dco_admin_field_wrap_start( $label, $desc );
	printf( '<input type="text" class="widefat" name="%1$s" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
	dco_admin_field_wrap_end();
}

function dco_admin_email( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dco_admin_field_wrap_start( $label, $desc );
	printf( '<input type="email" class="widefat" name="%1$s" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
	dco_admin_field_wrap_end();
}

function dco_admin_textarea( $post, $key, $label, $desc = '', $rows = 4 ) {
	$value = get_post_meta( $post->ID, $key, true );
	dco_admin_field_wrap_start( $label, $desc );
	printf( '<textarea class="widefat" rows="%1$d" name="%2$s">%3$s</textarea>', (int) $rows, esc_attr( $key ), esc_textarea( $value ) );
	dco_admin_field_wrap_end();
}

function dco_admin_select( $post, $key, $label, $options, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dco_admin_field_wrap_start( $label, $desc );
	echo '<select class="widefat dco-select-estado" name="' . esc_attr( $key ) . '">';
	echo '<option value="">— Selecione —</option>';
	foreach ( $options as $opt_value => $opt_label ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $opt_value ), selected( $value, $opt_value, false ), esc_html( $opt_label ) );
	}
	echo '</select>';
	dco_admin_field_wrap_end();
}

function dco_admin_date( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dco_admin_field_wrap_start( $label, $desc );
	printf( '<input type="date" name="%1$s" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
	dco_admin_field_wrap_end();
}

/** Campo de arquivo (PDF do currículo): botão para abrir a Biblioteca de Mídia (wp.media), guarda o ID do anexo. */
function dco_admin_file( $post, $key, $label, $desc = '' ) {
	$attachment_id = (int) get_post_meta( $post->ID, $key, true );
	$filename      = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
	dco_admin_field_wrap_start( $label, $desc );
	echo '<div class="dco-file-field" data-key="' . esc_attr( $key ) . '">';
	echo '<span class="dco-file-preview" style="display:block;margin-bottom:6px;font-style:italic;">' . esc_html( $filename ) . '</span>';
	printf( '<input type="hidden" class="dco-file-id" name="%1$s" value="%2$d" />', esc_attr( $key ), $attachment_id );
	echo '<button type="button" class="button dco-file-select">Selecionar arquivo</button> ';
	echo '<button type="button" class="button dco-file-remove">Remover</button>';
	echo '</div>';
	dco_admin_field_wrap_end();
}

function dco_admin_section_title( $title ) {
	echo '<h4 style="margin:22px 0 8px;padding-top:16px;border-top:1px solid #dcdcde;">' . esc_html( $title ) . '</h4>';
}

function dco_admin_readonly_info( $label, $value ) {
	echo '<p class="dco-field"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
}

/**
 * Salva um conjunto de campos de texto simples ($keys) lidos diretamente de
 * $_POST — mesmo padrão de nonce/autosave/capability check em todos os
 * metaboxes deste plugin (ver cpt-curriculo.php e cpt-vaga.php).
 */
function dco_salvar_campos_texto( $post_id, $keys ) {
	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$valor = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( $valor !== '' ) {
			update_post_meta( $post_id, $key, $valor );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}

function dco_salvar_campos_textarea( $post_id, $keys ) {
	foreach ( $keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$valor = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) );
		if ( $valor !== '' ) {
			update_post_meta( $post_id, $key, $valor );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}
}

function dco_salvar_campo_email( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}
	$valor = sanitize_email( wp_unslash( $_POST[ $key ] ) );
	if ( $valor !== '' ) {
		update_post_meta( $post_id, $key, $valor );
	} else {
		delete_post_meta( $post_id, $key );
	}
}

function dco_salvar_campo_data( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}
	$valor = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $valor ) ) {
		update_post_meta( $post_id, $key, $valor );
	} else {
		delete_post_meta( $post_id, $key );
	}
}

function dco_salvar_campo_arquivo( $post_id, $key ) {
	if ( ! isset( $_POST[ $key ] ) ) {
		return;
	}
	$attachment_id = (int) $_POST[ $key ];
	if ( $attachment_id > 0 ) {
		update_post_meta( $post_id, $key, $attachment_id );
	} else {
		delete_post_meta( $post_id, $key );
	}
}
