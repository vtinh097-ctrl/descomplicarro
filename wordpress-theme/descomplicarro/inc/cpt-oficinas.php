<?php
/**
 * DESCOMPLICARRO — GI CONECTA: cadastro de Oficinas (Custom Post Type nativo).
 *
 * Cada oficina é um post do tipo "oficina_gi". O status de curadoria (em
 * avaliação / aprovada / suspensa / descredenciada) usa o próprio sistema de
 * status do WordPress — "em avaliação" é o status nativo "pending", e
 * "suspensa"/"descredenciada" são dois status customizados registrados
 * abaixo. Com isso, a regra "só oficinas aprovadas ficam públicas" já é
 * aplicada pelo núcleo do WordPress automaticamente (como acontece com
 * rascunhos/pendentes): não é preciso nenhum código extra para impedir o
 * acesso direto a um perfil não aprovado, nem para excluí-lo do arquivo
 * público — e nada é apagado ao suspender/descredenciar, só deixa de
 * aparecer no site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'dc_registrar_status_oficinas' );
function dc_registrar_status_oficinas() {
	register_post_status( 'suspensa', array(
		'label'                     => 'Suspensa',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		/* translators: %s: número de oficinas suspensas. */
		'label_count'               => _n_noop( 'Suspensa <span class="count">(%s)</span>', 'Suspensas <span class="count">(%s)</span>' ),
	) );

	register_post_status( 'descredenciada', array(
		'label'                     => 'Descredenciada',
		'public'                    => false,
		'internal'                  => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Descredenciada <span class="count">(%s)</span>', 'Descredenciadas <span class="count">(%s)</span>' ),
	) );
}

add_action( 'init', 'dc_registrar_cpt_oficinas' );
function dc_registrar_cpt_oficinas() {
	register_post_type( 'oficina_gi', array(
		'labels' => array(
			'name'               => 'Oficinas GI CONECTA',
			'singular_name'      => 'Oficina GI CONECTA',
			'add_new_item'       => 'Adicionar oficina',
			'edit_item'          => 'Editar oficina',
			'new_item'           => 'Nova oficina',
			'view_item'          => 'Ver oficina',
			'search_items'       => 'Buscar oficinas',
			'not_found'          => 'Nenhuma oficina encontrada',
			'all_items'          => 'Todas as oficinas',
			'menu_name'          => 'Oficinas GI CONECTA',
		),
		'public'              => true,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => false,
		'menu_icon'           => 'dashicons-admin-tools',
		'menu_position'       => 25,
		'supports'            => array( 'title', 'thumbnail' ),
		'has_archive'         => 'oficinas-gi-conecta',
		'rewrite'             => array( 'slug' => 'oficinas-gi-conecta', 'with_front' => false ),
		'capability_type'     => 'page',
		'map_meta_cap'        => true,
	) );
}

/**
 * Libera as regras de URL do novo CPT uma única vez (necessário sempre que um
 * CPT com rewrite é registrado). Roda sozinho, sem exigir que o tema seja
 * reativado — mesmo padrão já usado para o category_base do Em Foco.
 */
/**
 * flush_rewrite_rules() chamado durante "init" não é confiável: o próprio
 * WordPress adia a gravação das regras para o momento em que a ação
 * "wp_loaded" dispara (para dar tempo de todo mundo registrar suas rotas
 * antes de salvar) — e se o código que chama o flush marcar "já flushei"
 * antes desse adiamento terminar, a marca fica gravada sem o flush real ter
 * acontecido. Por isso o flush e a marca andam juntos em "wp_loaded", que é
 * o primeiro momento em que uma chamada a flush_rewrite_rules() está
 * garantida a rodar na hora, sem adiamento.
 */
add_action( 'wp_loaded', 'dc_ensure_oficinas_rewrite' );
function dc_ensure_oficinas_rewrite() {
	if ( get_option( 'dc_oficinas_rewrite_v1' ) ) {
		return;
	}
	flush_rewrite_rules();
	update_option( 'dc_oficinas_rewrite_v1', 1 );
}

/* ==========================================================================
   METABOX — dados da oficina
   ========================================================================== */
add_action( 'add_meta_boxes', 'dc_registrar_metabox_oficina' );
function dc_registrar_metabox_oficina() {
	add_meta_box( 'dc_oficina_dados', 'Dados da oficina', 'dc_render_metabox_oficina', 'oficina_gi', 'normal', 'high' );
}

function dc_render_metabox_oficina( $post ) {
	wp_nonce_field( 'dc_save_oficina', 'dc_oficina_nonce' );

	dc_admin_text( $post, 'dc_oficina_endereco', 'Endereço' );
	dc_admin_text( $post, 'dc_oficina_cidade', 'Cidade' );
	dc_admin_select( $post, 'dc_oficina_estado', 'Estado (UF)', dc_uf_list() );
	dc_admin_textarea( $post, 'dc_oficina_servicos', 'Serviços (um por linha)', '', 4 );
	dc_admin_textarea( $post, 'dc_oficina_especialidades', 'Especialidades (uma por linha)', '', 3 );
	dc_admin_text( $post, 'dc_oficina_telefone', 'Telefone' );
	dc_admin_text( $post, 'dc_oficina_whatsapp', 'WhatsApp', 'Somente números, com DDD (ex.: 11999998888). Deixe em branco para não exibir o botão de WhatsApp.' );
	dc_admin_url( $post, 'dc_oficina_instagram', 'Instagram (URL)' );
	dc_admin_url( $post, 'dc_oficina_maps', 'Google Maps (URL)' );
	dc_admin_textarea( $post, 'dc_oficina_apresentacao', 'Apresentação da empresa', '', 6 );
	dc_admin_repeater( $post, 'dc_oficina_galeria', 'Galeria de fotos', array(
		'imagem' => array( 'label' => 'Foto', 'type' => 'image' ),
	), 'Fotos adicionais da oficina, exibidas no perfil público. O logotipo é cadastrado em "Imagem destacada", na lateral desta tela.' );

	echo '<p style="color:#646970;font-size:12px;margin-top:16px;">O status de curadoria (Em avaliação / Publicado = Aprovada / Suspensa / Descredenciada) é definido na caixa "Publicar", à direita. Somente oficinas com status Publicado (Aprovada) aparecem no site.</p>';

	dc_meta_box_footer(
		array( 'dc_oficina_endereco', 'dc_oficina_cidade', 'dc_oficina_servicos', 'dc_oficina_especialidades', 'dc_oficina_telefone', 'dc_oficina_apresentacao' ),
		array( 'dc_oficina_instagram', 'dc_oficina_maps' ),
		array(),
		array(),
		array( 'dc_oficina_galeria' )
	);
	// dc_oficina_estado (select) e dc_oficina_whatsapp (só dígitos) não se
	// encaixam nos tipos de campo padrão do dc_save_meta_boxes — são salvos
	// à parte, em dc_save_metabox_oficina().
}

add_action( 'save_post_oficina_gi', 'dc_save_metabox_oficina' );
function dc_save_metabox_oficina( $post_id ) {
	if ( ! isset( $_POST['dc_oficina_nonce'] ) || ! wp_verify_nonce( $_POST['dc_oficina_nonce'], 'dc_save_oficina' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['dc_oficina_estado'] ) ) {
		$uf = sanitize_text_field( wp_unslash( $_POST['dc_oficina_estado'] ) );
		if ( array_key_exists( $uf, dc_uf_list() ) ) {
			update_post_meta( $post_id, 'dc_oficina_estado', $uf );
		} else {
			delete_post_meta( $post_id, 'dc_oficina_estado' );
		}
	}

	if ( isset( $_POST['dc_oficina_whatsapp'] ) ) {
		$whatsapp = preg_replace( '/[^0-9]/', '', wp_unslash( $_POST['dc_oficina_whatsapp'] ) );
		if ( $whatsapp !== '' ) {
			update_post_meta( $post_id, 'dc_oficina_whatsapp', $whatsapp );
		} else {
			delete_post_meta( $post_id, 'dc_oficina_whatsapp' );
		}
	}
}

/* ==========================================================================
   ADMIN — colunas e filtro da listagem de oficinas
   ========================================================================== */
add_filter( 'manage_oficina_gi_posts_columns', 'dc_oficina_admin_columns' );
function dc_oficina_admin_columns( $columns ) {
	$nova = array();
	foreach ( $columns as $key => $label ) {
		$nova[ $key ] = $label;
		if ( $key === 'title' ) {
			$nova['dc_cidade_estado'] = 'Cidade / UF';
			$nova['dc_status_curadoria'] = 'Status de curadoria';
		}
	}
	return $nova;
}

add_action( 'manage_oficina_gi_posts_custom_column', 'dc_oficina_admin_column_content', 10, 2 );
function dc_oficina_admin_column_content( $column, $post_id ) {
	if ( $column === 'dc_cidade_estado' ) {
		$cidade = get_post_meta( $post_id, 'dc_oficina_cidade', true );
		$uf     = get_post_meta( $post_id, 'dc_oficina_estado', true );
		echo esc_html( trim( $cidade . ( $uf ? ' / ' . $uf : '' ), ' /' ) ?: '—' );
	}
	if ( $column === 'dc_status_curadoria' ) {
		$status = get_post_status( $post_id );
		$mapa   = array(
			'publish'        => array( 'Aprovada', '#1e8e3e' ),
			'pending'        => array( 'Em avaliação', '#b26a00' ),
			'suspensa'       => array( 'Suspensa', '#b32d2e' ),
			'descredenciada' => array( 'Descredenciada', '#646970' ),
			'draft'          => array( 'Rascunho', '#646970' ),
		);
		$info = isset( $mapa[ $status ] ) ? $mapa[ $status ] : array( ucfirst( $status ), '#646970' );
		echo '<strong style="color:' . esc_attr( $info[1] ) . ';">' . esc_html( $info[0] ) . '</strong>';
	}
}

add_action( 'restrict_manage_posts', 'dc_oficina_admin_filtro_estado' );
function dc_oficina_admin_filtro_estado() {
	global $typenow;
	if ( $typenow !== 'oficina_gi' ) {
		return;
	}
	$atual = isset( $_GET['dc_filtro_estado'] ) ? sanitize_text_field( wp_unslash( $_GET['dc_filtro_estado'] ) ) : '';
	echo '<select name="dc_filtro_estado"><option value="">Todos os estados</option>';
	foreach ( dc_uf_list() as $uf => $nome ) {
		printf( '<option value="%1$s"%2$s>%1$s — %3$s</option>', esc_attr( $uf ), selected( $atual, $uf, false ), esc_html( $nome ) );
	}
	echo '</select>';
}

add_action( 'pre_get_posts', 'dc_oficina_admin_aplicar_filtro' );
function dc_oficina_admin_aplicar_filtro( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'oficina_gi' ) {
		return;
	}
	if ( ! empty( $_GET['dc_filtro_estado'] ) ) {
		$query->set( 'meta_key', 'dc_oficina_estado' );
		$query->set( 'meta_value', sanitize_text_field( wp_unslash( $_GET['dc_filtro_estado'] ) ) );
	}
}

/**
 * Inclui "Em avaliação" (pending), "Suspensa" e "Descredenciada" como opções
 * reais no seletor de status da tela de edição — por padrão, o WordPress só
 * mostra status customizados na edição em massa, não na caixa "Publicar".
 */
add_action( 'admin_footer-post.php', 'dc_oficina_admin_status_js' );
add_action( 'admin_footer-post-new.php', 'dc_oficina_admin_status_js' );
function dc_oficina_admin_status_js() {
	global $post;
	if ( ! $post || $post->post_type !== 'oficina_gi' ) {
		return;
	}
	$status_atual = $post->post_status;
	$rotulos = array(
		'pending'        => 'Em avaliação',
		'suspensa'       => 'Suspensa',
		'descredenciada' => 'Descredenciada',
	);
	?>
	<script>
	jQuery(function($){
		var statusAtual = <?php echo wp_json_encode( $status_atual ); ?>;
		var rotulos = <?php echo wp_json_encode( $rotulos ); ?>;
		var $select = $('#post_status');
		if (!$select.length) { return; }
		$.each(rotulos, function(valor, rotulo){
			if ($select.find('option[value="' + valor + '"]').length === 0) {
				$select.append($('<option>', { value: valor, text: rotulo }));
			}
		});
		if (rotulos[statusAtual]) {
			$select.val(statusAtual);
			$('#post-status-display').text(rotulos[statusAtual]);
		}
	});
	</script>
	<?php
}
