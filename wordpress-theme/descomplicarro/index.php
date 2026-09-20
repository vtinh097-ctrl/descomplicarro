<?php
/**
 * Template padrão (fallback obrigatório do WordPress). Não faz parte das
 * páginas institucionais da V1 — usado apenas se o WordPress não encontrar
 * um template mais específico para a URL solicitada.
 */
get_header();
?>
  <section class="section section--offwhite">
    <div class="container">
      <div class="bloco-texto__col">
        <?php if ( have_posts() ) : ?>
          <?php while ( have_posts() ) : the_post(); ?>
            <h1><?php the_title(); ?></h1>
            <?php the_content(); ?>
          <?php endwhile; ?>
        <?php else : ?>
          <h1>Nada encontrado</h1>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php get_footer(); ?>
