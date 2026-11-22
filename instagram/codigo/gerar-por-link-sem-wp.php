<?php
/**
 * DESCOMPLICARRO — gerador sob demanda SEM WORDPRESS.
 *
 * Mesma ideia de gerar-por-link.php, mas sem bootstrap de WordPress
 * nenhum. Reaproveita, sem alterar uma linha, os mesmos arquivos
 * aprovados do renderizador (inc/editorial-render.php,
 * inc/editorial-render-modelo2.php) e das funções de montagem de specs
 * e legenda (inc/editorial-gerador.php, inc/editorial-legenda.php,
 * inc/editorial-selecao.php) — só a "cola" muda: em vez de criar uma
 * matéria-espelho no WordPress local para poder chamar
 * dco_editorial_montar_pacote($post_id), este script monta o mesmo
 * array $pacote diretamente a partir do HTML raspado do link, sem
 * nenhum post, nem banco de dados, nem biblioteca de mídia.
 *
 * Por que isso é possível sem tocar nos arquivos aprovados: o
 * renderizador (editorial-render.php / editorial-render-modelo2.php) é
 * GD puro — não chama nenhuma função do WordPress. A única parte
 * realmente presa ao WordPress era a ORQUESTRAÇÃO
 * (dco_editorial_gerar_arquivos() + dco_editorial_montar_pacote()) — e
 * esta script substitui só essa cola, sem alterar o que já foi aprovado.
 *
 * Duas funções do WordPress (remove_accents, wp_strip_all_tags) são
 * usadas por inc/editorial-legenda.php — como não temos o WordPress
 * aqui, este script define versões equivalentes ANTES de incluir esse
 * arquivo (só entram em ação se essas funções ainda não existirem).
 *
 * Uso: php gerar-por-link-sem-wp.php "<url-da-materia>" "<pasta-de-saida>"
 *      [--destaques="linha1|linha2|linha3"]                 (Reviews — Modelo 1)
 *      [--chamada="..."] [--entenda="..."] [--questione="..."]
 *      [--decida="..."] [--usarfoto=1]                      (Técnica/Guias — Modelo 2)
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Só roda via linha de comando.\n" );
}

// ---------------------------------------------------------------------
// Polyfills — só para este script não depender do WordPress. Não
// substituem nada dentro dos arquivos aprovados, só preenchem duas
// funções que inc/editorial-legenda.php espera encontrar.
// ---------------------------------------------------------------------
if ( ! function_exists( 'remove_accents' ) ) {
	function remove_accents( $texto ) {
		$convertido = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $texto );
		return false !== $convertido ? $convertido : $texto;
	}
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $texto ) {
		$texto = html_entity_decode( (string) $texto, ENT_QUOTES, 'UTF-8' );
		return trim( preg_replace( '/\s+/u', ' ', strip_tags( $texto ) ) );
	}
}

// Os arquivos em inc/ são arquivos de plugin WordPress de verdade — cada
// um começa com "if (!defined('ABSPATH')) exit;" (convenção padrão do
// WordPress pra impedir acesso direto via HTTP). Sem WordPress nenhum
// rodando, isso mataria o script na primeira linha — então definimos a
// constante aqui, só pra passar por essa guarda. Não é o WordPress real,
// é só uma constante com o mesmo nome que a guarda verifica.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$DIR_INC = __DIR__ . '/../../wordpress-plugins/descomplicarro-oportunidades/inc/';
if ( ! is_dir( $DIR_INC ) ) {
	// Caminho alternativo: quando este script roda a partir do pacote de
	// transferência (codigo/gerar-por-link-sem-wp.php, com os mesmos
	// arquivos em codigo/inc/).
	$DIR_INC = __DIR__ . '/inc/';
}
define( 'DCO_PLUGIN_DIR', dirname( $DIR_INC ) . '/' );

require_once $DIR_INC . 'editorial-selecao.php';
require_once $DIR_INC . 'editorial-render.php';
require_once $DIR_INC . 'editorial-render-modelo2.php';
require_once $DIR_INC . 'editorial-gerador.php';
require_once $DIR_INC . 'editorial-legenda.php';

$url = $argv[1] ?? null;
$pasta_saida = isset( $argv[2] ) ? rtrim( $argv[2], '/' ) : null;
if ( ! $url || ! $pasta_saida ) {
	fwrite( STDERR, "Uso: php gerar-por-link-sem-wp.php <url> <pasta-de-saida> [--destaques=...] [--chamada=...] [--entenda=...] [--questione=...] [--decida=...] [--usarfoto=1]\n" );
	exit( 1 );
}

$opcoes = array();
foreach ( array_slice( $argv, 3 ) as $arg ) {
	if ( preg_match( '/^--(\w+)=(.*)$/s', $arg, $m ) ) {
		$opcoes[ $m[1] ] = $m[2];
	}
}

function sw_log( $msg ) {
	fwrite( STDOUT, $msg . "\n" );
}

function sw_baixar( $url_recurso ) {
	$ctx = stream_context_create( array( 'http' => array( 'header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 20 ) ) );
	return @file_get_contents( $url_recurso, false, $ctx );
}

// ---------------------------------------------------------------------
// 1) Busca e parse do HTML real da matéria — mesmos seletores de
//    gerar-por-link.php, porque é a mesma estrutura HTML que o site
//    publica.
// ---------------------------------------------------------------------
sw_log( "Buscando $url ..." );
$html = sw_baixar( $url );
if ( ! $html ) {
	fwrite( STDERR, "Não consegui buscar a página.\n" );
	exit( 1 );
}

$dom = new DOMDocument();
libxml_use_internal_errors( true );
$dom->loadHTML( $html );
libxml_clear_errors();
$xpath = new DOMXPath( $dom );

function sw_texto( $xpath, $consulta ) {
	$nos = $xpath->query( $consulta );
	return $nos->length ? trim( $nos->item( 0 )->textContent ) : '';
}

$titulo    = sw_texto( $xpath, '//h1[contains(@class,"materia-titulo")]' );
$subtitulo = sw_texto( $xpath, '//p[contains(@class,"materia-subtitulo")]' );
$editoria  = sw_texto( $xpath, '//span[contains(@class,"materia-editoria")]' );

if ( '' === $titulo ) {
	fwrite( STDERR, "Não encontrei o título da matéria — o link é de uma matéria publicada no site?\n" );
	exit( 1 );
}

$tags = array();
foreach ( $xpath->query( '//a[contains(@class,"tag-chip")]' ) as $no ) {
	$tags[] = trim( $no->textContent );
}

$capa_url = '';
$no_capa  = $xpath->query( '//figure[contains(@class,"materia-imagem-principal")]//img' );
if ( $no_capa->length ) {
	$capa_url = $no_capa->item( 0 )->getAttribute( 'src' );
}

$no_corpo   = $xpath->query( '//div[contains(@class,"materia-corpo")]' );
$corpo_html = $no_corpo->length ? $dom->saveHTML( $no_corpo->item( 0 ) ) : '';

$mapa_editoria_slug = array(
	'Reviews'    => 'reviews',
	'Guias'      => 'guias',
	'Técnica'    => 'tecnica',
	'Bastidores' => 'bastidores',
	'Notícias'   => 'noticias',
);
$categoria_slug  = $mapa_editoria_slug[ $editoria ] ?? 'noticias';
$categoria_label = $editoria ?: 'Notícias';

sw_log( "Título: $titulo" );
sw_log( "Editoria: $editoria ($categoria_slug)" );

// ---------------------------------------------------------------------
// 2) Baixa as imagens (capa + do corpo) direto para arquivos locais
//    temporários — SEM biblioteca de mídia do WordPress, porque não há
//    WordPress. dco_editorial_compor_foto_cover() (no renderizador já
//    aprovado) só precisa de um caminho de arquivo local, então isso
//    basta.
// ---------------------------------------------------------------------
$pasta_tmp_imagens = sys_get_temp_dir() . '/dco-sem-wp-' . uniqid();
mkdir( $pasta_tmp_imagens, 0777, true );

function sw_baixar_imagem( $url_imagem, $pasta_tmp ) {
	if ( ! $url_imagem ) {
		return null;
	}
	$dados = sw_baixar( $url_imagem );
	if ( ! $dados ) {
		return null;
	}
	$nome    = preg_replace( '/[^a-zA-Z0-9._-]/', '_', basename( parse_url( $url_imagem, PHP_URL_PATH ) ) );
	$destino = rtrim( $pasta_tmp, '/' ) . '/' . uniqid() . '-' . $nome;
	file_put_contents( $destino, $dados );
	return $destino;
}

$pool_imagens = array();
$capa_local   = sw_baixar_imagem( $capa_url, $pasta_tmp_imagens );
if ( $capa_local ) {
	$pool_imagens[] = $capa_local;
}

$imagens_corpo_urls = array();
if ( $no_corpo->length ) {
	foreach ( $no_corpo->item( 0 )->getElementsByTagName( 'img' ) as $img ) {
		$imagens_corpo_urls[] = $img->getAttribute( 'src' );
	}
}
foreach ( $imagens_corpo_urls as $u ) {
	$local = sw_baixar_imagem( $u, $pasta_tmp_imagens );
	if ( $local ) {
		$pool_imagens[] = $local;
	}
}
sw_log( 'Imagens baixadas: ' . count( $pool_imagens ) );

// ---------------------------------------------------------------------
// 3) Monta os "itens" do caminho comum (um por H2 + primeiro parágrafo
//    daquela seção) direto do HTML raspado — mesma lógica de
//    dco_editorial_itens_comuns() (inc/editorial-selecao.php), só que
//    lendo $corpo_html em vez de post_content de um post do WordPress
//    (o conteúdo é o mesmo: o HTML que o site já publicou).
// ---------------------------------------------------------------------
function sw_itens_comuns( $corpo_html, $limite ) {
	global $titulo, $subtitulo; // fallback "sem H2 → usa o resumo" precisa enxergar estes valores
	$itens = array();
	if ( $corpo_html ) {
		$dom_corpo = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom_corpo->loadHTML( '<?xml encoding="utf-8" ?>' . $corpo_html );
		libxml_clear_errors();

		foreach ( $dom_corpo->getElementsByTagName( 'h2' ) as $h2 ) {
			if ( count( $itens ) >= $limite ) {
				break;
			}
			$titulo_item = trim( wp_strip_all_tags( $dom_corpo->saveHTML( $h2 ) ) );
			if ( '' === $titulo_item ) {
				continue;
			}
			$texto_item = '';
			$no         = $h2->nextSibling;
			while ( $no ) {
				if ( XML_ELEMENT_NODE === $no->nodeType && 'p' === $no->nodeName ) {
					$texto_item = trim( wp_strip_all_tags( $dom_corpo->saveHTML( $no ) ) );
					if ( '' !== $texto_item ) {
						break;
					}
				}
				if ( XML_ELEMENT_NODE === $no->nodeType && 'h2' === $no->nodeName ) {
					break;
				}
				$no = $no->nextSibling;
			}
			$itens[] = array(
				'titulo' => dco_editorial_recortar( $titulo_item, 90 ),
				'texto'  => dco_editorial_recortar( $texto_item, 280 ),
			);
		}
	}
	if ( empty( $itens ) && $subtitulo ) {
		$itens[] = array( 'titulo' => $titulo, 'texto' => dco_editorial_recortar( $subtitulo, 280 ) );
	}
	return $itens;
}

$tipo_conteudo = 'comum';
$itens         = array();

if ( 'reviews' === $categoria_slug && ! empty( $opcoes['destaques'] ) ) {
	foreach ( array_filter( array_map( 'trim', explode( '|', $opcoes['destaques'] ) ) ) as $linha ) {
		$itens[] = array( 'titulo' => dco_editorial_recortar( $linha, 110 ), 'texto' => '' );
	}
	$itens         = array_slice( $itens, 0, DCO_EDITORIAL_MAX_ITENS_CARROSSEL );
	$tipo_conteudo = 'review';
}

if ( empty( $itens ) ) {
	$itens         = sw_itens_comuns( $corpo_html, DCO_EDITORIAL_MAX_ITENS_CARROSSEL );
	$tipo_conteudo = 'comum';
}

// ---------------------------------------------------------------------
// 4) Monta o MESMO formato de $pacote que dco_editorial_montar_pacote()
//    produz (inc/editorial-selecao.php) — as funções de specs/legenda
//    (não alteradas) não sabem a diferença entre um pacote vindo do
//    WordPress ou montado aqui direto do HTML raspado.
// ---------------------------------------------------------------------
$pacote = array(
	'post_id'         => 0,
	'tipo_conteudo'   => $tipo_conteudo,
	'categoria_slug'  => $categoria_slug,
	'categoria_label' => $categoria_label,
	'titulo'          => $titulo,
	'subtitulo'       => ! empty( $opcoes['chamada'] ) ? null : ( $subtitulo ?: '' ),
	'link'            => $url,
	'pool_imagens'    => $pool_imagens,
	'itens'           => $itens,
	'tags'            => $tags,
	'm2_chamada'      => $opcoes['chamada'] ?? '',
	'm2_entenda'      => $opcoes['entenda'] ?? '',
	'm2_questione'    => $opcoes['questione'] ?? '',
	'm2_decida'       => $opcoes['decida'] ?? '',
	'm2_usar_foto'    => ! empty( $opcoes['usarfoto'] ),
);
// subtitulo nunca deve ser null para as funções de desenho (fazem empty()).
if ( null === $pacote['subtitulo'] ) {
	$pacote['subtitulo'] = $subtitulo ?: '';
}

// ---------------------------------------------------------------------
// 5) Decide o modelo e gera os arquivos — mesmas funções de specs e de
//    renderização já aprovadas (inc/editorial-gerador.php,
//    inc/editorial-render.php, inc/editorial-render-modelo2.php),
//    chamadas diretamente em vez de passar por
//    dco_editorial_gerar_arquivos() (que espera um $post_id do
//    WordPress). mkdir()/rtrim() no lugar de wp_mkdir_p()/
//    trailingslashit() — mesmo efeito, sem WordPress.
// ---------------------------------------------------------------------
$modelo = dco_editorial_modelo_visual_da_categoria( $categoria_slug );

if ( 2 === $modelo ) {
	$specs_carrossel  = dco_editorial_specs_carrossel_modelo2( $pacote );
	$funcao_carrossel = 'dco_editorial_renderizar_slide_modelo2';
} else {
	$specs_carrossel  = dco_editorial_specs_carrossel( $pacote );
	$funcao_carrossel = 'dco_editorial_renderizar_slide';
}
$specs_stories  = dco_editorial_specs_stories( $pacote );
$funcao_stories = 'dco_editorial_renderizar_slide';

$pasta_carrossel = $pasta_saida . '/carrossel';
$pasta_stories   = $pasta_saida . '/stories';
mkdir( $pasta_carrossel, 0777, true );
mkdir( $pasta_stories, 0777, true );

$arquivos_carrossel = array();
foreach ( $specs_carrossel as $spec ) {
	$caminho = $pasta_carrossel . '/' . sprintf( '%02d-%s.png', $spec['numero'], $spec['tipo'] );
	if ( call_user_func( $funcao_carrossel, $spec, $caminho ) ) {
		$arquivos_carrossel[] = $caminho;
	}
}

$arquivos_stories = array();
foreach ( $specs_stories as $i => $spec ) {
	$caminho = $pasta_stories . '/' . sprintf( '%02d-%s.png', $i + 1, $spec['tipo'] );
	if ( call_user_func( $funcao_stories, $spec, $caminho ) ) {
		$arquivos_stories[] = $caminho;
	}
}

// ---------------------------------------------------------------------
// 6) Legenda — mesma função aprovada (inc/editorial-legenda.php).
// ---------------------------------------------------------------------
$legenda = dco_editorial_montar_legenda( $pacote );
file_put_contents( $pasta_saida . '/legenda.txt', $legenda . "\n\nLink da matéria: $url" );

sw_log( 'Tipo de conteúdo usado: ' . $tipo_conteudo );
sw_log( 'Slides de carrossel: ' . count( $arquivos_carrossel ) );
sw_log( 'Slides de Stories: ' . count( $arquivos_stories ) );

// ---------------------------------------------------------------------
// 7) Limpeza — apaga as imagens originais baixadas (temporárias); os
//    PNGs finais já foram gravados em $pasta_saida.
// ---------------------------------------------------------------------
foreach ( glob( $pasta_tmp_imagens . '/*' ) as $f ) {
	@unlink( $f );
}
@rmdir( $pasta_tmp_imagens );

sw_log( 'Arquivos finais em: ' . $pasta_saida );
