<?php
/**
 * DESCOMPLICARRO Oportunidades — upload do PDF de currículo, direto para a
 * pasta protegida (ver dco_pasta_protegida_path() no arquivo principal).
 *
 * NUNCA passa pela Biblioteca de Mídia do WordPress (wp.media) nem pela
 * pasta pública de uploads, nem por um instante: o arquivo sai direto da
 * pasta temporária do PHP (onde o próprio PHP grava um upload recebido,
 * antes de qualquer código nosso rodar) para a pasta protegida, num único
 * movimento. Usado tanto pelo formulário público quanto pela tela de
 * administração — um só caminho de código para os dois, para nunca existir
 * uma versão "menos protegida" em algum lugar.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recebe UM item de $_FILES (ex.: $_FILES['curriculo_pdf']), valida e, se
 * aprovado, move para a pasta protegida com nome aleatório. Retorna o NOME
 * do arquivo dentro da pasta protegida (string) em caso de sucesso, ou
 * WP_Error com uma mensagem adequada para mostrar à pessoa em caso de
 * rejeição. Nunca lança exceção nem grava nada fora da pasta protegida.
 */
function dco_processar_upload_pdf( $arquivo ) {
	if ( empty( $arquivo ) || ! is_array( $arquivo ) ) {
		return new WP_Error( 'dco_sem_arquivo', 'Selecione o arquivo em PDF do currículo.' );
	}

	if ( ! isset( $arquivo['error'] ) || $arquivo['error'] === UPLOAD_ERR_NO_FILE ) {
		return new WP_Error( 'dco_sem_arquivo', 'Selecione o arquivo em PDF do currículo.' );
	}

	if ( $arquivo['error'] !== UPLOAD_ERR_OK ) {
		return new WP_Error( 'dco_erro_envio', 'Houve um problema ao receber o arquivo. Tente novamente.' );
	}

	if ( ! is_uploaded_file( $arquivo['tmp_name'] ) ) {
		// Proteção padrão contra manipulação direta de $_FILES fora de um upload real.
		return new WP_Error( 'dco_upload_invalido', 'Envio de arquivo inválido.' );
	}

	$tamanho_maximo = 5 * MB_IN_BYTES;
	if ( (int) $arquivo['size'] > $tamanho_maximo || filesize( $arquivo['tmp_name'] ) > $tamanho_maximo ) {
		return new WP_Error( 'dco_arquivo_grande', 'O arquivo excede o limite de 5 MB.' );
	}

	// Confere o CONTEÚDO real do arquivo (sniff via fileinfo), não só o nome/extensão enviada pelo navegador.
	$checagem = wp_check_filetype_and_ext( $arquivo['tmp_name'], $arquivo['name'] );
	if ( empty( $checagem['ext'] ) || strtolower( $checagem['ext'] ) !== 'pdf' || $checagem['type'] !== 'application/pdf' ) {
		return new WP_Error( 'dco_tipo_invalido', 'O arquivo não é um PDF válido.' );
	}

	dco_garantir_pasta_protegida();
	$pasta = dco_pasta_protegida_path();
	$nome_final = 'curriculo-' . gmdate( 'Ymd' ) . '-' . wp_generate_password( 20, false, false ) . '.pdf';
	$destino = trailingslashit( $pasta ) . $nome_final;

	if ( ! move_uploaded_file( $arquivo['tmp_name'], $destino ) ) {
		return new WP_Error( 'dco_mover_falhou', 'Não foi possível salvar o arquivo. Tente novamente.' );
	}
	chmod( $destino, 0600 );

	return $nome_final;
}

/** Caminho absoluto completo de um arquivo já salvo na pasta protegida, a partir do nome gravado em postmeta. */
function dco_caminho_pdf_protegido( $nome_arquivo ) {
	if ( ! $nome_arquivo ) {
		return '';
	}
	// Procura nas duas pastas candidatas (o arquivo pode ter sido salvo quando uma delas era a ativa, e a outra ter passado a ser a ativa depois).
	foreach ( array( dco_pasta_protegida_fora_webroot_path(), dco_pasta_protegida_reserva_path() ) as $pasta ) {
		$caminho = trailingslashit( $pasta ) . $nome_arquivo;
		if ( file_exists( $caminho ) ) {
			return $caminho;
		}
	}
	return '';
}

/** Remove fisicamente o PDF protegido de um currículo (usado tanto na autoexclusão quanto na exclusão permanente pelo admin). */
function dco_excluir_pdf_protegido( $nome_arquivo ) {
	$caminho = dco_caminho_pdf_protegido( $nome_arquivo );
	if ( $caminho && file_exists( $caminho ) ) {
		@unlink( $caminho );
	}
}
