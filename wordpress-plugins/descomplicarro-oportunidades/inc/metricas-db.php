<?php
/**
 * DESCOMPLICARRO — Métricas simples de cliques.
 *
 * Tabela própria (wp_dco_metricas_cliques), independente de qualquer
 * serviço externo (Google Analytics, Google Tag Manager ou qualquer outro
 * pago) — a camada GA4/GTM já existente no tema (inc/analytics.php,
 * assets/js/dc-tracking.js) continua preservada, mas permanece DESATIVADA
 * (nenhum ID cadastrado); este painel funciona de forma totalmente
 * independente dela.
 *
 * PRIVACIDADE POR DESENHO: cada linha é um TOTAL AGREGADO por dia (não um
 * evento por clique) — "nesta data, nesta área, este tipo de evento, este
 * item, X cliques". Nunca se grava IP, user-agent, cookie, identificador de
 * sessão ou qualquer outro dado que permita reconstruir o comportamento de
 * uma pessoa específica. Um clique duplo rápido no mesmo botão é filtrado
 * do lado do navegador (ver assets/js/dc-tracking.js do tema), não aqui —
 * esta tabela nunca vê o clique individual, só o total do dia.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_metricas_nome_tabela() {
	global $wpdb;
	return $wpdb->prefix . 'dco_metricas_cliques';
}

/**
 * Garante que a tabela existe, com a estrutura certa — checado a cada
 * ativação e também uma vez por versão do plugin (mesma lição de não
 * confiar só numa marca "já criei uma vez": dbDelta() é idempotente e
 * barata, então rodar de novo não tem custo real).
 */
function dco_metricas_garantir_tabela() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$tabela          = dco_metricas_nome_tabela();
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE {$tabela} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		data DATE NOT NULL,
		area VARCHAR(50) NOT NULL,
		evento VARCHAR(50) NOT NULL,
		identificador VARCHAR(100) NOT NULL DEFAULT '',
		cliques BIGINT UNSIGNED NOT NULL DEFAULT 0,
		PRIMARY KEY (id),
		UNIQUE KEY dco_dia_item (data, area, evento, identificador),
		KEY dco_data (data)
	) {$charset_collate};";

	dbDelta( $sql );
}

add_action( 'init', 'dco_metricas_ensure_tabela', 5 );
function dco_metricas_ensure_tabela() {
	if ( get_option( 'dco_metricas_versao_tabela' ) !== DCO_VERSION ) {
		dco_metricas_garantir_tabela();
		update_option( 'dco_metricas_versao_tabela', DCO_VERSION );
	}
}

/** Nomes permitidos de "área" e "evento" — nunca aceita valor arbitrário vindo do navegador, para a tabela nunca virar lixeira de qualquer string que alguém queira mandar. */
function dco_metricas_areas_permitidas() {
	return array( 'anuncio', 'materia', 'oficina', 'parceiro', 'vaga', 'curriculo', 'navegacao', 'geral' );
}

function dco_metricas_eventos_permitidos() {
	return array(
		'ad_click', 'ad_impression', 'article_click', 'partner_click',
		'whatsapp_click', 'email_click', 'social_click', 'file_download',
		'credenciamento_click', 'button_click', 'navigation_click',
		'candidatura_click', 'contato_click',
	);
}

/**
 * Registra (agrega) um clique. $identificador é truncado a 100 caracteres
 * por segurança (coluna VARCHAR(100)) — nunca deve ser um dado pessoal, só
 * um ID/slug estável (ID de campanha, ID de matéria, slug de parceiro...).
 */
function dco_metricas_registrar( $area, $evento, $identificador = '' ) {
	global $wpdb;

	if ( ! in_array( $area, dco_metricas_areas_permitidas(), true ) ) {
		$area = 'geral';
	}
	if ( ! in_array( $evento, dco_metricas_eventos_permitidos(), true ) ) {
		return false;
	}
	$identificador = substr( sanitize_text_field( $identificador ), 0, 100 );
	$hoje          = current_time( 'Y-m-d' );
	$tabela        = dco_metricas_nome_tabela();

	// INSERT ... ON DUPLICATE KEY UPDATE: ou cria a linha do dia com 1
	// clique, ou soma +1 na linha já existente — nunca duas linhas para o
	// mesmo (dia, área, evento, identificador).
	return $wpdb->query( $wpdb->prepare(
		"INSERT INTO {$tabela} (data, area, evento, identificador, cliques) VALUES (%s, %s, %s, %s, 1)
		 ON DUPLICATE KEY UPDATE cliques = cliques + 1",
		$hoje, $area, $evento, $identificador
	) );
}

/** Consulta agregada para o painel — soma cliques por (área, evento, identificador) dentro de um período. */
function dco_metricas_consultar( $data_inicio, $data_fim, $area_filtro = '' ) {
	global $wpdb;
	$tabela = dco_metricas_nome_tabela();

	$sql = "SELECT area, evento, identificador, SUM(cliques) AS total
		FROM {$tabela}
		WHERE data BETWEEN %s AND %s";
	$params = array( $data_inicio, $data_fim );

	if ( $area_filtro && in_array( $area_filtro, dco_metricas_areas_permitidas(), true ) ) {
		$sql .= ' AND area = %s';
		$params[] = $area_filtro;
	}

	$sql .= ' GROUP BY area, evento, identificador ORDER BY total DESC';

	return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
}
