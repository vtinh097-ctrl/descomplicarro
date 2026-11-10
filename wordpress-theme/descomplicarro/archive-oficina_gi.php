<?php
/**
 * Listagem pública das oficinas GI CONECTA — usada automaticamente pelo
 * WordPress para o arquivo do CPT "oficina_gi". A consulta principal já
 * exclui oficinas não aprovadas (o WordPress nunca inclui posts com status
 * diferente de "publicado" em consultas públicas) — o filtro abaixo só
 * adiciona estado/cidade por cima dessa regra nativa.
 */
get_header();

$estado_atual = isset( $_GET['estado'] ) ? sanitize_text_field( wp_unslash( $_GET['estado'] ) ) : '';
$cidade_atual = isset( $_GET['cidade'] ) ? sanitize_text_field( wp_unslash( $_GET['cidade'] ) ) : '';
$uf_lista     = dc_uf_list();
if ( ! array_key_exists( $estado_atual, $uf_lista ) ) {
	$estado_atual = '';
}
?>

  <section class="section section--emfoco-hero section--graphite emfoco-hero" aria-labelledby="oficinas-heading">
    <div class="container">
      <nav class="breadcrumb breadcrumb--on-dark" aria-label="Trilha de navegação">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
        <a href="<?php echo esc_url( home_url( '/para-motoristas' ) ); ?>">Para Motoristas</a><span class="breadcrumb__sep" aria-hidden="true">/</span>
        <span aria-current="page">Oficinas GI CONECTA</span>
      </nav>
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>GI CONECTA</span></div>
      <h1 id="oficinas-heading" class="abertura__heading">OFICINAS CREDENCIADAS</h1>
      <p class="emfoco-hero__assinatura">Curadoria de oficinas que demonstram cuidado com atendimento, transparência e experiência do cliente.</p>
    </div>
  </section>

  <section class="section section--offwhite" aria-labelledby="oficinas-filtro-heading">
    <div class="container">
      <h2 id="oficinas-filtro-heading" class="visually-hidden">Filtrar oficinas</h2>
      <form method="get" action="<?php echo esc_url( get_post_type_archive_link( 'oficina_gi' ) ); ?>" class="oficinas-filtro">
        <div class="oficinas-filtro__campo">
          <label for="oficinas-filtro-estado">Estado</label>
          <select id="oficinas-filtro-estado" name="estado">
            <option value="">Todos os estados</option>
            <?php foreach ( $uf_lista as $uf => $nome ) : ?>
              <option value="<?php echo esc_attr( $uf ); ?>"<?php selected( $estado_atual, $uf ); ?>><?php echo esc_html( $uf . ' — ' . $nome ); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="oficinas-filtro__campo">
          <label for="oficinas-filtro-cidade">Cidade</label>
          <input type="text" id="oficinas-filtro-cidade" name="cidade" value="<?php echo esc_attr( $cidade_atual ); ?>" placeholder="Ex.: Campinas">
        </div>
        <button type="submit" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">FILTRAR</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </button>
        <?php if ( $estado_atual || $cidade_atual ) : ?>
          <a href="<?php echo esc_url( get_post_type_archive_link( 'oficina_gi' ) ); ?>" class="oficinas-filtro__limpar">Limpar filtros</a>
        <?php endif; ?>
      </form>

      <?php
      $args = array(
        'post_type'           => 'oficina_gi',
        'post_status'         => 'publish',
        'posts_per_page'      => 12,
        'paged'               => max( 1, get_query_var( 'paged' ) ),
        'ignore_sticky_posts' => true,
      );
      $meta_query = array();
      if ( $estado_atual ) {
        $meta_query[] = array( 'key' => 'dc_oficina_estado', 'value' => $estado_atual );
      }
      if ( $cidade_atual ) {
        $meta_query[] = array( 'key' => 'dc_oficina_cidade', 'value' => $cidade_atual, 'compare' => 'LIKE' );
      }
      if ( $meta_query ) {
        $args['meta_query'] = $meta_query;
      }
      $oficinas = new WP_Query( $args );
      ?>

      <?php if ( $oficinas->have_posts() ) : ?>
        <div class="emfoco-grid">
          <?php while ( $oficinas->have_posts() ) : $oficinas->the_post(); ?>
            <?php
            $cidade = get_post_meta( get_the_ID(), 'dc_oficina_cidade', true );
            $uf     = get_post_meta( get_the_ID(), 'dc_oficina_estado', true );
            $thumb  = dc_oficina_capa_url( get_the_ID() );
            if ( ! $thumb ) {
              $thumb = get_template_directory_uri() . '/assets/images/placeholder-photo.svg';
            }
            ?>
            <a href="<?php the_permalink(); ?>" class="emfoco-card">
              <div class="emfoco-card__media"><img src="<?php echo esc_url( $thumb ); ?>" alt="" class="emfoco-card__image"></div>
              <?php if ( $cidade || $uf ) : ?>
                <span class="emfoco-card__category"><?php echo esc_html( trim( $cidade . ( $uf ? ' — ' . $uf : '' ), ' —' ) ); ?></span>
              <?php endif; ?>
              <h3 class="emfoco-card__title"><?php the_title(); ?></h3>
            </a>
          <?php endwhile; ?>
        </div>

        <?php
        $pagina_atual = max( 1, get_query_var( 'paged' ) );
        if ( $pagina_atual < (int) $oficinas->max_num_pages ) :
          $proxima = add_query_arg( array( 'estado' => $estado_atual, 'cidade' => $cidade_atual, 'paged' => $pagina_atual + 1 ), get_post_type_archive_link( 'oficina_gi' ) );
          ?>
          <div class="section-cta">
            <a href="<?php echo esc_url( $proxima ); ?>" class="btn-bracket">
              <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">CARREGAR MAIS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
            </a>
          </div>
        <?php endif; ?>
      <?php else : ?>
        <p class="perfil__text">Nenhuma oficina encontrada com esses filtros. <?php echo ( $estado_atual || $cidade_atual ) ? 'Tente outro estado ou cidade.' : 'Em breve novas oficinas credenciadas aparecerão aqui.'; ?></p>
      <?php endif; ?>
      <?php wp_reset_postdata(); ?>

      <div class="section-cta">
        <a href="<?php echo esc_url( dc_oficinas_credenciamento_url() ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">QUERO CREDENCIAR MINHA OFICINA</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
