<?php
/**
 * DESCOMPLICARRO — "Configurações Descomplicarro".
 * Página de opções nativa (Settings API do WordPress, sem plugin) com os
 * dados centralizados de contato/redes sociais/identidade visual. Qualquer
 * template que precisar de WhatsApp, e-mail ou redes sociais lê estes
 * valores via dc_option() (inc/helpers.php) — nunca hardcoded.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dc_register_options_page' );
function dc_register_options_page() {
	add_menu_page(
		'Configurações Descomplicarro',
		'Configurações Descomplicarro',
		'manage_options',
		'dc-configuracoes',
		'dc_render_options_page',
		'dashicons-admin-generic',
		61
	);
}

add_action( 'admin_init', 'dc_register_options_settings' );
function dc_register_options_settings() {
	register_setting( 'dc_options_group', 'dc_options', 'dc_sanitize_options' );
}

function dc_sanitize_options( $input ) {
	$clean = array();
	$text_fields = array( 'whatsapp', 'email', 'instagram', 'youtube', 'linkedin', 'tiktok', 'youtube_channel_id' );
	foreach ( $text_fields as $f ) {
		$clean[ $f ] = isset( $input[ $f ] ) ? sanitize_text_field( wp_unslash( $input[ $f ] ) ) : '';
	}
	// E-mail: só grava se for um endereço válido (ou vazio — fica "pendente").
	if ( $clean['email'] !== '' && ! is_email( $clean['email'] ) ) {
		add_settings_error( 'dc_options', 'email_invalido', 'E-mail informado não é válido — não foi salvo.' );
		$clean['email'] = get_option( 'dc_options' )['email'] ?? '';
	}
	// WhatsApp: mantém somente dígitos (formato internacional, ex.: 5511999998888).
	if ( $clean['whatsapp'] !== '' ) {
		$clean['whatsapp'] = preg_replace( '/[^0-9]/', '', $clean['whatsapp'] );
	}
	foreach ( array( 'logo_principal', 'logo_alternativo', 'favicon' ) as $img_field ) {
		$clean[ $img_field ] = isset( $input[ $img_field ] ) ? (int) $input[ $img_field ] : 0;
	}
	return $clean;
}

function dc_render_options_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$options = get_option( 'dc_options', array() );
	$get = function ( $key ) use ( $options ) {
		return isset( $options[ $key ] ) ? $options[ $key ] : '';
	};
	?>
	<div class="wrap">
		<h1>Configurações Descomplicarro</h1>
		<p>Dados centralizados de contato, redes sociais e identidade visual. Alterar um valor aqui atualiza automaticamente todos os botões e links do site que o utilizam (footer, WhatsApp dos CTAs de orçamento, etc.).</p>
		<p><strong>Enquanto um dado oficial não for informado, deixe o campo em branco.</strong> O site usará um placeholder claramente identificado em vez de publicar um contato inválido.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'dc_options_group' ); ?>

			<h2>Contato</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="dc_whatsapp">WhatsApp oficial</label></th>
					<td>
						<input type="text" id="dc_whatsapp" name="dc_options[whatsapp]" value="<?php echo esc_attr( $get( 'whatsapp' ) ); ?>" class="regular-text" placeholder="Ex.: 5511999998888 (código do país + DDD + número, somente dígitos)" />
						<p class="description">Enquanto vazio, os botões de WhatsApp usam o placeholder <code>WHATSAPP_DESCOMPLICARRO</code> em vez de um número.</p>
					</td>
				</tr>
				<tr>
					<th><label for="dc_email">E-mail oficial</label></th>
					<td>
						<input type="email" id="dc_email" name="dc_options[email]" value="<?php echo esc_attr( $get( 'email' ) ); ?>" class="regular-text" placeholder="contato@descomplicarro.com.br" />
						<p class="description">Enquanto vazio, o botão E-mail do footer usa o placeholder <code>EMAIL_DESCOMPLICARRO</code>.</p>
					</td>
				</tr>
			</table>

			<h2>Redes sociais</h2>
			<table class="form-table" role="presentation">
				<tr><th><label for="dc_instagram">Instagram</label></th><td><input type="url" id="dc_instagram" name="dc_options[instagram]" value="<?php echo esc_attr( $get( 'instagram' ) ); ?>" class="regular-text" placeholder="https://instagram.com/..." /></td></tr>
				<tr><th><label for="dc_youtube">YouTube (URL do canal)</label></th><td><input type="url" id="dc_youtube" name="dc_options[youtube]" value="<?php echo esc_attr( $get( 'youtube' ) ); ?>" class="regular-text" placeholder="https://youtube.com/@..." /></td></tr>
				<tr><th><label for="dc_youtube_channel_id">Identificador do canal do YouTube</label></th><td><input type="text" id="dc_youtube_channel_id" name="dc_options[youtube_channel_id]" value="<?php echo esc_attr( $get( 'youtube_channel_id' ) ); ?>" class="regular-text" placeholder="UCxxxxxxxxxxxxxxxxxxxxxx" /><p class="description">Usado futuramente para integração automática com o YouTube (etapa posterior — não implementada ainda).</p></td></tr>
				<tr><th><label for="dc_linkedin">LinkedIn</label></th><td><input type="url" id="dc_linkedin" name="dc_options[linkedin]" value="<?php echo esc_attr( $get( 'linkedin' ) ); ?>" class="regular-text" placeholder="https://linkedin.com/company/..." /></td></tr>
				<tr><th><label for="dc_tiktok">TikTok</label></th><td><input type="url" id="dc_tiktok" name="dc_options[tiktok]" value="<?php echo esc_attr( $get( 'tiktok' ) ); ?>" class="regular-text" placeholder="https://tiktok.com/@..." /></td></tr>
			</table>

			<h2>Identidade visual</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th>Logotipo principal</th>
					<td><?php dc_options_image_field( 'logo_principal', $get( 'logo_principal' ) ); ?></td>
				</tr>
				<tr>
					<th>Logotipo alternativo</th>
					<td><?php dc_options_image_field( 'logo_alternativo', $get( 'logo_alternativo' ) ); ?></td>
				</tr>
				<tr>
					<th>Favicon</th>
					<td><?php dc_options_image_field( 'favicon', $get( 'favicon' ) ); ?></td>
				</tr>
			</table>
			<p class="description">Enquanto nenhum logotipo for enviado, o site continua usando o monograma/wordmark de texto atual (idêntico ao aprovado na V1) — nada é substituído automaticamente por um espaço vazio.</p>

			<?php submit_button( 'Salvar configurações' ); ?>
		</form>
	</div>
	<?php
}

function dc_options_image_field( $key, $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	$url = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'medium' ) : '';
	echo '<div class="dc-image-field" data-key="dc_options[' . esc_attr( $key ) . ']">';
	echo '<div class="dc-image-preview" style="margin-bottom:6px;">';
	if ( $url ) {
		echo '<img src="' . esc_url( $url ) . '" style="max-width:160px;height:auto;display:block;border:1px solid #dcdcde;" />';
	}
	echo '</div>';
	printf( '<input type="hidden" class="dc-image-id" name="dc_options[%1$s]" value="%2$d" />', esc_attr( $key ), $attachment_id );
	echo '<button type="button" class="button dc-image-select">Selecionar imagem</button> ';
	echo '<button type="button" class="button dc-image-remove">Remover</button>';
	echo '</div>';
}
