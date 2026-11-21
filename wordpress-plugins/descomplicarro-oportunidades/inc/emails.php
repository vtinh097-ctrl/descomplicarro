<?php
/**
 * DESCOMPLICARRO Oportunidades — e-mails transacionais (confirmação,
 * gestão, solicitação de contato, denúncia). Usa wp_mail() nativo do
 * WordPress — ver nota em inc/public-forms.php sobre a recomendação de um
 * serviço de envio dedicado para produção (pendência de aprovação já
 * registrada no plano técnico).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_email_remetente_nome() {
	return 'DESCOMPLICARRO';
}

function dco_enviar_email( $destinatario, $assunto, $corpo_html ) {
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	return wp_mail( $destinatario, $assunto, dco_email_layout( $corpo_html ), $headers );
}

/** Envelope visual simples e consistente para todos os e-mails deste plugin. */
function dco_email_layout( $conteudo_html ) {
	return '<div style="font-family:Arial,sans-serif;max-width:560px;margin:0 auto;color:#252525;">'
		. '<p style="font-size:12px;letter-spacing:1px;color:#C65A32;text-transform:uppercase;margin-bottom:24px;">DESCOMPLICARRO</p>'
		. $conteudo_html
		. '<p style="font-size:12px;color:#888;margin-top:32px;border-top:1px solid #eee;padding-top:16px;">Este é um e-mail automático do site descomplicarro.com.br — se você não reconhece esta ação, pode ignorá-lo com segurança.</p>'
		. '</div>';
}

function dco_email_confirmacao_curriculo( $post_id ) {
	$email = get_post_meta( $post_id, 'dco_email', true );
	if ( ! $email ) {
		return false;
	}
	$token = dco_definir_token( $post_id, 'confirmacao', DCO_TOKEN_CONFIRMACAO_SEGUNDOS );
	$link  = dco_url_acao( 'confirmar', 'curriculo', $post_id, $token );
	$corpo = '<h2 style="font-size:18px;">Confirme seu cadastro de currículo</h2>'
		. '<p>Recebemos o cadastro do seu currículo no banco de Oportunidades Automotivas do DESCOMPLICARRO. Para publicá-lo, confirme seu e-mail clicando no link abaixo (válido por 48 horas):</p>'
		. '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#252525;color:#F4F1EA;padding:12px 20px;text-decoration:none;">Confirmar meu cadastro</a></p>'
		. '<p style="font-size:13px;color:#555;">Se você não fez este cadastro, ignore este e-mail — nada será publicado sem a confirmação.</p>';
	return dco_enviar_email( $email, 'Confirme seu cadastro de currículo — DESCOMPLICARRO', $corpo );
}

function dco_email_confirmacao_vaga( $post_id ) {
	$email = get_post_meta( $post_id, 'dco_email_candidatura', true );
	if ( ! $email ) {
		return false;
	}
	$token = dco_definir_token( $post_id, 'confirmacao', DCO_TOKEN_CONFIRMACAO_SEGUNDOS );
	$link  = dco_url_acao( 'confirmar', 'vaga', $post_id, $token );
	$corpo = '<h2 style="font-size:18px;">Confirme a publicação da vaga</h2>'
		. '<p>Recebemos o cadastro da vaga no banco de Oportunidades Automotivas do DESCOMPLICARRO. Para publicá-la, confirme seu e-mail clicando no link abaixo (válido por 48 horas):</p>'
		. '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#252525;color:#F4F1EA;padding:12px 20px;text-decoration:none;">Confirmar publicação</a></p>'
		. '<p style="font-size:13px;color:#555;">Se você não fez este cadastro, ignore este e-mail — nada será publicado sem a confirmação.</p>';
	return dco_enviar_email( $email, 'Confirme a publicação da vaga — DESCOMPLICARRO', $corpo );
}

/** Enviado logo após a confirmação — o link de gestão (editar/renovar/excluir) por conta própria. */
function dco_email_link_gestao( $post_id, $tipo_post ) {
	$email = $tipo_post === 'vaga'
		? get_post_meta( $post_id, 'dco_email_candidatura', true )
		: get_post_meta( $post_id, 'dco_email', true );
	if ( ! $email ) {
		return false;
	}
	$token = dco_definir_token( $post_id, 'gestao', DCO_TOKEN_GESTAO_SEGUNDOS );
	$link  = dco_url_acao( 'gerenciar', $tipo_post, $post_id, $token );
	$rotulo = $tipo_post === 'vaga' ? 'sua vaga' : 'seu currículo';
	$corpo = '<h2 style="font-size:18px;">Cadastro confirmado</h2>'
		. '<p>Seu cadastro está publicado. Use o link abaixo agora para editar, renovar ou excluir ' . esc_html( $rotulo ) . ' — sem precisar de login nem senha.</p>'
		. '<p><a href="' . esc_url( $link ) . '" style="display:inline-block;background:#C65A32;color:#F4F1EA;padding:12px 20px;text-decoration:none;">Gerenciar meu cadastro</a></p>'
		. '<p style="font-size:13px;color:#555;">Por segurança, este link funciona uma única vez e expira em 30 minutos. Se precisar gerenciar ' . esc_html( $rotulo ) . ' depois, basta solicitar um novo link a qualquer momento — não é preciso guardar este e-mail.</p>';
	return dco_enviar_email( $email, 'Seu link de gestão — DESCOMPLICARRO', $corpo );
}

/** "Solicitar contato" (currículos): avisa o profissional, SEM que a equipe DESCOMPLICARRO veja quem solicitou. */
function dco_email_solicitar_contato( $post_id, $nome_solicitante, $email_solicitante, $mensagem ) {
	$email = get_post_meta( $post_id, 'dco_email', true );
	if ( ! $email ) {
		return false;
	}
	$corpo = '<h2 style="font-size:18px;">Alguém quer falar com você</h2>'
		. '<p>Uma empresa/pessoa encontrou seu currículo no DESCOMPLICARRO e gostaria de entrar em contato. Você decide se responde — seus dados nunca são compartilhados automaticamente.</p>'
		. '<p><strong>Nome:</strong> ' . esc_html( $nome_solicitante ) . '<br>'
		. '<strong>E-mail para resposta:</strong> ' . esc_html( $email_solicitante ) . '</p>'
		. '<p><strong>Mensagem:</strong><br>' . nl2br( esc_html( $mensagem ) ) . '</p>';
	return dco_enviar_email( $email, 'Alguém quer falar com você — DESCOMPLICARRO', $corpo );
}

/** Notifica a administração quando um cadastro recebe uma denúncia — nunca suspende sozinho, só avisa para revisão manual. */
function dco_email_notificar_denuncia( $post_id, $tipo_post, $motivo ) {
	$admin_email = get_option( 'admin_email' );
	$titulo      = get_the_title( $post_id );
	$total       = (int) get_post_meta( $post_id, 'dco_denuncias', true );
	$editar_url  = admin_url( 'post.php?post=' . (int) $post_id . '&action=edit' );
	$corpo = '<h2 style="font-size:18px;">Denúncia recebida</h2>'
		. '<p><strong>Tipo:</strong> ' . esc_html( $tipo_post === 'vaga' ? 'Vaga' : 'Currículo' ) . '<br>'
		. '<strong>Título:</strong> ' . esc_html( $titulo ) . '<br>'
		. '<strong>Total de denúncias recebidas:</strong> ' . $total . '</p>'
		. ( $motivo ? '<p><strong>Motivo informado:</strong><br>' . nl2br( esc_html( $motivo ) ) . '</p>' : '' )
		. '<p><a href="' . esc_url( $editar_url ) . '">Revisar no painel administrativo</a></p>';
	return dco_enviar_email( $admin_email, 'Denúncia recebida em Oportunidades — DESCOMPLICARRO', $corpo );
}
