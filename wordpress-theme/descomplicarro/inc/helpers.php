<?php
/**
 * DESCOMPLICARRO — funções auxiliares de leitura de conteúdo (uso no front-end).
 *
 * Estas funções são o único ponto de leitura de post meta usado pelos
 * templates. Isso mantém a lógica "buscar valor, aplicar fallback, escapar"
 * em um único lugar em vez de repetida em cada arquivo de template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lê um campo de texto/URL do post atual (ou informado), com fallback.
 * Sempre escapado para uso seguro em atributos/texto simples.
 */
function dc_field( $key, $default = '', $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return ( $value !== '' && $value !== null ) ? $value : $default;
}

/** Igual a dc_field(), mas sem escapar — usar apenas dentro de esc_html()/esc_attr() no template. */
function dc_field_raw( $key, $default = '', $post_id = null ) {
	return dc_field( $key, $default, $post_id );
}

/** Campo de texto: imprime já escapado. */
function dc_text( $key, $default = '', $post_id = null ) {
	echo esc_html( dc_field( $key, $default, $post_id ) );
}

/** Campo de parágrafo/textarea: permite quebras de linha simples, escapa o resto. */
function dc_paragraph( $key, $default = '', $post_id = null ) {
	echo wp_kses_post( wpautop( dc_field( $key, $default, $post_id ) ) );
}

/**
 * Imprime um campo de texto de várias linhas como uma sequência de <p>,
 * uma tag por linha não vazia — usado para blocos de parágrafos "corridos"
 * armazenados em um único textarea (ver inc/meta-boxes.php).
 */
function dc_paragraphs_lines( $key, $default_lines, $post_id = null, $class = '' ) {
	$raw   = dc_field_raw( $key, '', $post_id );
	$lines = $raw !== '' ? preg_split( '/\r\n|\r|\n/', trim( $raw ) ) : (array) $default_lines;
	$class_attr = $class ? ' class="' . esc_attr( $class ) . '"' : '';
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		echo '<p' . $class_attr . '>' . esc_html( $line ) . '</p>';
	}
}

/** Campo de URL: imprime já escapado para uso em href. */
function dc_url( $key, $default = '#', $post_id = null ) {
	$value = dc_field( $key, $default, $post_id );
	echo esc_url( $value ? $value : $default );
}

/**
 * Campo de imagem: aceita um ID de anexo (Biblioteca de Mídia) armazenado
 * em post meta e devolve a URL. Se vazio, usa o placeholder do tema.
 */
function dc_image_url( $key, $post_id = null, $fallback_file = 'placeholder-photo.svg' ) {
	$post_id      = $post_id ? $post_id : get_the_ID();
	$attachment_id = (int) get_post_meta( $post_id, $key, true );
	if ( $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'large' );
		if ( $url ) {
			return $url;
		}
	}
	return get_template_directory_uri() . '/assets/images/' . $fallback_file;
}

/**
 * Resolve a URL de um anexo (ID da Biblioteca de Mídia) a partir do tamanho
 * pedido, com uma cadeia de alternativas antes de desistir: tamanho pedido →
 * "full" → URL bruta do arquivo original. Isso cobre o caso em que o
 * WordPress não conseguiu gerar as variações de tamanho de uma imagem (ex.:
 * certos formatos/perfis de cor podem falhar na geração de miniaturas,
 * deixando o anexo salvo mas sem metadados de tamanho) — sem essa cadeia,
 * `wp_get_attachment_image_url()` pode devolver vazio mesmo com um anexo
 * válido e corretamente salvo no campo. Usada pelo perfil e pela listagem de
 * oficinas do GI CONECTA (logotipo e galeria de fotos).
 */
function dc_attachment_image_url( $attachment_id, $size = 'large' ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id ) {
		return '';
	}
	$url = wp_get_attachment_image_url( $attachment_id, $size );
	if ( $url ) {
		return $url;
	}
	if ( $size !== 'full' ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'full' );
		if ( $url ) {
			return $url;
		}
	}
	$url = wp_get_attachment_url( $attachment_id );
	return $url ? $url : '';
}

/**
 * Imprime o conteúdo completo de um espaço publicitário — o
 * <a class="ad-slot__unit">, com tudo dentro. Ponto único usado por TODOS
 * os espaços publicitários do tema (atuais e futuros): qualquer novo
 * .ad-slot deve chamar esta função em vez de repetir o HTML do
 * placeholder, para herdar a mesma lógica de exibição.
 *
 * $key_prefix identifica o par de campos legados daquele espaço:
 * "{$key_prefix}_imagem" (ID de anexo) e "{$key_prefix}_url" (link do
 * anunciante), cadastrados direto na página — ex.: dc_ad_slot_unit('dc_home_ad1').
 *
 * $posicao (opcional) ativa o Gerenciador de Publicidade (ver
 * inc/cpt-campanhas.php) para este espaço. $categoria_ids só é usado
 * quando a posição é segmentada por editoria (matéria/página de editoria).
 *
 * Ordem de exibição:
 * 1. Campanha ativa elegível em "Publicidade" para $posicao.
 * 2. Imagem/link cadastrados manualmente nesta página ($key_prefix) —
 *    comportamento já existente antes do Gerenciador de Publicidade.
 * 3. Banner institucional padrão (Configurações Descomplicarro).
 * 4. Placeholder original ("PUBLICIDADE — ESPAÇO RESERVADO").
 *
 * Em qualquer um dos três primeiros casos, a imagem é exibida em proporção
 * original, sem corte (ver .ad-slot__image no CSS) — nenhuma dimensão do
 * espaço é alterada entre os casos.
 */
function dc_ad_slot_unit( $key_prefix, $posicao = null, $categoria_ids = array() ) {
	$anuncio = $posicao ? dc_resolver_anuncio( $posicao, $categoria_ids ) : null;

	if ( ! $anuncio ) {
		$post_id  = get_the_ID();
		$image_id = (int) get_post_meta( $post_id, "{$key_prefix}_imagem", true );
		if ( $image_id && wp_attachment_is_image( $image_id ) ) {
			$anuncio = array(
				'imagem_id' => $image_id,
				'href'      => dc_field_raw( "{$key_prefix}_url", '#', $post_id ) ?: '#',
				'alt'       => 'Publicidade',
				'nova_aba'  => false,
			);
		}
	}

	if ( ! $anuncio ) {
		$anuncio = dc_banner_institucional();
	}

	$href      = $anuncio ? ( $anuncio['href'] ?: '#' ) : '#';
	$classes   = 'ad-slot__unit' . ( $anuncio ? ' ad-slot__unit--filled' : '' );
	$target    = ( $anuncio && ! empty( $anuncio['nova_aba'] ) ) ? ' target="_blank" rel="noopener noreferrer"' : '';

	/*
	 * Identificadores para as métricas de publicidade (ver assets/js/dc-tracking.js
	 * e inc/analytics.php) — câmpanha/anunciante/posição/página/editoria, nunca
	 * só o texto do botão. "data-dc-campanha"/"data-dc-institucional" também
	 * marcam o elemento para o observador de impressões (50% visível por 1s).
	 */
	$editoria_rotulo = '';
	if ( ! empty( $categoria_ids ) ) {
		$termo = get_category( reset( $categoria_ids ) );
		$editoria_rotulo = $termo ? $termo->name : '';
	}
	$dados_rastreio = ' data-dc-event="ad_click" data-dc-posicao="' . esc_attr( (string) $posicao ) . '" data-dc-pagina="' . esc_attr( get_the_title() ) . '"';
	if ( $editoria_rotulo ) {
		$dados_rastreio .= ' data-dc-editoria="' . esc_attr( $editoria_rotulo ) . '"';
	}
	if ( $anuncio && ! empty( $anuncio['campanha_id'] ) ) {
		$dados_rastreio .= ' data-dc-campanha="' . esc_attr( $anuncio['campanha_id'] ) . '" data-dc-anunciante="' . esc_attr( $anuncio['anunciante'] ) . '"';
	} elseif ( $anuncio && ! empty( $anuncio['institucional'] ) ) {
		$dados_rastreio .= ' data-dc-institucional="1"';
	}

	echo '<a href="' . esc_url( $href ) . '" class="' . esc_attr( $classes ) . '"' . $target . $dados_rastreio . '>';

	if ( $anuncio ) {
		echo '<span class="ad-slot__eyebrow">Publicidade</span>';
		/*
		 * "dc_anuncio" (1600×400, registrado em inc/setup.php) no lugar de
		 * "large" (limitado a 1024px) — é essa troca que corrige a perda de
		 * nitidez dos banners. wp_get_attachment_image() também gera
		 * srcset/sizes nativamente, então uma tela retina pode pedir uma
		 * versão maior quando o arquivo original permitir.
		 */
		echo wp_get_attachment_image( $anuncio['imagem_id'], 'dc_anuncio', false, array(
			'class' => 'ad-slot__image',
			'alt'   => $anuncio['alt'] ?: 'Publicidade',
		) );
	} else {
		echo '<span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>';
		echo '<span class="ad-slot__eyebrow">Publicidade</span>';
		echo '<span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>';
		echo '<span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>';
	}

	echo '</a>';
}

/** Verifica se uma seção opcional está ativa (padrão: ativa, quando o campo nunca foi salvo). */
function dc_section_active( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return $value === '' ? true : (bool) $value;
}

/**
 * Lê um repetidor (lista de itens) salvo como array associativo em post meta.
 * Retorna sempre um array (nunca null), para uso direto em foreach.
 */
function dc_repeater( $key, $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$value   = get_post_meta( $post_id, $key, true );
	return is_array( $value ) ? $value : array();
}

/**
 * Lê um campo de LEGENDA DE IMAGEM. Diferente de dc_field(), distingue um
 * campo que nunca foi salvo (usa o texto padrão da V1) de um campo que o
 * administrador esvaziou de propósito (metadata_exists() continua
 * verdadeiro — a linha existe no banco com valor '' — e a ausência de
 * legenda passa a ser permanente, sem o padrão reaparecer).
 * Ver inc/meta-boxes.php: os campos de legenda são sempre gravados no
 * save, mesmo vazios, em vez de apagados — é isso que preserva o registro
 * de "esvaziado intencionalmente".
 */
function dc_caption( $key, $default = '', $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	if ( ! metadata_exists( 'post', $post_id, $key ) ) {
		return $default;
	}
	return get_post_meta( $post_id, $key, true );
}

/**
 * Imprime a tag da legenda apenas quando há texto a exibir. Quando o campo
 * foi esvaziado intencionalmente (ou uma legenda de repetidor está vazia),
 * nada é impresso — nem a tag/o espaço reservado.
 */
function dc_caption_html( $key, $default = '', $class = 'figure-caption', $post_id = null ) {
	$value = dc_caption( $key, $default, $post_id );
	if ( $value === '' ) {
		return;
	}
	echo '<p class="' . esc_attr( $class ) . '">' . esc_html( $value ) . '</p>';
}

/** Mesma regra "vazio = nada" para a legenda de um item de repetidor (galerias). */
function dc_caption_html_raw( $value, $class = 'figure-caption' ) {
	$value = trim( (string) $value );
	if ( $value === '' ) {
		return;
	}
	echo '<p class="' . esc_attr( $class ) . '">' . esc_html( $value ) . '</p>';
}

/**
 * CONFIGURAÇÕES GLOBAIS — lê um valor da página de opções "Configurações
 * Descomplicarro" (option_name: dc_options). Ver inc/options-page.php.
 */
function dc_option( $key, $default = '' ) {
	$options = get_option( 'dc_options', array() );
	return ( isset( $options[ $key ] ) && $options[ $key ] !== '' ) ? $options[ $key ] : $default;
}

/**
 * Monta um link do WhatsApp (wa.me) a partir do número cadastrado em
 * Configurações Descomplicarro + uma mensagem pré-preenchida específica.
 *
 * Enquanto o número oficial não for cadastrado no painel, o link aponta
 * para um placeholder claramente identificado (WHATSAPP_DESCOMPLICARRO),
 * nunca para um número inventado.
 */
function dc_whatsapp_link( $message = '' ) {
	$numero = dc_option( 'whatsapp', '' );
	$numero = $numero !== '' ? preg_replace( '/[^0-9]/', '', $numero ) : 'WHATSAPP_DESCOMPLICARRO';
	$url    = 'https://wa.me/' . $numero;
	if ( $message !== '' ) {
		$url .= '?text=' . rawurlencode( $message );
	}
	return $url;
}

/** Monta o mailto: do e-mail oficial cadastrado, ou o placeholder enquanto não houver e-mail definido. */
function dc_email_link() {
	$email = dc_option( 'email', '' );
	return 'mailto:' . ( $email !== '' ? $email : 'EMAIL_DESCOMPLICARRO' );
}

/** Devolve o valor a exibir para o e-mail/whatsapp quando usados como texto visível (não apenas link). */
function dc_option_or_pending( $key, $label_pendente ) {
	$value = dc_option( $key, '' );
	return $value !== '' ? $value : $label_pendente;
}

/** As 27 UFs — lista fixa usada no filtro público do GI CONECTA e no cadastro de oficinas no admin. */
function dc_uf_list() {
	return array(
		'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
		'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
		'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
		'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
		'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
		'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
		'SE' => 'Sergipe', 'TO' => 'Tocantins',
	);
}

/**
 * GI CONECTA — URL do formulário de credenciamento de oficinas. Fonte única
 * usada tanto pelo botão em Para Motoristas quanto pelo botão do rodapé, para
 * nunca haver dois links divergentes. Enquanto não houver valor cadastrado em
 * Configurações Descomplicarro, usa o formulário oficial já fornecido.
 */
function dc_oficinas_credenciamento_url() {
	return dc_option( 'oficinas_credenciamento_url', 'https://docs.google.com/forms/d/e/1FAIpQLSc04iO7lDkTZ5SHdMk4xUrM5P6-tRbeY2TUxggV_waTUWVtLQ/viewform' );
}

/**
 * GI CONECTA — a página Para Oficinas está temporariamente fora do ar
 * (reformulação) enquanto este valor não for marcado em Configurações
 * Descomplicarro. Nada é apagado: reativar é só marcar a caixa e salvar,
 * sem editar nenhum arquivo.
 */
function dc_oficinas_pagina_ativa() {
	return (int) dc_option( 'oficinas_pagina_ativa', 0 ) === 1;
}

/**
 * GI CONECTA — resolve a imagem de capa de uma oficina (usada no card da
 * listagem e no perfil individual): Imagem Destacada (logotipo) quando
 * cadastrada; se não houver, a primeira foto da Galeria de fotos — muitas
 * oficinas são cadastradas só com fotos da galeria, sem logotipo próprio
 * definido, e a capa não deve ficar em branco nesse caso; sem nenhuma das
 * duas, string vazia (quem chama decide o placeholder).
 */
function dc_oficina_capa_url( $post_id, $size = 'large' ) {
	$thumb_id = get_post_thumbnail_id( $post_id );
	if ( $thumb_id ) {
		$url = dc_attachment_image_url( $thumb_id, $size );
		if ( $url ) {
			return $url;
		}
	}
	$galeria = dc_repeater( 'dc_oficina_galeria', $post_id );
	if ( ! empty( $galeria[0]['imagem'] ) ) {
		$url = dc_attachment_image_url( $galeria[0]['imagem'], $size );
		if ( $url ) {
			return $url;
		}
	}
	return '';
}

/**
 * Imprime uma lista <ul><li> a partir de um campo de texto com uma linha por
 * item (serviços, especialidades, benefícios). $default_lines aceita uma
 * string com quebras de linha (\n) ou um array — usado enquanto o campo
 * nunca foi editado no admin, mesmo padrão de dc_paragraphs_lines().
 */
function dc_list_lines( $key, $default_lines = '', $post_id = null, $class = '' ) {
	$raw = dc_field_raw( $key, '', $post_id );
	if ( $raw !== '' ) {
		$lines = preg_split( '/\r\n|\r|\n/', trim( $raw ) );
	} elseif ( is_array( $default_lines ) ) {
		$lines = $default_lines;
	} else {
		$lines = $default_lines !== '' ? preg_split( '/\r\n|\r|\n/', trim( $default_lines ) ) : array();
	}
	if ( ! $lines ) {
		return;
	}
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		echo '<li>' . esc_html( $line ) . '</li>';
	}
	echo '</ul>';
}

/**
 * INTEGRAÇÃO EDITORIAL — as cinco editorias oficiais do Em Foco (slug da
 * categoria nativa do WordPress => rótulo/assinatura exibidos no site).
 * Ponto único de referência usado por template-em-foco.php e category.php,
 * para nunca haver divergência entre o texto exibido em cada lugar.
 */
function dc_emfoco_categorias() {
	return array(
		'noticias'   => array( 'label' => 'Notícias', 'assinatura' => 'O que está acontecendo.' ),
		'reviews'    => array( 'label' => 'Reviews', 'assinatura' => 'O que testamos e analisamos.' ),
		'guias'      => array( 'label' => 'Guias', 'assinatura' => 'O que você precisa entender para decidir.' ),
		'tecnica'    => array( 'label' => 'Técnica', 'assinatura' => 'Como funciona, como diagnosticar e como reparar.' ),
		'bastidores' => array( 'label' => 'Bastidores', 'assinatura' => 'Quem, como e onde o setor acontece.' ),
	);
}

/**
 * Imprime um cartão .emfoco-card para uma publicação real do WordPress —
 * usado na capa do Em Foco, nas páginas de editoria e nos "Conteúdos
 * relacionados" da matéria individual, para nunca duplicar o HTML do
 * cartão em cada template.
 */
function dc_emfoco_card( $post, $extra_class = '', $on_dark = false ) {
	$categorias = get_the_category( $post->ID );
	$categoria  = ! empty( $categorias ) ? $categorias[0]->name : '';
	$thumb      = get_the_post_thumbnail_url( $post, 'large' );
	if ( ! $thumb ) {
		$thumb = get_template_directory_uri() . '/assets/images/placeholder-photo.svg';
	}
	$classes      = 'emfoco-card' . ( $extra_class ? ' ' . $extra_class : '' );
	$date_classes = 'emfoco-card__date' . ( $on_dark ? ' emfoco-card__date--on-dark' : '' );
	?>
	<a href="<?php echo esc_url( get_permalink( $post ) ); ?>" class="<?php echo esc_attr( $classes ); ?>" data-dc-event="article_click" data-dc-materia="<?php echo esc_attr( $post->ID ); ?>" data-dc-editoria="<?php echo esc_attr( $categoria ); ?>">
		<div class="emfoco-card__media">
			<img src="<?php echo esc_url( $thumb ); ?>" alt="" class="emfoco-card__image">
		</div>
		<?php if ( $categoria ) : ?>
			<span class="emfoco-card__category"><?php echo esc_html( $categoria ); ?></span>
		<?php endif; ?>
		<h3 class="emfoco-card__title"><?php echo esc_html( get_the_title( $post ) ); ?></h3>
		<span class="<?php echo esc_attr( $date_classes ); ?>"><?php echo esc_html( get_the_date( '', $post ) ); ?></span>
	</a>
	<?php
}
