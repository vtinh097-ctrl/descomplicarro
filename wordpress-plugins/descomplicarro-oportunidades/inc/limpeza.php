<?php
/**
 * DESCOMPLICARRO Oportunidades — política completa de retenção e exclusão
 * de dados, executada automaticamente uma vez por dia (wp_cron nativo).
 * Três rotinas, nesta ordem:
 *
 * 1) PENDENTES NUNCA CONFIRMADOS: um cadastro em "Em avaliação" (pending)
 *    só vira público depois da confirmação por e-mail (link válido por
 *    48h — DCO_TOKEN_CONFIRMACAO_SEGUNDOS). Cadastros nunca confirmados são
 *    excluídos definitivamente após 7 dias (folga generosa sobre as 48h).
 *
 * 2) TRANSIÇÃO PARA EXPIRADO: um cadastro "Publicado" cuja data de validade
 *    (dco_expira_em) já passou deixa de ser exibido publicamente — o status
 *    muda sozinho para "Expirado". NADA é apagado aqui: é só isso, "sair do
 *    ar", exatamente como já acontece ao suspender manualmente.
 *
 * 3) EXCLUSÃO DE EXPIRADOS ANTIGOS: um cadastro "Expirado" que nunca foi
 *    renovado em até 6 meses (180 dias) depois de expirar é excluído
 *    definitivamente (dados + PDF, no caso de currículo) — política de
 *    retenção explícita: não guardamos dados pessoais indefinidamente só
 *    porque ninguém pediu a exclusão. Renovar a qualquer momento antes
 *    desse prazo (pelo link de gestão, solicitável por e-mail) cancela a
 *    exclusão automaticamente, porque o cadastro volta a ser "Publicado".
 *
 * NUNCA mexe em cadastros "suspensa" (moderação) — esses só saem do estado
 * em que estão por decisão humana no wp-admin, nunca por esta rotina.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_LIMPEZA_PRAZO_PENDENTE_DIAS', 7 );
define( 'DCO_LIMPEZA_PRAZO_EXPIRADO_DIAS', 180 );

add_action( 'init', 'dco_ensure_cron_limpeza' );
function dco_ensure_cron_limpeza() {
	if ( ! wp_next_scheduled( 'dco_cron_limpeza_pendentes' ) ) {
		wp_schedule_event( time(), 'daily', 'dco_cron_limpeza_pendentes' );
	}
}

register_deactivation_hook( DCO_PLUGIN_FILE, 'dco_desagendar_cron_limpeza' );
function dco_desagendar_cron_limpeza() {
	$agendado = wp_next_scheduled( 'dco_cron_limpeza_pendentes' );
	if ( $agendado ) {
		wp_unschedule_event( $agendado, 'dco_cron_limpeza_pendentes' );
	}
}

add_action( 'dco_cron_limpeza_pendentes', 'dco_executar_limpeza_pendentes' );
add_action( 'dco_cron_limpeza_pendentes', 'dco_executar_transicao_expirados' );
add_action( 'dco_cron_limpeza_pendentes', 'dco_executar_exclusao_expirados_antigos' );

/** Rotina 1 — ver cabeçalho do arquivo. */
function dco_executar_limpeza_pendentes() {
	// IMPORTANTE: usa a coluna "post_date" (hora local do site), nunca
	// "post_date_gmt" — o WordPress grava "post_date_gmt" como
	// "0000-00-00 00:00:00" em posts com status "pending" sem data
	// explícita (comportamento do próprio núcleo), o que faria TODO
	// cadastro pendente parecer "antigo" imediatamente, por mais recente
	// que fosse. current_time('timestamp') dá a hora atual já no fuso do
	// site, compatível com o que "post_date" guarda.
	$limite = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DCO_LIMPEZA_PRAZO_PENDENTE_DIAS * DAY_IN_SECONDS );

	$antigos = get_posts( array(
		'post_type'      => array( 'dco_curriculo', 'dco_vaga' ),
		'post_status'    => 'pending',
		'posts_per_page' => -1,
		'date_query'     => array( array( 'before' => $limite, 'column' => 'post_date', 'inclusive' => true ) ),
		'no_found_rows'  => true,
	) );

	foreach ( $antigos as $post ) {
		// wp_delete_post com force=true dispara before_delete_post, que já
		// remove o PDF órfão para dco_curriculo (ver cpt-curriculo.php) —
		// nenhuma lógica de arquivo duplicada aqui.
		wp_delete_post( $post->ID, true );
	}

	return count( $antigos );
}

/** Rotina 2 — ver cabeçalho do arquivo. Compara pela META dco_expira_em (string "Y-m-d"), não por nenhuma coluna de data do post. */
function dco_executar_transicao_expirados() {
	$hoje = current_time( 'Y-m-d' );

	$vencidos = get_posts( array(
		'post_type'      => array( 'dco_curriculo', 'dco_vaga' ),
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_query'     => array( array( 'key' => 'dco_expira_em', 'value' => $hoje, 'compare' => '<', 'type' => 'DATE' ) ),
		'no_found_rows'  => true,
	) );

	foreach ( $vencidos as $post ) {
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'expirada' ) );
	}

	return count( $vencidos );
}

/** Rotina 3 — ver cabeçalho do arquivo. */
function dco_executar_exclusao_expirados_antigos() {
	$limite = date( 'Y-m-d', current_time( 'timestamp' ) - DCO_LIMPEZA_PRAZO_EXPIRADO_DIAS * DAY_IN_SECONDS );

	$expirados_antigos = get_posts( array(
		'post_type'      => array( 'dco_curriculo', 'dco_vaga' ),
		'post_status'    => 'expirada',
		'posts_per_page' => -1,
		'meta_query'     => array( array( 'key' => 'dco_expira_em', 'value' => $limite, 'compare' => '<', 'type' => 'DATE' ) ),
		'no_found_rows'  => true,
	) );

	foreach ( $expirados_antigos as $post ) {
		wp_delete_post( $post->ID, true );
	}

	return count( $expirados_antigos );
}
