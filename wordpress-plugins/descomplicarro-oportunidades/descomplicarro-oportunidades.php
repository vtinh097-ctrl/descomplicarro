<?php
/**
 * Plugin Name: DESCOMPLICARRO — Oportunidades Automotivas
 * Description: Banco de currículos e vagas do setor automotivo (autogestão por link mágico, sem senha), métricas simples de cliques e automação editorial — construído como plugin próprio para que os dados sobrevivam a trocas/atualizações do tema DESCOMPLICARRO. O tema cuida só da apresentação visual.
 * Version: 0.4.0
 * Author: DESCOMPLICARRO
 * Text Domain: descomplicarro-oportunidades
 *
 * IMPORTANTE — este plugin NUNCA apaga dados ao ser desativado ou mesmo
 * removido pelo wp-admin: currículos, vagas e qualquer outro conteúdo
 * cadastrado continuam no banco de dados. Isso é intencional (ver
 * uninstall.php) — a limpeza completa, se um dia for necessária, é uma
 * decisão que só o cliente toma, nunca automática.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DCO_VERSION', '0.4.0' );
define( 'DCO_PLUGIN_FILE', __FILE__ );
define( 'DCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DCO_PLUGIN_DIR . 'inc/admin-fields.php';
require_once DCO_PLUGIN_DIR . 'inc/admin-status-js.php';
require_once DCO_PLUGIN_DIR . 'inc/municipios.php';
require_once DCO_PLUGIN_DIR . 'inc/upload-handler.php';
require_once DCO_PLUGIN_DIR . 'inc/tokens.php';
require_once DCO_PLUGIN_DIR . 'inc/emails.php';
require_once DCO_PLUGIN_DIR . 'inc/moderacao.php';
require_once DCO_PLUGIN_DIR . 'inc/cpt-curriculo.php';
require_once DCO_PLUGIN_DIR . 'inc/cpt-vaga.php';
require_once DCO_PLUGIN_DIR . 'inc/public-forms.php';
require_once DCO_PLUGIN_DIR . 'inc/busca.php';
require_once DCO_PLUGIN_DIR . 'inc/admin-panel.php';
require_once DCO_PLUGIN_DIR . 'inc/limpeza.php';
require_once DCO_PLUGIN_DIR . 'inc/metricas-db.php';
require_once DCO_PLUGIN_DIR . 'inc/metricas-endpoint.php';
require_once DCO_PLUGIN_DIR . 'inc/metricas-painel.php';

register_activation_hook( __FILE__, 'dco_ativar_plugin' );
/**
 * Ativação: garante a estrutura de armazenamento protegido para os PDFs de
 * currículo (pasta fora do alcance de listagem/execução direta) e concede a
 * capacidade de gerenciar Oportunidades ao administrador. Nada aqui cria ou
 * apaga conteúdo — só prepara o terreno.
 */
function dco_ativar_plugin() {
	dco_garantir_pasta_protegida();
	dco_garantir_capacidades();
	dco_metricas_garantir_tabela();
	// CPTs recém-registrados só valem depois de um flush de rewrite rules.
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'dco_desativar_plugin' );
function dco_desativar_plugin() {
	flush_rewrite_rules();
}

/**
 * AUTOCORREÇÃO — mesma lição já aplicada no tema (ver dc_ensure_*_oculto_por_padrao
 * em inc/activation.php do tema): nunca confiar só numa marca "já rodei uma
 * vez". Roda em todo request (leitura barata) e só escreve quando algo
 * realmente falta — cobre o caso de alguém reenviar os arquivos do plugin
 * sem reativar, ou a pasta protegida ser perdida por qualquer outro motivo.
 */
add_action( 'init', 'dco_ensure_pasta_protegida', 5 );
function dco_ensure_pasta_protegida() {
	dco_garantir_pasta_protegida();
}

add_action( 'init', 'dco_ensure_capacidades', 5 );
function dco_ensure_capacidades() {
	dco_garantir_capacidades();
}

/**
 * ONDE os PDFs de currículo ficam guardados: duas camadas de proteção, não
 * uma só.
 *
 * CAMADA PRINCIPAL — uma pasta FORA da raiz pública do site (um nível acima
 * de ABSPATH, a pasta onde o WordPress está instalado). Em qualquer
 * hospedagem normal (inclusive a Hostinger), só o conteúdo DENTRO da raiz
 * pública (geralmente "public_html") é servido pela internet — uma pasta um
 * nível acima dela simplesmente não tem URL nenhuma que alcance, em
 * NENHUM servidor (Apache, nginx, LiteSpeed, o que for), com ou sem
 * ".htaccess". Por isso esta camada não depende de nenhuma configuração do
 * servidor para funcionar — ao contrário de só confiar num .htaccess.
 *
 * CAMADA DE RESERVA — caso a hospedagem restrinja a escrita fora da raiz
 * pública (algumas configurações de "open_basedir" fazem isso), uma pasta
 * dentro de wp-content/uploads, protegida por .htaccess ("Require all
 * denied" + a sintaxe equivalente para Apache 2.2, "Deny from all") e sem
 * listagem de diretório. Verificado com um servidor Apache de verdade neste
 * ambiente de desenvolvimento (não só lendo o conteúdo do arquivo): acesso
 * direto ao PDF, listagem do diretório e leitura do próprio .htaccess
 * retornaram 403 nos três casos.
 *
 * dco_pasta_protegida_path() testa a camada principal a cada uso (sem
 * confiar numa marca "já verifiquei uma vez" — mesma lição já registrada em
 * outras partes do projeto) e só recorre à de reserva se a principal não
 * puder ser criada/escrita.
 */
function dco_pasta_protegida_fora_webroot_path() {
	return trailingslashit( dirname( ABSPATH ) ) . 'dco-curriculos-privado';
}

function dco_pasta_protegida_reserva_path() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'dco-curriculos-privado';
}

function dco_pasta_utilizavel( $pasta ) {
	if ( ! file_exists( $pasta ) ) {
		if ( ! @wp_mkdir_p( $pasta ) ) {
			return false;
		}
	}
	return is_dir( $pasta ) && is_writable( $pasta );
}

function dco_pasta_protegida_path() {
	$principal = dco_pasta_protegida_fora_webroot_path();
	if ( dco_pasta_utilizavel( $principal ) ) {
		return $principal;
	}
	return dco_pasta_protegida_reserva_path();
}

/**
 * Prepara as duas pastas candidatas (mesmo que só uma acabe sendo usada por
 * dco_pasta_protegida_path()) com .htaccess + index.html — a de reserva
 * porque pode vir a ser necessária, e a principal como camada extra mesmo
 * fora do webroot (nunca custa, e cobre o caso raro de uma hospedagem
 * expor essa pasta por engano, ex.: um alias de servidor mal configurado).
 */
function dco_garantir_pasta_protegida() {
	foreach ( array( dco_pasta_protegida_fora_webroot_path(), dco_pasta_protegida_reserva_path() ) as $pasta ) {
		if ( ! dco_pasta_utilizavel( $pasta ) ) {
			continue;
		}
		$htaccess = $pasta . '/.htaccess';
		$conteudo_esperado = "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\nOptions -Indexes\n";
		if ( ! file_exists( $htaccess ) || file_get_contents( $htaccess ) !== $conteudo_esperado ) {
			@file_put_contents( $htaccess, $conteudo_esperado );
		}
		$index = $pasta . '/index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' );
		}
	}
}

/**
 * Capacidades dedicadas (nunca reaproveita uma capability nativa como
 * 'manage_options' — ver a lição registrada no sistema de Publicidade do
 * tema sobre colisão global de meta-capabilities) para gerenciar Currículos
 * e Vagas, concedidas só ao Administrador.
 *
 * Usa get_post_type_capabilities() — a mesma função que o núcleo do
 * WordPress usa dentro de register_post_type() — para derivar o conjunto
 * COMPLETO de capacidades que "map_meta_cap" vai exigir (edit_published_*,
 * delete_private_*, edit_private_* etc., além das básicas de editar e
 * excluir). Conceder só as básicas não basta: ao editar um post JÁ PUBLICADO, por
 * exemplo, o WordPress passa a exigir "edit_published_{plural}" em vez de
 * "edit_{singular}" — sem essa capacidade extra, o próprio autor perde a
 * permissão de editar o que acabou de publicar.
 */
function dco_garantir_capacidades() {
	$admin = get_role( 'administrator' );
	if ( ! $admin ) {
		return;
	}
	foreach ( array(
		array( 'dco_curriculo', 'dco_curriculos' ),
		array( 'dco_vaga', 'dco_vagas' ),
	) as $tipo ) {
		$capacidades = get_post_type_capabilities( (object) array(
			'capability_type' => $tipo,
			'capabilities'    => array(),
			'map_meta_cap'    => true,
		) );
		foreach ( (array) $capacidades as $cap ) {
			if ( $cap === 'read' ) {
				continue; // capacidade nativa do WordPress, já concedida a todo mundo — nunca mexer nela aqui.
			}
			if ( ! $admin->has_cap( $cap ) ) {
				$admin->add_cap( $cap );
			}
		}
	}
}

add_action( 'admin_menu', 'dco_registrar_menu_admin' );
function dco_registrar_menu_admin() {
	add_menu_page(
		'Oportunidades',
		'Oportunidades',
		'edit_dco_curriculos',
		'dco-oportunidades',
		'dco_render_pagina_painel',
		'dashicons-businessman',
		26
	);
}

