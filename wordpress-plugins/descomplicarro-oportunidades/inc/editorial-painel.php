<?php
/**
 * DESCOMPLICARRO — painel "Automação Editorial": configuração das
 * integrações (Google Drive, Brevo) e acompanhamento simples das
 * matérias processadas — gerado / aguardando / falha, com link da pasta
 * no Drive e botão de reprocessar manualmente. Nada sofisticado, só o
 * necessário para acompanhar e resolver problemas, como pedido.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dco_editorial_registrar_menu_admin' );
function dco_editorial_registrar_menu_admin() {
	add_menu_page(
		'Automação Editorial',
		'Automação Editorial',
		'manage_options',
		'dco-editorial',
		'dco_editorial_render_pagina',
		'dashicons-megaphone',
		28
	);
}

add_action( 'admin_post_dco_editorial_salvar_config', 'dco_editorial_admin_salvar_config' );
function dco_editorial_admin_salvar_config() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dco_editorial_config' ) ) {
		wp_die( 'Não autorizado.' );
	}

	$credenciais = isset( $_POST['dco_drive_credenciais'] ) ? wp_unslash( $_POST['dco_drive_credenciais'] ) : '';
	if ( '' !== trim( $credenciais ) ) {
		json_decode( $credenciais, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			wp_safe_redirect( add_query_arg( array( 'page' => 'dco-editorial', 'dco_erro' => 'json_invalido' ), admin_url( 'admin.php' ) ) );
			exit;
		}
		update_option( DCO_EDITORIAL_OPCAO_DRIVE_CREDENCIAIS, $credenciais );
	}

	if ( isset( $_POST['dco_drive_pasta'] ) && '' !== trim( wp_unslash( $_POST['dco_drive_pasta'] ) ) ) {
		update_option( DCO_EDITORIAL_OPCAO_DRIVE_PASTA_RAIZ, sanitize_text_field( wp_unslash( $_POST['dco_drive_pasta'] ) ) );
	}

	if ( isset( $_POST['dco_brevo_api_key'] ) && '' !== trim( wp_unslash( $_POST['dco_brevo_api_key'] ) ) ) {
		update_option( DCO_EDITORIAL_OPCAO_BREVO_API_KEY, sanitize_text_field( wp_unslash( $_POST['dco_brevo_api_key'] ) ) );
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'dco-editorial', 'dco_salvo' => '1' ), admin_url( 'admin.php' ) ) );
	exit;
}

add_action( 'admin_post_dco_editorial_reprocessar', 'dco_editorial_admin_reprocessar' );
function dco_editorial_admin_reprocessar() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dco_editorial_reprocessar' ) ) {
		wp_die( 'Não autorizado.' );
	}
	$post_id = isset( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
	if ( $post_id ) {
		dco_editorial_reprocessar( $post_id );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'dco-editorial', 'dco_reprocessado' => $post_id ), admin_url( 'admin.php' ) ) );
	exit;
}

function dco_editorial_rotulo_status( $status ) {
	$rotulos = array(
		'pendente'    => 'Aguardando',
		'processando' => 'Em processamento',
		'concluido'   => 'Gerado',
		'falha'       => 'Falha',
	);
	return $rotulos[ $status ] ?? $status;
}

function dco_editorial_render_pagina() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Não autorizado.' );
	}

	$drive_configurado = dco_editorial_drive_configurado();
	$email_configurado = dco_editorial_email_configurado();
	$pasta_raiz_atual   = get_option( DCO_EDITORIAL_OPCAO_DRIVE_PASTA_RAIZ );
	?>
	<div class="wrap">
		<h1>Automação Editorial — Instagram</h1>
		<p>Gera carrossel, Stories e legenda sugerida para o Instagram quando uma matéria é publicada, envia ao Google Drive e avisa por e-mail. A publicação no Instagram continua manual. Nada é publicado automaticamente em nenhuma rede.</p>

		<?php if ( isset( $_GET['dco_salvo'] ) ) : ?>
			<div class="notice notice-success"><p>Configuração salva.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['dco_erro'] ) && 'json_invalido' === $_GET['dco_erro'] ) : ?>
			<div class="notice notice-error"><p>O JSON da conta de serviço do Google não é válido — confira se colou o arquivo inteiro.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['dco_reprocessado'] ) ) : ?>
			<div class="notice notice-success"><p>Reprocessamento disparado para a matéria #<?php echo (int) $_GET['dco_reprocessado']; ?>.</p></div>
		<?php endif; ?>

		<h2>Configuração</h2>
		<table class="widefat" style="max-width:700px;margin-bottom:20px;">
			<tr><td><strong>Google Drive</strong></td><td><?php echo $drive_configurado ? '✅ configurado' : '⚠️ não configurado'; ?></td></tr>
			<tr><td><strong>E-mail (Brevo)</strong></td><td><?php echo $email_configurado ? '✅ configurado' : '⚠️ não configurado'; ?></td></tr>
		</table>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'dco_editorial_config' ); ?>
			<input type="hidden" name="action" value="dco_editorial_salvar_config" />

			<h3>Google Drive</h3>
			<p>Cole aqui o conteúdo do arquivo JSON da conta de serviço do Google (gerado no Google Cloud Console). Deixe em branco para manter o que já está salvo.</p>
			<p><textarea name="dco_drive_credenciais" class="large-text code" rows="6" placeholder="{&quot;type&quot;: &quot;service_account&quot;, ...}"></textarea></p>
			<p>
				<label>Pasta do Drive (link ou ID) —
				<input type="text" name="dco_drive_pasta" class="regular-text" value="<?php echo esc_attr( $pasta_raiz_atual ); ?>" placeholder="https://drive.google.com/drive/folders/..." />
				</label>
			</p>

			<h3>E-mail (Brevo)</h3>
			<p>Chave de API da Brevo (plano gratuito). Deixe em branco para manter a que já está salva.</p>
			<p><input type="text" name="dco_brevo_api_key" class="regular-text" placeholder="xkeysib-..." /></p>

			<?php submit_button( 'Salvar configuração' ); ?>
		</form>

		<h2>Matérias processadas</h2>
		<?php $linhas_status = dco_editorial_listar_status(); ?>
		<table class="widefat striped">
			<thead><tr><th>Matéria</th><th>Status</th><th>Atualizado em</th><th>Pasta no Drive</th><th>Ação</th></tr></thead>
			<tbody>
				<?php foreach ( $linhas_status as $linha ) :
					$link_edicao = get_edit_post_link( $linha['post_id'] );
					$titulo_materia = get_the_title( $linha['post_id'] );
					?>
					<tr>
						<td>
							<?php if ( $link_edicao && $titulo_materia ) : ?>
								<a href="<?php echo esc_url( $link_edicao ); ?>"><?php echo esc_html( $titulo_materia ); ?></a>
							<?php else : ?>
								#<?php echo (int) $linha['post_id']; ?> (matéria removida)
							<?php endif; ?>
						</td>
						<td>
							<?php echo esc_html( dco_editorial_rotulo_status( $linha['status'] ) ); ?>
							<?php if ( $linha['mensagem_erro'] ) : ?>
								<br><span style="color:#b32d2e;font-size:12px;"><?php echo esc_html( $linha['mensagem_erro'] ); ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $linha['atualizado_em'] ); ?></td>
						<td><?php echo $linha['link_drive'] ? '<a href="' . esc_url( $linha['link_drive'] ) . '" target="_blank">Abrir pasta</a>' : '—'; ?></td>
						<td>
							<a class="button button-small" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'dco_editorial_reprocessar', 'post_id' => $linha['post_id'] ), admin_url( 'admin-post.php' ) ), 'dco_editorial_reprocessar' ) ); ?>">Reprocessar</a>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $linhas_status ) ) : ?>
					<tr><td colspan="5">Nenhuma matéria processada ainda.</td></tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
