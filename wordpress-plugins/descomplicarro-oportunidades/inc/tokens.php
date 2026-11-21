<?php
/**
 * DESCOMPLICARRO Oportunidades — tokens de link mágico (sem senha),
 * compartilhados entre Currículo e Vaga.
 *
 * Dois tipos de token por cadastro:
 *   - "confirmacao": uso ÚNICO, confirma o e-mail e publica o cadastro.
 *     Curto (48h) — se não for usado, o cadastro nunca sai de "Em avaliação"
 *     e pode ser limpo depois (ver rotina de limpeza, a ser criada).
 *   - "gestao": uso MÚLTIPLO dentro da validade (renovável), para o próprio
 *     profissional/empresa editar, renovar ou excluir o próprio cadastro —
 *     sem precisar criar conta nem senha.
 *
 * NUNCA se guarda o token em texto puro — só o hash (SHA-256). Quem só tem
 * acesso ao banco de dados (um backup vazado, por exemplo) não consegue
 * forjar nem reconstruir um link válido a partir do hash.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_TOKEN_CONFIRMACAO_SEGUNDOS', 2 * DAY_IN_SECONDS );
define( 'DCO_TOKEN_GESTAO_SEGUNDOS', 395 * DAY_IN_SECONDS ); // 13 meses — folga de 1 mês sobre a validade de 12 meses do conteúdo.

function dco_gerar_token_bruto() {
	return bin2hex( random_bytes( 24 ) ); // 48 caracteres hexadecimais — não previsível por força bruta.
}

function dco_hash_token( $token_bruto ) {
	return hash( 'sha256', $token_bruto );
}

/**
 * Gera e grava um novo token do tipo informado para o post, substituindo
 * qualquer token anterior do mesmo tipo (um link antigo deixa de funcionar
 * assim que um novo é emitido). Retorna o token BRUTO (só existe nesta
 * chamada — nunca é lido de volta do banco).
 */
function dco_definir_token( $post_id, $tipo, $duracao_segundos ) {
	$token_bruto = dco_gerar_token_bruto();
	update_post_meta( $post_id, "dco_token_{$tipo}_hash", dco_hash_token( $token_bruto ) );
	update_post_meta( $post_id, "dco_token_{$tipo}_expira", time() + (int) $duracao_segundos );
	return $token_bruto;
}

/** Confere um token bruto recebido (ex.: da URL) contra o hash gravado, em tempo constante (hash_equals), e a validade. */
function dco_token_valido( $post_id, $tipo, $token_bruto ) {
	if ( ! $post_id || ! $token_bruto ) {
		return false;
	}
	$hash_gravado = get_post_meta( $post_id, "dco_token_{$tipo}_hash", true );
	$expira       = (int) get_post_meta( $post_id, "dco_token_{$tipo}_expira", true );
	if ( ! $hash_gravado || ! $expira || $expira < time() ) {
		return false;
	}
	return hash_equals( $hash_gravado, dco_hash_token( $token_bruto ) );
}

function dco_invalidar_token( $post_id, $tipo ) {
	delete_post_meta( $post_id, "dco_token_{$tipo}_hash" );
	delete_post_meta( $post_id, "dco_token_{$tipo}_expira" );
}

/** Monta a URL pública de uma ação (confirmar, gerenciar, candidatar etc.) — sempre na página que tem o shortcode [dco_oportunidades], via query var própria (ver inc/public-forms.php). */
function dco_url_acao( $acao, $tipo_post, $post_id, $token_bruto ) {
	return add_query_arg(
		array(
			'dco_acao'  => $acao,
			'dco_tipo'  => $tipo_post,
			'dco_id'    => (int) $post_id,
			'dco_token' => $token_bruto,
		),
		dco_pagina_oportunidades_url()
	);
}

/**
 * Descobre sozinha qual página do site tem o shortcode [dco_oportunidades]
 * — nunca aponta para a home por padrão (a home normalmente não tem o
 * shortcode, e um link para lá simplesmente ignoraria a ação). Guarda o ID
 * encontrado numa opção, mas sempre confere se aquela página AINDA existe e
 * AINDA tem o shortcode antes de confiar nela (mesma lição de não confiar
 * só numa marca "já resolvi uma vez" — a página pode ser editada, trocada
 * ou apagada a qualquer momento).
 */
function dco_pagina_oportunidades_url() {
	$id_guardado = (int) get_option( 'dco_pagina_oportunidades_id' );
	if ( $id_guardado ) {
		$pagina = get_post( $id_guardado );
		if ( $pagina && $pagina->post_status === 'publish' && has_shortcode( $pagina->post_content, 'dco_oportunidades' ) ) {
			return get_permalink( $pagina );
		}
	}

	$encontrada = get_posts( array(
		'post_type'      => 'any',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		's'              => '[dco_oportunidades',
		'no_found_rows'  => true,
	) );
	foreach ( $encontrada as $candidata ) {
		if ( has_shortcode( $candidata->post_content, 'dco_oportunidades' ) ) {
			update_option( 'dco_pagina_oportunidades_id', $candidata->ID );
			return get_permalink( $candidata );
		}
	}

	// Nenhuma página com o shortcode foi encontrada — isto é uma situação de
	// configuração incompleta do site (o shortcode precisa estar em alguma
	// página publicada). Retorna a home como último recurso só para o link
	// não quebrar, mas isto deve ser corrigido antes de ir ao ar.
	return home_url( '/' );
}
