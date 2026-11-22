<?php
/**
 * DESCOMPLICARRO — notificação por e-mail da Automação Editorial, via
 * Brevo (plano gratuito, aprovado) — API HTTP direta (wp_remote_post),
 * sem SDK. A chave de API fica guardada como opção do WordPress,
 * preenchida pelo administrador no painel — nunca pedida ou exibida em
 * texto puro na conversa com o Claude.
 *
 * Usa a API transacional da Brevo em vez do wp_mail() nativo do
 * WordPress porque, como já registrado no plano desta etapa, e-mails
 * disparados por wp_mail() costumam ser entregues de forma inconsistente
 * em hospedagem compartilhada (inclusive a Hostinger).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_EDITORIAL_OPCAO_BREVO_API_KEY', 'dco_editorial_brevo_api_key' );
define( 'DCO_EDITORIAL_EMAIL_DESTINO', 'vitor@descomplicarro.com.br' );

function dco_editorial_email_configurado() {
	return (bool) get_option( DCO_EDITORIAL_OPCAO_BREVO_API_KEY );
}

/**
 * Envia a notificação de que os materiais de uma matéria foram gerados —
 * ou, em caso de falha na geração/upload, avisa qual foi o problema, para
 * que ninguém fique esperando um e-mail que nunca chegaria.
 */
function dco_editorial_enviar_notificacao( $post_id, $link_pasta_drive, $erro = null ) {
	if ( ! dco_editorial_email_configurado() ) {
		return new WP_Error( 'dco_email_nao_configurado', 'Notificação por e-mail (Brevo) ainda não foi configurada.' );
	}

	$titulo = get_the_title( $post_id );
	$link_materia = get_permalink( $post_id );

	if ( $erro ) {
		$assunto = '⚠️ Falha ao gerar materiais — ' . $titulo;
		$corpo_html = sprintf(
			'<p>Não foi possível gerar os materiais de Instagram para a matéria <strong>%s</strong>.</p><p><strong>Erro:</strong> %s</p><p>Link da matéria: <a href="%s">%s</a></p><p>Você pode tentar novamente pelo painel "Automação Editorial" no WordPress, sem precisar publicar a matéria de novo.</p>',
			esc_html( $titulo ),
			esc_html( $erro ),
			esc_url( $link_materia ),
			esc_html( $link_materia )
		);
	} else {
		$assunto = 'Materiais prontos — ' . $titulo;
		$corpo_html = sprintf(
			'<p>Os materiais de Instagram (carrossel + Stories + legenda) da matéria <strong>%s</strong> já estão prontos.</p><p>Matéria: <a href="%s">%s</a></p><p>Pasta no Google Drive: <a href="%s">%s</a></p>',
			esc_html( $titulo ),
			esc_url( $link_materia ),
			esc_html( $link_materia ),
			esc_url( $link_pasta_drive ),
			esc_html( $link_pasta_drive )
		);
	}

	$resposta = wp_remote_post( 'https://api.brevo.com/v3/smtp/email', array(
		'timeout' => 30,
		'headers' => array(
			'api-key'      => get_option( DCO_EDITORIAL_OPCAO_BREVO_API_KEY ),
			'Content-Type' => 'application/json',
			'Accept'       => 'application/json',
		),
		'body'    => wp_json_encode( array(
			'sender'      => array( 'name' => 'DESCOMPLICARRO — Automação Editorial', 'email' => 'automacao@descomplicarro.com.br' ),
			'to'          => array( array( 'email' => DCO_EDITORIAL_EMAIL_DESTINO ) ),
			'subject'     => $assunto,
			'htmlContent' => $corpo_html,
		) ),
	) );

	if ( is_wp_error( $resposta ) ) {
		return $resposta;
	}
	$codigo = wp_remote_retrieve_response_code( $resposta );
	if ( $codigo >= 300 ) {
		return new WP_Error( 'dco_email_http_' . $codigo, 'Brevo respondeu ' . $codigo . ': ' . wp_remote_retrieve_body( $resposta ) );
	}
	return true;
}
