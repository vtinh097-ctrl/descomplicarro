<?php
/**
 * DESCOMPLICARRO — disparo e orquestração da Automação Editorial (Etapa 4).
 *
 * Dispara no PRIMEIRO publish de uma matéria (inclusive por agendamento —
 * ver a condição $old_status !== 'publish' abaixo, que cobre tanto
 * "rascunho/revisão → publicado" quanto "agendado → publicado" via
 * wp-cron, mas nunca um simples salvar de uma matéria já publicada).
 *
 * O processamento pesado (gerar imagens, enviar ao Drive, mandar e-mail)
 * SEMPRE roda em segundo plano via wp_schedule_single_event — nunca na
 * mesma requisição que publica a matéria, para a publicação do site NUNCA
 * ficar mais lenta ou travar por causa desta automação.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'transition_post_status', 'dco_editorial_ao_publicar_materia', 10, 3 );
function dco_editorial_ao_publicar_materia( $novo_status, $antigo_status, $post ) {
	if ( 'post' !== $post->post_type ) {
		return;
	}
	if ( 'publish' !== $novo_status || 'publish' === $antigo_status ) {
		return; // só nos interessa a TRANSIÇÃO para publicado, nunca um resalvar de algo já publicado.
	}
	if ( dco_editorial_ja_processado_ou_em_andamento( $post->ID ) ) {
		return;
	}

	dco_editorial_marcar_status( $post->ID, 'pendente' );
	// agenda para "agora" (próxima execução do wp_cron) em vez de processar
	// na própria requisição — a publicação da matéria nunca espera por isso.
	if ( ! wp_next_scheduled( 'dco_editorial_processar_evento', array( $post->ID ) ) ) {
		wp_schedule_single_event( time() + 5, 'dco_editorial_processar_evento', array( $post->ID ) );
	}
}

add_action( 'dco_editorial_processar_evento', 'dco_editorial_processar_materia' );

/**
 * Processa uma matéria: gera os arquivos, envia ao Drive, manda o e-mail
 * de notificação. Qualquer falha em qualquer etapa é registrada (nunca
 * lançada como erro fatal do PHP) — fica disponível para reprocessar pelo
 * painel administrativo, sem precisar publicar a matéria de novo.
 */
function dco_editorial_processar_materia( $post_id ) {
	if ( dco_editorial_ja_processado_ou_em_andamento( $post_id ) ) {
		return;
	}
	dco_editorial_marcar_status( $post_id, 'processando' );

	$pasta_trabalho = trailingslashit( get_temp_dir() ) . 'dco-editorial-' . $post_id . '-' . wp_generate_password( 6, false );

	try {
		$resultado = dco_editorial_gerar_arquivos( $post_id, $pasta_trabalho );
		if ( empty( $resultado['carrossel'] ) ) {
			throw new Exception( 'Nenhum slide de carrossel foi gerado.' );
		}

		$legenda    = dco_editorial_montar_legenda( $resultado['pacote'] );
		$link_drive = dco_editorial_drive_enviar_pacote( $post_id, $resultado, $legenda );
		if ( is_wp_error( $link_drive ) ) {
			throw new Exception( $link_drive->get_error_message() );
		}

		$envio_email = dco_editorial_enviar_notificacao( $post_id, $link_drive );
		if ( is_wp_error( $envio_email ) ) {
			// os materiais FORAM gerados e enviados ao Drive — isso não é uma
			// falha da automação em si, só do aviso. Registra como concluído,
			// mas guarda o aviso de e-mail na mensagem de erro para visibilidade.
			dco_editorial_marcar_status( $post_id, 'concluido', $link_drive, 'Aviso: e-mail de notificação não enviado — ' . $envio_email->get_error_message() );
		} else {
			dco_editorial_marcar_status( $post_id, 'concluido', $link_drive );
		}
	} catch ( Exception $e ) {
		dco_editorial_marcar_status( $post_id, 'falha', '', $e->getMessage() );
		dco_editorial_enviar_notificacao( $post_id, '', $e->getMessage() );
	}

	dco_editorial_limpar_pasta( $pasta_trabalho );
}

function dco_editorial_limpar_pasta( $pasta ) {
	if ( ! is_dir( $pasta ) ) {
		return;
	}
	$itens = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $pasta, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
	foreach ( $itens as $item ) {
		$item->isDir() ? @rmdir( $item->getPathname() ) : @unlink( $item->getPathname() );
	}
	@rmdir( $pasta );
}

/** Reprocessar manualmente pelo painel — ver inc/editorial-painel.php. */
function dco_editorial_reprocessar( $post_id ) {
	global $wpdb;
	$wpdb->delete( dco_editorial_nome_tabela(), array( 'post_id' => $post_id ) );
	dco_editorial_processar_materia( $post_id );
}
