<?php
/**
 * DESCOMPLICARRO Oportunidades — lista de estados e municípios do Brasil.
 *
 * Carregada de um arquivo estático (assets/data/municipios-ibge.json),
 * baixado uma única vez de uma base aberta que espelha os dados oficiais do
 * IBGE — nunca consultada ao vivo a cada visita do site: mantém o site
 * rápido e funcionando mesmo se a fonte original sair do ar. Repetir esse
 * download só será necessário se o IBGE criar/desmembrar algum município no
 * futuro (acontece raramente).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_uf_list() {
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

/** Carrega e mantém em memória (por request) o JSON { "UF": ["Cidade", ...], ... }. */
function dco_municipios_todos() {
	static $dados = null;
	if ( $dados === null ) {
		$arquivo = DCO_PLUGIN_DIR . 'assets/data/municipios-ibge.json';
		$conteudo = file_exists( $arquivo ) ? file_get_contents( $arquivo ) : '';
		$decodificado = $conteudo ? json_decode( $conteudo, true ) : null;
		$dados = is_array( $decodificado ) ? $decodificado : array();
	}
	return $dados;
}

function dco_municipios_por_uf( $uf ) {
	$todos = dco_municipios_todos();
	$uf    = strtoupper( $uf );
	return isset( $todos[ $uf ] ) ? $todos[ $uf ] : array();
}

/** Confere se "$cidade" é um nome real de município do estado $uf (evita cadastro com cidade inventada/digitada errado). */
function dco_cidade_valida( $uf, $cidade ) {
	$lista = dco_municipios_por_uf( $uf );
	return in_array( $cidade, $lista, true );
}

/** Serve o JSON de municípios para o seletor em cascata (Estado → Cidade) nos formulários públicos. */
add_action( 'wp_ajax_dco_municipios', 'dco_ajax_municipios' );
add_action( 'wp_ajax_nopriv_dco_municipios', 'dco_ajax_municipios' );
function dco_ajax_municipios() {
	$uf = isset( $_GET['uf'] ) ? sanitize_text_field( wp_unslash( $_GET['uf'] ) ) : '';
	if ( ! array_key_exists( $uf, dco_uf_list() ) ) {
		wp_send_json_error( array( 'message' => 'Estado inválido.' ), 400 );
	}
	wp_send_json_success( dco_municipios_por_uf( $uf ) );
}
