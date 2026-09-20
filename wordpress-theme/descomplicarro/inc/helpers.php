<?php
/**
 * DESCOMPLICARRO — funções auxiliares de leitura de conteúdo (uso no front-end).
 *
 * Estas funções são o único ponto de leitura de post meta usado pelos
 * templates. Isso mantém a lógica "buscar valor, aplicar fallback, escapar"
 * em um único lugar em vez de repetida em cada arquivo de template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lê um campo de texto/URL do post atual (ou informado), com fallback.
 * Sempre escapado para uso seguro em atributos/texto simples.
 */
function dc_field( $key, $default = '', $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return ( $value !== '' && $value !== null ) ? $value : $default;
}

/** Igual a dc_field(), mas sem escapar — usar apenas dentro de esc_html()/esc_attr() no template. */
function dc_field_raw( $key, $default = '', $post_id = null ) {
	return dc_field( $key, $default, $post_id );
}

/** Campo de texto: imprime já escapado. */
function dc_text( $key, $default = '', $post_id = null ) {
	echo esc_html( dc_field( $key, $default, $post_id ) );
}

/** Campo de parágrafo/textarea: permite quebras de linha simples, escapa o resto. */
function dc_paragraph( $key, $default = '', $post_id = null ) {
	echo wp_kses_post( wpautop( dc_field( $key, $default, $post_id ) ) );
}

/**
 * Imprime um campo de texto de várias linhas como uma sequência de <p>,
 * uma tag por linha não vazia — usado para blocos de parágrafos "corridos"
 * armazenados em um único textarea (ver inc/meta-boxes.php).
 */
function dc_paragraphs_lines( $key, $default_lines, $post_id = null, $class = '' ) {
	$raw   = dc_field_raw( $key, '', $post_id );
	$lines = $raw !== '' ? preg_split( '/\r\n|\r|\n/', trim( $raw ) ) : (array) $default_lines;
	$class_attr = $class ? ' class="' . esc_attr( $class ) . '"' : '';
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		echo '<p' . $class_attr . '>' . esc_html( $line ) . '</p>';
	}
}

/** Campo de URL: imprime já escapado para uso em href. */
function dc_url( $key, $default = '#', $post_id = null ) {
	$value = dc_field( $key, $default, $post_id );
	echo esc_url( $value ? $value : $default );
}

/**
 * Campo de imagem: aceita um ID de anexo (Biblioteca de Mídia) armazenado
 * em post meta e devolve a URL. Se vazio, usa o placeholder do tema.
 */
function dc_image_url( $key, $post_id = null, $fallback_file = 'placeholder-photo.svg' ) {
	$post_id      = $post_id ? $post_id : get_the_ID();
	$attachment_id = (int) get_post_meta( $post_id, $key, true );
	if ( $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'large' );
		if ( $url ) {
			return $url;
		}
	}
	return get_template_directory_uri() . '/assets/images/' . $fallback_file;
}

/** Verifica se uma seção opcional está ativa (padrão: ativa, quando o campo nunca foi salvo). */
function dc_section_active( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return $value === '' ? true : (bool) $value;
}

/**
 * Lê um repetidor (lista de itens) salvo como array associativo em post meta.
 * Retorna sempre um array (nunca null), para uso direto em foreach.
 */
function dc_repeater( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return is_array( $value ) ? $value : array();
}

/**
 * CONFIGURAÇÕES GLOBAIS — lê um valor da página de opções "Configurações
 * Descomplicarro" (option_name: dc_options). Ver inc/options-page.php.
 */
function dc_option( $key, $default = '' ) {
	$options = get_option( 'dc_options', array() );
	return ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) ? $options[ $key ] : $default;
}

/**
 * Monta um link do WhatsApp (wa.me) a partir do número cadastrado em
 * Configurações Descomplicarro + uma mensagem pré-preenchida específica.
 *
 * Enquanto o número oficial não for cadastrado no painel, o link aponta
 * para um placeholder claramente identificado (WHATSAPP_DESCOMPLICARRO),
 * nunca para um número inventado.
 */
function dc_whatsapp_link( $message = '' ) {
	$numero = dc_option( 'whatsapp', '' );
	$numero = $numero !== '' ? preg_replace( '/[^0-9]/', '', $numero ) : 'WHATSAPP_DESCOMPLICARRO';
	$url    = 'https://wa.me/' . $numero;
	if ( $message !== '' ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/** Monta o mailto: do e-mail oficial cadastrado, ou o placeholder enquanto não houver e-mail definido. */
function dc_email_link() {
	$email = dc_option( 'email', '' );
	return 'mailto:' . ( $email !== '' ? $email : 'EMAIL_DESCOMPLICARRO' );
}

/** Devolve o valor a exibir para o e-mail/whatsapp quando usados como texto visível (não apenas link). */
function dc_option_or_pending( $key, $label_pendente ) {
	$value = dc_option( $key, '' );
	return $value !== '' ? $value : $label_pendente;
}
