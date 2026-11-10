<?php
/**
 * Perfil público de uma oficina GI CONECTA. O WordPress só chega até aqui
 * para oficinas com status "Publicado" (Aprovada) — uma oficina em
 * avaliação, suspensa ou descredenciada não é publicamente consultável, e
 * visitar seu endereço diretamente resulta em "não encontrado", pelo
 * comportamento nativo do WordPress para conteúdo não publicado (o mesmo
 * mecanismo que protege rascunhos em qualquer site WordPress).
 *
 * Nesta etapa (Fase 1) não há sistema de avaliações: nenhuma nota, estrela
 * ou comentário é exibido — isso fica para uma etapa futura.
 */
get_header();

while ( have_posts() ) :
	the_post();

	$endereco       = dc_field( 'dc_oficina_endereco' );
	$cidade         = dc_field( 'dc_oficina_cidade' );
	$uf             = dc_field( 'dc_oficina_estado' );
	$telefone       = dc_field( 'dc_oficina_telefone' );
	$whatsapp       = dc_field( 'dc_oficina_whatsapp' );
	$instagram      = dc_field( 'dc_oficina_instagram' );
	$maps           = dc_field( 'dc_oficina_maps' );
	$galeria        = dc_repeater( 'dc_oficina_galeria' );
	$thumb          = dc_oficina_capa_url( get_the_ID() );
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
						<a href="<?php echo esc_url( home_url( '/para-motoristas' ) ); ?>">Para Motoristas</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
						<a href="<?php echo esc_url( get_post_type_archive_link( 'oficina_gi' ) ); ?>">Oficinas GI CONECTA</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
						<span aria-current="page"><?php the_title(); ?></span>
					</nav>
				</header>

				<div class="perfil">
					<div class="perfil__figure">
						<img src="<?php echo esc_url( $thumb ); ?>" alt="<?php the_title_attribute(); ?>" class="perfil__image">
						<span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
						<span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
					</div>
					<div class="perfil__body">
						<?php if ( $cidade || $uf ) : ?>
							<span class="perfil__role"><?php echo esc_html( trim( $cidade . ( $uf ? ' — ' . $uf : '' ), ' —' ) ); ?></span>
						<?php endif; ?>
						<h1 class="perfil__name"><?php the_title(); ?></h1>

						<?php if ( $endereco ) : ?>
							<p class="perfil__text"><?php echo esc_html( $endereco ); ?></p>
						<?php endif; ?>

						<?php dc_paragraph( 'dc_oficina_apresentacao' ); ?>

						<?php
						$servicos       = dc_field_raw( 'dc_oficina_servicos' );
						$especialidades = dc_field_raw( 'dc_oficina_especialidades' );
						$tags           = array_filter( array_merge(
							$servicos ? preg_split( '/\r\n|\r|\n/', trim( $servicos ) ) : array(),
							$especialidades ? preg_split( '/\r\n|\r|\n/', trim( $especialidades ) ) : array()
						) );
						if ( $tags ) :
							?>
							<div class="tag-chips" aria-label="Serviços e especialidades">
								<?php foreach ( $tags as $tag ) : ?>
									<span class="tag-chip"><?php echo esc_html( trim( $tag ) ); ?></span>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<div class="solucao__cta oficina-contatos">
							<?php if ( $whatsapp ) : ?>
								<a href="<?php echo esc_url( 'https://wa.me/55' . $whatsapp ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
									<span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">WHATSAPP</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
								</a>
							<?php endif; ?>
							<?php if ( $telefone ) : ?>
								<a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $telefone ) ); ?>" class="btn-bracket">
									<span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">TELEFONE</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
								</a>
							<?php endif; ?>
							<?php if ( $instagram ) : ?>
								<a href="<?php echo esc_url( $instagram ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
									<span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">INSTAGRAM</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
								</a>
							<?php endif; ?>
							<?php if ( $maps ) : ?>
								<a href="<?php echo esc_url( $maps ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
									<span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER NO MAPA</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
								</a>
							<?php endif; ?>
						</div>
					</div>
				</div>

			</div>
		</div>

		<?php if ( $galeria ) : ?>
		<div class="section section--offwhite">
			<div class="container">
				<div class="section-heading-row"><h2 class="section-heading">FOTOS</h2></div>
				<div class="oficina-galeria">
					<?php foreach ( $galeria as $item ) :
						$img_id = isset( $item['imagem'] ) ? (int) $item['imagem'] : 0;
						$img_url = $img_id ? dc_attachment_image_url( $img_id, 'large' ) : '';
						if ( ! $img_url ) {
							continue;
						}
						?>
						<div class="oficina-galeria__item"><img src="<?php echo esc_url( $img_url ); ?>" alt="" loading="lazy"></div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<div class="section section--offwhite">
			<div class="container">
				<div class="section-cta">
					<a href="<?php echo esc_url( get_post_type_archive_link( 'oficina_gi' ) ); ?>" class="btn-bracket">
						<span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER OUTRAS OFICINAS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
					</a>
				</div>
			</div>
		</div>
	</article>

	<?php
endwhile;
get_footer();
?>
