<?php
/**
 * Arquivo de categoria (editoria do Em Foco) — usado automaticamente pelo
 * WordPress para QUALQUER categoria nativa (Posts → Categories), servindo
 * de forma genérica as cinco editorias (Notícias, Reviews, Guias, Técnica,
 * Bastidores). Reproduz fielmente o modelo aprovado em
 * em-foco/<editoria>/index.html, alimentado por publicações reais do
 * WordPress (WP_Query principal, post type "post", status "publish").
 *
 * A consulta principal já vem limitada a 4 publicações por página (1
 * destaque + 3 na grade "Últimas") pelo filtro pre_get_posts em
 * inc/setup.php — por isso a paginação nativa (CARREGAR MAIS) avança
 * corretamente sem repetir nem pular matérias.
 */
get_header();

$termo      = get_queried_object();
$editorias  = dc_emfoco_categorias();
$info       = isset( $editorias[ $termo->slug ] ) ? $editorias[ $termo->slug ] : array( 'label' => $termo->name, 'assinatura' => '' );

$posts_pagina = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$posts_pagina[] = get_post();
	}
}
$destaque = ! empty( $posts_pagina ) ? array_shift( $posts_pagina ) : null;
$ultimas  = $posts_pagina;

// Tags reais (nativas do WordPress) das matérias exibidas nesta página — usadas nos chips "relacionados".
$tags_exibidas = array();
foreach ( array_filter( array_merge( array( $destaque ), $ultimas ) ) as $p ) {
	foreach ( wp_get_post_tags( $p->ID ) as $tag ) {
		$tags_exibidas[ $tag->term_id ] = $tag;
	}
}
?>

  <section class="section section--emfoco-hero section--graphite emfoco-hero" aria-labelledby="editoria-heading">
    <div class="container">

      <nav class="breadcrumb breadcrumb--on-dark" aria-label="Trilha de navegação">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
        <a href="<?php echo esc_url( home_url( '/em-foco' ) ); ?>">Em Foco</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
        <span aria-current="page"><?php echo esc_html( $info['label'] ); ?></span>
      </nav>

      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>EDITORIA</span></div>

      <h1 id="editoria-heading" class="abertura__heading"><?php echo esc_html( mb_strtoupper( $info['label'], 'UTF-8' ) ); ?></h1>
      <?php if ( $info['assinatura'] ) : ?>
        <p class="emfoco-hero__assinatura"><?php echo esc_html( $info['assinatura'] ); ?></p>
      <?php endif; ?>

    </div>
  </section>

  <?php if ( $destaque ) : ?>
  <section class="section section--offwhite" aria-labelledby="editoria-destaque-heading">
    <div class="container">
      <h2 id="editoria-destaque-heading" class="visually-hidden">Destaque de <?php echo esc_html( $info['label'] ); ?></h2>
      <?php dc_emfoco_card( $destaque, 'emfoco-card--principal' ); ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="section section--offwhite" aria-labelledby="editoria-ultimas-heading">
    <div class="container">

      <div class="section-heading-row">
        <h2 id="editoria-ultimas-heading" class="section-heading">ÚLTIMAS EM <?php echo esc_html( mb_strtoupper( $info['label'], 'UTF-8' ) ); ?></h2>
      </div>

      <?php if ( $ultimas ) : ?>
        <div class="ultimas-grid">
          <?php foreach ( $ultimas as $post_item ) : dc_emfoco_card( $post_item ); endforeach; ?>
        </div>
      <?php elseif ( ! $destaque ) : ?>
        <p class="perfil__text">Ainda não há matérias publicadas nesta editoria. Assim que uma nova publicação for cadastrada no WordPress com esta categoria, ela aparecerá aqui automaticamente.</p>
      <?php endif; ?>

      <?php
      $pagina_atual = max( 1, get_query_var( 'paged' ) );
      global $wp_query;
      if ( $pagina_atual < (int) $wp_query->max_num_pages ) :
        ?>
        <div class="section-cta">
          <a href="<?php echo esc_url( get_pagenum_link( $pagina_atual + 1 ) ); ?>" class="btn-bracket">
            <span class="btn-bracket__bracket" aria-hidden="true">[</span>
            <span class="btn-bracket__label">CARREGAR MAIS</span>
            <span class="btn-bracket__bracket" aria-hidden="true">]</span>
          </a>
        </div>
      <?php endif; ?>

      <?php if ( $tags_exibidas ) : ?>
        <div class="tag-chips" aria-label="Tags relacionadas a <?php echo esc_attr( $info['label'] ); ?>">
          <?php foreach ( $tags_exibidas as $tag ) : ?>
            <a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>" class="tag-chip"><?php echo esc_html( $tag->name ); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </section>

  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="emfoco-editoria-<?php echo esc_attr( $termo->slug ); ?>">
    <div class="container">
      <a href="#" class="ad-slot__unit">
        <span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
        <span class="ad-slot__eyebrow">Publicidade</span>
        <span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
        <span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
      </a>
    </div>
  </section>

<?php get_footer(); ?>
