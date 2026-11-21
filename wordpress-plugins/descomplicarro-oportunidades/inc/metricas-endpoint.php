<?php
/**
 * DESCOMPLICARRO — endpoint que recebe os cliques do site (tema) e grava
 * na tabela de métricas. Chamado pelo assets/js/dc-tracking.js do TEMA via
 * admin-ajax.php — funciona mesmo esta chamada vindo de um tema diferente
 * no futuro, desde que ele poste os mesmos três campos.
 *
 * Não exige login (visitante comum do site) nem nonce de sessão
 * administrativa — é tráfego público por natureza. A proteção contra abuso
 * é o limite por IP (reaproveita dco_limite_por_ip_excedido(), já usado
 * nos formulários de Oportunidades) e a lista fechada de áreas/eventos
 * aceitos (dco_metricas_areas_permitidas()/dco_metricas_eventos_permitidos()).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_dco_metricas_registrar_clique', 'dco_metricas_endpoint_registrar' );
add_action( 'wp_ajax_nopriv_dco_metricas_registrar_clique', 'dco_metricas_endpoint_registrar' );
function dco_metricas_endpoint_registrar() {
	// Limite generoso (não é um formulário sensível, é só contagem) — só
	// para impedir um script malicioso de inflar a tabela indefinidamente.
	if ( dco_limite_por_ip_excedido( 'metricas_clique', 300, MINUTE_IN_SECONDS * 10 ) ) {
		wp_send_json_error( array( 'message' => 'limite excedido' ), 429 );
	}

	$area          = isset( $_POST['area'] ) ? sanitize_key( wp_unslash( $_POST['area'] ) ) : '';
	$evento        = isset( $_POST['evento'] ) ? sanitize_key( wp_unslash( $_POST['evento'] ) ) : '';
	$identificador = isset( $_POST['identificador'] ) ? sanitize_text_field( wp_unslash( $_POST['identificador'] ) ) : '';

	if ( ! $evento ) {
		wp_send_json_error( array( 'message' => 'evento ausente' ), 400 );
	}

	$gravado = dco_metricas_registrar( $area, $evento, $identificador );
	if ( $gravado === false ) {
		// "evento" fora da lista permitida — resposta honesta, não um
		// falso "sucesso" por cima de um registro que não aconteceu.
		wp_send_json_error( array( 'message' => 'evento não reconhecido' ), 400 );
	}

	// 204-like: resposta mínima, o navegador nem precisa ler o corpo.
	wp_send_json_success();
}
