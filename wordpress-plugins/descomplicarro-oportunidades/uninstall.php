<?php
/**
 * Executado pelo WordPress só quando alguém clica em "Excluir" o plugin pelo
 * wp-admin (nunca em "Desativar" — isso nem chega a rodar este arquivo).
 *
 * DELIBERADAMENTE NÃO APAGA NADA: currículos, vagas, PDFs e configurações
 * continuam no banco/disco mesmo que o plugin seja removido. Excluir dados
 * de verdade é uma decisão que só o cliente deve tomar manualmente — nunca
 * algo que um clique acidental em "Excluir plugin" dispara sozinho.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Intencionalmente vazio.
