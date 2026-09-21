<?php
/**
 * Template: Home (front-page.php — usado automaticamente pelo WordPress
 * quando esta página é a "página inicial estática", ver inc/activation.php).
 * Reproduz fielmente a Home aprovada da V1. Conteúdo vem de post meta
 * (editável em "Conteúdo — Home" ao editar esta página); estrutura/HTML
 * permanece fixa, igual à V1.
 */
get_header();
?>

  <!-- BLOCO 1 — ABERTURA -->
  <section class="section section--abertura section--graphite" aria-labelledby="abertura-heading">
    <div class="container section--abertura__inner">

      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>ABERTURA</span></div>

      <div class="abertura__content">
        <h1 id="abertura-heading" class="abertura__heading"><?php dc_text( 'dc_home_abertura_titulo', 'Existe um universo automotivo extremamente técnico, complexo e, muitas vezes, pouco acessível.' ); ?></h1>
        <p class="abertura__paragraph"><?php dc_text( 'dc_home_abertura_paragrafo', 'O DESCOMPLICARRO existe para transformar essa complexidade em informação que as pessoas conseguem compreender, questionar e usar para tomar melhores decisões.' ); ?></p>

        <a href="<?php dc_url( 'dc_home_abertura_botao_url', home_url( '/sobre-nos' ) ); ?>" class="btn-bracket btn-bracket--on-dark">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label"><?php dc_text( 'dc_home_abertura_botao_label', 'SAIBA MAIS' ); ?></span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>

      <div class="abertura__figure" aria-hidden="true">
        <img src="<?php echo esc_url( dc_image_url( 'dc_home_abertura_imagem' ) ); ?>" alt="" class="abertura__image">
        <span class="abertura__frame-mark abertura__frame-mark--tl"></span>
        <span class="abertura__frame-mark abertura__frame-mark--br"></span>
      </div>

    </div>
  </section>

  <!-- BLOCO 2 — PORTAS DE ENTRADA -->
  <section class="section section--portas section--offwhite" aria-labelledby="portas-heading">
    <div class="container">

      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>PORTAS DE ENTRADA</span></div>
      <h2 id="portas-heading" class="visually-hidden">Portas de entrada</h2>

      <div class="portas-grid">
        <?php
        $portas_padrao = array(
          1 => array( 'url' => home_url( '/para-motoristas' ), 'titulo' => 'PARA MOTORISTAS', 'desc' => 'Informação e soluções para quem vive o carro no dia a dia.' ),
          2 => array( 'url' => home_url( '/para-oficinas' ), 'titulo' => 'PARA OFICINAS', 'desc' => 'Conhecimento e soluções para profissionais que vivem a rotina da reparação.' ),
          3 => array( 'url' => home_url( '/palestras-workshops' ), 'titulo' => 'PALESTRAS E WORKSHOPS', 'desc' => 'Conhecimento que se transforma em experiência, treinamento e transformação.' ),
          4 => array( 'url' => home_url( '/em-foco' ), 'titulo' => 'EM FOCO', 'desc' => 'Notícias, reviews, dicas e análises para entender o que acontece no universo automotivo.' ),
        );
        foreach ( $portas_padrao as $i => $padrao ) :
          ?>
          <a href="<?php echo esc_url( $padrao['url'] ); ?>" class="porta-card">
            <span class="porta-card__index"><?php echo esc_html( sprintf( '%02d', $i ) ); ?></span>
            <div class="porta-card__media">
              <img src="<?php echo esc_url( dc_image_url( "dc_home_porta{$i}_imagem" ) ); ?>" alt="" class="porta-card__image">
            </div>
            <div class="porta-card__body">
              <h3 class="porta-card__title"><?php dc_text( "dc_home_porta{$i}_titulo", $padrao['titulo'] ); ?></h3>
              <p class="porta-card__desc"><?php dc_text( "dc_home_porta{$i}_desc", $padrao['desc'] ); ?></p>
              <span class="porta-card__arrow" aria-hidden="true">→</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_home_ad1_ativo' ) ) : ?>
  <!-- ESPAÇO PUBLICITÁRIO 01 -->
  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="home-portas-emfoco">
    <div class="container">
      <?php dc_ad_slot_unit( 'dc_home_ad1' ); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO 3 — EM FOCO -->
  <section class="section section--emfoco section--offwhite" aria-labelledby="emfoco-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <circle cx="970" cy="70" r="380" fill="none" stroke="#252525" stroke-width="1.5" opacity="0.09"></circle>
      <line x1="0" y1="410" x2="1200" y2="215" stroke="#252525" stroke-width="1.5" opacity="0.08"></line>
      <line x1="770" y1="0" x2="770" y2="260" stroke="#252525" stroke-width="1.5" opacity="0.08"></line>
      <circle cx="770" cy="215" r="5" fill="#C65A32" opacity="0.6"></circle>
    </svg>
    <div class="container">

      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>SELEÇÃO EDITORIAL</span></div>

      <div class="section-heading-row">
        <h2 id="emfoco-heading" class="section-heading">EM FOCO</h2>
      </div>

      <div class="emfoco-grid">
        <?php
        $emfoco_padrao = array(
          1 => 'PLACEHOLDER — Título da matéria selecionada 01',
          2 => 'PLACEHOLDER — Título da matéria selecionada 02',
          3 => 'PLACEHOLDER — Título da matéria selecionada 03',
        );
        foreach ( $emfoco_padrao as $i => $titulo_padrao ) :
          ?>
          <a href="<?php dc_url( "dc_home_emfoco{$i}_url" ); ?>" class="emfoco-card">
            <div class="emfoco-card__media">
              <img src="<?php echo esc_url( dc_image_url( "dc_home_emfoco{$i}_imagem" ) ); ?>" alt="" class="emfoco-card__image">
            </div>
            <span class="emfoco-card__category"><?php dc_text( "dc_home_emfoco{$i}_categoria", 'PLACEHOLDER — CATEGORIA' ); ?></span>
            <h3 class="emfoco-card__title"><?php dc_text( "dc_home_emfoco{$i}_titulo", $titulo_padrao ); ?></h3>
            <p class="emfoco-card__excerpt"><?php dc_text( "dc_home_emfoco{$i}_resumo", 'PLACEHOLDER — pequena chamada/resumo da matéria a ser substituído pelo conteúdo real.' ); ?></p>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="section-cta">
        <a href="<?php echo esc_url( home_url( '/em-foco' ) ); ?>" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label">VER MAIS</span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>

    </div>
  </section>

  <?php if ( dc_section_active( 'dc_home_ad2_ativo' ) ) : ?>
  <!-- ESPAÇO PUBLICITÁRIO 02 -->
  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="home-emfoco-youtube">
    <div class="container">
      <?php dc_ad_slot_unit( 'dc_home_ad2' ); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO 4 — YOUTUBE -->
  <section class="section section--youtube section--graphite" aria-labelledby="youtube-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="520" y1="150" x2="760" y2="65" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="760" y1="65" x2="980" y2="135" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="980" y1="135" x2="1150" y2="55" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="520" cy="150" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="760" cy="65" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="980" cy="135" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="1150" cy="55" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">

      <div class="section-index section-index--on-dark"><span>N.03</span><span class="section-index__line"></span><span>CANAL OFICIAL</span></div>

      <div class="section-heading-row">
        <h2 id="youtube-heading" class="section-heading section-heading--on-dark">NO YOUTUBE</h2>
      </div>

      <div class="youtube-grid">
        <?php
        $video_padrao = array( 1 => 'PLACEHOLDER — Título do vídeo 01', 2 => 'PLACEHOLDER — Título do vídeo 02', 3 => 'PLACEHOLDER — Título do vídeo 03' );
        foreach ( $video_padrao as $i => $titulo_padrao ) :
          $titulo_atual = dc_field( "dc_home_video{$i}_titulo", $titulo_padrao );
          ?>
          <a href="<?php dc_url( "dc_home_video{$i}_url" ); ?>" class="video-card" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( 'Assistir: ' . $titulo_atual . ' (abre no YouTube)' ); ?>">
            <div class="video-card__media">
              <img src="<?php echo esc_url( dc_image_url( "dc_home_video{$i}_imagem", null, 'placeholder-video.svg' ) ); ?>" alt="" class="video-card__image">
            </div>
            <h3 class="video-card__title"><?php echo esc_html( $titulo_atual ); ?></h3>
            <p class="video-card__excerpt"><?php dc_text( "dc_home_video{$i}_resumo", 'PLACEHOLDER — pequena chamada do vídeo.' ); ?></p>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="section-cta">
        <a href="<?php echo esc_url( dc_field( 'dc_home_youtube_botao_url', dc_option( 'youtube', '#' ) ) ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label">ASSISTA NO YOUTUBE</span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>

    </div>
  </section>

<?php get_footer(); ?>
