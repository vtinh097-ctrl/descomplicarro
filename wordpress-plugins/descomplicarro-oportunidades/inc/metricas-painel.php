<?php
/**
 * DESCOMPLICARRO — painel "Relatório de Cliques": período, filtro por área,
 * tabela de totais e exportação em CSV. Sem gráficos (como pedido) — só
 * números. Nenhum dado pessoal de visitante existe para mostrar: a tabela
 * só guarda totais agregados por dia (ver inc/metricas-db.php).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'dco_metricas_registrar_menu_admin' );
function dco_metricas_registrar_menu_admin() {
	add_menu_page(
		'Métricas',
		'Métricas',
		'manage_options',
		'dco-metricas',
		'dco_render_pagina_metricas',
		'dashicons-chart-bar',
		27
	);
}

function dco_metricas_periodo_atual() {
	$periodo = isset( $_GET['dco_periodo'] ) ? sanitize_key( wp_unslash( $_GET['dco_periodo'] ) ) : '7dias';
	$hoje    = current_time( 'Y-m-d' );

	switch ( $periodo ) {
		case 'hoje':
			return array( $hoje, $hoje, $periodo );
		case '30dias':
			return array( date( 'Y-m-d', strtotime( $hoje . ' -29 days' ) ), $hoje, $periodo );
		case 'personalizado':
			$inicio = isset( $_GET['dco_inicio'] ) ? sanitize_text_field( wp_unslash( $_GET['dco_inicio'] ) ) : $hoje;
			$fim    = isset( $_GET['dco_fim'] ) ? sanitize_text_field( wp_unslash( $_GET['dco_fim'] ) ) : $hoje;
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $inicio ) ) { $inicio = $hoje; }
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $fim ) ) { $fim = $hoje; }
			return array( $inicio, $fim, $periodo );
		case '7dias':
		default:
			return array( date( 'Y-m-d', strtotime( $hoje . ' -6 days' ) ), $hoje, '7dias' );
	}
}

/** Tenta transformar o identificador (quase sempre um ID de post) num rótulo legível — sem nunca quebrar se o post já não existir mais (ex.: matéria apagada). */
function dco_metricas_rotulo_item( $area, $identificador ) {
	if ( $identificador !== '' && ctype_digit( (string) $identificador ) ) {
		$titulo = get_the_title( (int) $identificador );
		if ( $titulo ) {
			return $titulo;
		}
	}
	return $identificador !== '' ? $identificador : '—';
}

function dco_metricas_rotulo_area( $area ) {
	$rotulos = array(
		'anuncio'   => 'Publicidade',
		'materia'   => 'Matéria',
		'oficina'   => 'Oficina (GI Conecta)',
		'parceiro'  => 'Parceiro',
		'vaga'      => 'Vaga',
		'curriculo' => 'Currículo',
		'navegacao' => 'Navegação',
		'geral'     => 'Geral',
	);
	return $rotulos[ $area ] ?? $area;
}

function dco_metricas_rotulo_evento( $evento ) {
	$rotulos = array(
		'ad_click'             => 'Clique em anúncio',
		'ad_impression'        => 'Impressão de anúncio',
		'article_click'        => 'Clique em matéria',
		'partner_click'        => 'Clique em parceiro',
		'whatsapp_click'       => 'Clique em WhatsApp',
		'email_click'          => 'Clique em e-mail',
		'social_click'         => 'Clique em rede social',
		'file_download'        => 'Download de arquivo',
		'credenciamento_click' => 'Clique em credenciamento',
		'button_click'         => 'Clique em botão',
		'navigation_click'     => 'Clique em navegação',
		'candidatura_click'    => 'Candidatura a vaga',
		'contato_click'        => 'Solicitação de contato',
	);
	return $rotulos[ $evento ] ?? $evento;
}

/**
 * A exportação em CSV precisa rodar em "admin_init" — ANTES do WordPress
 * começar a enviar o HTML da própria tela de admin (que já manda seus
 * próprios cabeçalhos HTTP). Chamar isso de dentro do callback da página
 * (dco_render_pagina_metricas()) é tarde demais: o header() do CSV não
 * tem mais efeito, e o navegador recebe a página de admin normal com
 * content-type text/html em vez do arquivo.
 *
 * Prioridade bem baixa (-999, roda antes de qualquer outro "admin_init"
 * de prioridade padrão) para reduzir ao mínimo a chance de outra rotina
 * do próprio núcleo do WordPress (ex.: a checagem periódica de
 * atualizações) emitir algum aviso/saída antes da nossa, o que impediria
 * os cabeçalhos do CSV de serem definidos a tempo.
 */
add_action( 'admin_init', 'dco_metricas_interceptar_exportacao_csv', -999 );
function dco_metricas_interceptar_exportacao_csv() {
	if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'dco-metricas' ) {
		return;
	}
	if ( ! isset( $_GET['dco_exportar'] ) || $_GET['dco_exportar'] !== 'csv' ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	list( $inicio, $fim ) = dco_metricas_periodo_atual();
	$area_filtro = isset( $_GET['dco_area'] ) ? sanitize_key( wp_unslash( $_GET['dco_area'] ) ) : '';
	dco_metricas_exportar_csv( $inicio, $fim, $area_filtro );
	exit;
}

function dco_render_pagina_metricas() {
	list( $inicio, $fim, $periodo ) = dco_metricas_periodo_atual();
	$area_filtro = isset( $_GET['dco_area'] ) ? sanitize_key( wp_unslash( $_GET['dco_area'] ) ) : '';

	$resultados = dco_metricas_consultar( $inicio, $fim, $area_filtro );
	$total_geral = array_sum( wp_list_pluck( $resultados, 'total' ) );
	?>
	<div class="wrap">
		<h1>Relatório de Cliques</h1>
		<p style="color:#646970;">Contagem nativa, sem Google Analytics, Google Tag Manager ou qualquer serviço pago — e sem nenhum dado pessoal de quem clicou (só totais agregados por dia).</p>

		<form method="get" style="margin:16px 0;">
			<input type="hidden" name="page" value="dco-metricas">
			<select name="dco_periodo" onchange="this.form.submit()">
				<option value="hoje" <?php selected( $periodo, 'hoje' ); ?>>Hoje</option>
				<option value="7dias" <?php selected( $periodo, '7dias' ); ?>>Últimos 7 dias</option>
				<option value="30dias" <?php selected( $periodo, '30dias' ); ?>>Últimos 30 dias</option>
				<option value="personalizado" <?php selected( $periodo, 'personalizado' ); ?>>Personalizado</option>
			</select>
			<?php if ( $periodo === 'personalizado' ) : ?>
				De <input type="date" name="dco_inicio" value="<?php echo esc_attr( $inicio ); ?>">
				até <input type="date" name="dco_fim" value="<?php echo esc_attr( $fim ); ?>">
			<?php endif; ?>
			<select name="dco_area">
				<option value="">Todas as áreas</option>
				<?php foreach ( dco_metricas_areas_permitidas() as $area ) : ?>
					<option value="<?php echo esc_attr( $area ); ?>" <?php selected( $area_filtro, $area ); ?>><?php echo esc_html( dco_metricas_rotulo_area( $area ) ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button">Filtrar</button>
			<a class="button" href="<?php echo esc_url( add_query_arg( array_merge( $_GET, array( 'dco_exportar' => 'csv' ) ) ) ); ?>">Exportar CSV</a>
		</form>

		<p><strong>Total de cliques no período: <?php echo (int) $total_geral; ?></strong> (<?php echo esc_html( $inicio === $fim ? $inicio : "{$inicio} a {$fim}" ); ?>)</p>

		<?php if ( empty( $resultados ) ) : ?>
			<p>Nenhum clique registrado neste período<?php echo $area_filtro ? ' para esta área' : ''; ?>.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:800px;">
				<thead><tr><th>Área</th><th>Tipo de evento</th><th>Item</th><th>Total de cliques</th></tr></thead>
				<tbody>
					<?php foreach ( $resultados as $linha ) : ?>
						<tr>
							<td><?php echo esc_html( dco_metricas_rotulo_area( $linha->area ) ); ?></td>
							<td><?php echo esc_html( dco_metricas_rotulo_evento( $linha->evento ) ); ?></td>
							<td><?php echo esc_html( dco_metricas_rotulo_item( $linha->area, $linha->identificador ) ); ?></td>
							<td><?php echo (int) $linha->total; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}

function dco_metricas_exportar_csv( $inicio, $fim, $area_filtro ) {
	$resultados = dco_metricas_consultar( $inicio, $fim, $area_filtro );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="metricas-descomplicarro-' . $inicio . '-a-' . $fim . '.csv"' );

	$saida = fopen( 'php://output', 'w' );
	fputcsv( $saida, array( 'Área', 'Tipo de evento', 'Item', 'Total de cliques' ) );
	foreach ( $resultados as $linha ) {
		fputcsv( $saida, array(
			dco_metricas_rotulo_area( $linha->area ),
			dco_metricas_rotulo_evento( $linha->evento ),
			dco_metricas_rotulo_item( $linha->area, $linha->identificador ),
			(int) $linha->total,
		) );
	}
	fclose( $saida );
}
