<?php
/**
 * Template Name: Em Foco
 *
 * Reproduz a capa do Em Foco exatamente como aprovada na V1. Por decisão
 * explícita desta etapa da migração, esta página permanece estática (sem
 * metabox de conteúdo) — o sistema editorial dinâmico (matérias, editorias,
 * destaques administráveis) será implementado em etapa futura. Trocar aqui
 * é só uma questão de, na etapa seguinte, passar a alimentar estes mesmos
 * blocos a partir de consultas reais (WP_Query) em vez de conteúdo fixo.
 */
get_header();
$img = get_template_directory_uri() . '/assets/images/placeholder-photo.svg';
$vid = get_template_directory_uri() . '/assets/images/placeholder-video.svg';
?>

  <section class="section section--emfoco-hero section--graphite emfoco-hero" aria-labelledby="emfoco-heading">
    <div class="container">
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>ABERTURA</span></div>
      <h1 id="emfoco-heading" class="abertura__heading">EM FOCO</h1>
      <p class="emfoco-hero__assinatura">Informação automotiva além da superfície.</p>
      <p class="emfoco-hero__paragrafo">Notícias, reviews, guias, conteúdo técnico e bastidores para entender o que acontece no setor automotivo — e o que isso significa na prática.</p>

      <nav class="editorias-nav" aria-label="Editorias do Em Foco">
        <?php foreach ( array( 'noticias' => 'NOTÍCIAS', 'reviews' => 'REVIEWS', 'guias' => 'GUIAS', 'tecnica' => 'TÉCNICA', 'bastidores' => 'BASTIDORES' ) as $slug => $label ) : ?>
        <a href="<?php echo esc_url( home_url( '/em-foco/' . $slug ) ); ?>" class="btn-bracket btn-bracket--on-dark">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label"><?php echo esc_html( $label ); ?></span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </section>

  <section class="section section--destaques-emfoco section--offwhite" aria-labelledby="destaques-heading">
    <div class="container">
      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>DESTAQUES</span></div>
      <h2 id="destaques-heading" class="visually-hidden">Destaques</h2>
      <div class="destaques-grid">
        <a href="<?php echo esc_url( home_url( '/em-foco/noticias/materia-modelo' ) ); ?>" class="emfoco-card emfoco-card--principal">
          <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
          <span class="emfoco-card__category">Notícias</span>
          <h3 class="emfoco-card__title">[ Título da matéria principal ]</h3>
          <span class="emfoco-card__date">[ Data ]</span>
        </a>
        <div class="destaques-secundarios">
          <a href="<?php echo esc_url( home_url( '/em-foco/reviews/materia-modelo' ) ); ?>" class="emfoco-card">
            <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
            <span class="emfoco-card__category">Reviews</span>
            <h3 class="emfoco-card__title">[ Título da matéria secundária 01 ]</h3>
            <span class="emfoco-card__date">[ Data ]</span>
          </a>
          <a href="<?php echo esc_url( home_url( '/em-foco/tecnica/materia-modelo' ) ); ?>" class="emfoco-card">
            <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
            <span class="emfoco-card__category">Técnica</span>
            <h3 class="emfoco-card__title">[ Título da matéria secundária 02 ]</h3>
            <span class="emfoco-card__date">[ Data ]</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="emfoco-destaques-ultimas">
    <div class="container">
      <a href="#" class="ad-slot__unit">
        <span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
        <span class="ad-slot__eyebrow">Publicidade</span>
        <span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
        <span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
      </a>
    </div>
  </section>

  <section class="section section--ultimas section--offwhite" aria-labelledby="ultimas-heading">
    <div class="container">
      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>ÚLTIMAS PUBLICAÇÕES</span></div>
      <div class="section-heading-row"><h2 id="ultimas-heading" class="section-heading">ÚLTIMAS</h2></div>
      <div class="ultimas-grid">
        <?php
        $ultimas = array(
          array( 'cat' => 'Guias', 'titulo' => '[ Título da publicação 01 ]', 'wide' => true, 'url' => '/em-foco/guias/materia-modelo' ),
          array( 'cat' => 'Notícias', 'titulo' => '[ Título da publicação 02 ]', 'wide' => false, 'url' => '/em-foco/noticias/materia-modelo' ),
          array( 'cat' => 'Bastidores', 'titulo' => '[ Título da publicação 03 ]', 'wide' => false, 'url' => '/em-foco/bastidores/materia-modelo' ),
          array( 'cat' => 'Reviews', 'titulo' => '[ Título da publicação 04 ]', 'wide' => false, 'url' => '/em-foco/reviews/materia-modelo' ),
          array( 'cat' => 'Técnica', 'titulo' => '[ Título da publicação 05 ]', 'wide' => false, 'url' => '/em-foco/tecnica/materia-modelo' ),
        );
        foreach ( $ultimas as $u ) :
          ?>
          <a href="<?php echo esc_url( home_url( $u['url'] ) ); ?>" class="emfoco-card<?php echo $u['wide'] ? ' emfoco-card--wide' : ''; ?>">
            <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
            <span class="emfoco-card__category"><?php echo esc_html( $u['cat'] ); ?></span>
            <h3 class="emfoco-card__title"><?php echo esc_html( $u['titulo'] ); ?></h3>
            <span class="emfoco-card__date">[ Data ]</span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="section-cta">
        <button type="button" class="btn-bracket" data-action="carregar-mais" data-pagina-atual="1">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">CARREGAR MAIS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </button>
      </div>
    </div>
  </section>

  <!-- NOTÍCIAS -->
  <section class="section section--editoria-noticias section--offwhite" aria-labelledby="editoria-noticias-heading">
    <div class="container">
      <div class="section-index"><span>N.03</span><span class="section-index__line"></span><span>EDITORIAS</span></div>
      <div class="editoria-cabecalho">
        <h2 id="editoria-noticias-heading" class="section-heading">NOTÍCIAS</h2>
        <p class="solucao__assinatura">O que está acontecendo.</p>
      </div>
      <div class="editoria-materias">
        <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
        <a href="<?php echo esc_url( home_url( '/em-foco/noticias/materia-modelo' ) ); ?>" class="emfoco-card">
          <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
          <span class="emfoco-card__category">Notícias</span>
          <h3 class="emfoco-card__title">[ Título da matéria 0<?php echo (int) $i; ?> ]</h3>
          <span class="emfoco-card__date">[ Data ]</span>
        </a>
        <?php endfor; ?>
      </div>
      <div class="editoria-cta">
        <a href="<?php echo esc_url( home_url( '/em-foco/noticias' ) ); ?>" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER MAIS NOTÍCIAS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

  <!-- REVIEWS -->
  <section class="section section--editoria-reviews section--graphite" aria-labelledby="editoria-reviews-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="1200" y1="140" x2="740" y2="140" stroke="#F4F1EA" stroke-width="1" opacity="0.09"></line>
      <line x1="740" y1="140" x2="740" y2="420" stroke="#F4F1EA" stroke-width="1" opacity="0.09"></line>
      <circle cx="740" cy="420" r="4" fill="#C65A32" opacity="0.55"></circle>
    </svg>
    <div class="container">
      <div class="editoria-cabecalho">
        <h2 id="editoria-reviews-heading" class="section-heading section-heading--on-dark">REVIEWS</h2>
        <p class="solucao__assinatura">O que testamos e analisamos.</p>
      </div>
      <div class="editoria-materias editoria-materias--retrato">
        <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
        <a href="<?php echo esc_url( home_url( '/em-foco/reviews/materia-modelo' ) ); ?>" class="emfoco-card">
          <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
          <span class="emfoco-card__category">Reviews</span>
          <h3 class="emfoco-card__title">[ Título da matéria 0<?php echo (int) $i; ?> ]</h3>
          <span class="emfoco-card__date emfoco-card__date--on-dark">[ Data ]</span>
        </a>
        <?php endfor; ?>
      </div>
      <div class="editoria-cta">
        <a href="<?php echo esc_url( home_url( '/em-foco/reviews' ) ); ?>" class="btn-bracket btn-bracket--on-dark">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER MAIS REVIEWS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

  <!-- GUIAS -->
  <section class="section section--editoria-guias section--offwhite" aria-labelledby="editoria-guias-heading">
    <div class="container editoria-layout--lateral">
      <div class="editoria-bloco-texto">
        <div class="editoria-cabecalho">
          <h2 id="editoria-guias-heading" class="section-heading">GUIAS</h2>
          <p class="solucao__assinatura">O que você precisa <span>entender para decidir.</span></p>
        </div>
        <div class="editoria-cta">
          <a href="<?php echo esc_url( home_url( '/em-foco/guias' ) ); ?>" class="btn-bracket">
            <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER MAIS GUIAS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
          </a>
        </div>
      </div>
      <div class="editoria-bloco-materias">
        <div class="editoria-materias">
          <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
          <a href="<?php echo esc_url( home_url( '/em-foco/guias/materia-modelo' ) ); ?>" class="emfoco-card">
            <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
            <span class="emfoco-card__category">Guias</span>
            <h3 class="emfoco-card__title">[ Título da matéria 0<?php echo (int) $i; ?> ]</h3>
            <span class="emfoco-card__date">[ Data ]</span>
          </a>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- TÉCNICA -->
  <section class="section section--editoria-tecnica section--graphite" aria-labelledby="editoria-tecnica-heading">
    <div class="container editoria-layout--lateral editoria-layout--lateral-reversa">
      <div class="editoria-bloco-texto">
        <div class="editoria-cabecalho">
          <h2 id="editoria-tecnica-heading" class="section-heading section-heading--on-dark">TÉCNICA</h2>
          <p class="solucao__assinatura">Como funciona, <span>como diagnosticar e como reparar.</span></p>
        </div>
        <div class="editoria-cta">
          <a href="<?php echo esc_url( home_url( '/em-foco/tecnica' ) ); ?>" class="btn-bracket btn-bracket--on-dark">
            <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER MAIS TÉCNICA</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
          </a>
        </div>
      </div>
      <div class="editoria-bloco-materias">
        <div class="editoria-materias">
          <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
          <a href="<?php echo esc_url( home_url( '/em-foco/tecnica/materia-modelo' ) ); ?>" class="emfoco-card">
            <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
            <span class="emfoco-card__category">Técnica</span>
            <h3 class="emfoco-card__title">[ Título da matéria 0<?php echo (int) $i; ?> ]</h3>
            <span class="emfoco-card__date emfoco-card__date--on-dark">[ Data ]</span>
          </a>
          <?php endfor; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- BASTIDORES -->
  <section class="section section--editoria-bastidores section--offwhite" aria-labelledby="editoria-bastidores-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="120" x2="1200" y2="120" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="60" cy="820" r="220" fill="none" stroke="#252525" stroke-width="1" opacity="0.07"></circle>
      <circle cx="1140" cy="120" r="4" fill="#C65A32" opacity="0.55"></circle>
    </svg>
    <div class="container">
      <div class="editoria-cabecalho">
        <h2 id="editoria-bastidores-heading" class="section-heading">BASTIDORES</h2>
        <p class="solucao__assinatura">Quem, como <span>e onde o setor acontece.</span></p>
      </div>
      <div class="editoria-materias editoria-materias--paisagem">
        <?php for ( $i = 1; $i <= 2; $i++ ) : ?>
        <a href="<?php echo esc_url( home_url( '/em-foco/bastidores/materia-modelo' ) ); ?>" class="emfoco-card">
          <div class="emfoco-card__media"><img src="<?php echo esc_url( $img ); ?>" alt="" class="emfoco-card__image"></div>
          <span class="emfoco-card__category">Bastidores</span>
          <h3 class="emfoco-card__title">[ Título da matéria 0<?php echo (int) $i; ?> ]</h3>
          <span class="emfoco-card__date">[ Data ]</span>
        </a>
        <?php endfor; ?>
      </div>
      <div class="editoria-cta">
        <a href="<?php echo esc_url( home_url( '/em-foco/bastidores' ) ); ?>" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VER MAIS BASTIDORES</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="emfoco-editorias-youtube">
    <div class="container">
      <a href="#" class="ad-slot__unit">
        <span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
        <span class="ad-slot__eyebrow">Publicidade</span>
        <span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
        <span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
      </a>
    </div>
  </section>

  <section class="section section--youtube section--graphite" aria-labelledby="emfoco-youtube-heading">
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.04</span><span class="section-index__line"></span><span>NO YOUTUBE</span></div>
      <div class="section-heading-row"><h2 id="emfoco-youtube-heading" class="section-heading section-heading--on-dark">NO YOUTUBE</h2></div>
      <div class="youtube-grid">
        <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
        <a href="#" class="video-card" target="_blank" rel="noopener noreferrer" aria-label="Assistir: [ Título do vídeo 0<?php echo (int) $i; ?> ] (abre no YouTube)">
          <div class="video-card__media"><img src="<?php echo esc_url( $vid ); ?>" alt="" class="video-card__image"></div>
          <h3 class="video-card__title">[ Título do vídeo 0<?php echo (int) $i; ?> ]</h3>
          <span class="video-card__date video-card__date--on-dark">[ Data ]</span>
        </a>
        <?php endfor; ?>
      </div>
      <div class="section-cta">
        <a href="<?php echo esc_url( dc_option( 'youtube', '#' ) ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">VEJA TODOS NO YOUTUBE</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
