<?php
/**
 * DESCOMPLICARRO Oportunidades — Currículo (CPT "dco_curriculo").
 *
 * Status de curadoria usa o sistema nativo de status do WordPress (mesmo
 * padrão já usado no GI CONECTA, ver cpt-oficinas.php do tema):
 *   - "pending"  = aguardando confirmação de e-mail (ainda não é público)
 *   - "publish"  = ativo e visível
 *   - "suspensa" (customizado) = denunciado/suspenso pela administração
 *   - "expirada" (customizado) = passou da validade de 12 meses sem renovação
 * Nada é apagado ao suspender/expirar — só deixa de aparecer publicamente.
 *
 * NESTA ENTREGA: CPT, metabox de administração e armazenamento protegido do
 * PDF já funcionam (o administrador já pode cadastrar/gerenciar pelo
 * wp-admin). O AUTOCADASTRO PÚBLICO (formulário no site, link mágico de
 * confirmação por e-mail, "gerenciar meu cadastro", "solicitar contato" e
 * denúncia) é a próxima parte desta mesma etapa — ver o relatório de
 * entrega para o que falta e o prazo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'dco_registrar_status_curriculo' );
function dco_registrar_status_curriculo() {
	register_post_status( 'suspensa', array(
		'label'                     => 'Suspenso',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Suspenso <span class="count">(%s)</span>', 'Suspensos <span class="count">(%s)</span>' ),
	) );
	register_post_status( 'expirada', array(
		'label'                     => 'Expirado',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Expirado <span class="count">(%s)</span>', 'Expirados <span class="count">(%s)</span>' ),
	) );
}

add_action( 'init', 'dco_registrar_cpt_curriculo' );
function dco_registrar_cpt_curriculo() {
	register_post_type( 'dco_curriculo', array(
		'labels' => array(
			'name'          => 'Currículos',
			'singular_name' => 'Currículo',
			'add_new_item'  => 'Adicionar currículo',
			'edit_item'     => 'Editar currículo',
			'new_item'      => 'Novo currículo',
			'view_item'     => 'Ver currículo',
			'search_items'  => 'Buscar currículos',
			'not_found'     => 'Nenhum currículo encontrado',
			'all_items'     => 'Currículos',
			'menu_name'     => 'Currículos',
		),
		// "public" fica false nesta entrega: a listagem/perfil público (parte
		// do autocadastro, ainda não construída) só é ligada quando essa parte
		// estiver pronta e autorizada — ver cabeçalho do arquivo.
		'public'             => false,
		'publicly_queryable' => false,
		'exclude_from_search'=> true,
		'show_ui'            => true,
		'show_in_menu'       => 'dco-oportunidades',
		'show_in_rest'       => false,
		'supports'           => array( 'title' ),
		'capability_type'    => array( 'dco_curriculo', 'dco_curriculos' ),
		'map_meta_cap'       => true,
	) );
}

/* ==========================================================================
   METABOX — dados do currículo
   ========================================================================== */
add_action( 'add_meta_boxes', 'dco_registrar_metabox_curriculo' );
function dco_registrar_metabox_curriculo() {
	add_meta_box( 'dco_curriculo_dados', 'Dados do currículo', 'dco_render_metabox_curriculo', 'dco_curriculo', 'normal', 'high' );
}

function dco_render_metabox_curriculo( $post ) {
	wp_nonce_field( 'dco_save_curriculo', 'dco_curriculo_nonce' );

	dco_admin_text( $post, 'dco_cargo', 'Cargo / função desejada' );
	dco_admin_select( $post, 'dco_estado', 'Estado (UF)', dco_uf_list() );
	dco_admin_text( $post, 'dco_cidade', 'Cidade', 'Precisa ser um município real do estado selecionado.' );
	dco_admin_text( $post, 'dco_telefone', 'Telefone / WhatsApp' );
	dco_admin_email( $post, 'dco_email', 'E-mail', 'NUNCA exibido publicamente — usado só para a confirmação de cadastro e o link de gestão.' );
	dco_admin_file( $post, 'dco_curriculo_pdf', 'Currículo em PDF', 'Somente PDF, até 5 MB. Verificado pelo conteúdo real do arquivo (não só pela extensão) e movido para armazenamento protegido ao salvar.' );

	dco_admin_section_title( 'Informações de controle' );
	dco_admin_readonly_info( 'E-mail confirmado', get_post_meta( $post->ID, 'dco_email_confirmado', true ) ? 'Sim' : 'Não' );
	$expira = get_post_meta( $post->ID, 'dco_expira_em', true );
	dco_admin_readonly_info( 'Expira em', $expira ? $expira : '— (defina ao publicar)' );
	dco_admin_readonly_info( 'Denúncias recebidas', (int) get_post_meta( $post->ID, 'dco_denuncias', true ) );

	echo '<p style="color:#646970;font-size:12px;margin-top:16px;">Status: "Em avaliação" (pendente) até a confirmação de e-mail; "Publicado" = ativo e visível; "Suspenso" = retirado do ar pela administração (denúncia/moderação); "Expirado" = passou de 12 meses sem renovação. Definido na caixa "Publicar", à direita.</p>';
}

add_action( 'save_post_dco_curriculo', 'dco_save_metabox_curriculo' );
function dco_save_metabox_curriculo( $post_id ) {
	if ( ! isset( $_POST['dco_curriculo_nonce'] ) || ! wp_verify_nonce( $_POST['dco_curriculo_nonce'], 'dco_save_curriculo' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	dco_salvar_campos_texto( $post_id, array( 'dco_cargo', 'dco_cidade', 'dco_telefone' ) );
	dco_salvar_campo_email( $post_id, 'dco_email' );

	if ( isset( $_POST['dco_estado'] ) ) {
		$uf = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ) );
		if ( array_key_exists( $uf, dco_uf_list() ) ) {
			update_post_meta( $post_id, 'dco_estado', $uf );
		} else {
			delete_post_meta( $post_id, 'dco_estado' );
		}
	}

	if ( isset( $_POST['dco_curriculo_pdf'] ) ) {
		$novo_attachment_id = (int) $_POST['dco_curriculo_pdf'];
		$atual_attachment_id = (int) get_post_meta( $post_id, 'dco_curriculo_pdf', true );
		if ( $novo_attachment_id > 0 && $novo_attachment_id !== $atual_attachment_id ) {
			$resultado = dco_validar_e_proteger_pdf( $novo_attachment_id );
			if ( is_wp_error( $resultado ) ) {
				set_transient( 'dco_erro_pdf_' . $post_id, $resultado->get_error_message(), 60 );
			} else {
				update_post_meta( $post_id, 'dco_curriculo_pdf', $novo_attachment_id );
			}
		} elseif ( $novo_attachment_id === 0 ) {
			delete_post_meta( $post_id, 'dco_curriculo_pdf' );
		}
	}

	// Primeira publicação: define a validade de 12 meses (renovável depois pelo profissional).
	if ( get_post_status( $post_id ) === 'publish' && ! get_post_meta( $post_id, 'dco_expira_em', true ) ) {
		update_post_meta( $post_id, 'dco_expira_em', gmdate( 'Y-m-d', strtotime( '+12 months' ) ) );
	}
}

add_action( 'admin_notices', 'dco_admin_notice_erro_pdf' );
function dco_admin_notice_erro_pdf() {
	global $post;
	if ( ! $post || $post->post_type !== 'dco_curriculo' ) {
		return;
	}
	$erro = get_transient( 'dco_erro_pdf_' . $post->ID );
	if ( $erro ) {
		delete_transient( 'dco_erro_pdf_' . $post->ID );
		echo '<div class="notice notice-error"><p><strong>Currículo em PDF não foi aceito:</strong> ' . esc_html( $erro ) . '</p></div>';
	}
}

/**
 * Valida o arquivo enviado (extensão REAL do conteúdo, não só o nome, via
 * wp_check_filetype_and_ext — que confere o tipo verdadeiro do arquivo; e
 * tamanho máximo de 5 MB) e, se válido, move fisicamente o arquivo da pasta
 * pública de uploads para a pasta protegida (fora de listagem/execução
 * direta — ver dco_garantir_pasta_protegida() no arquivo principal do
 * plugin). O anexo é marcado como privado para não aparecer em nenhuma
 * consulta pública de mídia do WordPress.
 */
function dco_validar_e_proteger_pdf( $attachment_id ) {
	$caminho_atual = get_attached_file( $attachment_id );
	if ( ! $caminho_atual || ! file_exists( $caminho_atual ) ) {
		return new WP_Error( 'dco_arquivo_ausente', 'o arquivo enviado não foi encontrado no servidor.' );
	}

	$tamanho_maximo = 5 * MB_IN_BYTES;
	if ( filesize( $caminho_atual ) > $tamanho_maximo ) {
		wp_delete_attachment( $attachment_id, true );
		return new WP_Error( 'dco_arquivo_grande', 'o arquivo excede o limite de 5 MB.' );
	}

	$checagem = wp_check_filetype_and_ext( $caminho_atual, wp_basename( $caminho_atual ) );
	if ( empty( $checagem['ext'] ) || strtolower( $checagem['ext'] ) !== 'pdf' || $checagem['type'] !== 'application/pdf' ) {
		wp_delete_attachment( $attachment_id, true );
		return new WP_Error( 'dco_tipo_invalido', 'o arquivo não é um PDF válido (o conteúdo real do arquivo foi conferido, não só o nome).' );
	}

	$pasta_protegida = dco_pasta_protegida_path();
	$nome_final      = 'curriculo-' . $attachment_id . '-' . wp_generate_password( 12, false, false ) . '.pdf';
	$destino         = trailingslashit( $pasta_protegida ) . $nome_final;

	if ( ! @rename( $caminho_atual, $destino ) ) {
		return new WP_Error( 'dco_mover_falhou', 'não foi possível mover o arquivo para o armazenamento protegido.' );
	}

	update_attached_file( $attachment_id, $destino );
	wp_update_post( array( 'ID' => $attachment_id, 'post_status' => 'private' ) );
	// Remove qualquer metadado de tamanhos/derivados (não se aplica a PDF, mas evita URLs públicas residuais).
	delete_post_meta( $attachment_id, '_wp_attachment_metadata' );

	return true;
}
