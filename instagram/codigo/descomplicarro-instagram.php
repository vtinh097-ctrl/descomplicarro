<?php
/**
 * Plugin Name: DESCOMPLICARRO — Automação Editorial para Instagram
 * Description: Carregador mínimo, criado só para este projeto de transferência.
 *              Não é uma recriação dos modelos — é apenas o "fio condutor" que
 *              carrega, sem nenhuma alteração, os mesmos arquivos aprovados e
 *              validados no projeto original (wordpress-plugins/
 *              descomplicarro-oportunidades/inc/editorial-*.php). O plugin
 *              original tem outras features (currículos, vagas, métricas)
 *              que não têm nada a ver com Instagram — este loader existe pra
 *              não precisar trazer tudo isso pra cá.
 * Version: 1.0.0
 *
 * DCO_PLUGIN_DIR é a constante que editorial-render.php usa para localizar
 * as fontes (assets/fontes/*.ttf) — no plugin original ela é definida no
 * bootstrap principal; aqui é definida do mesmo jeito, só que por este
 * arquivo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Ordem importa: selecao.php define as constantes DCO_EDITORIAL_MAX_ITENS_*
// usadas por gerador.php; render.php e render-modelo2.php precisam existir
// antes de gerador.php (que referencia as funções de ambos).
require_once DCO_PLUGIN_DIR . 'inc/editorial-selecao.php';
require_once DCO_PLUGIN_DIR . 'inc/editorial-render.php';
require_once DCO_PLUGIN_DIR . 'inc/editorial-render-modelo2.php';
require_once DCO_PLUGIN_DIR . 'inc/editorial-gerador.php';
require_once DCO_PLUGIN_DIR . 'inc/editorial-legenda.php';

// Opcionais — só servem para preencher os campos de uma matéria pelo
// wp-admin em vez das flags de linha de comando do gerar-por-link.php.
// Não são usados pelo fluxo principal (link → arquivos). Descomente se
// for usar o editor do WordPress diretamente.
// require_once DCO_PLUGIN_DIR . 'inc/admin-fields.php';
// require_once DCO_PLUGIN_DIR . 'inc/editorial-campos.php';
