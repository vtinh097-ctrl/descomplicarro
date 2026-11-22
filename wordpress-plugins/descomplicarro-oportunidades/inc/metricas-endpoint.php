<?php
/**
 * DESCOMPLICARRO — endpoint que recebe os cliques do site (tema) e grava
 * na tabela de métricas. Chamado pelo assets/js/dc-tracking.js do TEMA via
 * admin-ajax.php — funciona mesmo esta chamada vindo de um tema diferente
 * no futuro, desde que ele poste os mesmos três campos.
 *
 * Não exige login (visitante comum do site) nem nonce de sessão
 * administrativa — é tráfego público por natureza. Como estas métricas
 * passam a embasar relatório para parceiros/anunciantes, a proteção contra
 * clique artificial/repetição indevida tem VÁRIAS camadas leves, nenhuma
 * com custo perceptível (a chamada é assíncrona, em segundo plano — nunca
 * atrasa a navegação de quem está no site) e nenhuma guardando dado
 * pessoal do visitante:
 *
 *   1) Debounce no navegador (ver dc-tracking.js) — ignora clique
 *      duplo/repique no mesmo botão dentro de 1,5s.
 *   2) Checagem leve de origem (dco_origem_suspeita()) — descarta chamadas
 *      cujo Origin/Referer aponte claramente para outro domínio.
 *   3) Limite FINO por item (dco_limite_item_excedido()) — no máximo 1
 *      clique do "mesmo" visitante (impressão digital efêmera, trocada
 *      todo dia, nunca o IP em si) no MESMO anúncio/matéria/etc. a cada 60
 *      segundos. É o que impede alguém de inflar o clique de UM anúncio
 *      específico clicando nele repetidamente.
 *   4) Limite GERAL por IP (dco_limite_por_ip_excedido(), já existia) —
 *      teto no total de envios de um mesmo IP em 10 minutos, independente
 *      do item, contra um script disparando eventos variados.
 *   5) Lista fechada de áreas/eventos aceitos — nunca grava uma string
 *      arbitrária vinda do navegador.
 *
 * Nenhuma camada é absoluta (não existe proteção 100% contra fraude sem
 * fricção para o visitante, tipo CAPTCHA, que foi deliberadamente evitado
 * aqui) — juntas, tornam inflar números de forma simples/automatizada
 * bem mais difícil, sem pedir nada do visitante real.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_dco_metricas_registrar_clique', 'dco_metricas_endpoint_registrar' );
add_action( 'wp_ajax_nopriv_dco_metricas_registrar_clique', 'dco_metricas_endpoint_registrar' );
function dco_metricas_endpoint_registrar() {
	if ( dco_origem_suspeita() ) {
		wp_send_json_error( array( 'message' => 'origem não reconhecida' ), 403 );
	}

	// Limite geral (não é um formulário sensível, é só contagem) — só
	// para impedir um script malicioso de inflar a tabela indefinidamente.
	if ( dco_limite_por_ip_excedido( 'metricas_clique', 300, MINUTE_IN_SECONDS * 10 ) ) {
		wp_send_json_error( array( 'message' => 'limite excedido' ), 429 );
	}

	$area          = isset( $_POST['area'] ) ? sanitize_key( wp_unslash( $_POST['area'] ) ) : '';
	$evento        = isset( $_POST['evento'] ) ? sanitize_key( wp_unslash( $_POST['evento'] ) ) : '';
	$identificador = isset( $_POST['identificador'] ) ? sanitize_text_field( wp_unslash( $_POST['identificador'] ) ) : '';

	if ( ! $evento ) {
		wp_send_json_error( array( 'message' => 'evento ausente' ), 400 );
	}

	// Limite fino por item — ver explicação no cabeçalho do arquivo.
	if ( dco_limite_item_excedido( 'metricas', $area . '|' . $evento . '|' . $identificador, 1, 60 ) ) {
		wp_send_json_error( array( 'message' => 'repetição descartada' ), 429 );
	}

	$gravado = dco_metricas_registrar( $area, $evento, $identificador );
	if ( $gravado === false ) {
		// "evento" fora da lista permitida — resposta honesta, não um
		// falso "sucesso" por cima de um registro que não aconteceu.
		wp_send_json_error( array( 'message' => 'evento não reconhecido' ), 400 );
	}

	// 204-like: resposta mínima, o navegador nem precisa ler o corpo.
	wp_send_json_success();
}
