<?php
/**
 * DESCOMPLICARRO — Gerenciador de Publicidade.
 *
 * Cada campanha publicitária é um post do tipo "dc_campanha", gerenciável
 * em "Publicidade" no menu lateral do WordPress (restrito a administradores).
 * Os espaços publicitários do tema (ver dc_ad_slot_unit() em inc/helpers.php)
 * consultam dc_resolver_anuncio() para decidir qual campanha exibir em cada
 * posição — nenhum HTML/CSS do tema é alterado por este arquivo.
 *
 * Ordem de exibição em qualquer espaço publicitário (sem exceção):
 *   1. Campanha ativa elegível para a posição (e, quando a posição é
 *      segmentada por editoria, para a categoria da matéria/página).
 *   2. Imagem/link cadastrados manualmente naquela página específica
 *      (comportamento já existente antes deste sistema — preservado).
 *   3. Banner institucional padrão ("Configurações Descomplicarro").
 *   4. Placeholder "PUBLICIDADE — ESPAÇO RESERVADO" (comportamento original).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lista única das posições publicitárias do tema — usada no campo de
 * seleção da campanha, nas colunas/filtro do admin e pelos templates que
 * chamam dc_ad_slot_unit(). "segmentada" = true quando o espaço deve filtrar
 * por categoria editorial (matéria e página de editoria); false = espaço
 * geral (Home, Em Foco, Para Motoristas, Para Oficinas), que ignora
 * categoria e aceita qualquer campanha vinculada àquela posição.
 */
function dc_campanha_posicoes() {
	return array(
		'home-portas-emfoco'       => array( 'label' => 'Home — entre Portas e Em Foco', 'segmentada' => false ),
		'home-emfoco-youtube'      => array( 'label' => 'Home — entre Em Foco e YouTube', 'segmentada' => false ),
		'emfoco-destaques-ultimas' => array( 'label' => 'Em Foco — entre Destaques e Últimas', 'segmentada' => false ),
		'emfoco-editorias-youtube' => array( 'label' => 'Em Foco — entre Editorias e YouTube', 'segmentada' => false ),
		'materia-apos-conteudo'    => array( 'label' => 'Matéria — após o conteúdo (por editoria)', 'segmentada' => true ),
		'editoria-rodape'          => array( 'label' => 'Página de editoria — rodapé (por editoria)', 'segmentada' => true ),
		'motoristas-solucoes-match' => array( 'label' => 'Para Motoristas — entre Soluções e MATCH', 'segmentada' => false ),
		'motoristas-final'         => array( 'label' => 'Para Motoristas — final da página', 'segmentada' => false ),
		'oficinas-formacao'        => array( 'label' => 'Para Oficinas — seção Formação', 'segmentada' => false ),
	);
}

/* ==========================================================================
   CUSTOM POST TYPE
   ========================================================================== */
add_action( 'init', 'dc_registrar_cpt_campanhas' );
function dc_registrar_cpt_campanhas() {
	register_post_type( 'dc_campanha', array(
		'labels' => array(
			'name'          => 'Publicidade',
			'singular_name' => 'Campanha',
			'add_new_item'  => 'Nova campanha',
			'edit_item'     => 'Editar campanha',
			'new_item'      => 'Nova campanha',
			'view_item'     => 'Ver campanha',
			'search_items'  => 'Buscar campanhas',
			'not_found'     => 'Nenhuma campanha encontrada',
			'all_items'     => 'Campanhas',
			'menu_name'     => 'Publicidade',
		),
		'public'             => false,
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => false,
		'menu_icon'          => 'dashicons-megaphone',
		'menu_position'      => 58,
		'supports'           => array( 'title' ),
		'map_meta_cap'       => true,
		/*
		 * Restringe toda a gestão de campanhas a administradores. As
		 * capacidades usam nomes PRÓPRIOS deste CPT (nunca "manage_options"
		 * diretamente aqui) — o WordPress registra os nomes de edit_post/
		 * read_post/delete_post de qualquer CPT como apelidos globais dessas
		 * meta-capacidades (ver _post_type_meta_capabilities() no núcleo);
		 * reutilizar "manage_options" ali faria QUALQUER verificação de
		 * manage_options no site inteiro (inclusive a página Configurações
		 * Descomplicarro) passar a exigir um ID de objeto, quebrando-a. As
		 * capacidades abaixo são concedidas somente ao papel Administrador
		 * em dc_ensure_campanha_caps(), logo abaixo.
		 */
		'capability_type'    => array( 'dc_campanha', 'dc_campanhas' ),
		'capabilities'       => array(
			'edit_post'             => 'edit_dc_campanha',
			'read_post'             => 'read_dc_campanha',
			'delete_post'           => 'delete_dc_campanha',
			'edit_posts'            => 'edit_dc_campanhas',
			'edit_others_posts'     => 'edit_others_dc_campanhas',
			'publish_posts'         => 'publish_dc_campanhas',
			'read_private_posts'    => 'read_private_dc_campanhas',
			'delete_posts'          => 'delete_dc_campanhas',
			'delete_private_posts'  => 'delete_private_dc_campanhas',
			'delete_published_posts' => 'delete_published_dc_campanhas',
			'delete_others_posts'   => 'delete_others_dc_campanhas',
			'edit_private_posts'    => 'edit_private_dc_campanhas',
			'edit_published_posts'  => 'edit_published_dc_campanhas',
		),
	) );
}

/** Lista única das capacidades do CPT de campanhas — usada ao registrá-lo e ao concedê-las ao Administrador. */
function dc_campanha_capacidades() {
	return array(
		'edit_dc_campanha', 'read_dc_campanha', 'delete_dc_campanha',
		'edit_dc_campanhas', 'edit_others_dc_campanhas', 'publish_dc_campanhas',
		'read_private_dc_campanhas', 'delete_dc_campanhas', 'delete_private_dc_campanhas',
		'delete_published_dc_campanhas', 'delete_others_dc_campanhas',
		'edit_private_dc_campanhas', 'edit_published_dc_campanhas',
	);
}

/**
 * Concede as capacidades do CPT de campanhas somente ao papel Administrador
 * — nenhum outro papel (Editor, Autor etc.) ganha acesso ao menu
 * Publicidade. Roda uma única vez (opção dc_campanha_caps_v2); se novas
 * capacidades forem adicionadas no futuro, basta repetir o padrão com uma
 * opção "_v3" (nunca reaproveitar um nome de opção já "gasto" — ver nota em
 * dc_ensure_experiencias_oculta_por_padrao(), em inc/activation.php).
 */
add_action( 'init', 'dc_ensure_campanha_caps', 20 );
function dc_ensure_campanha_caps() {
	if ( get_option( 'dc_campanha_caps_v2' ) ) {
		return;
	}
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( dc_campanha_capacidades() as $cap ) {
			$role->add_cap( $cap );
		}
	}
	update_option( 'dc_campanha_caps_v2', 1 );
}

/* ==========================================================================
   METABOX — dados da campanha
   ========================================================================== */
add_action( 'add_meta_boxes', 'dc_registrar_metabox_campanha' );
function dc_registrar_metabox_campanha() {
	add_meta_box( 'dc_campanha_dados', 'Dados da campanha', 'dc_render_metabox_campanha', 'dc_campanha', 'normal', 'high' );
}

function dc_render_metabox_campanha( $post ) {
	wp_nonce_field( 'dc_save_campanha', 'dc_campanha_nonce' );

	dc_admin_section_title( 'Identificação' );
	echo '<p style="color:#646970;">O "Nome da campanha" é o título do post, no topo desta tela.</p>';
	dc_admin_text( $post, 'dc_campanha_anunciante', 'Nome do anunciante' );
	dc_admin_text( $post, 'dc_campanha_id_interno', 'Identificação interna (opcional)', 'Uso interno — ex.: número do pedido/contrato. Não aparece no site.' );

	dc_admin_section_title( 'Conteúdo' );
	dc_admin_image( $post, 'dc_campanha_imagem', 'Imagem do banner' );
	dc_admin_url( $post, 'dc_campanha_url', 'URL de destino' );
	dc_admin_text( $post, 'dc_campanha_alt', 'Texto alternativo da imagem (acessibilidade)' );
	dc_admin_checkbox( $post, 'dc_campanha_nova_aba', 'Abrir destino em nova aba' );

	dc_admin_section_title( 'Segmentação' );
	dc_admin_checklist( $post, 'dc_campanha_posicoes', 'Posições publicitárias', wp_list_pluck( dc_campanha_posicoes(), 'label' ), 'Marque uma ou mais — a mesma campanha pode aparecer em vários espaços ao mesmo tempo (ex.: Home e Para Motoristas juntos).' );
	dc_admin_categorias_checklist( $post, 'dc_campanha_categorias', 'Categorias editoriais vinculadas', 'Vale só para as posições segmentadas por editoria (Matéria e Página de editoria) — nas demais, é ignorado. Nenhuma marcada = campanha geral, exibida em qualquer categoria. Marque uma ou mais para restringir.' );

	dc_admin_section_title( 'Período' );
	dc_admin_date( $post, 'dc_campanha_data_inicio', 'Data de início' );
	dc_admin_date( $post, 'dc_campanha_data_fim', 'Data de término', 'Deixe em branco para uma campanha sem data de encerramento definida.' );
	dc_admin_checkbox( $post, 'dc_campanha_ativa', 'Campanha ativa', 'Desmarque para suspender a campanha imediatamente, independentemente das datas (fica como "Desativada"). Marque de novo para reativá-la.' );

	$status = dc_campanha_status( $post->ID );
	echo '<p style="margin-top:16px;"><strong>Status atual: ' . esc_html( dc_campanha_status_label( $status ) ) . '</strong> — calculado automaticamente a partir das datas e do campo "desativada manualmente" acima. Não precisa de atualização manual.</p>';
	echo '<p style="color:#646970;">O site não deixa duas campanhas ficarem ativas ao mesmo tempo no mesmo espaço: se as datas desta campanha baterem com as de outra já ativa na mesma posição (e mesma categoria, quando for o caso), esta aqui é salva automaticamente como desativada, com um aviso explicando qual campanha está conflitando.</p>';
}

add_action( 'save_post_dc_campanha', 'dc_save_metabox_campanha' );
function dc_save_metabox_campanha( $post_id ) {
	if ( ! isset( $_POST['dc_campanha_nonce'] ) || ! wp_verify_nonce( $_POST['dc_campanha_nonce'], 'dc_save_campanha' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Texto simples.
	foreach ( array( 'dc_campanha_anunciante', 'dc_campanha_id_interno', 'dc_campanha_alt' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
			if ( $value === '' ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}

	// URL.
	if ( isset( $_POST['dc_campanha_url'] ) ) {
		$url = esc_url_raw( wp_unslash( $_POST['dc_campanha_url'] ) );
		if ( $url === '' ) {
			delete_post_meta( $post_id, 'dc_campanha_url' );
		} else {
			update_post_meta( $post_id, 'dc_campanha_url', $url );
		}
	}

	// Imagem (ID de anexo).
	if ( isset( $_POST['dc_campanha_imagem'] ) ) {
		$attachment_id = (int) $_POST['dc_campanha_imagem'];
		if ( $attachment_id === 0 ) {
			delete_post_meta( $post_id, 'dc_campanha_imagem' );
		} else {
			update_post_meta( $post_id, 'dc_campanha_imagem', $attachment_id );
		}
	}

	// Checkboxes.
	foreach ( array( 'dc_campanha_nova_aba', 'dc_campanha_ativa' ) as $key ) {
		update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
	}

	// Posições (array de slugs, sempre conferidos contra a lista oficial — nunca grava um valor fora dela).
	$posicoes_enviadas = isset( $_POST['dc_campanha_posicoes'] ) && is_array( $_POST['dc_campanha_posicoes'] )
		? array_map( 'sanitize_key', wp_unslash( $_POST['dc_campanha_posicoes'] ) )
		: array();
	$posicoes_validas = array_values( array_intersect( $posicoes_enviadas, array_keys( dc_campanha_posicoes() ) ) );
	if ( empty( $posicoes_validas ) ) {
		delete_post_meta( $post_id, 'dc_campanha_posicoes' );
	} else {
		update_post_meta( $post_id, 'dc_campanha_posicoes', $posicoes_validas );
	}

	// Categorias (array de IDs reais de categoria).
	$categorias_enviadas = isset( $_POST['dc_campanha_categorias'] ) && is_array( $_POST['dc_campanha_categorias'] )
		? array_map( 'intval', $_POST['dc_campanha_categorias'] )
		: array();
	$categorias_validas = array_values( array_filter( $categorias_enviadas, function ( $term_id ) {
		return term_exists( $term_id, 'category' );
	} ) );
	if ( empty( $categorias_validas ) ) {
		delete_post_meta( $post_id, 'dc_campanha_categorias' );
	} else {
		update_post_meta( $post_id, 'dc_campanha_categorias', $categorias_validas );
	}

	// Datas (YYYY-MM-DD; só grava se vier num formato válido).
	foreach ( array( 'dc_campanha_data_inicio', 'dc_campanha_data_fim' ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$data = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( $data === '' ) {
			delete_post_meta( $post_id, $key );
		} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $data ) ) {
			update_post_meta( $post_id, $key, $data );
		}
	}

	dc_campanha_resolver_conflito( $post_id );
}

/**
 * Nunca deixa duas campanhas ficarem ativas ao mesmo tempo no mesmo espaço:
 * depois de salvos os campos acima, se esta campanha (do jeito que acabou de
 * ser salva) está "ativa" e compete por alguma posição/categoria com outra
 * campanha que já está ativa, ESTA é quem recua — grava
 * dc_campanha_ativa=0 nela mesma e guarda um aviso (dc_campanha_aviso_conflito)
 * explicando com qual campanha e em qual posição, exibido uma única vez no
 * admin (ver dc_campanha_admin_aviso_conflito()). A campanha já em exibição
 * nunca é desativada por causa de uma campanha nova sendo salva.
 */
function dc_campanha_resolver_conflito( $post_id ) {
	if ( dc_campanha_status( $post_id ) !== 'ativa' ) {
		delete_post_meta( $post_id, 'dc_campanha_aviso_conflito' );
		return;
	}

	$posicoes   = (array) get_post_meta( $post_id, 'dc_campanha_posicoes', true );
	$categorias = (array) get_post_meta( $post_id, 'dc_campanha_categorias', true );
	$inicio     = get_post_meta( $post_id, 'dc_campanha_data_inicio', true );
	$fim        = get_post_meta( $post_id, 'dc_campanha_data_fim', true );
	if ( empty( $posicoes ) ) {
		return; // Sem posição marcada, não compete por espaço nenhum.
	}

	$conflito = dc_campanha_encontrar_conflito( $post_id, $posicoes, $categorias, $inicio, $fim );
	if ( ! $conflito ) {
		delete_post_meta( $post_id, 'dc_campanha_aviso_conflito' );
		return;
	}

	update_post_meta( $post_id, 'dc_campanha_ativa', 0 );
	$posicoes_label = implode( ', ', wp_list_pluck(
		array_intersect_key( dc_campanha_posicoes(), array_flip( $conflito['posicoes_em_comum'] ) ),
		'label'
	) );
	update_post_meta(
		$post_id,
		'dc_campanha_aviso_conflito',
		sprintf(
			'Esta campanha entrou em conflito de datas com a campanha "%s" no(s) espaço(s) "%s" e foi salva como desativada. Ajuste as datas, as posições/categorias, ou desative a outra campanha e marque "Campanha ativa" aqui de novo.',
			get_the_title( $conflito['post']->ID ),
			$posicoes_label
		)
	);
}

/** Procura, entre as demais campanhas ativas, uma que dispute a mesma posição+categoria+período. Devolve array( 'post' => WP_Post, 'posicoes_em_comum' => array ) ou null. */
function dc_campanha_encontrar_conflito( $post_id, $posicoes, $categorias, $inicio, $fim ) {
	$todas_posicoes = dc_campanha_posicoes();
	$outras         = get_posts( array(
		'post_type'      => 'dc_campanha',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'exclude'        => array( $post_id ),
	) );

	foreach ( $outras as $outra ) {
		if ( dc_campanha_status( $outra->ID ) !== 'ativa' ) {
			continue;
		}
		$outra_posicoes = (array) get_post_meta( $outra->ID, 'dc_campanha_posicoes', true );
		$posicoes_em_comum = array_values( array_intersect( $posicoes, $outra_posicoes ) );
		if ( empty( $posicoes_em_comum ) ) {
			continue;
		}
		$outra_inicio = get_post_meta( $outra->ID, 'dc_campanha_data_inicio', true );
		$outra_fim    = get_post_meta( $outra->ID, 'dc_campanha_data_fim', true );
		if ( ! dc_campanha_periodos_se_sobrepoem( $inicio, $fim, $outra_inicio, $outra_fim ) ) {
			continue;
		}

		$outra_categorias = (array) get_post_meta( $outra->ID, 'dc_campanha_categorias', true );
		foreach ( $posicoes_em_comum as $posicao ) {
			$segmentada = ! empty( $todas_posicoes[ $posicao ]['segmentada'] );
			if ( ! $segmentada ) {
				return array( 'post' => $outra, 'posicoes_em_comum' => $posicoes_em_comum ); // Posição geral: conflita sempre.
			}
			// Posição segmentada: só conflita se as categorias se cruzarem, ou
			// se uma das duas for "geral" (sem categoria = concorre em todas).
			if ( empty( $categorias ) || empty( $outra_categorias ) || array_intersect( $categorias, $outra_categorias ) ) {
				return array( 'post' => $outra, 'posicoes_em_comum' => $posicoes_em_comum );
			}
		}
	}
	return null;
}

/** true quando os dois períodos (início/fim, "" = sem limite) se sobrepõem em algum dia. */
function dc_campanha_periodos_se_sobrepoem( $inicio_a, $fim_a, $inicio_b, $fim_b ) {
	$inicio_a = $inicio_a ?: '0000-01-01';
	$fim_a    = $fim_a ?: '9999-12-31';
	$inicio_b = $inicio_b ?: '0000-01-01';
	$fim_b    = $fim_b ?: '9999-12-31';
	return ( $inicio_a <= $fim_b ) && ( $inicio_b <= $fim_a );
}

/** Mostra, uma única vez, o aviso de conflito gravado por dc_campanha_resolver_conflito() ao abrir a campanha afetada. */
add_action( 'admin_notices', 'dc_campanha_admin_aviso_conflito' );
function dc_campanha_admin_aviso_conflito() {
	global $post;
	if ( ! $post || $post->post_type !== 'dc_campanha' ) {
		return;
	}
	$aviso = get_post_meta( $post->ID, 'dc_campanha_aviso_conflito', true );
	if ( ! $aviso ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Publicidade:</strong> ' . esc_html( $aviso ) . '</p></div>';
}

/* ==========================================================================
   STATUS — calculado a partir das datas, nunca armazenado
   ========================================================================== */
function dc_campanha_status( $post_id ) {
	// "" (nunca salvo) conta como ativa, igual ao próprio checkbox no admin
	// (vem marcado por padrão) — só um "0" explícito (campanha já salva com
	// a caixa desmarcada) conta como desativada manualmente.
	$ativa_meta = get_post_meta( $post_id, 'dc_campanha_ativa', true );
	if ( $ativa_meta !== '' && ! $ativa_meta ) {
		return 'desativada';
	}
	$hoje   = current_time( 'Y-m-d' );
	$inicio = get_post_meta( $post_id, 'dc_campanha_data_inicio', true );
	$fim    = get_post_meta( $post_id, 'dc_campanha_data_fim', true );

	if ( $inicio && $hoje < $inicio ) {
		return 'programada';
	}
	if ( $fim && $hoje > $fim ) {
		return 'encerrada';
	}
	return 'ativa';
}

function dc_campanha_status_label( $status ) {
	$labels = array(
		'ativa'      => 'Ativa',
		'programada' => 'Programada',
		'encerrada'  => 'Encerrada',
		'desativada' => 'Desativada',
	);
	return isset( $labels[ $status ] ) ? $labels[ $status ] : ucfirst( $status );
}

/* ==========================================================================
   O RESOLVEDOR — decide qual campanha exibir em cada espaço
   ========================================================================== */
/**
 * Devolve os dados prontos para exibição (imagem, link, alt, nova aba) da
 * melhor campanha ativa para a posição informada, ou null se nenhuma
 * campanha for elegível (o chamador então recorre ao banner institucional).
 *
 * $categoria_ids: IDs das categorias REAIS da matéria/página sendo exibida.
 * Ignorado quando a posição não é segmentada (ver dc_campanha_posicoes()).
 * Dentro de uma posição segmentada, uma campanha sem nenhuma categoria
 * vinculada é "geral" e concorre em qualquer categoria.
 *
 * Nunca deveria existir mais de uma campanha ativa elegível ao mesmo tempo
 * aqui — dc_campanha_resolver_conflito() impede isso no momento de salvar a
 * campanha. Se ainda assim houver mais de uma (edição direta no banco,
 * por exemplo), a mais antiga (menor ID) vence, como rede de segurança
 * simples e previsível — nunca rodízio.
 */
function dc_resolver_anuncio( $posicao, $categoria_ids = array() ) {
	$posicoes = dc_campanha_posicoes();
	if ( ! isset( $posicoes[ $posicao ] ) ) {
		return null;
	}
	$segmentada = $posicoes[ $posicao ]['segmentada'];

	// "dc_campanha_posicoes" é salvo como array (uma campanha pode valer para
	// várias posições ao mesmo tempo) — um meta_query simples não filtra
	// array serializado de forma confiável, então a lista de campanhas vem
	// inteira (volume baixo, nada que pese no banco) e o filtro por posição
	// é feito aqui em PHP, junto com o de categoria.
	$query = new WP_Query( array(
		'post_type'              => 'dc_campanha',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	) );

	$elegiveis = array();
	foreach ( $query->posts as $campanha ) {
		$posicoes_campanha = get_post_meta( $campanha->ID, 'dc_campanha_posicoes', true );
		$posicoes_campanha = is_array( $posicoes_campanha ) ? $posicoes_campanha : array();
		if ( ! in_array( $posicao, $posicoes_campanha, true ) ) {
			continue;
		}
		if ( dc_campanha_status( $campanha->ID ) !== 'ativa' ) {
			continue;
		}
		if ( $segmentada && ! empty( $categoria_ids ) ) {
			$categorias_campanha = get_post_meta( $campanha->ID, 'dc_campanha_categorias', true );
			$categorias_campanha = is_array( $categorias_campanha ) ? $categorias_campanha : array();
			// Campanha "geral" (sem categoria vinculada) concorre em qualquer categoria.
			if ( ! empty( $categorias_campanha ) && ! array_intersect( $categorias_campanha, $categoria_ids ) ) {
				continue;
			}
		}
		$imagem_id = (int) get_post_meta( $campanha->ID, 'dc_campanha_imagem', true );
		if ( ! $imagem_id ) {
			continue; // Campanha sem imagem cadastrada não tem o que exibir.
		}
		$elegiveis[] = $campanha;
	}
	wp_reset_postdata();

	if ( empty( $elegiveis ) ) {
		return null;
	}

	usort( $elegiveis, function ( $a, $b ) {
		return $a->ID <=> $b->ID;
	} );

	$vencedora = $elegiveis[0];
	$imagem_id = (int) get_post_meta( $vencedora->ID, 'dc_campanha_imagem', true );
	if ( ! $imagem_id || ! wp_attachment_is_image( $imagem_id ) ) {
		return null;
	}

	return array(
		'imagem_id'   => $imagem_id,
		'href'        => dc_field_raw( 'dc_campanha_url', '#', $vencedora->ID ) ?: '#',
		'alt'         => dc_field_raw( 'dc_campanha_alt', '', $vencedora->ID ),
		'nova_aba'    => (bool) get_post_meta( $vencedora->ID, 'dc_campanha_nova_aba', true ),
		'campanha_id' => $vencedora->ID,
		'anunciante'  => dc_field_raw( 'dc_campanha_anunciante', '', $vencedora->ID ),
	);
}

/**
 * Banner institucional padrão ("Configurações Descomplicarro"), exibido
 * quando nenhuma campanha ativa é elegível para a posição. Devolve null
 * quando nenhuma imagem institucional foi cadastrada ainda — o chamador
 * então mantém o placeholder original do tema.
 */
function dc_banner_institucional() {
	$imagem_id = (int) dc_option( 'banner_institucional_imagem', 0 );
	if ( ! $imagem_id || ! wp_attachment_is_image( $imagem_id ) ) {
		return null;
	}
	$url = dc_option( 'banner_institucional_url', '' );
	if ( ! $url ) {
		$url = dc_whatsapp_link( 'Olá! Quero anunciar no DESCOMPLICARRO.' );
	}
	return array(
		'imagem_id'      => $imagem_id,
		'href'           => $url,
		'alt'            => dc_option( 'banner_institucional_alt', 'Divulgue sua marca aqui' ),
		'nova_aba'       => true,
		'institucional'  => true,
	);
}

/* ==========================================================================
   ADMIN — colunas e filtros da listagem de campanhas
   ========================================================================== */
add_filter( 'manage_dc_campanha_posts_columns', 'dc_campanha_admin_columns' );
function dc_campanha_admin_columns( $columns ) {
	$nova = array();
	foreach ( $columns as $key => $label ) {
		if ( $key === 'date' ) {
			continue; // Datas próprias da campanha já aparecem nas colunas abaixo.
		}
		$nova[ $key ] = ( $key === 'title' ) ? 'Campanha' : $label;
		if ( $key === 'title' ) {
			$nova['dc_anunciante'] = 'Anunciante';
			$nova['dc_posicao']    = 'Posição';
			$nova['dc_categorias'] = 'Categoria editorial';
			$nova['dc_inicio']     = 'Início';
			$nova['dc_fim']        = 'Término';
			$nova['dc_status']     = 'Status';
		}
	}
	return $nova;
}

add_action( 'manage_dc_campanha_posts_custom_column', 'dc_campanha_admin_column_content', 10, 2 );
function dc_campanha_admin_column_content( $column, $post_id ) {
	if ( $column === 'dc_anunciante' ) {
		echo esc_html( get_post_meta( $post_id, 'dc_campanha_anunciante', true ) ?: '—' );
	}
	if ( $column === 'dc_posicao' ) {
		$posicoes      = dc_campanha_posicoes();
		$selecionadas  = get_post_meta( $post_id, 'dc_campanha_posicoes', true );
		$selecionadas  = is_array( $selecionadas ) ? $selecionadas : array();
		if ( empty( $selecionadas ) ) {
			echo '—';
		} else {
			$rotulos = array_map( function ( $slug ) use ( $posicoes ) {
				return isset( $posicoes[ $slug ] ) ? $posicoes[ $slug ]['label'] : null;
			}, $selecionadas );
			echo esc_html( implode( ', ', array_filter( $rotulos ) ) );
		}
	}
	if ( $column === 'dc_categorias' ) {
		$ids = get_post_meta( $post_id, 'dc_campanha_categorias', true );
		$ids = is_array( $ids ) ? $ids : array();
		if ( empty( $ids ) ) {
			echo '<em>Geral (todas)</em>';
		} else {
			$nomes = array_map( function ( $id ) {
				$termo = get_category( $id );
				return $termo ? $termo->name : null;
			}, $ids );
			echo esc_html( implode( ', ', array_filter( $nomes ) ) );
		}
	}
	if ( $column === 'dc_inicio' ) {
		echo esc_html( get_post_meta( $post_id, 'dc_campanha_data_inicio', true ) ?: '—' );
	}
	if ( $column === 'dc_fim' ) {
		echo esc_html( get_post_meta( $post_id, 'dc_campanha_data_fim', true ) ?: 'Sem data definida' );
	}
	if ( $column === 'dc_status' ) {
		$status = dc_campanha_status( $post_id );
		$cores  = array(
			'ativa'      => '#1e8e3e',
			'programada' => '#0a5fa8',
			'encerrada'  => '#646970',
			'desativada' => '#b32d2e',
		);
		echo '<strong style="color:' . esc_attr( $cores[ $status ] ?? '#646970' ) . ';">' . esc_html( dc_campanha_status_label( $status ) ) . '</strong>';
	}
}

add_action( 'restrict_manage_posts', 'dc_campanha_admin_filtros' );
function dc_campanha_admin_filtros() {
	global $typenow;
	if ( $typenow !== 'dc_campanha' ) {
		return;
	}

	$posicao_atual = isset( $_GET['dc_filtro_posicao'] ) ? sanitize_key( wp_unslash( $_GET['dc_filtro_posicao'] ) ) : '';
	echo '<select name="dc_filtro_posicao"><option value="">Todas as posições</option>';
	foreach ( dc_campanha_posicoes() as $slug => $info ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $slug ), selected( $posicao_atual, $slug, false ), esc_html( $info['label'] ) );
	}
	echo '</select>';

	$categoria_atual = isset( $_GET['dc_filtro_categoria'] ) ? (int) $_GET['dc_filtro_categoria'] : 0;
	echo '<select name="dc_filtro_categoria"><option value="">Todas as categorias</option>';
	foreach ( get_categories( array( 'hide_empty' => false ) ) as $categoria ) {
		printf( '<option value="%1$d"%2$s>%3$s</option>', $categoria->term_id, selected( $categoria_atual, $categoria->term_id, false ), esc_html( $categoria->name ) );
	}
	echo '</select>';

	$status_atual = isset( $_GET['dc_filtro_status'] ) ? sanitize_key( wp_unslash( $_GET['dc_filtro_status'] ) ) : '';
	echo '<select name="dc_filtro_status"><option value="">Todos os status</option>';
	foreach ( array( 'ativa', 'programada', 'encerrada', 'desativada' ) as $status ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $status ), selected( $status_atual, $status, false ), esc_html( dc_campanha_status_label( $status ) ) );
	}
	echo '</select>';
}

/**
 * Posição, categoria e status são todos filtrados aqui, em PHP, depois da
 * consulta principal — "posições" e "categorias" são arrays salvos em post
 * meta (um meta_query simples não filtra array serializado de forma
 * confiável) e "status" nem é salvo, é calculado. Volume baixo de
 * campanhas, sem impacto de desempenho.
 */
add_filter( 'the_posts', 'dc_campanha_admin_filtrar_resultados', 10, 2 );
function dc_campanha_admin_filtrar_resultados( $posts, $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'dc_campanha' ) {
		return $posts;
	}
	$posicao_filtro   = ! empty( $_GET['dc_filtro_posicao'] ) ? sanitize_key( wp_unslash( $_GET['dc_filtro_posicao'] ) ) : '';
	$categoria_filtro = ! empty( $_GET['dc_filtro_categoria'] ) ? (int) $_GET['dc_filtro_categoria'] : 0;
	$status_filtro    = ! empty( $_GET['dc_filtro_status'] ) ? sanitize_key( wp_unslash( $_GET['dc_filtro_status'] ) ) : '';
	if ( ! $posicao_filtro && ! $categoria_filtro && ! $status_filtro ) {
		return $posts;
	}
	return array_values( array_filter( $posts, function ( $post ) use ( $posicao_filtro, $categoria_filtro, $status_filtro ) {
		if ( $status_filtro && dc_campanha_status( $post->ID ) !== $status_filtro ) {
			return false;
		}
		if ( $posicao_filtro ) {
			$posicoes = get_post_meta( $post->ID, 'dc_campanha_posicoes', true );
			$posicoes = is_array( $posicoes ) ? $posicoes : array();
			if ( ! in_array( $posicao_filtro, $posicoes, true ) ) {
				return false;
			}
		}
		if ( $categoria_filtro ) {
			$categorias = get_post_meta( $post->ID, 'dc_campanha_categorias', true );
			$categorias = is_array( $categorias ) ? $categorias : array();
			if ( ! in_array( $categoria_filtro, $categorias, true ) ) {
				return false;
			}
		}
		return true;
	} ) );
}
