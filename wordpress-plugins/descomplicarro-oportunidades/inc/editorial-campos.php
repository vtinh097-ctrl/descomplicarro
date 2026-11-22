<?php
/**
 * DESCOMPLICARRO — campos editoriais opcionais que alimentam a Automação
 * Editorial (Etapa 4), no editor nativo de matérias do WordPress (post
 * type 'post', o mesmo que o tema já usa — ver single.php do tema).
 *
 * TUDO aqui é opcional. Nenhum campo bloqueia ou atrasa a publicação da
 * matéria — se nada for preenchido, a automação usa só o que já existe
 * na matéria (título, resumo, subtítulos, imagens), nunca inventando
 * informação. Os campos aparecem sempre (não dependem de JS escolhendo
 * categoria em tempo real) — o editor preenche só a seção da editoria
 * que fizer sentido para aquela matéria.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'dco_editorial_registrar_metabox' );
function dco_editorial_registrar_metabox() {
	add_meta_box(
		'dco-editorial-instagram',
		'Automação Editorial (Instagram)',
		'dco_editorial_render_metabox',
		'post',
		'normal',
		'default'
	);
}

function dco_editorial_campo_linhas_textarea( $post, $key, $label, $desc, $max_linhas ) {
	$valores = get_post_meta( $post->ID, $key, true );
	$valores = is_array( $valores ) ? $valores : array();
	$texto   = implode( "\n", $valores );
	dco_admin_field_wrap_start( $label, $desc . ' (até ' . $max_linhas . ', uma por linha)' );
	printf( '<textarea class="widefat" rows="4" name="%1$s">%2$s</textarea>', esc_attr( $key ), esc_textarea( $texto ) );
	dco_admin_field_wrap_end();
}

function dco_editorial_render_metabox( $post ) {
	wp_nonce_field( 'dco_editorial_salvar', 'dco_editorial_nonce' );

	echo '<p style="color:#646970;">Todos os campos abaixo são opcionais — preencha só o que fizer sentido para esta matéria. Nada aqui atrasa ou bloqueia a publicação.</p>';

	dco_admin_section_title( 'Reviews — Destaques do veículo' );
	echo '<p style="color:#646970;margin-top:-4px;">Usado no carrossel/Stories quando a matéria é da editoria Reviews. Escreva fatos reais e já verificados — a automação nunca completa nem corrige um dado técnico sozinha.</p>';
	dco_editorial_campo_linhas_textarea( $post, '_dco_destaques_veiculo', 'Destaques do veículo', 'Ex.: "0–100 km/h em 7,2 segundos", "943 litros de caçamba"', 6 );

	dco_admin_section_title( 'Técnica e Guias — "Entenda. Questione. Decida."' );
	echo '<p style="color:#646970;margin-top:-4px;">Usado quando a matéria é da editoria Técnica ou Guias — carrossel de 5 slides fixos (capa, Entenda, Questione, Decida, CTA). Escreva cada bloco a partir do que a matéria já diz — nunca invente dado técnico ou recomendação. Dentro do texto, <code>**palavra**</code> destaca esse trecho em laranja no slide.</p>';
	dco_admin_text( $post, '_dco_m2_chamada', 'Chamada da capa (opcional)', 'Sem preencher, usa o título da matéria' );
	dco_admin_textarea( $post, '_dco_m2_entenda', 'Entenda — explicação técnica acessível', 'O que é e por que acontece, em linguagem simples' );
	dco_admin_textarea( $post, '_dco_m2_questione', 'Questione — reflexão, dúvida ou quebra de percepção', 'Uma pergunta ou mito relacionado ao assunto' );
	dco_admin_textarea( $post, '_dco_m2_decida', 'Decida — orientação prática', 'O que o leitor deve observar ou fazer a respeito' );
	dco_admin_field_wrap_start( 'Usar fotografia na capa', 'Por padrão o Modelo 2 não usa foto — prioridade é a composição tipográfica' );
	printf(
		'<label><input type="checkbox" name="_dco_m2_usar_foto" value="1" %s /> usar a imagem destacada da matéria na capa</label>',
		checked( get_post_meta( $post->ID, '_dco_m2_usar_foto', true ), '1', false )
	);
	dco_admin_field_wrap_end();
}

add_action( 'save_post', 'dco_editorial_salvar_campos' );
function dco_editorial_salvar_campos( $post_id ) {
	if ( ! isset( $_POST['dco_editorial_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['dco_editorial_nonce'] ), 'dco_editorial_salvar' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	dco_salvar_campos_texto( $post_id, array( '_dco_edu_gancho', '_dco_m2_chamada' ) );

	foreach ( array( '_dco_destaques_veiculo', '_dco_edu_curiosidades', '_dco_edu_perguntas_mitos', '_dco_edu_frases' ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$linhas = preg_split( '/\r\n|\r|\n/', wp_unslash( $_POST[ $key ] ) );
		$linhas = array_map( 'sanitize_text_field', $linhas );
		$linhas = array_values( array_filter( $linhas, fn( $l ) => '' !== trim( $l ) ) );
		if ( ! empty( $linhas ) ) {
			update_post_meta( $post_id, $key, $linhas );
		} else {
			delete_post_meta( $post_id, $key );
		}
	}

	// MODELO 2 — "Entenda. Questione. Decida." (Técnica/Guias): cada bloco
	// é um parágrafo livre (com marcadores **destaque** opcionais), não
	// uma lista de linhas — por isso usa textarea, não
	// dco_editorial_campo_linhas_textarea().
	dco_salvar_campos_textarea( $post_id, array( '_dco_m2_entenda', '_dco_m2_questione', '_dco_m2_decida' ) );
	update_post_meta( $post_id, '_dco_m2_usar_foto', isset( $_POST['_dco_m2_usar_foto'] ) ? '1' : '' );
}
