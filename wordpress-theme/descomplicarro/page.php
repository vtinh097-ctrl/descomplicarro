<?php
/**
 * Template genérico de página (fallback) — usado apenas se uma página
 * institucional for criada sem um dos modelos específicos atribuído.
 * Reaproveita a seção de abertura + o conteúdo do editor de blocos padrão,
 * mantendo a identidade visual (grafite/off-white/título N.00), mas sem
 * nenhuma das seções específicas das páginas institucionais.
 */
get_header();
?>
  <section class="section section--abertura section--graphite" aria-labelledby="page-heading">
    <div class="container section--abertura__inner">
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>PÁGINA</span></div>
      <div class="abertura__content">
        <h1 id="page-heading" class="abertura__heading"><?php the_title(); ?></h1>
      </div>
    </div>
  </section>

  <section class="section section--offwhite">
    <div class="container">
      <div class="bloco-texto__col">
        <?php
        while ( have_posts() ) :
          the_post();
          the_content();
        endwhile;
        ?>
      </div>
    </div>
  </section>
<?php get_footer(); ?>
