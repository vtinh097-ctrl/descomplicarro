<?php
/**
 * DESCOMPLICARRO Oportunidades — listagem/busca pública de Vagas e
 * Currículos por estado/cidade. Nunca mostra e-mail, telefone ou o PDF em
 * si — só o necessário para alguém decidir "solicitar contato" ou
 * "candidatar-se".
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_renderizar_busca_vagas() {
	$estado = isset( $_GET['vaga_estado'] ) ? sanitize_text_field( wp_unslash( $_GET['vaga_estado'] ) ) : '';
	$cidade = isset( $_GET['vaga_cidade'] ) ? sanitize_text_field( wp_unslash( $_GET['vaga_cidade'] ) ) : '';
	if ( ! array_key_exists( $estado, dco_uf_list() ) ) {
		$estado = '';
	}

	echo '<div class="dco-busca" id="dco-vagas">';
	echo '<h3>Vagas em aberto</h3>';
	echo '<form method="get" class="dco-busca__form">' . dco_campos_ocultos_atuais( array( 'vaga_estado', 'vaga_cidade' ) );
	echo '<select name="vaga_estado"><option value="">Todos os estados</option>';
	foreach ( dco_uf_list() as $uf => $nome ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $uf ), selected( $estado, $uf, false ), esc_html( $uf . ' — ' . $nome ) );
	}
	echo '</select> ';
	printf( '<input type="text" name="vaga_cidade" value="%s" placeholder="Cidade">', esc_attr( $cidade ) );
	echo ' <button type="submit">Filtrar</button></form>';

	$args = array(
		'post_type'      => 'dco_vaga',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
	);
	$meta_query = array();
	if ( $estado ) {
		$meta_query[] = array( 'key' => 'dco_estado', 'value' => $estado );
	}
	if ( $cidade ) {
		$meta_query[] = array( 'key' => 'dco_cidade', 'value' => $cidade, 'compare' => 'LIKE' );
	}
	if ( $meta_query ) {
		$args['meta_query'] = $meta_query;
	}
	$vagas = new WP_Query( $args );

	if ( ! $vagas->have_posts() ) {
		echo '<p>Nenhuma vaga encontrada com esses filtros.</p>';
	} else {
		echo '<ul class="dco-lista">';
		while ( $vagas->have_posts() ) {
			$vagas->the_post();
			$id = get_the_ID();
			$empresa = get_post_meta( $id, 'dco_empresa', true );
			$cargo   = get_post_meta( $id, 'dco_cargo', true );
			$cid     = get_post_meta( $id, 'dco_cidade', true );
			$uf      = get_post_meta( $id, 'dco_estado', true );
			$email_candidatura = get_post_meta( $id, 'dco_email_candidatura', true );
			$assunto = rawurlencode( 'Candidatura: ' . $cargo . ' (via DESCOMPLICARRO)' );
			echo '<li class="dco-card">';
			echo '<strong>' . esc_html( $cargo ) . '</strong> — ' . esc_html( $empresa ) . '<br>';
			echo '<span>' . esc_html( trim( $cid . ( $uf ? ' / ' . $uf : '' ), ' /' ) ) . '</span><br>';
			if ( $email_candidatura ) {
				printf(
					'<a href="mailto:%1$s?subject=%2$s">Candidatar-se</a> &nbsp;',
					esc_attr( $email_candidatura ),
					esc_attr( $assunto )
				);
			}
			printf(
				'<a href="%s">Denunciar</a>',
				esc_url( dco_url_acao( 'denunciar', 'vaga', $id, '' ) )
			);
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();
	}
	echo '</div>';
}

function dco_renderizar_busca_curriculos() {
	$estado = isset( $_GET['curriculo_estado'] ) ? sanitize_text_field( wp_unslash( $_GET['curriculo_estado'] ) ) : '';
	$cidade = isset( $_GET['curriculo_cidade'] ) ? sanitize_text_field( wp_unslash( $_GET['curriculo_cidade'] ) ) : '';
	if ( ! array_key_exists( $estado, dco_uf_list() ) ) {
		$estado = '';
	}

	echo '<div class="dco-busca" id="dco-curriculos">';
	echo '<h3>Currículos disponíveis</h3>';
	echo '<form method="get" class="dco-busca__form">' . dco_campos_ocultos_atuais( array( 'curriculo_estado', 'curriculo_cidade' ) );
	echo '<select name="curriculo_estado"><option value="">Todos os estados</option>';
	foreach ( dco_uf_list() as $uf => $nome ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $uf ), selected( $estado, $uf, false ), esc_html( $uf . ' — ' . $nome ) );
	}
	echo '</select> ';
	printf( '<input type="text" name="curriculo_cidade" value="%s" placeholder="Cidade">', esc_attr( $cidade ) );
	echo ' <button type="submit">Filtrar</button></form>';

	$args = array(
		'post_type'      => 'dco_curriculo',
		'post_status'    => 'publish',
		'posts_per_page' => 20,
	);
	$meta_query = array();
	if ( $estado ) {
		$meta_query[] = array( 'key' => 'dco_estado', 'value' => $estado );
	}
	if ( $cidade ) {
		$meta_query[] = array( 'key' => 'dco_cidade', 'value' => $cidade, 'compare' => 'LIKE' );
	}
	if ( $meta_query ) {
		$args['meta_query'] = $meta_query;
	}
	$curriculos = new WP_Query( $args );

	if ( ! $curriculos->have_posts() ) {
		echo '<p>Nenhum currículo encontrado com esses filtros.</p>';
	} else {
		echo '<ul class="dco-lista">';
		while ( $curriculos->have_posts() ) {
			$curriculos->the_post();
			$id = get_the_ID();
			$cargo = get_post_meta( $id, 'dco_cargo', true );
			$cid   = get_post_meta( $id, 'dco_cidade', true );
			$uf    = get_post_meta( $id, 'dco_estado', true );
			echo '<li class="dco-card">';
			echo '<strong>' . esc_html( get_the_title() ) . '</strong> — ' . esc_html( $cargo ) . '<br>';
			echo '<span>' . esc_html( trim( $cid . ( $uf ? ' / ' . $uf : '' ), ' /' ) ) . '</span><br>';
			printf(
				'<a href="%s">Solicitar contato</a> &nbsp;',
				esc_url( dco_url_acao( 'solicitar_contato', 'curriculo', $id, '' ) )
			);
			printf(
				'<a href="%s">Denunciar</a>',
				esc_url( dco_url_acao( 'denunciar', 'curriculo', $id, '' ) )
			);
			echo '</li>';
		}
		echo '</ul>';
		wp_reset_postdata();
	}
	echo '</div>';
}

/** Mantém outros filtros/parâmetros da URL atual como campos ocultos, exceto os que o próprio formulário já declara (evita perder o filtro da outra lista ao submeter este). */
function dco_campos_ocultos_atuais( $excluir ) {
	$html = '';
	foreach ( $_GET as $chave => $valor ) {
		$chave = sanitize_key( $chave );
		if ( in_array( $chave, $excluir, true ) || $chave === '' ) {
			continue;
		}
		$html .= '<input type="hidden" name="' . esc_attr( $chave ) . '" value="' . esc_attr( sanitize_text_field( wp_unslash( $valor ) ) ) . '">';
	}
	return $html;
}
