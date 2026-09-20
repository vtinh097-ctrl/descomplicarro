<?php
/**
 * DESCOMPLICARRO — metaboxes de conteúdo por página.
 *
 * Um metabox por template de página institucional. Cada um reúne os campos
 * de TÍTULOS, PARÁGRAFOS, IMAGENS, BOTÕES (texto+URL) e LISTAS daquela
 * página específica — o layout/estrutura HTML permanece fixo no template
 * PHP; apenas o conteúdo destes campos é editável no painel.
 *
 * Convenção: parágrafos "corridos" de um mesmo bloco de texto (sem classe
 * visual própria) são agrupados em UM textarea (uma linha em branco = novo
 * parágrafo); frases com tratamento visual específico (destaque, negrito,
 * assinatura) são campos individuais, porque sua formatação faz parte do
 * layout aprovado, não apenas do texto.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'add_meta_boxes', 'dc_register_meta_boxes' );
function dc_register_meta_boxes() {
	$template = get_page_template_slug();

	$map = array(
		'front-page.php'                  => array( 'dc_home_conteudo', 'Conteúdo — Home' ),
		'template-sobre-nos.php'          => array( 'dc_sobre_conteudo', 'Conteúdo — Sobre Nós' ),
		'template-para-motoristas.php'    => array( 'dc_motoristas_conteudo', 'Conteúdo — Para Motoristas' ),
		'template-para-oficinas.php'      => array( 'dc_oficinas_conteudo', 'Conteúdo — Para Oficinas' ),
		'template-palestras-workshops.php' => array( 'dc_palestras_conteudo', 'Conteúdo — Palestras & Workshops' ),
	);

	// front-page.php é detectado via is_front_page atribuída à página, não via "modelo" —
	// tratamos separadamente logo abaixo além do mapa por template.
	foreach ( $map as $tpl => $box ) {
		if ( $tpl === 'front-page.php' ) {
			continue;
		}
		if ( $template === $tpl ) {
			add_meta_box( $box[0], $box[1], 'dc_render_meta_box_' . str_replace( array( 'dc_', '_conteudo' ), '', $box[0] ), 'page', 'normal', 'high' );
		}
	}

	// Home = a página marcada em Configurações → Leitura como "página inicial estática".
	$front_id = (int) get_option( 'page_on_front' );
	if ( $front_id && get_the_ID() === $front_id ) {
		add_meta_box( 'dc_home_conteudo', 'Conteúdo — Home', 'dc_render_meta_box_home', 'page', 'normal', 'high' );
	}
}

add_action( 'save_post', 'dc_save_meta_boxes' );
function dc_save_meta_boxes( $post_id ) {
	if ( ! isset( $_POST['dc_meta_nonce'] ) || ! wp_verify_nonce( $_POST['dc_meta_nonce'], 'dc_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	$text_keys = isset( $_POST['dc_text_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_text_keys'] ) ) : array();
	foreach ( $text_keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = wp_unslash( $_POST[ $key ] );
		$value = is_array( $value ) ? '' : sanitize_textarea_field( $value );
		if ( $value === '' ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	// Legendas de imagem: SEMPRE gravadas, mesmo em branco — nunca apagadas
	// com delete_post_meta(). É essa linha (mesmo com valor '') que registra
	// "o administrador esvaziou este campo de propósito" e impede que o
	// texto padrão da V1 volte a aparecer. Ver dc_caption() em inc/helpers.php.
	$caption_keys = isset( $_POST['dc_caption_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_caption_keys'] ) ) : array();
	foreach ( $caption_keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		update_post_meta( $post_id, $key, $value );
	}

	$url_keys = isset( $_POST['dc_url_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_url_keys'] ) ) : array();
	foreach ( $url_keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = esc_url_raw( wp_unslash( $_POST[ $key ] ) );
		if ( $value === '' ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	$image_keys = isset( $_POST['dc_image_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_image_keys'] ) ) : array();
	foreach ( $image_keys as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$value = (int) $_POST[ $key ];
		if ( $value === 0 ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}

	$checkbox_keys = isset( $_POST['dc_checkbox_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_checkbox_keys'] ) ) : array();
	foreach ( $checkbox_keys as $key ) {
		update_post_meta( $post_id, $key, isset( $_POST[ $key ] ) ? 1 : 0 );
	}

	$repeater_keys = isset( $_POST['dc_repeater_keys'] ) ? array_map( 'sanitize_key', explode( ',', $_POST['dc_repeater_keys'] ) ) : array();
	foreach ( $repeater_keys as $key ) {
		dc_save_repeater( $post_id, $key );
	}
}

/** Imprime os campos ocultos que dizem ao save_post quais chaves processar de cada tipo. */
function dc_meta_box_footer( $text = array(), $url = array(), $image = array(), $checkbox = array(), $repeater = array(), $caption = array() ) {
	wp_nonce_field( 'dc_save_meta', 'dc_meta_nonce' );
	printf( '<input type="hidden" name="dc_text_keys" value="%s">', esc_attr( implode( ',', $text ) ) );
	printf( '<input type="hidden" name="dc_url_keys" value="%s">', esc_attr( implode( ',', $url ) ) );
	printf( '<input type="hidden" name="dc_image_keys" value="%s">', esc_attr( implode( ',', $image ) ) );
	printf( '<input type="hidden" name="dc_checkbox_keys" value="%s">', esc_attr( implode( ',', $checkbox ) ) );
	printf( '<input type="hidden" name="dc_repeater_keys" value="%s">', esc_attr( implode( ',', $repeater ) ) );
	printf( '<input type="hidden" name="dc_caption_keys" value="%s">', esc_attr( implode( ',', $caption ) ) );
}

/** Campo de legenda no admin — mesmo input de texto simples, com nota sobre o comportamento "vazio = sem legenda, permanente". */
function dc_admin_caption( $post, $key, $label ) {
	dc_admin_text( $post, $key, $label, 'Deixe em branco para não exibir nenhuma legenda — a ausência de legenda é salva e não volta a mostrar um texto padrão depois.' );
}

/* ==========================================================================
   HOME
   ========================================================================== */
function dc_render_meta_box_home( $post ) {
	dc_admin_section_title( 'N.00 — Abertura' );
	dc_admin_text( $post, 'dc_home_abertura_titulo', 'Título (H1)' );
	dc_admin_textarea( $post, 'dc_home_abertura_paragrafo', 'Parágrafo principal' );
	dc_admin_text( $post, 'dc_home_abertura_botao_label', 'Texto do botão' );
	dc_admin_url( $post, 'dc_home_abertura_botao_url', 'URL do botão' );
	dc_admin_image( $post, 'dc_home_abertura_imagem', 'Imagem de apoio' );

	dc_admin_section_title( 'N.01 — Portas de entrada (4 itens fixos)' );
	foreach ( array( 1 => 'Para Motoristas', 2 => 'Para Oficinas', 3 => 'Palestras e Workshops', 4 => 'Em Foco' ) as $i => $label ) {
		echo '<p style="font-weight:600;margin-bottom:2px;">Porta ' . $i . ' — ' . esc_html( $label ) . '</p>';
		dc_admin_text( $post, "dc_home_porta{$i}_titulo", 'Título' );
		dc_admin_textarea( $post, "dc_home_porta{$i}_desc", 'Descrição', '', 2 );
		dc_admin_image( $post, "dc_home_porta{$i}_imagem", 'Imagem' );
	}

	dc_admin_section_title( 'Espaço publicitário 1 (entre Portas e Em Foco)' );
	dc_admin_checkbox( $post, 'dc_home_ad1_ativo', 'Exibir este espaço publicitário' );
	dc_admin_url( $post, 'dc_home_ad1_url', 'Link do anunciante' );
	dc_admin_image( $post, 'dc_home_ad1_imagem', 'Imagem/banner' );

	dc_admin_section_title( 'N.02 — Em Foco (seleção editorial, 3 cards)' );
	for ( $i = 1; $i <= 3; $i++ ) {
		echo '<p style="font-weight:600;margin-bottom:2px;">Card ' . $i . '</p>';
		dc_admin_text( $post, "dc_home_emfoco{$i}_categoria", 'Categoria' );
		dc_admin_text( $post, "dc_home_emfoco{$i}_titulo", 'Título' );
		dc_admin_textarea( $post, "dc_home_emfoco{$i}_resumo", 'Resumo', '', 2 );
		dc_admin_url( $post, "dc_home_emfoco{$i}_url", 'Link da matéria' );
		dc_admin_image( $post, "dc_home_emfoco{$i}_imagem", 'Imagem' );
	}

	dc_admin_section_title( 'Espaço publicitário 2 (entre Em Foco e YouTube)' );
	dc_admin_checkbox( $post, 'dc_home_ad2_ativo', 'Exibir este espaço publicitário' );
	dc_admin_url( $post, 'dc_home_ad2_url', 'Link do anunciante' );
	dc_admin_image( $post, 'dc_home_ad2_imagem', 'Imagem/banner' );

	dc_admin_section_title( 'N.03 — No YouTube (3 vídeos)' );
	dc_admin_url( $post, 'dc_home_youtube_botao_url', 'URL do canal oficial (botão "Assista no YouTube")', 'Se vazio, usa a URL cadastrada em Configurações Descomplicarro.' );
	for ( $i = 1; $i <= 3; $i++ ) {
		echo '<p style="font-weight:600;margin-bottom:2px;">Vídeo ' . $i . '</p>';
		dc_admin_text( $post, "dc_home_video{$i}_titulo", 'Título' );
		dc_admin_textarea( $post, "dc_home_video{$i}_resumo", 'Chamada', '', 2 );
		dc_admin_url( $post, "dc_home_video{$i}_url", 'URL do vídeo no YouTube' );
		dc_admin_image( $post, "dc_home_video{$i}_imagem", 'Thumbnail' );
	}

	dc_meta_box_footer(
		array( 'dc_home_abertura_titulo', 'dc_home_abertura_paragrafo', 'dc_home_abertura_botao_label',
			'dc_home_porta1_titulo', 'dc_home_porta1_desc', 'dc_home_porta2_titulo', 'dc_home_porta2_desc',
			'dc_home_porta3_titulo', 'dc_home_porta3_desc', 'dc_home_porta4_titulo', 'dc_home_porta4_desc',
			'dc_home_emfoco1_categoria', 'dc_home_emfoco1_titulo', 'dc_home_emfoco1_resumo',
			'dc_home_emfoco2_categoria', 'dc_home_emfoco2_titulo', 'dc_home_emfoco2_resumo',
			'dc_home_emfoco3_categoria', 'dc_home_emfoco3_titulo', 'dc_home_emfoco3_resumo',
			'dc_home_video1_titulo', 'dc_home_video1_resumo', 'dc_home_video2_titulo', 'dc_home_video2_resumo',
			'dc_home_video3_titulo', 'dc_home_video3_resumo',
		),
		array( 'dc_home_abertura_botao_url', 'dc_home_ad1_url', 'dc_home_ad2_url', 'dc_home_youtube_botao_url',
			'dc_home_emfoco1_url', 'dc_home_emfoco2_url', 'dc_home_emfoco3_url',
			'dc_home_video1_url', 'dc_home_video2_url', 'dc_home_video3_url',
		),
		array( 'dc_home_abertura_imagem', 'dc_home_porta1_imagem', 'dc_home_porta2_imagem', 'dc_home_porta3_imagem', 'dc_home_porta4_imagem',
			'dc_home_ad1_imagem', 'dc_home_ad2_imagem',
			'dc_home_emfoco1_imagem', 'dc_home_emfoco2_imagem', 'dc_home_emfoco3_imagem',
			'dc_home_video1_imagem', 'dc_home_video2_imagem', 'dc_home_video3_imagem',
		),
		array( 'dc_home_ad1_ativo', 'dc_home_ad2_ativo' )
	);
}

/* ==========================================================================
   SOBRE NÓS
   ========================================================================== */
function dc_render_meta_box_sobre( $post ) {
	dc_admin_section_title( 'N.00 — Abertura institucional' );
	dc_admin_text( $post, 'dc_sobre_abertura_titulo', 'Título (H1) — use <br> para forçar quebra de linha manual, se necessário' );
	dc_admin_textarea( $post, 'dc_sobre_abertura_paragrafos', 'Parágrafos (um por linha)', 'Um parágrafo por linha em branco.', 5 );
	dc_admin_textarea( $post, 'dc_sobre_abertura_forte', 'Parágrafo de destaque (negrito)', '', 2 );
	dc_admin_image( $post, 'dc_sobre_abertura_imagem', 'Foto — Giovana + Vitor' );
	dc_admin_caption( $post, 'dc_sobre_abertura_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'N.01 — O Encontro que virou Descomplicarro' );
	dc_admin_text( $post, 'dc_sobre_encontro_heading', 'Título da seção' );
	dc_admin_text( $post, 'dc_sobre_encontro_lead', 'Frase de abertura (destaque)' );
	dc_admin_textarea( $post, 'dc_sobre_encontro_corpo1', 'Parágrafos — bloco 1 (antes das linhas de destaque)', '', 5 );
	dc_admin_textarea( $post, 'dc_sobre_encontro_destaque1', 'Linha de destaque 1' );
	dc_admin_textarea( $post, 'dc_sobre_encontro_destaque2', 'Linha de destaque 2' );
	dc_admin_textarea( $post, 'dc_sobre_encontro_corpo2', 'Parágrafos — bloco 2 (depois das linhas de destaque, primeiro é destaque)', '', 5 );

	dc_admin_section_title( 'N.02 — Giovana Toso' );
	dc_admin_text( $post, 'dc_sobre_giovana_role', 'Papel/eyebrow' );
	dc_admin_text( $post, 'dc_sobre_giovana_nome', 'Nome' );
	dc_admin_textarea( $post, 'dc_sobre_giovana_texto', 'Texto', '', 4 );
	dc_admin_image( $post, 'dc_sobre_giovana_imagem', 'Foto' );
	dc_admin_caption( $post, 'dc_sobre_giovana_imagem_legenda', 'Legenda da foto' );

	dc_admin_section_title( 'N.03 — Vitor Lima' );
	dc_admin_text( $post, 'dc_sobre_vitor_role', 'Papel/eyebrow' );
	dc_admin_text( $post, 'dc_sobre_vitor_nome', 'Nome' );
	dc_admin_textarea( $post, 'dc_sobre_vitor_texto', 'Texto', '', 4 );
	dc_admin_image( $post, 'dc_sobre_vitor_imagem', 'Foto' );
	dc_admin_caption( $post, 'dc_sobre_vitor_imagem_legenda', 'Legenda da foto' );

	dc_admin_section_title( 'N.04 — Manifesto' );
	dc_admin_text( $post, 'dc_sobre_manifesto_lead', 'Frase de abertura' );
	dc_admin_textarea( $post, 'dc_sobre_manifesto_corpo1', 'Parágrafos — bloco 1', '', 4 );
	dc_admin_textarea( $post, 'dc_sobre_manifesto_destaque1', 'Linha de destaque 1' );
	dc_admin_textarea( $post, 'dc_sobre_manifesto_destaque2', 'Linha de destaque 2' );
	dc_admin_textarea( $post, 'dc_sobre_manifesto_destaque3', 'Linha de destaque 3' );
	dc_admin_textarea( $post, 'dc_sobre_manifesto_corpo2', 'Parágrafos — bloco 2', '', 4 );

	dc_admin_section_title( 'N.05 — Nosso Jeito de Fazer' );
	dc_admin_textarea( $post, 'dc_sobre_missao', 'Missão (parágrafos)', '', 5 );
	dc_admin_textarea( $post, 'dc_sobre_visao', 'Visão (parágrafos)', '', 5 );
	dc_admin_repeater( $post, 'dc_sobre_valores', 'Valores (lista numerada)', array(
		'titulo' => array( 'label' => 'Título', 'type' => 'text' ),
		'desc'   => array( 'label' => 'Descrição', 'type' => 'textarea' ),
	), 'A numeração (01, 02, 03...) é gerada automaticamente pela ordem dos itens.' );

	dc_admin_section_title( 'N.06 — Fechamento' );
	dc_admin_text( $post, 'dc_sobre_fechamento_intro', 'Frase de introdução' );
	dc_admin_textarea( $post, 'dc_sobre_fechamento_detalhe1', 'Entenda — texto', '', 2 );
	dc_admin_textarea( $post, 'dc_sobre_fechamento_detalhe2', 'Questione — texto', '', 2 );
	dc_admin_textarea( $post, 'dc_sobre_fechamento_detalhe3', 'Decida — texto', '', 2 );
	dc_admin_textarea( $post, 'dc_sobre_fechamento_final1', 'Parágrafo final 1' );
	dc_admin_textarea( $post, 'dc_sobre_fechamento_final2', 'Parágrafo final 2 (destaque)' );

	dc_meta_box_footer(
		array(
			'dc_sobre_abertura_titulo', 'dc_sobre_abertura_paragrafos', 'dc_sobre_abertura_forte',
			'dc_sobre_encontro_heading', 'dc_sobre_encontro_lead', 'dc_sobre_encontro_corpo1', 'dc_sobre_encontro_destaque1', 'dc_sobre_encontro_destaque2', 'dc_sobre_encontro_corpo2',
			'dc_sobre_giovana_role', 'dc_sobre_giovana_nome', 'dc_sobre_giovana_texto',
			'dc_sobre_vitor_role', 'dc_sobre_vitor_nome', 'dc_sobre_vitor_texto',
			'dc_sobre_manifesto_lead', 'dc_sobre_manifesto_corpo1', 'dc_sobre_manifesto_destaque1', 'dc_sobre_manifesto_destaque2', 'dc_sobre_manifesto_destaque3', 'dc_sobre_manifesto_corpo2',
			'dc_sobre_missao', 'dc_sobre_visao',
			'dc_sobre_fechamento_intro', 'dc_sobre_fechamento_detalhe1', 'dc_sobre_fechamento_detalhe2', 'dc_sobre_fechamento_detalhe3', 'dc_sobre_fechamento_final1', 'dc_sobre_fechamento_final2',
		),
		array(),
		array( 'dc_sobre_abertura_imagem', 'dc_sobre_giovana_imagem', 'dc_sobre_vitor_imagem' ),
		array(),
		array( 'dc_sobre_valores' ),
		array( 'dc_sobre_abertura_imagem_legenda', 'dc_sobre_giovana_imagem_legenda', 'dc_sobre_vitor_imagem_legenda' )
	);
}

/* ==========================================================================
   PARA MOTORISTAS
   ========================================================================== */
function dc_render_meta_box_motoristas( $post ) {
	dc_admin_section_title( 'N.00 — Abertura' );
	dc_admin_text( $post, 'dc_motoristas_titulo', 'Título (H1)' );
	dc_admin_textarea( $post, 'dc_motoristas_paragrafos', 'Parágrafos', '', 4 );
	dc_admin_textarea( $post, 'dc_motoristas_forte', 'Parágrafo de destaque', '', 2 );
	dc_admin_image( $post, 'dc_motoristas_imagem', 'Imagem de abertura' );
	dc_admin_caption( $post, 'dc_motoristas_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'N.02 — Match Automotivo' );
	dc_admin_text( $post, 'dc_match_role', 'Eyebrow' );
	dc_admin_text( $post, 'dc_match_nome', 'Título' );
	dc_admin_textarea( $post, 'dc_match_texto', 'Parágrafos', '', 6 );
	dc_admin_text( $post, 'dc_match_stat1_numero', 'Estatística 1 — número' );
	dc_admin_text( $post, 'dc_match_stat1_label', 'Estatística 1 — legenda' );
	dc_admin_text( $post, 'dc_match_stat2_numero', 'Estatística 2 — número' );
	dc_admin_text( $post, 'dc_match_stat2_label', 'Estatística 2 — legenda' );
	dc_admin_text( $post, 'dc_match_assinatura_parte1', 'Assinatura da solução — parte 1, texto normal' );
	dc_admin_text( $post, 'dc_match_assinatura_parte2', 'Assinatura da solução — parte 2, em destaque laranja' );
	dc_admin_text( $post, 'dc_match_botao_label', 'Texto do botão (Hotmart)' );
	dc_admin_url( $post, 'dc_match_botao_url', 'URL de compra (Hotmart)' );
	dc_admin_image( $post, 'dc_match_imagem', 'Identidade visual' );
	dc_admin_caption( $post, 'dc_match_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'N.03 — GI Conecta' );
	dc_admin_text( $post, 'dc_gi_role', 'Eyebrow' );
	dc_admin_text( $post, 'dc_gi_nome', 'Título' );
	dc_admin_textarea( $post, 'dc_gi_texto', 'Parágrafos', '', 6 );
	dc_admin_textarea( $post, 'dc_gi_destaque', 'Linhas de destaque (uma por linha)', '', 3 );
	dc_admin_image( $post, 'dc_gi_imagem', 'Identidade visual' );
	dc_admin_caption( $post, 'dc_gi_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'Espaços publicitários' );
	dc_admin_checkbox( $post, 'dc_motoristas_ad1_ativo', 'Exibir espaço publicitário 1 (antes do Match Automotivo)' );
	dc_admin_url( $post, 'dc_motoristas_ad1_url', 'Link do anunciante 1' );
	dc_admin_image( $post, 'dc_motoristas_ad1_imagem', 'Imagem/banner 1' );
	dc_admin_checkbox( $post, 'dc_motoristas_ad2_ativo', 'Exibir espaço publicitário 2 (final da página)' );
	dc_admin_url( $post, 'dc_motoristas_ad2_url', 'Link do anunciante 2' );
	dc_admin_image( $post, 'dc_motoristas_ad2_imagem', 'Imagem/banner 2' );

	dc_meta_box_footer(
		array( 'dc_motoristas_titulo', 'dc_motoristas_paragrafos', 'dc_motoristas_forte',
			'dc_match_role', 'dc_match_nome', 'dc_match_texto', 'dc_match_stat1_numero', 'dc_match_stat1_label', 'dc_match_stat2_numero', 'dc_match_stat2_label', 'dc_match_assinatura_parte1', 'dc_match_assinatura_parte2', 'dc_match_botao_label',
			'dc_gi_role', 'dc_gi_nome', 'dc_gi_texto', 'dc_gi_destaque',
		),
		array( 'dc_match_botao_url', 'dc_motoristas_ad1_url', 'dc_motoristas_ad2_url' ),
		array( 'dc_motoristas_imagem', 'dc_match_imagem', 'dc_gi_imagem', 'dc_motoristas_ad1_imagem', 'dc_motoristas_ad2_imagem' ),
		array( 'dc_motoristas_ad1_ativo', 'dc_motoristas_ad2_ativo' ),
		array(),
		array( 'dc_motoristas_imagem_legenda', 'dc_match_imagem_legenda', 'dc_gi_imagem_legenda' )
	);
}

/* ==========================================================================
   PARA OFICINAS
   ========================================================================== */
function dc_render_meta_box_oficinas( $post ) {
	dc_admin_section_title( 'N.00 — Abertura' );
	dc_admin_text( $post, 'dc_oficinas_titulo', 'Título (H1)' );
	dc_admin_textarea( $post, 'dc_oficinas_paragrafos', 'Parágrafos', '', 4 );
	dc_admin_textarea( $post, 'dc_oficinas_forte', 'Parágrafo de destaque', '', 2 );
	dc_admin_image( $post, 'dc_oficinas_imagem', 'Imagem de abertura' );
	dc_admin_caption( $post, 'dc_oficinas_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'N.01 — Da Entrada ao Pós-venda' );
	dc_admin_text( $post, 'dc_oficinas_jornada_heading', 'Título da seção' );
	dc_admin_textarea( $post, 'dc_oficinas_jornada_texto', 'Parágrafos', '', 3 );
	dc_admin_textarea( $post, 'dc_oficinas_jornada_etapas', 'Etapas da jornada (uma por linha, na ordem exibida)', 'Texto simples — o layout (setas, numeração, intervenção em laranja entre a 5ª e 6ª etapa) é fixo.', 8 );

	dc_admin_section_title( 'N.02 — Formação (treinamentos, ordem fixa)' );
	dc_admin_repeater( $post, 'dc_oficinas_formacao', 'Treinamentos', array(
		'jornada_tag' => array( 'label' => 'Etapa da jornada (etiqueta)', 'type' => 'text' ),
		'titulo'      => array( 'label' => 'Título do treinamento', 'type' => 'text' ),
		'conceito'    => array( 'label' => 'Conceito (subtítulo curto)', 'type' => 'text' ),
		'texto'       => array( 'label' => 'Parágrafo', 'type' => 'textarea' ),
		'destaque1'   => array( 'label' => 'Linha de destaque 1 (opcional — ex.: só usada no Checklist Vendedor)', 'type' => 'text' ),
		'destaque2'   => array( 'label' => 'Linha de destaque 2 (opcional)', 'type' => 'text' ),
		'jornada_visual' => array( 'label' => 'Etapas do método, para exibir como Teoria→Técnica→Tradução→Venda (opcional, uma por linha — ex.: só usada no Método T3V)', 'type' => 'textarea' ),
		'assinatura_parte1' => array( 'label' => 'Assinatura — parte 1, texto normal (opcional — ex.: só usada em Depois da Entrega)', 'type' => 'text' ),
		'assinatura_parte2' => array( 'label' => 'Assinatura — parte 2, em destaque laranja (opcional)', 'type' => 'text' ),
		'botao_label' => array( 'label' => 'Texto do botão', 'type' => 'text' ),
		'botao_url'   => array( 'label' => 'URL de compra (Hotmart)', 'type' => 'text' ),
	), 'Mantenha a ordem em que os treinamentos devem aparecer na página. Os campos opcionais só aparecem no site quando preenchidos.' );

	dc_admin_section_title( 'N.03 — Pack de Papelaria' );
	dc_admin_text( $post, 'dc_pack_lead', 'Frase de abertura' );
	dc_admin_textarea( $post, 'dc_pack_texto', 'Parágrafos', '', 4 );
	dc_admin_textarea( $post, 'dc_pack_itens', 'Itens do pack (um por linha)', '', 5 );
	dc_admin_text( $post, 'dc_pack_botao_label', 'Texto do botão' );
	dc_admin_url( $post, 'dc_pack_botao_url', 'URL de compra (Hotmart)' );

	dc_admin_section_title( 'N.04 — GI Conecta (credenciamento)' );
	dc_admin_textarea( $post, 'dc_oficinas_gi_texto', 'Parágrafos', '', 3 );
	dc_admin_text( $post, 'dc_oficinas_gi_botao_label', 'Texto do botão' );
	dc_admin_url( $post, 'dc_oficinas_gi_botao_url', 'URL do formulário de credenciamento' );

	dc_admin_section_title( 'N.05 — Parceiros (QR Car + Mecânico Que Lucra)' );
	dc_admin_checkbox( $post, 'dc_parceiros_ativo', 'Exibir seção de Parceiros' );
	dc_admin_textarea( $post, 'dc_parceiros_texto', 'Texto de introdução', '', 3 );
	dc_admin_text( $post, 'dc_qrcar_texto', 'QR Car — descrição oficial' );
	dc_admin_url( $post, 'dc_qrcar_url', 'QR Car — URL comercial' );
	dc_admin_image( $post, 'dc_qrcar_imagem', 'QR Car — logo' );
	dc_admin_caption( $post, 'dc_qrcar_imagem_legenda', 'QR Car — legenda da imagem' );
	dc_admin_image( $post, 'dc_ml_imagem', 'Mecânico Que Lucra — logo' );
	dc_admin_caption( $post, 'dc_ml_imagem_legenda', 'Mecânico Que Lucra — legenda da imagem' );
	dc_admin_repeater( $post, 'dc_ml_acessos', 'Mecânico Que Lucra — acessos (ML Repertório, ML Tração, ML Comando)', array(
		'titulo'    => array( 'label' => 'Título', 'type' => 'text' ),
		'desc'      => array( 'label' => 'Descrição', 'type' => 'textarea' ),
		'botao_url' => array( 'label' => 'URL comercial', 'type' => 'text' ),
	) );

	dc_admin_section_title( 'Espaço publicitário (após Formação)' );
	dc_admin_checkbox( $post, 'dc_oficinas_ad_ativo', 'Exibir este espaço publicitário' );
	dc_admin_url( $post, 'dc_oficinas_ad_url', 'Link do anunciante' );
	dc_admin_image( $post, 'dc_oficinas_ad_imagem', 'Imagem/banner' );

	dc_meta_box_footer(
		array( 'dc_oficinas_titulo', 'dc_oficinas_paragrafos', 'dc_oficinas_forte',
			'dc_oficinas_jornada_heading', 'dc_oficinas_jornada_texto', 'dc_oficinas_jornada_etapas',
			'dc_pack_lead', 'dc_pack_texto', 'dc_pack_itens', 'dc_pack_botao_label',
			'dc_oficinas_gi_texto', 'dc_oficinas_gi_botao_label',
			'dc_parceiros_texto', 'dc_qrcar_texto',
		),
		array( 'dc_pack_botao_url', 'dc_oficinas_gi_botao_url', 'dc_qrcar_url', 'dc_oficinas_ad_url' ),
		array( 'dc_oficinas_imagem', 'dc_qrcar_imagem', 'dc_ml_imagem', 'dc_oficinas_ad_imagem' ),
		array( 'dc_parceiros_ativo', 'dc_oficinas_ad_ativo' ),
		array( 'dc_oficinas_formacao', 'dc_ml_acessos' ),
		array( 'dc_oficinas_imagem_legenda', 'dc_qrcar_imagem_legenda', 'dc_ml_imagem_legenda' )
	);
}

/* ==========================================================================
   PALESTRAS & WORKSHOPS
   ========================================================================== */
function dc_render_meta_box_palestras( $post ) {
	dc_admin_section_title( 'N.00 — Abertura' );
	dc_admin_text( $post, 'dc_palestras_titulo', 'Título (H1)' );
	dc_admin_textarea( $post, 'dc_palestras_paragrafos', 'Parágrafos', '', 4 );
	dc_admin_textarea( $post, 'dc_palestras_forte', 'Parágrafo de destaque', '', 2 );
	dc_admin_image( $post, 'dc_palestras_imagem', 'Imagem de abertura' );
	dc_admin_caption( $post, 'dc_palestras_imagem_legenda', 'Legenda da imagem' );

	dc_admin_section_title( 'N.01 — Formatos' );
	dc_admin_text( $post, 'dc_formatos_heading', 'Título da seção' );
	dc_admin_repeater( $post, 'dc_formatos_lista', 'Formatos oferecidos', array(
		'label' => array( 'label' => 'Nome do formato', 'type' => 'text' ),
	), 'A numeração (01, 02...) é gerada pela ordem dos itens.' );
	dc_admin_text( $post, 'dc_formatos_nota', 'Nota discreta' );

	dc_admin_section_title( 'N.02 — Temas' );
	dc_admin_text( $post, 'dc_temas_heading', 'Título da seção' );
	dc_admin_textarea( $post, 'dc_temas_intro', 'Introdução (lead + parágrafo)', '', 3 );
	dc_admin_repeater( $post, 'dc_temas_lista', 'Territórios de atuação', array(
		'titulo' => array( 'label' => 'Título', 'type' => 'text' ),
		'desc'   => array( 'label' => 'Descrição', 'type' => 'textarea' ),
	) );
	dc_admin_text( $post, 'dc_temas_assinatura_parte1', 'Assinatura — parte 1, texto normal (ex.: "O tema")' );
	dc_admin_text( $post, 'dc_temas_assinatura_parte2', 'Assinatura — parte 2, em destaque laranja (ex.: "não precisa estar nesta lista.")' );
	dc_admin_textarea( $post, 'dc_temas_fechamento', 'Parágrafo final' );

	dc_admin_section_title( 'N.03 — DOMINIUM' );
	dc_admin_text( $post, 'dc_dominium_lead', 'Frase de abertura' );
	dc_admin_textarea( $post, 'dc_dominium_texto', 'Parágrafos', '', 4 );
	dc_admin_textarea( $post, 'dc_dominium_pilar1', 'Pilar 1 (Entender)', '', 2 );
	dc_admin_textarea( $post, 'dc_dominium_pilar2', 'Pilar 2 (Identificar)', '', 2 );
	dc_admin_textarea( $post, 'dc_dominium_pilar3', 'Pilar 3 (Decidir)', '', 2 );
	dc_admin_text( $post, 'dc_dominium_botao_label', 'Texto do botão' );
	dc_admin_repeater( $post, 'dc_dominium_galeria', 'Galeria DOMINIUM', array(
		'imagem'  => array( 'label' => 'Foto', 'type' => 'image' ),
		'legenda' => array( 'label' => 'Legenda', 'type' => 'text' ),
	) );

	dc_admin_section_title( 'N.04 — Quem leva o conteúdo' );
	dc_admin_text( $post, 'dc_equipe_heading', 'Título da seção' );
	dc_admin_textarea( $post, 'dc_equipe_intro', 'Parágrafo de introdução', '', 2 );
	dc_admin_text( $post, 'dc_equipe_giovana_role', 'Giovana — papel' );
	dc_admin_textarea( $post, 'dc_equipe_giovana_texto', 'Giovana — texto', '', 3 );
	dc_admin_image( $post, 'dc_equipe_giovana_imagem', 'Giovana — foto' );
	dc_admin_caption( $post, 'dc_equipe_giovana_imagem_legenda', 'Giovana — legenda da foto' );
	dc_admin_text( $post, 'dc_equipe_vitor_role', 'Vitor — papel' );
	dc_admin_textarea( $post, 'dc_equipe_vitor_texto', 'Vitor — texto', '', 3 );
	dc_admin_image( $post, 'dc_equipe_vitor_imagem', 'Vitor — foto' );
	dc_admin_caption( $post, 'dc_equipe_vitor_imagem_legenda', 'Vitor — legenda da foto' );

	dc_admin_section_title( 'N.05 — Já estivemos por aqui' );
	dc_admin_checkbox( $post, 'dc_experiencias_ativo', 'Exibir esta galeria' );
	dc_admin_repeater( $post, 'dc_experiencias_galeria', 'Registros de eventos', array(
		'imagem'  => array( 'label' => 'Foto', 'type' => 'image' ),
		'legenda' => array( 'label' => 'Legenda', 'type' => 'text' ),
	), 'Usar apenas registros reais — não inserir nomes de eventos/clientes fictícios.' );

	dc_admin_section_title( 'N.06 — Contato' );
	dc_admin_text( $post, 'dc_contato_heading', 'Título da seção' );
	dc_admin_textarea( $post, 'dc_contato_texto', 'Parágrafos', '', 3 );
	dc_admin_text( $post, 'dc_contato_botao_label', 'Texto do botão' );

	dc_meta_box_footer(
		array( 'dc_palestras_titulo', 'dc_palestras_paragrafos', 'dc_palestras_forte',
			'dc_formatos_heading', 'dc_formatos_nota',
			'dc_temas_heading', 'dc_temas_intro', 'dc_temas_assinatura_parte1', 'dc_temas_assinatura_parte2', 'dc_temas_fechamento',
			'dc_dominium_lead', 'dc_dominium_texto', 'dc_dominium_pilar1', 'dc_dominium_pilar2', 'dc_dominium_pilar3', 'dc_dominium_botao_label',
			'dc_equipe_heading', 'dc_equipe_intro', 'dc_equipe_giovana_role', 'dc_equipe_giovana_texto', 'dc_equipe_vitor_role', 'dc_equipe_vitor_texto',
			'dc_contato_heading', 'dc_contato_texto', 'dc_contato_botao_label',
		),
		array(),
		array( 'dc_palestras_imagem', 'dc_equipe_giovana_imagem', 'dc_equipe_vitor_imagem' ),
		array( 'dc_experiencias_ativo' ),
		array( 'dc_formatos_lista', 'dc_temas_lista', 'dc_dominium_galeria', 'dc_experiencias_galeria' ),
		array( 'dc_palestras_imagem_legenda', 'dc_equipe_giovana_imagem_legenda', 'dc_equipe_vitor_imagem_legenda' )
	);
}
