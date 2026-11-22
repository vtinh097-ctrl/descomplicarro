<?php
/**
 * DESCOMPLICARRO — gerador SOB DEMANDA de carrossel/Stories/legenda a
 * partir do link de uma matéria já publicada em descomplicarro.com.br.
 *
 * NÃO é código do site nem do plugin — é uma ferramenta de bastidor que
 * roda neste ambiente de desenvolvimento (precisa do WordPress local
 * carregado, porque reaproveita INTEGRALMENTE as mesmas funções já
 * aprovadas e testadas do plugin: inc/editorial-selecao.php,
 * inc/editorial-render.php, inc/editorial-gerador.php,
 * inc/editorial-legenda.php — nada de layout foi duplicado ou
 * reconstruído).
 *
 * O que faz:
 *   1. Busca o HTML da matéria pelo link.
 *   2. Lê título, subtítulo, editoria, tags e corpo (com as mesmas tags
 *      HTML — <h2>, <p>, <img> — que o WordPress real produz).
 *   3. Baixa as imagens (capa + do corpo) e as importa na biblioteca de
 *      mídia LOCAL, só para poder reaproveitar as funções já prontas.
 *   4. Cria uma matéria-espelho LOCAL com status 'draft' — nunca
 *      'publish' — para NUNCA disparar a automação de Drive/e-mail
 *      (que fica intacta e preservada para uma ativação futura).
 *   5. Gera os arquivos (mesmo pipeline de sempre) e a legenda.
 *   6. Apaga a matéria-espelho e os anexos locais (não precisa ficar
 *      acumulando no banco local).
 *
 * Uso: php gerar-por-link.php "<url-da-materia>" "<pasta-de-saida>"
 *      [--destaques="linha1|linha2|linha3"]   (Reviews, opcional)
 *      [--educativo="linha1|linha2|linha3"]   (Técnica/Guias, opcional)
 *      [--gancho="frase do ângulo editorial"] (Técnica/Guias, opcional)
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Só roda via linha de comando.\n" );
}

$WP_LOAD = getenv( 'DCO_WP_LOAD' ) ?: '/home/user/wp-local/wp-load.php';
require $WP_LOAD;
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$url = $argv[1] ?? null;
$pasta_saida = isset( $argv[2] ) ? rtrim( $argv[2], '/' ) : null;
if ( ! $url || ! $pasta_saida ) {
	fwrite( STDERR, "Uso: php gerar-por-link.php <url> <pasta-de-saida> [--destaques=...] [--educativo=...] [--gancho=...]\n" );
	exit( 1 );
}

$opcoes = array();
foreach ( array_slice( $argv, 3 ) as $arg ) {
	if ( preg_match( '/^--(\w+)=(.*)$/s', $arg, $m ) ) {
		$opcoes[ $m[1] ] = $m[2];
	}
}

function sob_demanda_log( $msg ) {
	fwrite( STDOUT, $msg . "\n" );
}

// ---------------------------------------------------------------------
// 1-2) Busca e parse do HTML real da matéria
// ---------------------------------------------------------------------
sob_demanda_log( "Buscando $url ..." );
$contexto = stream_context_create( array( 'http' => array( 'header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 20 ) ) );
$html = @file_get_contents( $url, false, $contexto );
if ( ! $html ) {
	fwrite( STDERR, "Não consegui buscar a página.\n" );
	exit( 1 );
}

$dom = new DOMDocument();
libxml_use_internal_errors( true );
$dom->loadHTML( $html );
libxml_clear_errors();
$xpath = new DOMXPath( $dom );

function sob_demanda_texto( $xpath, $consulta ) {
	$nos = $xpath->query( $consulta );
	return $nos->length ? trim( $nos->item( 0 )->textContent ) : '';
}

$titulo    = sob_demanda_texto( $xpath, '//h1[contains(@class,"materia-titulo")]' );
$subtitulo = sob_demanda_texto( $xpath, '//p[contains(@class,"materia-subtitulo")]' );
$editoria  = sob_demanda_texto( $xpath, '//span[contains(@class,"materia-editoria")]' );

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

$no_corpo = $xpath->query( '//div[contains(@class,"materia-corpo")]' );
$corpo_html = $no_corpo->length ? $dom->saveHTML( $no_corpo->item( 0 ) ) : '';

$imagens_corpo_urls = array();
if ( $no_corpo->length ) {
	foreach ( $no_corpo->item( 0 )->getElementsByTagName( 'img' ) as $img ) {
		$imagens_corpo_urls[] = $img->getAttribute( 'src' );
	}
}

$mapa_editoria_slug = array(
	'Reviews'    => 'reviews',
	'Guias'      => 'guias',
	'Técnica'    => 'tecnica',
	'Bastidores' => 'bastidores',
	'Notícias'   => 'noticias',
);
$categoria_slug = $mapa_editoria_slug[ $editoria ] ?? 'noticias';

sob_demanda_log( "Título: $titulo" );
sob_demanda_log( "Editoria: $editoria ($categoria_slug)" );
sob_demanda_log( 'Imagens no corpo: ' . count( $imagens_corpo_urls ) );
sob_demanda_log( 'Tags: ' . ( $tags ? implode( ', ', $tags ) : '(nenhuma)' ) );

// ---------------------------------------------------------------------
// 3) Baixa e importa as imagens na mídia local
// ---------------------------------------------------------------------
function sob_demanda_importar_imagem_de_url( $url_imagem, $post_pai ) {
	if ( ! $url_imagem ) {
		return 0;
	}
	$dados = @file_get_contents( $url_imagem, false, stream_context_create( array( 'http' => array( 'header' => "User-Agent: Mozilla/5.0\r\n", 'timeout' => 20 ) ) ) );
	if ( ! $dados ) {
		return 0;
	}
	$upload_dir = wp_upload_dir();
	$nome       = wp_unique_filename( $upload_dir['path'], basename( parse_url( $url_imagem, PHP_URL_PATH ) ) );
	$destino    = trailingslashit( $upload_dir['path'] ) . $nome;
	file_put_contents( $destino, $dados );

	$tipo  = wp_check_filetype( $nome, null );
	if ( ! $tipo['type'] ) {
		@unlink( $destino );
		return 0;
	}
	$anexo = array( 'post_mime_type' => $tipo['type'], 'post_title' => sanitize_file_name( $nome ), 'post_status' => 'inherit' );
	$anexo_id = wp_insert_attachment( $anexo, $destino, $post_pai );
	wp_update_attachment_metadata( $anexo_id, wp_generate_attachment_metadata( $anexo_id, $destino ) );
	return $anexo_id;
}

// ---------------------------------------------------------------------
// 4) Cria a matéria-espelho LOCAL (status 'draft' — nunca dispara a
//    automação de publicação)
// ---------------------------------------------------------------------
$categoria_id = get_cat_ID( ucfirst( $categoria_slug ) ) ?: get_category_by_slug( $categoria_slug )->term_id ?? 1;

$post_id = wp_insert_post( array(
	'post_title'    => $titulo,
	'post_excerpt'  => $subtitulo,
	'post_content'  => $corpo_html,
	'post_status'   => 'draft',
	'post_category' => array( $categoria_id ),
	'tags_input'    => $tags,
) );
sob_demanda_log( "Matéria-espelho local criada: #$post_id (rascunho, nunca publicada)" );

$anexo_capa = sob_demanda_importar_imagem_de_url( $capa_url, $post_id );
if ( $anexo_capa ) {
	set_post_thumbnail( $post_id, $anexo_capa );
}

// Importa as imagens do corpo e REESCREVE os <img src="..."> do conteúdo
// para apontar para as cópias locais recém-importadas — sem isso, o
// gerador tentaria abrir a imagem direto da URL remota original (o que
// falha silenciosamente aqui), em vez de usar o anexo já baixado.
foreach ( $imagens_corpo_urls as $u ) {
	$anexo_id = sob_demanda_importar_imagem_de_url( $u, $post_id );
	if ( $anexo_id ) {
		$url_local  = wp_get_attachment_url( $anexo_id );
		$corpo_html = str_replace( $u, $url_local, $corpo_html );
	}
}
wp_update_post( array( 'ID' => $post_id, 'post_content' => $corpo_html ) );

if ( ! empty( $opcoes['destaques'] ) ) {
	update_post_meta( $post_id, '_dco_destaques_veiculo', array_filter( array_map( 'trim', explode( '|', $opcoes['destaques'] ) ) ) );
}
if ( ! empty( $opcoes['educativo'] ) ) {
	update_post_meta( $post_id, '_dco_edu_perguntas_mitos', array_filter( array_map( 'trim', explode( '|', $opcoes['educativo'] ) ) ) );
}
if ( ! empty( $opcoes['gancho'] ) ) {
	update_post_meta( $post_id, '_dco_edu_gancho', $opcoes['gancho'] );
}

// ---------------------------------------------------------------------
// 5) Gera os arquivos e a legenda — MESMO pipeline já aprovado, sem
//    nenhuma alteração.
// ---------------------------------------------------------------------
$resultado = dco_editorial_gerar_arquivos( $post_id, $pasta_saida );
$legenda   = dco_editorial_montar_legenda( $resultado['pacote'] );
file_put_contents( trailingslashit( $pasta_saida ) . 'legenda.txt', $legenda . "\n\nLink da matéria: $url" );

sob_demanda_log( 'Tipo de conteúdo usado: ' . $resultado['pacote']['tipo_conteudo'] );
sob_demanda_log( 'Slides de carrossel: ' . count( $resultado['carrossel'] ) );
sob_demanda_log( 'Slides de Stories: ' . count( $resultado['stories'] ) );

// ---------------------------------------------------------------------
// 6) Limpeza — apaga a matéria-espelho e os anexos locais
// ---------------------------------------------------------------------
foreach ( get_attached_media( '', $post_id ) as $a ) {
	wp_delete_attachment( $a->ID, true );
}
wp_delete_post( $post_id, true );
sob_demanda_log( 'Matéria-espelho local removida. Arquivos finais em: ' . $pasta_saida );
