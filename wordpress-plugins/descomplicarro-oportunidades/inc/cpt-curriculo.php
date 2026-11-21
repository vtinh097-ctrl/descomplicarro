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
 * "public" fica false DE PROPÓSITO: este CPT nunca tem página/arquivo
 * gerado automaticamente pelo WordPress (sem permalink individual, sem
 * URL previsível por ID) — toda exibição pública passa pelos shortcodes em
 * inc/public-forms.php e inc/busca.php, que decidem exatamente o que
 * mostrar (nunca e-mail, telefone ou o PDF em si).
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
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => 'dco-oportunidades',
		'show_in_rest'        => false,
		'supports'            => array( 'title' ),
		'capability_type'     => array( 'dco_curriculo', 'dco_curriculos' ),
		'map_meta_cap'        => true,
	) );
}

/* ==========================================================================
   METABOX — dados do currículo (administração pelo wp-admin)
   ========================================================================== */
add_action( 'add_meta_boxes', 'dco_registrar_metabox_curriculo' );
function dco_registrar_metabox_curriculo() {
	add_meta_box( 'dco_curriculo_dados', 'Dados do currículo', 'dco_render_metabox_curriculo', 'dco_curriculo', 'normal', 'high' );
}

/**
 * O formulário de edição padrão do WordPress só ganha enctype
 * "multipart/form-data" sozinho quando o tipo de conteúdo declara suporte a
 * "thumbnail" (comportamento antigo do núcleo, sem relação nenhuma com
 * upload de arquivo em metabox) — como "dco_curriculo" não declara esse
 * suporte (não precisamos de imagem destacada aqui), o campo de upload do
 * PDF simplesmente não seria enviado sem isto. "post_edit_form_tag" é o
 * gancho que o próprio WordPress documenta para plugins acrescentarem esse
 * atributo quando precisam de upload de arquivo num metabox.
 */
add_action( 'post_edit_form_tag', 'dco_adicionar_enctype_multipart_curriculo' );
function dco_adicionar_enctype_multipart_curriculo() {
	global $post;
	if ( $post && $post->post_type === 'dco_curriculo' ) {
		echo ' enctype="multipart/form-data"';
	}
}

function dco_render_metabox_curriculo( $post ) {
	wp_nonce_field( 'dco_save_curriculo', 'dco_curriculo_nonce' );

	dco_admin_text( $post, 'dco_cargo', 'Cargo / função desejada' );
	dco_admin_select( $post, 'dco_estado', 'Estado (UF)', dco_uf_list() );
	dco_admin_text( $post, 'dco_cidade', 'Cidade', 'Precisa ser um município real do estado selecionado.' );
	dco_admin_text( $post, 'dco_telefone', 'Telefone / WhatsApp' );
	dco_admin_email( $post, 'dco_email', 'E-mail', 'NUNCA exibido publicamente — usado só para a confirmação de cadastro e o link de gestão.' );
	dco_admin_textarea( $post, 'dco_resumo', 'Resumo profissional (opcional)', 'Exibido publicamente na busca, se preenchido. Nunca inclua dados de contato aqui.', 3 );
	dco_admin_text( $post, 'dco_especialidades', 'Especialidades (opcional)', 'Separadas por vírgula. Exibidas publicamente na busca, se preenchidas.' );
	dco_admin_file( $post, 'dco_curriculo_pdf', 'Currículo em PDF', 'Somente PDF, até 5 MB. Verificado pelo conteúdo real do arquivo (não só pela extensão) e salvo direto em armazenamento protegido — nunca passa pela Biblioteca de Mídia.' );
	echo '<p style="color:#646970;font-size:12px;">O NOME (título deste cadastro) nunca é exibido na busca pública — só cargo, localização, resumo e especialidades.</p>';

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

	dco_salvar_campos_texto( $post_id, array( 'dco_cargo', 'dco_cidade', 'dco_telefone', 'dco_especialidades' ) );
	dco_salvar_campos_textarea( $post_id, array( 'dco_resumo' ) );
	dco_salvar_campo_email( $post_id, 'dco_email' );

	if ( isset( $_POST['dco_estado'] ) ) {
		$uf = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ) );
		if ( array_key_exists( $uf, dco_uf_list() ) ) {
			update_post_meta( $post_id, 'dco_estado', $uf );
		} else {
			delete_post_meta( $post_id, 'dco_estado' );
		}
	}

	if ( ! empty( $_FILES['dco_curriculo_pdf_arquivo']['name'] ) ) {
		$resultado = dco_processar_upload_pdf( $_FILES['dco_curriculo_pdf_arquivo'] );
		if ( is_wp_error( $resultado ) ) {
			set_transient( 'dco_erro_pdf_' . $post_id, $resultado->get_error_message(), 60 );
		} else {
			$arquivo_anterior = get_post_meta( $post_id, 'dco_curriculo_pdf', true );
			update_post_meta( $post_id, 'dco_curriculo_pdf', $resultado );
			if ( $arquivo_anterior && $arquivo_anterior !== $resultado ) {
				dco_excluir_pdf_protegido( $arquivo_anterior );
			}
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
 * Exclusão PERMANENTE (não a lixeira — "Esvaziar lixeira" ou exclusão direta
 * via API) de um currículo também remove o PDF físico da pasta protegida,
 * para nunca deixar um arquivo órfão sem dono para sempre. Suspender,
 * expirar ou mover para a lixeira NUNCA apaga o arquivo — só a exclusão
 * definitiva, e só o arquivo DESTE post específico (nunca afeta outro
 * currículo).
 */
add_action( 'before_delete_post', 'dco_limpar_arquivo_ao_excluir_curriculo' );
function dco_limpar_arquivo_ao_excluir_curriculo( $post_id ) {
	if ( get_post_type( $post_id ) !== 'dco_curriculo' ) {
		return;
	}
	$arquivo = get_post_meta( $post_id, 'dco_curriculo_pdf', true );
	if ( $arquivo ) {
		dco_excluir_pdf_protegido( $arquivo );
	}
}
