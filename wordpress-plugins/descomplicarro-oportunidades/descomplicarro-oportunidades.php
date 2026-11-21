<?php
/**
 * Plugin Name: DESCOMPLICARRO — Oportunidades Automotivas
 * Description: Banco de currículos e vagas do setor automotivo (autogestão por link mágico, sem senha), métricas simples de cliques e automação editorial — construído como plugin próprio para que os dados sobrevivam a trocas/atualizações do tema DESCOMPLICARRO. O tema cuida só da apresentação visual.
 * Version: 0.1.0
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

define( 'DCO_VERSION', '0.1.0' );
define( 'DCO_PLUGIN_FILE', __FILE__ );
define( 'DCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DCO_PLUGIN_DIR . 'inc/admin-fields.php';
require_once DCO_PLUGIN_DIR . 'inc/admin-status-js.php';
require_once DCO_PLUGIN_DIR . 'inc/municipios.php';
require_once DCO_PLUGIN_DIR . 'inc/cpt-curriculo.php';
require_once DCO_PLUGIN_DIR . 'inc/cpt-vaga.php';

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

function dco_pasta_protegida_path() {
	$uploads = wp_upload_dir();
	return trailingslashit( $uploads['basedir'] ) . 'dco-curriculos-privado';
}

/**
 * Pasta de armazenamento dos PDFs de currículo: nome de arquivo não
 * previsível (sufixo aleatório, ver dco_validar_e_proteger_pdf() em
 * inc/cpt-curriculo.php) E bloqueio de acesso direto via .htaccess — sem
 * listagem de diretório e sem acesso via navegador a NENHUM arquivo da
 * pasta, nem o PDF nem um eventual script malicioso disfarçado. As duas
 * sintaxes (Apache 2.4 "Require all denied" e a 2.2 "Deny from all") são
 * gravadas para funcionar em qualquer versão — cada hospedagem ativa o
 * <IfModule> correto sozinha.
 *
 * IMPORTANTE SOBRE TESTE LOCAL: o servidor embutido do PHP (usado neste
 * ambiente de desenvolvimento local, "php -S") NÃO processa .htaccess —
 * nenhum servidor desse tipo processa. Isso significa que este bloqueio não
 * pôde ser verificado empiricamente no ambiente local; ele depende do
 * servidor de produção (Hostinger) ser Apache com suporte a .htaccess
 * habilitado, que é o padrão da hospedagem compartilhada deles. Antes de ir
 * para produção, farei uma verificação final diretamente lá.
 *
 * Nenhum link público para esta pasta existe ainda em lugar nenhum do site
 * — o único acesso possível hoje é pelo wp-admin autenticado. O link de
 * download com expiração (para o próprio profissional baixar seu currículo
 * pelo "gerenciar meu cadastro") é a próxima parte desta etapa.
 */
function dco_garantir_pasta_protegida() {
	$pasta = dco_pasta_protegida_path();
	if ( ! file_exists( $pasta ) ) {
		wp_mkdir_p( $pasta );
	}
	$htaccess = $pasta . '/.htaccess';
	$conteudo_esperado = "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\nOptions -Indexes\n";
	if ( ! file_exists( $htaccess ) || file_get_contents( $htaccess ) !== $conteudo_esperado ) {
		file_put_contents( $htaccess, $conteudo_esperado );
	}
	$index = $pasta . '/index.html';
	if ( ! file_exists( $index ) ) {
		file_put_contents( $index, '' );
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

add_action( 'admin_enqueue_scripts', 'dco_enqueue_admin_assets' );
function dco_enqueue_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$post_type = $hook === 'post-new.php'
		? ( isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : 'post' )
		: get_post_type();
	if ( ! in_array( $post_type, array( 'dco_curriculo', 'dco_vaga' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script(
		'descomplicarro-oportunidades-admin',
		DCO_PLUGIN_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		DCO_VERSION,
		true
	);
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

function dco_render_pagina_painel() {
	$total_curriculos = wp_count_posts( 'dco_curriculo' );
	$total_vagas      = wp_count_posts( 'dco_vaga' );
	?>
	<div class="wrap">
		<h1>Oportunidades Automotivas</h1>
		<p>Painel em construção (Etapa 2 em andamento). Por enquanto, gerencie Currículos e Vagas pelos menus abaixo — o painel consolidado (filtros, denúncias, exportações) chega nas próximas entregas desta etapa.</p>
		<ul>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dco_curriculo' ) ); ?>">Currículos<?php if ( $total_curriculos ) : ?> (<?php echo (int) $total_curriculos->publish; ?> ativos)<?php endif; ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dco_vaga' ) ); ?>">Vagas<?php if ( $total_vagas ) : ?> (<?php echo (int) $total_vagas->publish; ?> ativas)<?php endif; ?></a></li>
		</ul>
	</div>
	<?php
}
