<?php
/**
 * Template Name: Para Motoristas
 * Reproduz fielmente a página Para Motoristas aprovada da V1.
 */
get_header();
?>

  <!-- BLOCO N.00 — ABERTURA -->
  <section class="section section--abertura section--graphite" aria-labelledby="motoristas-heading">
    <div class="container section--abertura__inner">
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>ABERTURA</span></div>
      <div class="abertura__content">
        <h1 id="motoristas-heading" class="abertura__heading"><?php dc_text( 'dc_motoristas_titulo', 'INFORMAÇÃO PARA QUEM ESTÁ DO LADO DO VOLANTE.' ); ?></h1>
        <?php
        dc_paragraphs_lines( 'dc_motoristas_paragrafos', array(
          'Comprar um carro, escolher uma oficina, entender uma manutenção ou simplesmente saber se aquilo que estão dizendo sobre o seu veículo faz sentido.',
          'Quem dirige toma decisões o tempo todo — e nem sempre tem acesso às informações necessárias para tomar essas decisões com segurança.',
        ), null, 'abertura__paragraph' );
        ?>
        <p class="abertura__paragraph is-forte"><?php dc_text( 'dc_motoristas_forte', 'No DESCOMPLICARRO, transformamos conhecimento técnico em informação clara e útil para ajudar você a entender melhor o seu carro, fazer as perguntas certas e escolher com mais consciência.' ); ?></p>
      </div>
      <div class="abertura__figure">
        <img src="<?php echo esc_url( dc_image_url( 'dc_motoristas_imagem' ) ); ?>" alt="Fotografia editorial de um motorista — imagem oficial a ser adicionada posteriormente" class="abertura__image">
        <span class="abertura__frame-mark abertura__frame-mark--tl"></span>
        <span class="abertura__frame-mark abertura__frame-mark--br"></span>
        <?php dc_caption_html( 'dc_motoristas_imagem_legenda', 'Foto — experiência do motorista', 'figure-caption figure-caption--on-dark' ); ?>
      </div>
    </div>
  </section>

  <!-- BLOCO N.01 — CONHEÇA NOSSAS SOLUÇÕES -->
  <section class="section section--solucoes-nav section--offwhite" aria-labelledby="solucoes-heading">
    <div class="container">
      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>NOSSAS SOLUÇÕES</span></div>
      <div class="section-heading-row"><h2 id="solucoes-heading" class="section-heading">CONHEÇA NOSSAS SOLUÇÕES</h2></div>
      <nav class="solucoes-nav" aria-label="Soluções para motoristas">
        <a href="#match-automotivo" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">MATCH AUTOMOTIVO</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
        <a href="#gi-conecta" class="btn-bracket">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">GI CONECTA</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </nav>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_motoristas_ad1_ativo' ) ) : ?>
  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="motoristas-solucoes-match">
    <div class="container">
      <a href="<?php dc_url( 'dc_motoristas_ad1_url' ); ?>" class="ad-slot__unit">
        <span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
        <span class="ad-slot__eyebrow">Publicidade</span>
        <span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
        <span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
      </a>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO N.02 — MATCH AUTOMOTIVO -->
  <section id="match-automotivo" class="section section--match section--offwhite" aria-labelledby="match-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="120" x2="1200" y2="120" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <line x1="1040" y1="0" x2="1040" y2="900" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="1040" cy="120" r="4" fill="#C65A32" opacity="0.55"></circle>
      <circle cx="60" cy="820" r="220" fill="none" stroke="#252525" stroke-width="1" opacity="0.07"></circle>
    </svg>
    <div class="container">
      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>MATCH AUTOMOTIVO</span></div>
      <div class="perfil">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_match_imagem' ) ); ?>" alt="Identidade visual do MATCH Automotivo — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_match_imagem_legenda', 'Identidade — Match Automotivo' ); ?>
        </div>
        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_match_role', 'Consultoria' ); ?></span>
          <h2 id="match-heading" class="perfil__name"><?php dc_text( 'dc_match_nome', 'Match Automotivo' ); ?></h2>
          <?php
          dc_paragraphs_lines( 'dc_match_texto', array(
            'O MATCH Automotivo é uma consultoria criada para ajudar o motorista a descobrir quais carros realmente combinam com sua vida.',
            'A análise considera 35 variáveis relacionadas ao perfil, rotina, necessidades, preferências e orçamento do cliente.',
            'A partir dessas informações, são indicadas 3 opções de veículos que melhor se encaixam naquele perfil.',
            'A ideia não é simplesmente responder “qual é o melhor carro?”, porque não existe um carro que seja o melhor para todo mundo.',
            'Existe o carro que faz mais sentido para cada estilo de vida.',
          ), null, 'perfil__text' );
          ?>
          <div class="stat-grupo">
            <div class="stat-destaque">
              <span class="stat-destaque__numero"><?php dc_text( 'dc_match_stat1_numero', '35' ); ?></span>
              <span class="stat-destaque__label"><?php dc_text( 'dc_match_stat1_label', 'Variáveis analisadas' ); ?></span>
            </div>
            <div class="stat-destaque">
              <span class="stat-destaque__numero"><?php dc_text( 'dc_match_stat2_numero', '3' ); ?></span>
              <span class="stat-destaque__label"><?php dc_text( 'dc_match_stat2_label', 'Opções indicadas' ); ?></span>
            </div>
          </div>
          <p class="solucao__assinatura"><?php dc_text( 'dc_match_assinatura_parte1', 'O carro certo,' ); ?> <span><?php dc_text( 'dc_match_assinatura_parte2', 'para seu estilo de vida.' ); ?></span></p>
          <div class="solucao__cta">
            <a href="<?php dc_url( 'dc_match_botao_url' ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
              <span class="btn-bracket__bracket" aria-hidden="true">[</span>
              <span class="btn-bracket__label"><?php dc_text( 'dc_match_botao_label', 'QUERO ENCONTRAR MEU MATCH' ); ?></span>
              <span class="btn-bracket__bracket" aria-hidden="true">]</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOCO N.03 — GI CONECTA -->
  <section id="gi-conecta" class="section section--gi-conecta section--graphite" aria-labelledby="gi-conecta-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="1200" y1="140" x2="740" y2="140" stroke="#F4F1EA" stroke-width="1" opacity="0.09"></line>
      <line x1="740" y1="140" x2="740" y2="420" stroke="#F4F1EA" stroke-width="1" opacity="0.09"></line>
      <circle cx="740" cy="420" r="4" fill="#C65A32" opacity="0.55"></circle>
      <circle cx="1140" cy="800" r="220" fill="none" stroke="#F4F1EA" stroke-width="1" opacity="0.08"></circle>
    </svg>
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.03</span><span class="section-index__line"></span><span>GI CONECTA</span></div>
      <div class="perfil perfil--reverse">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_gi_imagem' ) ); ?>" alt="Identidade visual do GI Conecta — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_gi_imagem_legenda', 'Identidade — GI Conecta', 'figure-caption figure-caption--on-dark' ); ?>
        </div>
        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_gi_role', 'Curadoria de oficinas' ); ?></span>
          <h2 id="gi-conecta-heading" class="perfil__name"><?php dc_text( 'dc_gi_nome', 'GI Conecta' ); ?></h2>
          <?php
          dc_paragraphs_lines( 'dc_gi_texto', array(
            'Encontrar uma oficina não deveria depender apenas de indicação, proximidade ou quantidade de estrelas.',
            'O GI CONECTA será uma curadoria de oficinas construída para ajudar motoristas a encontrar empresas que demonstrem cuidado com atendimento, transparência e experiência do cliente.',
            'A proposta não é criar apenas uma lista de oficinas.',
            'É construir uma ferramenta que ajude o motorista a encontrar opções e tomar uma decisão mais consciente sobre onde levar seu carro.',
          ), null, 'perfil__text' );
          ?>
          <div class="destaque-linhas destaque-linhas--on-dark">
            <?php dc_paragraphs_lines( 'dc_gi_destaque', array( 'CURADORIA.', 'INFORMAÇÃO.', 'CONFIANÇA PARA ESCOLHER.' ) ); ?>
          </div>
          <span class="badge-tag">Em desenvolvimento</span>
        </div>
      </div>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_motoristas_ad2_ativo' ) ) : ?>
  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="motoristas-final">
    <div class="container">
      <a href="<?php dc_url( 'dc_motoristas_ad2_url' ); ?>" class="ad-slot__unit">
        <span class="ad-slot__frame-mark ad-slot__frame-mark--tl" aria-hidden="true"></span>
        <span class="ad-slot__eyebrow">Publicidade</span>
        <span class="ad-slot__placeholder-text">PUBLICIDADE — ESPAÇO RESERVADO</span>
        <span class="ad-slot__frame-mark ad-slot__frame-mark--br" aria-hidden="true"></span>
      </a>
    </div>
  </section>
  <?php endif; ?>

<?php get_footer(); ?>
