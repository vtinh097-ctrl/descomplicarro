<?php
/**
 * DESCOMPLICARRO — integração com o Google Drive (Etapa 4), via conta de
 * serviço (service account). SEM biblioteca/SDK do Google (não dá para
 * contar com Composer em hospedagem compartilhada) — fala direto com a
 * API REST do Drive v3 usando wp_remote_*, e assina o JWT de autenticação
 * só com a extensão OpenSSL do PHP (padrão em qualquer hospedagem PHP,
 * inclusive Hostinger).
 *
 * Credenciais: o JSON da conta de serviço e o ID da pasta raiz ficam
 * guardados como opção do WordPress (wp_options), preenchidos pelo
 * administrador no painel da Automação Editorial — nunca pedidos ou
 * exibidos em texto puro na conversa com o Claude, e nunca commitados no
 * repositório.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_EDITORIAL_OPCAO_DRIVE_CREDENCIAIS', 'dco_editorial_drive_credenciais' );
define( 'DCO_EDITORIAL_OPCAO_DRIVE_PASTA_RAIZ', 'dco_editorial_drive_pasta_raiz' );

function dco_editorial_drive_configurado() {
	return (bool) get_option( DCO_EDITORIAL_OPCAO_DRIVE_CREDENCIAIS ) && (bool) get_option( DCO_EDITORIAL_OPCAO_DRIVE_PASTA_RAIZ );
}

/** Extrai o ID de uma URL de pasta do Drive, ou devolve o próprio valor se já for só o ID. */
function dco_editorial_drive_extrair_id_pasta( $valor ) {
	$valor = trim( (string) $valor );
	if ( preg_match( '#/folders/([a-zA-Z0-9_-]+)#', $valor, $m ) ) {
		return $m[1];
	}
	return $valor;
}

function dco_editorial_drive_base64url( $dados ) {
	return rtrim( strtr( base64_encode( $dados ), '+/', '-_' ), '=' );
}

/**
 * Token de acesso OAuth2 via JWT assinado com a chave privada da conta de
 * serviço (fluxo "server-to-server" do Google, sem navegador/usuário).
 * Cacheado em transient por 50 minutos (o token do Google dura 60).
 */
function dco_editorial_drive_access_token() {
	$cache = get_transient( 'dco_editorial_drive_token' );
	if ( $cache ) {
		return $cache;
	}

	$credenciais_json = get_option( DCO_EDITORIAL_OPCAO_DRIVE_CREDENCIAIS );
	$credenciais      = json_decode( (string) $credenciais_json, true );
	if ( empty( $credenciais['client_email'] ) || empty( $credenciais['private_key'] ) ) {
		return new WP_Error( 'dco_drive_sem_credenciais', 'Credenciais da conta de serviço do Google Drive não configuradas.' );
	}

	$agora   = time();
	$cabecalho = dco_editorial_drive_base64url( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
	$corpo     = dco_editorial_drive_base64url( wp_json_encode( array(
		'iss'   => $credenciais['client_email'],
		'scope' => 'https://www.googleapis.com/auth/drive',
		'aud'   => 'https://oauth2.googleapis.com/token',
		'iat'   => $agora,
		'exp'   => $agora + 3600,
	) ) );

	$assinatura = '';
	$ok = openssl_sign( "$cabecalho.$corpo", $assinatura, $credenciais['private_key'], 'sha256WithRSAEncryption' );
	if ( ! $ok ) {
		return new WP_Error( 'dco_drive_assinatura', 'Falha ao assinar o JWT com a chave privada da conta de serviço.' );
	}
	$jwt = "$cabecalho.$corpo." . dco_editorial_drive_base64url( $assinatura );

	$resposta = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 20,
		'body'    => array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $jwt,
		),
	) );
	if ( is_wp_error( $resposta ) ) {
		return $resposta;
	}
	$corpo_resp = json_decode( wp_remote_retrieve_body( $resposta ), true );
	if ( empty( $corpo_resp['access_token'] ) ) {
		return new WP_Error( 'dco_drive_token', 'Google não devolveu um token de acesso: ' . wp_remote_retrieve_body( $resposta ) );
	}

	set_transient( 'dco_editorial_drive_token', $corpo_resp['access_token'], 50 * MINUTE_IN_SECONDS );
	return $corpo_resp['access_token'];
}

function dco_editorial_drive_requisicao( $metodo, $url, $args = array() ) {
	$token = dco_editorial_drive_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$args['method']  = $metodo;
	$args['timeout'] = $args['timeout'] ?? 30;
	$args['headers'] = array_merge( array( 'Authorization' => 'Bearer ' . $token ), $args['headers'] ?? array() );
	$resposta = wp_remote_request( $url, $args );
	if ( is_wp_error( $resposta ) ) {
		return $resposta;
	}
	$codigo = wp_remote_retrieve_response_code( $resposta );
	if ( $codigo >= 300 ) {
		return new WP_Error( 'dco_drive_http_' . $codigo, 'Google Drive respondeu ' . $codigo . ': ' . wp_remote_retrieve_body( $resposta ) );
	}
	return json_decode( wp_remote_retrieve_body( $resposta ), true );
}

function dco_editorial_drive_criar_pasta( $nome, $pasta_pai_id ) {
	$resultado = dco_editorial_drive_requisicao( 'POST', 'https://www.googleapis.com/drive/v3/files?supportsAllDrives=true', array(
		'headers' => array( 'Content-Type' => 'application/json' ),
		'body'    => wp_json_encode( array(
			'name'     => $nome,
			'mimeType' => 'application/vnd.google-apps.folder',
			'parents'  => array( $pasta_pai_id ),
		) ),
	) );
	if ( is_wp_error( $resultado ) ) {
		return $resultado;
	}
	return $resultado['id'] ?? new WP_Error( 'dco_drive_pasta', 'Resposta inesperada ao criar pasta no Drive.' );
}

/** Upload simples (multipart) de um arquivo local para uma pasta do Drive. */
function dco_editorial_drive_upload_arquivo( $caminho_local, $nome, $pasta_id ) {
	if ( ! file_exists( $caminho_local ) ) {
		return new WP_Error( 'dco_drive_arquivo', "Arquivo não encontrado: $caminho_local" );
	}
	$token = dco_editorial_drive_access_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$tipo      = wp_check_filetype( $nome );
	$mime      = $tipo['type'] ?: 'application/octet-stream';
	$metadados = wp_json_encode( array( 'name' => $nome, 'parents' => array( $pasta_id ) ) );
	$conteudo  = file_get_contents( $caminho_local );
	$fronteira = 'dco_' . wp_generate_password( 16, false );

	$corpo  = "--$fronteira\r\n";
	$corpo .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
	$corpo .= $metadados . "\r\n";
	$corpo .= "--$fronteira\r\n";
	$corpo .= "Content-Type: $mime\r\n\r\n";
	$corpo .= $conteudo . "\r\n";
	$corpo .= "--$fronteira--";

	$resposta = wp_remote_post( 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&supportsAllDrives=true', array(
		'timeout' => 60,
		'headers' => array(
			'Authorization' => 'Bearer ' . $token,
			'Content-Type'  => "multipart/related; boundary=$fronteira",
		),
		'body'    => $corpo,
	) );
	if ( is_wp_error( $resposta ) ) {
		return $resposta;
	}
	$codigo = wp_remote_retrieve_response_code( $resposta );
	if ( $codigo >= 300 ) {
		return new WP_Error( 'dco_drive_upload_' . $codigo, 'Falha no upload ao Drive: ' . wp_remote_retrieve_body( $resposta ) );
	}
	$dados = json_decode( wp_remote_retrieve_body( $resposta ), true );
	return $dados['id'] ?? new WP_Error( 'dco_drive_upload', 'Resposta inesperada do upload.' );
}

/**
 * Monta a estrutura de pastas de uma matéria no Drive (pasta da matéria >
 * STORIES / CARROSSEL / INFORMAÇÕES) e envia todos os arquivos gerados.
 * Devolve o link da pasta da matéria, ou um WP_Error com o motivo da falha
 * — nunca lança exceção (quem chama decide o que fazer com o erro).
 */
function dco_editorial_drive_enviar_pacote( $post_id, array $resultado_geracao, $legenda ) {
	if ( ! dco_editorial_drive_configurado() ) {
		return new WP_Error( 'dco_drive_nao_configurado', 'Integração com o Google Drive ainda não foi configurada.' );
	}
	$pasta_raiz = dco_editorial_drive_extrair_id_pasta( get_option( DCO_EDITORIAL_OPCAO_DRIVE_PASTA_RAIZ ) );

	$titulo_pasta = sprintf( '%s — %s', get_the_date( 'Y-m-d', $post_id ), wp_strip_all_tags( get_the_title( $post_id ) ) );
	$titulo_pasta = mb_substr( $titulo_pasta, 0, 120 );

	$pasta_materia = dco_editorial_drive_criar_pasta( $titulo_pasta, $pasta_raiz );
	if ( is_wp_error( $pasta_materia ) ) {
		return $pasta_materia;
	}

	$pasta_stories    = dco_editorial_drive_criar_pasta( 'STORIES', $pasta_materia );
	$pasta_carrossel  = dco_editorial_drive_criar_pasta( 'CARROSSEL', $pasta_materia );
	$pasta_info       = dco_editorial_drive_criar_pasta( 'INFORMAÇÕES', $pasta_materia );
	foreach ( array( $pasta_stories, $pasta_carrossel, $pasta_info ) as $p ) {
		if ( is_wp_error( $p ) ) {
			return $p;
		}
	}

	foreach ( $resultado_geracao['carrossel'] as $arquivo ) {
		$r = dco_editorial_drive_upload_arquivo( $arquivo, basename( $arquivo ), $pasta_carrossel );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}
	foreach ( $resultado_geracao['stories'] as $arquivo ) {
		$r = dco_editorial_drive_upload_arquivo( $arquivo, basename( $arquivo ), $pasta_stories );
		if ( is_wp_error( $r ) ) {
			return $r;
		}
	}

	$info_txt = $legenda . "\n\n---\nLink da matéria: " . get_permalink( $post_id );
	$caminho_info = trailingslashit( get_temp_dir() ) . 'dco-info-' . $post_id . '.txt';
	file_put_contents( $caminho_info, $info_txt );
	$r = dco_editorial_drive_upload_arquivo( $caminho_info, 'legenda-e-link.txt', $pasta_info );
	@unlink( $caminho_info );
	if ( is_wp_error( $r ) ) {
		return $r;
	}

	return 'https://drive.google.com/drive/folders/' . $pasta_materia;
}
