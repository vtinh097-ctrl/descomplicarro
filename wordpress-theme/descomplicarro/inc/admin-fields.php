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

/** Campo de seleção (dropdown nativo) a partir de uma lista fixa de opções ($options = array( valor => rótulo )). */
function dc_admin_select( $post, $key, $label, $options, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dc_admin_field_wrap_start( $label, $desc );
	echo '<select class="widefat" name="' . esc_attr( $key ) . '">';
	echo '<option value="">— Selecione —</option>';
	foreach ( $options as $opt_value => $opt_label ) {
		printf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $opt_value ),
			selected( $value, $opt_value, false ),
			esc_html( $opt_label )
		);
	}
	echo '</select>';
	dc_admin_field_wrap_end();
}

/** Campo de arquivo (PDF, etc.): botão para abrir a Biblioteca de Mídia (wp.media) sem restringir a imagens, guarda o ID do anexo. */
function dc_admin_file( $post, $key, $label, $desc = '' ) {
	$attachment_id = (int) get_post_meta( $post->ID, $key, true );
	$filename      = $attachment_id ? basename( get_attached_file( $attachment_id ) ) : '';
	dc_admin_field_wrap_start( $label, $desc );
	echo '<div class="dc-file-field" data-key="' . esc_attr( $key ) . '">';
	echo '<span class="dc-file-preview" style="display:block;margin-bottom:6px;font-style:italic;">' . esc_html( $filename ) . '</span>';
	printf(
		'<input type="hidden" class="dc-file-id" name="%1$s" value="%2$d" />',
		esc_attr( $key ),
		$attachment_id
	);
	echo '<button type="button" class="button dc-file-select">Selecionar arquivo</button> ';
	echo '<button type="button" class="button dc-file-remove">Remover</button>';
	echo '</div>';
	dc_admin_field_wrap_end();
}

/** Campo de data nativo (input type="date", formato YYYY-MM-DD). */
function dc_admin_date( $post, $key, $label, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	dc_admin_field_wrap_start( $label, $desc );
	printf(
		'<input type="date" name="%1$s" value="%2$s" />',
		esc_attr( $key ),
		esc_attr( $value )
	);
	dc_admin_field_wrap_end();
}

/** Campo numérico simples (ex.: prioridade). */
function dc_admin_number( $post, $key, $label, $default = 0, $desc = '' ) {
	$value = get_post_meta( $post->ID, $key, true );
	$value = ( $value !== '' ) ? $value : $default;
	dc_admin_field_wrap_start( $label, $desc );
	printf(
		'<input type="number" step="1" class="small-text" name="%1$s" value="%2$s" />',
		esc_attr( $key ),
		esc_attr( $value )
	);
	dc_admin_field_wrap_end();
}

/**
 * Lista de checkboxes com as categorias reais do WordPress (Posts →
 * Categories) — usada para vincular uma campanha a uma ou mais editorias.
 * Nenhuma marcada = campanha "geral" (não vinculada a nenhuma categoria
 * específica). O valor salvo é um array de IDs de categoria.
 */
function dc_admin_categorias_checklist( $post, $key, $label, $desc = '' ) {
	$selecionadas = get_post_meta( $post->ID, $key, true );
	$selecionadas = is_array( $selecionadas ) ? array_map( 'intval', $selecionadas ) : array();
	$categorias   = get_categories( array( 'hide_empty' => false ) );
	dc_admin_field_wrap_start( $label, $desc );
	if ( empty( $categorias ) ) {
		echo '<p style="color:#646970;">Nenhuma categoria cadastrada ainda em Posts → Categories.</p>';
	} else {
		foreach ( $categorias as $categoria ) {
			printf(
				'<label style="display:inline-block;margin:0 16px 6px 0;"><input type="checkbox" name="%1$s[]" value="%2$d" %3$s /> %4$s</label>',
				esc_attr( $key ),
				$categoria->term_id,
				checked( in_array( $categoria->term_id, $selecionadas, true ), true, false ),
				esc_html( $categoria->name )
			);
		}
	}
	dc_admin_field_wrap_end();
}

function dc_admin_section_title( $title ) {
	echo '<h4 style="margin:22px 0 8px;padding-top:16px;border-top:1px solid #dcdcde;">' . esc_html( $title ) . '</h4>';
}
