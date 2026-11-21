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
