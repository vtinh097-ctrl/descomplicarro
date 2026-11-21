<?php
/**
 * DESCOMPLICARRO Oportunidades — painel administrativo consolidado (menu
 * "Oportunidades"): contadores, cadastros pendentes de confirmação e
 * denúncias a revisar, de Currículos e Vagas juntos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dco_render_pagina_painel() {
	$contagem_curriculo = wp_count_posts( 'dco_curriculo' );
	$contagem_vaga      = wp_count_posts( 'dco_vaga' );

	$pendentes = get_posts( array(
		'post_type'      => array( 'dco_curriculo', 'dco_vaga' ),
		'post_status'    => 'pending',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	) );

	$denunciados = get_posts( array(
		'post_type'      => array( 'dco_curriculo', 'dco_vaga' ),
		'post_status'    => array( 'publish', 'suspensa' ),
		'posts_per_page' => -1,
		'meta_query'     => array( array( 'key' => 'dco_denuncias', 'value' => 0, 'compare' => '>' ) ),
		'orderby'        => 'meta_value_num',
		'meta_key'       => 'dco_denuncias',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	) );
	?>
	<div class="wrap">
		<h1>Oportunidades Automotivas</h1>

		<h2>Resumo</h2>
		<table class="widefat striped" style="max-width:640px;">
			<thead><tr><th></th><th>Publicados</th><th>Em avaliação</th><th>Suspensos</th><th>Expirados</th></tr></thead>
			<tbody>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dco_curriculo' ) ); ?>">Currículos</a></td>
					<td><?php echo (int) ( $contagem_curriculo->publish ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_curriculo->pending ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_curriculo->suspensa ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_curriculo->expirada ?? 0 ); ?></td>
				</tr>
				<tr>
					<td><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=dco_vaga' ) ); ?>">Vagas</a></td>
					<td><?php echo (int) ( $contagem_vaga->publish ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_vaga->pending ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_vaga->suspensa ?? 0 ); ?></td>
					<td><?php echo (int) ( $contagem_vaga->expirada ?? 0 ); ?></td>
				</tr>
			</tbody>
		</table>

		<h2>Aguardando confirmação de e-mail (<?php echo count( $pendentes ); ?>)</h2>
		<?php if ( empty( $pendentes ) ) : ?>
			<p>Nenhum cadastro pendente no momento.</p>
		<?php else : ?>
			<p style="color:#646970;">Cadastros aqui só se tornam públicos quando a pessoa confirma o e-mail. Os que nunca forem confirmados são removidos automaticamente após alguns dias (ver rotina de limpeza).</p>
			<table class="widefat striped" style="max-width:800px;">
				<thead><tr><th>Título</th><th>Tipo</th><th>Cadastrado em</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $pendentes as $post ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $post ) ); ?></td>
							<td><?php echo esc_html( $post->post_type === 'dco_vaga' ? 'Vaga' : 'Currículo' ); ?></td>
							<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $post->post_date ) ); ?></td>
							<td><a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>">Ver</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>Denúncias recebidas (<?php echo count( $denunciados ); ?>)</h2>
		<?php if ( empty( $denunciados ) ) : ?>
			<p>Nenhuma denúncia pendente de revisão.</p>
		<?php else : ?>
			<table class="widefat striped" style="max-width:800px;">
				<thead><tr><th>Título</th><th>Tipo</th><th>Status atual</th><th>Denúncias</th><th></th></tr></thead>
				<tbody>
					<?php foreach ( $denunciados as $post ) : ?>
						<tr>
							<td><?php echo esc_html( get_the_title( $post ) ); ?></td>
							<td><?php echo esc_html( $post->post_type === 'dco_vaga' ? 'Vaga' : 'Currículo' ); ?></td>
							<td><?php echo esc_html( dco_rotulo_status( $post->post_status ) ); ?></td>
							<td><?php echo (int) get_post_meta( $post->ID, 'dco_denuncias', true ); ?></td>
							<td><a href="<?php echo esc_url( get_edit_post_link( $post ) ); ?>">Revisar</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<h2>Página pública</h2>
		<?php
		$pagina_url = dco_pagina_oportunidades_url();
		$id_pagina  = (int) get_option( 'dco_pagina_oportunidades_id' );
		?>
		<?php if ( $id_pagina ) : ?>
			<p>O shortcode <code>[dco_oportunidades]</code> está publicado em: <a href="<?php echo esc_url( $pagina_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title( $id_pagina ) ); ?></a></p>
		<?php else : ?>
			<p style="color:#b32d2e;">Nenhuma página publicada com o shortcode <code>[dco_oportunidades]</code> foi encontrada — os links de e-mail (confirmação, gestão) não vão funcionar até que ele seja inserido em alguma página do site.</p>
		<?php endif; ?>
	</div>
	<?php
}
