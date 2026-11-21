<?php
/**
 * DESCOMPLICARRO — Para Oficinas: cadastro de Parceiros (ecossistema de
 * empresas e profissionais apresentados na página Para Oficinas).
 *
 * Cada parceiro é um post do tipo "dc_parceiro", gerenciável em
 * "Parceiros" no menu lateral do WordPress. Usa exclusivamente recursos
 * nativos do WordPress para o que já existe pronto:
 * - Nome do parceiro = título do post.
 * - Ativo/inativo = o próprio status de publicação (Publicado = aparece no
 *   site; Rascunho = fica oculto) — mesmo padrão já usado em "status de
 *   curadoria" das oficinas GI CONECTA, sem reinventar um campo próprio.
 * - Ordem de exibição = o campo nativo "Ordem" (menu_order), da mesma caixa
 *   "Atributos da página" que as páginas institucionais já usam.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'dc_registrar_cpt_parceiros' );
function dc_registrar_cpt_parceiros() {
	register_post_type( 'dc_parceiro', array(
		'labels' => array(
			'name'               => 'Parceiros',
			'singular_name'      => 'Parceiro',
			'add_new_item'       => 'Adicionar parceiro',
			'edit_item'          => 'Editar parceiro',
			'new_item'           => 'Novo parceiro',
			'view_item'          => 'Ver parceiro',
			'search_items'       => 'Buscar parceiros',
			'not_found'          => 'Nenhum parceiro encontrado',
			'all_items'          => 'Parceiros',
			'menu_name'          => 'Parceiros',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_icon'           => 'dashicons-groups',
		'menu_position'       => 26,
		'supports'            => array( 'title', 'page-attributes' ),
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
	) );
}

/* ==========================================================================
   METABOX — dados do parceiro
   ========================================================================== */
add_action( 'add_meta_boxes', 'dc_registrar_metabox_parceiro' );
function dc_registrar_metabox_parceiro() {
	add_meta_box( 'dc_parceiro_dados', 'Dados do parceiro', 'dc_render_metabox_parceiro', 'dc_parceiro', 'normal', 'high' );
}

function dc_render_metabox_parceiro( $post ) {
	echo '<p style="color:#646970;">O "Nome do parceiro" é o título do post, no topo desta tela. A ordem de exibição é definida em "Atributos da página", na lateral direita.</p>';
	dc_admin_text( $post, 'dc_parceiro_segmento', 'Segmento de atuação', 'Ex.: Marketing e aquisição.' );
	dc_admin_textarea( $post, 'dc_parceiro_descricao', 'Descrição', '', 4 );
	dc_admin_image( $post, 'dc_parceiro_logotipo', 'Logotipo' );
	dc_admin_text( $post, 'dc_parceiro_botao_label', 'Texto do botão', 'Ex.: CONHEÇA A LURE.' );
	dc_admin_url( $post, 'dc_parceiro_url', 'URL de destino', 'Deixe em branco enquanto o link não for confirmado — sem URL cadastrada, o botão não é exibido no site (nunca um link quebrado ou "#").' );

	echo '<p style="margin-top:16px;color:#646970;">Status de publicação (caixa "Publicar", à direita): <strong>Publicado</strong> = parceiro ativo e visível no site. <strong>Rascunho</strong> = parceiro desativado, fica oculto sem ser excluído.</p>';

	dc_meta_box_footer(
		array( 'dc_parceiro_segmento', 'dc_parceiro_descricao', 'dc_parceiro_botao_label' ),
		array( 'dc_parceiro_url' ),
		array( 'dc_parceiro_logotipo' )
	);
}

/* ==========================================================================
   ADMIN — colunas da listagem de parceiros
   ========================================================================== */
add_filter( 'manage_dc_parceiro_posts_columns', 'dc_parceiro_admin_columns' );
function dc_parceiro_admin_columns( $columns ) {
	$nova = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'date' ) {
			continue;
		}
		$nova[ $key ] = ( $key === 'title' ) ? 'Parceiro' : $label;
		if ( $key === 'title' ) {
			$nova['dc_segmento'] = 'Segmento';
			$nova['dc_logotipo'] = 'Logotipo';
			$nova['dc_ordem']    = 'Ordem';
		}
	}
	return $nova;
}

add_action( 'manage_dc_parceiro_posts_custom_column', 'dc_parceiro_admin_column_content', 10, 2 );
function dc_parceiro_admin_column_content( $column, $post_id ) {
	if ( $column === 'dc_segmento' ) {
		echo esc_html( get_post_meta( $post_id, 'dc_parceiro_segmento', true ) ?: '—' );
	}
	if ( $column === 'dc_logotipo' ) {
		$logo_id = (int) get_post_meta( $post_id, 'dc_parceiro_logotipo', true );
		echo $logo_id ? '✅' : '—';
	}
	if ( $column === 'dc_ordem' ) {
		global $post;
		echo (int) $post->menu_order;
	}
}

add_filter( 'manage_edit-dc_parceiro_sortable_columns', 'dc_parceiro_admin_coluna_ordenavel' );
function dc_parceiro_admin_coluna_ordenavel( $columns ) {
	$columns['dc_ordem'] = 'menu_order';
	return $columns;
}

add_action( 'pre_get_posts', 'dc_parceiro_admin_ordenar_por_padrao' );
function dc_parceiro_admin_ordenar_por_padrao( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'dc_parceiro' ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order title' );
		$query->set( 'order', 'ASC' );
	}
}

/* ==========================================================================
   SEMENTE INICIAL — os 6 parceiros fornecidos no lançamento deste ecossistema
   ========================================================================== */
/**
 * Cadastra os parceiros iniciais automaticamente ao atualizar o tema, sem
 * duplicar: cada parceiro só é criado se NENHUM post "dc_parceiro" com
 * aquele título já existir (checagem feita no banco de dados de verdade,
 * nunca por uma marca "já rodei" — mesma lição já aplicada em outras
 * migrações deste tema). Reativar o tema de novo, ou já ter cadastrado um
 * parceiro manualmente com o mesmo nome, nunca duplica nem sobrescreve.
 * Logotipo e URL ficam propositalmente em branco — cadastrados depois pela
 * administração (ver dc_render_metabox_parceiro() acima).
 */
add_action( 'init', 'dc_ensure_parceiros_iniciais', 20 );
function dc_ensure_parceiros_iniciais() {
	$parceiros_iniciais = array(
		array( 'nome' => 'LURE', 'segmento' => 'Marketing e aquisição', 'descricao' => 'Marketing estratégico, posicionamento digital, tráfego pago e atração de novos clientes.', 'botao' => 'CONHEÇA A LURE', 'ordem' => 1 ),
		array( 'nome' => 'Mecânico Que Lucra', 'segmento' => 'Gestão e lucratividade', 'descricao' => 'Mentoria empresarial para oficinas que buscam melhorar processos, desenvolver equipes, fortalecer a gestão e aumentar a lucratividade.', 'botao' => 'CONHEÇA O MECÂNICO QUE LUCRA', 'ordem' => 2 ),
		array( 'nome' => 'QR Car', 'segmento' => 'Relacionamento e fidelização', 'descricao' => 'Tecnologia para fortalecer o relacionamento entre oficinas e motoristas, melhorar a experiência do cliente e potencializar o pós-venda.', 'botao' => 'CONHEÇA O QR CAR', 'ordem' => 3 ),
		array( 'nome' => 'Bruno Cordeiro', 'segmento' => 'Segurança jurídica', 'descricao' => 'Assessoria jurídica especializada nas particularidades e nos desafios do setor de reparação automotiva.', 'botao' => 'CONHEÇA O ESPECIALISTA', 'ordem' => 4 ),
		array( 'nome' => 'Navence', 'segmento' => 'Contabilidade e organização financeira', 'descricao' => 'Contabilidade especializada para oficinas mecânicas, contribuindo para a organização fiscal, tributária e contábil do negócio.', 'botao' => 'CONHEÇA A NAVENCE', 'ordem' => 5 ),
		array( 'nome' => 'Lever Education', 'segmento' => 'Educação e desenvolvimento profissional', 'descricao' => 'Educação técnica especializada no setor automotivo, com formação prática e aplicada para capacitar profissionais, desenvolver equipes e preparar oficinas para a evolução do mercado.', 'botao' => 'CONHEÇA A LEVER EDUCATION', 'ordem' => 6 ),
	);

	foreach ( $parceiros_iniciais as $p ) {
		$existente = get_posts( array(
			'post_type'              => 'dc_parceiro',
			'post_status'            => array( 'publish', 'draft', 'pending' ),
			'title'                  => $p['nome'],
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		) );
		if ( ! empty( $existente ) ) {
			continue; // Já existe um parceiro com este nome — não duplica, não sobrescreve.
		}
		$post_id = wp_insert_post( array(
			'post_type'   => 'dc_parceiro',
			'post_title'  => $p['nome'],
			'post_status' => 'publish',
			'menu_order'  => $p['ordem'],
		), true );
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, 'dc_parceiro_segmento', $p['segmento'] );
			update_post_meta( $post_id, 'dc_parceiro_descricao', $p['descricao'] );
			update_post_meta( $post_id, 'dc_parceiro_botao_label', $p['botao'] );
		}
	}
}
