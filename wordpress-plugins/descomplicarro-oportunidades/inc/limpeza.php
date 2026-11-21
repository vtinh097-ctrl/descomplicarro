<?php
/**
 * DESCOMPLICARRO Oportunidades — limpeza automática de cadastros que nunca
 * foram confirmados por e-mail. Um cadastro em "Em avaliação" (pending) só
 * vira público depois da confirmação; o link de confirmação em si expira em
 * 48h (DCO_TOKEN_CONFIRMACAO_SEGUNDOS) — mas o POST em si, sem confirmação,
 * ficaria parado para sempre em "Em avaliação" se nada o removesse. Esta
 * rotina roda uma vez por dia (wp_cron nativo do WordPress) e remove
 * definitivamente (incluindo o PDF, no caso de currículo) qualquer cadastro
 * pendente com mais de 7 dias — uma folga generosa sobre as 48h do link,
 * para nunca remover algo que a pessoa ainda possa estar tentando confirmar.
 *
 * NUNCA mexe em cadastros "publish", "suspensa" ou "expirada" — só em
 * "pending" realmente antigos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_LIMPEZA_PRAZO_DIAS', 7 );

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
function dco_executar_limpeza_pendentes() {
	// IMPORTANTE: usa a coluna "post_date" (hora local do site), nunca
	// "post_date_gmt" — o WordPress grava "post_date_gmt" como
	// "0000-00-00 00:00:00" em posts com status "pending" sem data
	// explícita (comportamento do próprio núcleo), o que faria TODO
	// cadastro pendente parecer "antigo" imediatamente, por mais recente
	// que fosse. current_time('timestamp') dá a hora atual já no fuso do
	// site, compatível com o que "post_date" guarda.
	$limite = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - DCO_LIMPEZA_PRAZO_DIAS * DAY_IN_SECONDS );

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
