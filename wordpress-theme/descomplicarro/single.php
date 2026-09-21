<?php
/**
 * Página individual de matéria (post nativo do WordPress) — usada
 * automaticamente para qualquer publicação, de qualquer editoria.
 * Reproduz fielmente o modelo aprovado em
 * em-foco/noticias/materia-modelo/index.html, com título, imagem
 * destacada, data, autor, categoria e conteúdo completo vindos
 * diretamente da publicação — nenhum dado fixo no template.
 */
get_header();

while ( have_posts() ) :
	the_post();

	$categorias = get_the_category();
	$categoria  = ! empty( $categorias ) ? $categorias[0] : null;

	$tem_atualizacao = get_the_modified_time( 'U' ) > ( get_the_time( 'U' ) + MINUTE_IN_SECONDS );

	$palavras         = str_word_count( wp_strip_all_tags( get_the_content() ) );
	$minutos_leitura  = max( 1, (int) ceil( $palavras / 200 ) );

	$thumb = get_the_post_thumbnail_url( get_the_ID(), 'large' );
	if ( ! $thumb ) {
		$thumb = get_template_directory_uri() . '/assets/images/placeholder-photo.svg';
	}
	?>

	<article>

		<div class="section section--offwhite">
			<div class="container">

				<header>
					<nav class="breadcrumb" aria-label="Trilha de navegação">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( home_url( '/em-foco' ) ); ?>">Em Foco</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
						<?php if ( $categoria ) : ?>
							<a href="<?php echo esc_url( get_category_link( $categoria ) ); ?>"><?php echo esc_html( $categoria->name ); ?></a><span class="breadcrumb__sep" aria-hidden="true">/</span>
						<?php endif; ?>
						<span aria-current="page"><?php the_title(); ?></span>
					</nav>

					<div class="materia-header">
						<?php if ( $categoria ) : ?>
							<span class="materia-editoria"><?php echo esc_html( $categoria->name ); ?></span>
						<?php endif; ?>
						<h1 class="materia-titulo"><?php the_title(); ?></h1>
						<?php $resumo = get_the_excerpt(); ?>
						<?php if ( $resumo ) : ?>
							<p class="materia-subtitulo"><?php echo esc_html( $resumo ); ?></p>
						<?php endif; ?>

						<p class="materia-meta">
							<span>Por <strong><a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php the_author(); ?></a></strong></span>
							<span>Publicado em <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><strong><?php the_date(); ?></strong></time></span>
							<?php if ( $tem_atualizacao ) : ?>
								<span>Atualizado em <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><strong><?php the_modified_date(); ?></strong></time></span>
							<?php endif; ?>
							<span><strong><?php echo (int) $minutos_leitura; ?> min</strong> de leitura</span>
						</p>
					</div>
				</header>

				<figure class="materia-imagem-principal">
					<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>">
					<span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
					<span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
				</figure>

				<div class="materia-corpo">
					<?php the_content(); ?>
				</div>

			</div>
		</div>

		<section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="materia-apos-conteudo">
			<div class="container">
				<a href="#" class="ad-slot__unit">
					<span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
					<span class="ad-slot__eyebrow">Publicidade</span>
					<span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
					<span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
				</a>
			</div>
		</section>

		<?php
		$tags = get_the_tags();
		if ( $tags ) :
			?>
			<div class="section section--offwhite">
				<div class="container">
					<div class="materia-rodape">
						<div class="tag-chips" aria-label="Tags desta matéria">
							<?php foreach ( $tags as $tag ) : ?>
								<a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="tag-chip"><?php echo esc_html( $tag->name ); ?></a>
							<?php endforeach; ?>
						</div>

						<nav class="materia-compartilhar" aria-label="Compartilhar matéria">
							<p class="principios-list__eyebrow">Compartilhar</p>
							<?php
							$url_materia   = get_permalink();
							$titulo_materia = get_the_title();
							?>
							<ul class="compartilhar-lista">
								<li><a href="<?php echo esc_url( 'https://wa.me/?text=' . rawurlencode( $titulo_materia . ' — ' . $url_materia ) ); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
								<li><a href="<?php echo esc_url( 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url_materia ) ); ?>" target="_blank" rel="noopener noreferrer">Facebook</a></li>
								<li><a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url_materia ) ); ?>" target="_blank" rel="noopener noreferrer">LinkedIn</a></li>
								<li><a href="<?php echo esc_url( $url_materia ); ?>">Copiar link</a></li>
							</ul>
						</nav>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php
		$relacionados_args = array(
			'posts_per_page' => 3,
			'post__not_in'   => array( get_the_ID() ),
			'ignore_sticky_posts' => true,
		);
		if ( $categoria ) {
			$relacionados_args['cat'] = $categoria->term_id;
		}
		$relacionados = new WP_Query( $relacionados_args );
		if ( $relacionados->have_posts() ) :
			?>
			<section class="section section--emfoco section--offwhite" aria-labelledby="relacionados-heading">
				<div class="container">
					<div class="section-heading-row">
						<h2 id="relacionados-heading" class="section-heading">CONTEÚDOS RELACIONADOS</h2>
					</div>
					<div class="emfoco-grid">
						<?php while ( $relacionados->have_posts() ) : $relacionados->the_post(); ?>
							<?php dc_emfoco_card( get_post() ); ?>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
			<?php
		endif;
		wp_reset_postdata();
		?>

	</article>

	<?php
endwhile;
get_footer();
?>
