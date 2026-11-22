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

	dco_admin_section_title( 'Técnica e Guias — conteúdo educativo do carrossel' );
	echo '<p style="color:#646970;margin-top:-4px;">Usado quando a matéria é da editoria Técnica ou Guias, para fugir de um carrossel que só repete os parágrafos da matéria. Sem preenchimento, a automação usa os subtítulos da própria matéria.</p>';
	dco_admin_text( $post, '_dco_edu_gancho', 'Gancho principal do carrossel', 'Uma frase que resuma o ângulo editorial (ex.: "3 mitos sobre troca de óleo que ainda enganam motorista")' );
	dco_editorial_campo_linhas_textarea( $post, '_dco_edu_curiosidades', 'Curiosidades / informações complementares', 'Fatos reais que não precisam estar no corpo da matéria', 6 );
	dco_editorial_campo_linhas_textarea( $post, '_dco_edu_perguntas_mitos', 'Perguntas, mitos ou explicações', 'Ex.: "Mito: óleo mais grosso protege mais o motor"', 6 );
	dco_editorial_campo_linhas_textarea( $post, '_dco_edu_frases', 'Frases de destaque', 'Frases curtas e diretas para usar como título de slide', 4 );
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

	dco_salvar_campos_texto( $post_id, array( '_dco_edu_gancho' ) );

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
}
