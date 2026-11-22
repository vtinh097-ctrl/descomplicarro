<?php
/**
 * DESCOMPLICARRO Oportunidades — proteções básicas contra robôs nos
 * formulários públicos: campo-isca (honeypot), tempo mínimo de
 * preenchimento e limite de envios pelo mesmo IP. Nenhuma delas pede
 * CAPTCHA nem serviço de terceiros — tudo nativo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Campo invisível para humanos (escondido via CSS) — um robô genérico que preenche todo input geralmente cai aqui. */
function dco_campo_isca_html() {
	echo '<div style="position:absolute;left:-9999px;" aria-hidden="true">'
		. '<label>Deixe este campo vazio: <input type="text" name="dco_website" value="" tabindex="-1" autocomplete="off"></label>'
		. '</div>';
	echo '<input type="hidden" name="dco_carimbo" value="' . esc_attr( time() ) . '">';
}

/** true = passou (parece humano); false = bloquear silenciosamente (nunca avisar o robô do motivo exato). */
function dco_campo_isca_valido() {
	if ( ! empty( $_POST['dco_website'] ) ) {
		return false;
	}
	$carimbo = isset( $_POST['dco_carimbo'] ) ? (int) $_POST['dco_carimbo'] : 0;
	if ( ! $carimbo || ( time() - $carimbo ) < 3 ) {
		return false; // formulário enviado rápido demais para ter sido preenchido por uma pessoa.
	}
	return true;
}

/**
 * Limite simples de envios pelo mesmo IP por janela de tempo, usando
 * transients nativos do WordPress (sem tabela própria). $acao diferencia
 * limites por tipo de formulário (cadastro de currículo, de vaga, etc.).
 */
function dco_limite_por_ip_excedido( $acao, $maximo = 5, $janela_segundos = HOUR_IN_SECONDS ) {
	$ip  = dco_ip_do_visitante();
	$key = 'dco_limite_' . $acao . '_' . md5( $ip );
	$contagem = (int) get_transient( $key );
	if ( $contagem >= $maximo ) {
		return true;
	}
	set_transient( $key, $contagem + 1, $janela_segundos );
	return false;
}

function dco_ip_do_visitante() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	return $ip;
}

/**
 * "Impressão digital" efêmera do visitante, usada SÓ para limites de
 * abuso de curtíssimo prazo (nunca para identificar ninguém, nunca
 * gravada nas métricas em si): hash do IP + um "sal" que muda todo dia
 * (baseado em wp_salt(), único e secreto desta instalação do WordPress).
 * Como o sal muda à meia-noite, o mesmo visitante gera um hash diferente
 * a cada dia — impossível reconstruir o IP a partir do hash, e impossível
 * cruzar o comportamento da mesma pessoa de um dia para o outro a partir
 * dele. Só existe dentro de transients de poucos minutos (ver
 * dco_limite_item_excedido()) — nunca persiste além disso.
 */
function dco_hash_visitante_do_dia() {
	$sal = wp_salt( 'auth' ) . current_time( 'Y-m-d' );
	return hash( 'sha256', dco_ip_do_visitante() . $sal );
}

/**
 * Limite fino por ITEM específico (ex.: "este mesmo anúncio") dentro de
 * uma janela curta — complementa dco_limite_por_ip_excedido() (que só
 * limita o TOTAL de envios, sem distinguir em qual item), e é o que
 * realmente evita alguém inflar artificialmente o clique de UM anúncio
 * específico clicando nele repetidamente. $maximo pequeno e $janela
 * curta de propósito — é para pegar repetição rápida (um script, um
 * dedo preso no clique), não para limitar interesse genuíno ao longo do
 * dia.
 */
function dco_limite_item_excedido( $contexto, $chave_item, $maximo = 1, $janela_segundos = 60 ) {
	$visitante = dco_hash_visitante_do_dia();
	$key = 'dco_item_' . $contexto . '_' . md5( $visitante . '|' . $chave_item );
	$contagem = (int) get_transient( $key );
	if ( $contagem >= $maximo ) {
		return true;
	}
	set_transient( $key, $contagem + 1, $janela_segundos );
	return false;
}

/**
 * Checagem leve de origem: aceita quando não há Origin/Referer (comum em
 * navegadores que priorizam privacidade, ou em requisições diretas
 * legítimas) — só rejeita quando o cabeçalho EXISTE e aponta claramente
 * para outro domínio. Não usa isso para identificar visitantes, só para
 * descartar chamadas vindas de um script hospedado em outro site.
 */
function dco_origem_suspeita() {
	$origem = '';
	if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
		$origem = $_SERVER['HTTP_ORIGIN'];
	} elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
		$origem = $_SERVER['HTTP_REFERER'];
	}
	if ( ! $origem ) {
		return false; // sem cabeçalho nenhum — não penaliza, só não dá para confirmar.
	}
	$host_origem = wp_parse_url( $origem, PHP_URL_HOST );
	$host_site   = wp_parse_url( home_url(), PHP_URL_HOST );
	return $host_origem && $host_site && strcasecmp( $host_origem, $host_site ) !== 0;
}
