<?php
/**
 * DESCOMPLICARRO Oportunidades — Vaga (CPT "dco_vaga").
 *
 * Mesmo modelo de status do currículo (ver cpt-curriculo.php): "pending" =
 * aguardando confirmação de e-mail, "publish" = ativa, "suspensa" = retirada
 * pela administração, "expirada" = passou do prazo (padrão 30 dias,
 * renovável pela própria empresa). Nesta entrega: CPT e administração pelo
 * wp-admin já funcionam; autocadastro público e renovação por link de
 * gestão são a próxima parte desta etapa.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'dco_registrar_status_vaga' );
function dco_registrar_status_vaga() {
	register_post_status( 'suspensa', array(
		'label'                     => 'Suspensa',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Suspensa <span class="count">(%s)</span>', 'Suspensas <span class="count">(%s)</span>' ),
	) );
	register_post_status( 'expirada', array(
		'label'                     => 'Encerrada',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Encerrada <span class="count">(%s)</span>', 'Encerradas <span class="count">(%s)</span>' ),
	) );
}

add_action( 'init', 'dco_registrar_cpt_vaga' );
function dco_registrar_cpt_vaga() {
	register_post_type( 'dco_vaga', array(
		'labels' => array(
			'name'          => 'Vagas',
			'singular_name' => 'Vaga',
			'add_new_item'  => 'Adicionar vaga',
			'edit_item'     => 'Editar vaga',
			'new_item'      => 'Nova vaga',
			'view_item'     => 'Ver vaga',
			'search_items'  => 'Buscar vagas',
			'not_found'     => 'Nenhuma vaga encontrada',
			'all_items'     => 'Vagas',
			'menu_name'     => 'Vagas',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => 'dco-oportunidades',
		'show_in_rest'        => false,
		'supports'            => array( 'title' ),
		'capability_type'     => array( 'dco_vaga', 'dco_vagas' ),
		'map_meta_cap'        => true,
	) );
}

/* ==========================================================================
   METABOX — dados da vaga
   ========================================================================== */
add_action( 'add_meta_boxes', 'dco_registrar_metabox_vaga' );
function dco_registrar_metabox_vaga() {
	add_meta_box( 'dco_vaga_dados', 'Dados da vaga', 'dco_render_metabox_vaga', 'dco_vaga', 'normal', 'high' );
}

function dco_render_metabox_vaga( $post ) {
	wp_nonce_field( 'dco_save_vaga', 'dco_vaga_nonce' );

	dco_admin_text( $post, 'dco_empresa', 'Empresa' );
	dco_admin_text( $post, 'dco_cargo', 'Cargo' );
	dco_admin_select( $post, 'dco_estado', 'Estado (UF)', dco_uf_list() );
	dco_admin_text( $post, 'dco_cidade', 'Cidade', 'Precisa ser um município real do estado selecionado.' );
	dco_admin_textarea( $post, 'dco_descricao', 'Descrição da vaga', '', 5 );
	dco_admin_textarea( $post, 'dco_requisitos', 'Requisitos', '', 4 );
	dco_admin_textarea( $post, 'dco_beneficios', 'Benefícios', '', 3 );
	dco_admin_email( $post, 'dco_email_candidatura', 'E-mail para candidatura', 'Para onde o botão "Candidatar-se" abre o e-mail do visitante — nenhuma candidatura é armazenada neste sistema.' );

	dco_admin_section_title( 'Informações de controle' );
	dco_admin_readonly_info( 'E-mail confirmado', get_post_meta( $post->ID, 'dco_email_confirmado', true ) ? 'Sim' : 'Não' );
	$expira = get_post_meta( $post->ID, 'dco_expira_em', true );
	dco_admin_readonly_info( 'Expira em', $expira ? $expira : '— (defina ao publicar, padrão 30 dias)' );
	dco_admin_readonly_info( 'Denúncias recebidas', (int) get_post_meta( $post->ID, 'dco_denuncias', true ) );

	echo '<p style="color:#646970;font-size:12px;margin-top:16px;">Status: "Em avaliação" (pendente) até a confirmação de e-mail; "Publicado" = ativa; "Suspensa" = retirada pela administração; "Encerrada" = passou do prazo sem renovação. Definido na caixa "Publicar", à direita.</p>';
}

add_action( 'save_post_dco_vaga', 'dco_save_metabox_vaga' );
function dco_save_metabox_vaga( $post_id ) {
	if ( ! isset( $_POST['dco_vaga_nonce'] ) || ! wp_verify_nonce( $_POST['dco_vaga_nonce'], 'dco_save_vaga' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	dco_salvar_campos_texto( $post_id, array( 'dco_empresa', 'dco_cargo', 'dco_cidade' ) );
	dco_salvar_campos_textarea( $post_id, array( 'dco_descricao', 'dco_requisitos', 'dco_beneficios' ) );
	dco_salvar_campo_email( $post_id, 'dco_email_candidatura' );

	if ( isset( $_POST['dco_estado'] ) ) {
		$uf = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ) );
		if ( array_key_exists( $uf, dco_uf_list() ) ) {
			update_post_meta( $post_id, 'dco_estado', $uf );
		} else {
			delete_post_meta( $post_id, 'dco_estado' );
		}
	}

	// Primeira publicação: define o prazo padrão de 30 dias (renovável depois pela empresa).
	if ( get_post_status( $post_id ) === 'publish' && ! get_post_meta( $post_id, 'dco_expira_em', true ) ) {
		update_post_meta( $post_id, 'dco_expira_em', gmdate( 'Y-m-d', strtotime( '+30 days' ) ) );
	}
}
