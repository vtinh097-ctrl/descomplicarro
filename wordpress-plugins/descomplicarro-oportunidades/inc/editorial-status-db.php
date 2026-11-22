<?php
/**
 * DESCOMPLICARRO — tabela de status da Automação Editorial (Etapa 4):
 * uma linha por matéria processada, para o painel administrativo e para
 * o dedupe (nunca gerar materiais duas vezes para a mesma matéria).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_editorial_nome_tabela() {
	global $wpdb;
	return $wpdb->prefix . 'dco_editorial_status';
}

function dco_editorial_garantir_tabela() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tabela          = dco_editorial_nome_tabela();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$tabela} (
		post_id BIGINT UNSIGNED NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'pendente',
		link_drive VARCHAR(255) NOT NULL DEFAULT '',
		mensagem_erro TEXT NULL,
		tentativas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
		criado_em DATETIME NOT NULL,
		atualizado_em DATETIME NOT NULL,
		PRIMARY KEY (post_id),
		KEY dco_status (status)
	) {$charset_collate};";

	dbDelta( $sql );
}

add_action( 'init', 'dco_editorial_ensure_tabela', 5 );
function dco_editorial_ensure_tabela() {
	if ( get_option( 'dco_editorial_versao_tabela' ) !== DCO_VERSION ) {
		dco_editorial_garantir_tabela();
		update_option( 'dco_editorial_versao_tabela', DCO_VERSION );
	}
}

function dco_editorial_status_de( $post_id ) {
	global $wpdb;
	$tabela = dco_editorial_nome_tabela();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabela} WHERE post_id = %d", $post_id ), ARRAY_A );
}

/** true quando já existe material gerado (ou em geração) para esta matéria — nunca duplicar. */
function dco_editorial_ja_processado_ou_em_andamento( $post_id ) {
	$linha = dco_editorial_status_de( $post_id );
	return $linha && in_array( $linha['status'], array( 'concluido', 'processando' ), true );
}

function dco_editorial_marcar_status( $post_id, $status, $link_drive = '', $mensagem_erro = '' ) {
	global $wpdb;
	$tabela = dco_editorial_nome_tabela();
	$agora  = current_time( 'mysql' );
	$existente = dco_editorial_status_de( $post_id );

	if ( $existente ) {
		$tentativas = 'processando' === $status ? (int) $existente['tentativas'] + 1 : (int) $existente['tentativas'];
		$wpdb->update(
			$tabela,
			array(
				'status'        => $status,
				'link_drive'    => $link_drive,
				'mensagem_erro' => $mensagem_erro,
				'tentativas'    => $tentativas,
				'atualizado_em' => $agora,
			),
			array( 'post_id' => $post_id )
		);
	} else {
		$wpdb->insert(
			$tabela,
			array(
				'post_id'       => $post_id,
				'status'        => $status,
				'link_drive'    => $link_drive,
				'mensagem_erro' => $mensagem_erro,
				'tentativas'    => 'processando' === $status ? 1 : 0,
				'criado_em'     => $agora,
				'atualizado_em' => $agora,
			)
		);
	}
}

function dco_editorial_listar_status( $filtro_status = '', $limite = 100 ) {
	global $wpdb;
	$tabela = dco_editorial_nome_tabela();
	if ( $filtro_status ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabela} WHERE status = %s ORDER BY atualizado_em DESC LIMIT %d", $filtro_status, $limite ), ARRAY_A );
	}
	return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabela} ORDER BY atualizado_em DESC LIMIT %d", $limite ), ARRAY_A );
}
