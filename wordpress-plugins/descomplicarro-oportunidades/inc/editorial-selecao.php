<?php
/**
 * DESCOMPLICARRO — motor de seleção de conteúdo da Automação Editorial
 * (Etapa 4): decide, a partir da editoria da matéria e dos campos
 * editoriais opcionais preenchidos, o que vai em cada slide do
 * carrossel/Stories — SEM NUNCA inventar, resumir com IA ou complementar
 * com informação própria. Cada palavra de cada slide vem de algo que o
 * editor já escreveu (no corpo da matéria ou nos campos opcionais).
 *
 * Três caminhos, por editoria:
 *  - Reviews: usa "Destaques do veículo" (campo opcional) — um slide por
 *    destaque, frase curta e literal. Sem o campo preenchido, cai no
 *    caminho comum (H2 + parágrafo) como qualquer outra matéria.
 *  - Técnica / Guias: usa os campos educativos opcionais (gancho,
 *    curiosidades, perguntas/mitos, frases) — se PELO MENOS UM estiver
 *    preenchido, evita o carrossel "resumo de subtítulos". Sem nenhum
 *    preenchido, cai no caminho comum.
 *  - Comum (Notícias, Bastidores, ou Técnica/Guias sem campos
 *    educativos): um slide por subtítulo (H2) já existente no corpo,
 *    com o primeiro parágrafo daquela seção (recortado, nunca reescrito).
 *
 * Limite de 6 slides no carrossel (capa + até 4 de conteúdo + CTA), como
 * aprovado — isso orienta a geração nova; não se aplica retroativamente
 * a artes já produzidas e publicadas antes desta regra.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_EDITORIAL_MAX_ITENS_CARROSSEL', 4 );
define( 'DCO_EDITORIAL_MAX_ITENS_STORIES', 3 );

function dco_editorial_categoria_slug( $post_id ) {
	$categorias = get_the_category( $post_id );
	if ( empty( $categorias ) ) {
		return '';
	}
	return $categorias[0]->slug;
}

function dco_editorial_categoria_label( $post_id ) {
	$categorias = get_the_category( $post_id );
	return ! empty( $categorias ) ? $categorias[0]->name : '';
}

/**
 * Caminho local (no servidor) do arquivo de uma URL de anexo do próprio
 * site. Devolve a própria URL se não conseguir resolver (ex.: imagem
 * externa) — o renderizador tenta abrir normalmente (GD aceita URL se
 * allow_url_fopen estiver ativo, comum na Hostinger).
 */
function dco_editorial_resolver_caminho_imagem( $url ) {
	if ( ! $url ) {
		return null;
	}
	$id = attachment_url_to_postid( $url );
	if ( $id ) {
		$caminho = get_attached_file( $id );
		if ( $caminho && file_exists( $caminho ) ) {
			return $caminho;
		}
	}
	return $url;
}

/** Todas as imagens do corpo da matéria, na ordem em que aparecem (DOMDocument, não regex). */
function dco_editorial_imagens_do_corpo( $post_id ) {
	$conteudo = get_post_field( 'post_content', $post_id );
	$conteudo = apply_filters( 'the_content', $conteudo );
	if ( ! $conteudo ) {
		return array();
	}
	$dom = new DOMDocument();
	libxml_use_internal_errors( true );
	$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $conteudo );
	libxml_clear_errors();
	$urls = array();
	foreach ( $dom->getElementsByTagName( 'img' ) as $img ) {
		$src = $img->getAttribute( 'src' );
		if ( $src ) {
			$urls[] = $src;
		}
	}
	return array_values( array_unique( $urls ) );
}

/** Pool de imagens reais da matéria: destacada primeiro, depois as do corpo — nunca banco de imagens genérico. */
function dco_editorial_pool_imagens( $post_id ) {
	$pool = array();
	$destacada = get_the_post_thumbnail_url( $post_id, 'large' );
	if ( $destacada ) {
		$pool[] = dco_editorial_resolver_caminho_imagem( $destacada );
	}
	foreach ( dco_editorial_imagens_do_corpo( $post_id ) as $url ) {
		$pool[] = dco_editorial_resolver_caminho_imagem( $url );
	}
	$pool = array_values( array_filter( array_unique( $pool ) ) );
	return $pool;
}

function dco_editorial_imagem_para_indice( array $pool, $indice ) {
	if ( empty( $pool ) ) {
		return null;
	}
	return $pool[ $indice % count( $pool ) ];
}

/**
 * Caminho comum: um item por subtítulo (H2) do corpo + o primeiro
 * parágrafo daquela seção, recortado (nunca reescrito/resumido). Sem
 * H2 nenhum, usa o resumo (excerpt) como único item.
 */
function dco_editorial_itens_comuns( $post_id, $limite ) {
	$conteudo = get_post_field( 'post_content', $post_id );
	$conteudo = apply_filters( 'the_content', $conteudo );
	$itens    = array();

	if ( $conteudo ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $conteudo );
		libxml_clear_errors();

		foreach ( $dom->getElementsByTagName( 'h2' ) as $h2 ) {
			if ( count( $itens ) >= $limite ) {
				break;
			}
			$titulo = trim( wp_strip_all_tags( $dom->saveHTML( $h2 ) ) );
			if ( '' === $titulo ) {
				continue;
			}
			$texto = '';
			$no    = $h2->nextSibling;
			while ( $no ) {
				if ( XML_ELEMENT_NODE === $no->nodeType && 'p' === $no->nodeName ) {
					$texto = trim( wp_strip_all_tags( $dom->saveHTML( $no ) ) );
					if ( '' !== $texto ) {
						break;
					}
				}
				if ( XML_ELEMENT_NODE === $no->nodeType && 'h2' === $no->nodeName ) {
					break; // chegou na próxima seção sem achar parágrafo — para aqui.
				}
				$no = $no->nextSibling;
			}
			$itens[] = array(
				'titulo' => dco_editorial_recortar( $titulo, 90 ),
				'texto'  => dco_editorial_recortar( $texto, 280 ),
			);
		}
	}

	if ( empty( $itens ) ) {
		$resumo = get_the_excerpt( $post_id );
		if ( $resumo ) {
			$itens[] = array( 'titulo' => get_the_title( $post_id ), 'texto' => dco_editorial_recortar( $resumo, 280 ) );
		}
	}

	return $itens;
}

/** Recorta um texto em até $max caracteres, terminando numa fronteira de frase/palavra — nunca no meio de uma palavra. */
function dco_editorial_recortar( $texto, $max ) {
	$texto = trim( (string) $texto );
	if ( mb_strlen( $texto ) <= $max ) {
		return $texto;
	}
	$corte = mb_substr( $texto, 0, $max );
	$fim_frase = max( mb_strrpos( $corte, '.' ), mb_strrpos( $corte, '!' ), mb_strrpos( $corte, '?' ) );
	if ( false !== $fim_frase && $fim_frase > $max * 0.4 ) {
		return mb_substr( $corte, 0, $fim_frase + 1 );
	}
	$ultimo_espaco = mb_strrpos( $corte, ' ' );
	return rtrim( false !== $ultimo_espaco ? mb_substr( $corte, 0, $ultimo_espaco ) : $corte ) . '…';
}

function dco_editorial_meta_linhas( $post_id, $key ) {
	$valores = get_post_meta( $post_id, $key, true );
	return is_array( $valores ) ? array_values( array_filter( $valores ) ) : array();
}

/** Itens do carrossel educativo de Técnica/Guias, a partir dos campos opcionais. */
function dco_editorial_itens_educativos( $post_id, $limite ) {
	$itens = array();
	foreach ( dco_editorial_meta_linhas( $post_id, '_dco_edu_perguntas_mitos' ) as $linha ) {
		$itens[] = array( 'titulo' => dco_editorial_recortar( $linha, 110 ), 'texto' => '' );
	}
	foreach ( dco_editorial_meta_linhas( $post_id, '_dco_edu_curiosidades' ) as $linha ) {
		$itens[] = array( 'titulo' => dco_editorial_recortar( $linha, 110 ), 'texto' => '' );
	}
	foreach ( dco_editorial_meta_linhas( $post_id, '_dco_edu_frases' ) as $linha ) {
		$itens[] = array( 'titulo' => dco_editorial_recortar( $linha, 110 ), 'texto' => '' );
	}
	return array_slice( $itens, 0, $limite );
}

/** Itens de Reviews, a partir do campo "Destaques do veículo". */
function dco_editorial_itens_review( $post_id, $limite ) {
	$itens = array();
	foreach ( dco_editorial_meta_linhas( $post_id, '_dco_destaques_veiculo' ) as $linha ) {
		$itens[] = array( 'titulo' => dco_editorial_recortar( $linha, 110 ), 'texto' => '' );
	}
	return array_slice( $itens, 0, $limite );
}

/**
 * Monta o pacote de conteúdo completo de uma matéria: tipo de conteúdo
 * usado, título/subtítulo da capa, itens de conteúdo e o pool de imagens
 * reais — pronto para o renderizador (inc/editorial-render.php) e para
 * dco_editorial_montar_legenda().
 */
function dco_editorial_montar_pacote( $post_id ) {
	$categoria_slug  = dco_editorial_categoria_slug( $post_id );
	$categoria_label = dco_editorial_categoria_label( $post_id );
	$e_review        = 'reviews' === $categoria_slug;
	$e_educativa     = in_array( $categoria_slug, array( 'tecnica', 'guias' ), true );

	$tipo_conteudo = 'comum';
	$itens         = array();

	if ( $e_review ) {
		$itens = dco_editorial_itens_review( $post_id, DCO_EDITORIAL_MAX_ITENS_CARROSSEL );
		if ( ! empty( $itens ) ) {
			$tipo_conteudo = 'review';
		}
	} elseif ( $e_educativa ) {
		$itens = dco_editorial_itens_educativos( $post_id, DCO_EDITORIAL_MAX_ITENS_CARROSSEL );
		if ( ! empty( $itens ) ) {
			$tipo_conteudo = 'educativo';
		}
	}

	if ( empty( $itens ) ) {
		$itens         = dco_editorial_itens_comuns( $post_id, DCO_EDITORIAL_MAX_ITENS_CARROSSEL );
		$tipo_conteudo = 'comum';
	}

	$gancho = get_post_meta( $post_id, '_dco_edu_gancho', true );

	return array(
		'post_id'         => $post_id,
		'tipo_conteudo'   => $tipo_conteudo,
		'categoria_slug'  => $categoria_slug,
		'categoria_label' => $categoria_label,
		'titulo'          => get_the_title( $post_id ),
		'subtitulo'       => $gancho ? $gancho : get_the_excerpt( $post_id ),
		'link'            => get_permalink( $post_id ),
		'pool_imagens'    => dco_editorial_pool_imagens( $post_id ),
		'itens'           => $itens,
		'tags'            => wp_list_pluck( get_the_tags( $post_id ) ?: array(), 'name' ),
		// Estrutura fixa "Entenda. Questione. Decida." do MODELO 2 (Técnica/
		// Guias) — ver inc/editorial-render-modelo2.php. Campos aditivos:
		// não mudam nada do que o Modelo 1 já lê deste pacote.
		'm2_chamada'      => get_post_meta( $post_id, '_dco_m2_chamada', true ),
		'm2_entenda'      => get_post_meta( $post_id, '_dco_m2_entenda', true ),
		'm2_questione'    => get_post_meta( $post_id, '_dco_m2_questione', true ),
		'm2_decida'       => get_post_meta( $post_id, '_dco_m2_decida', true ),
		'm2_usar_foto'    => (bool) get_post_meta( $post_id, '_dco_m2_usar_foto', true ),
	);
}
