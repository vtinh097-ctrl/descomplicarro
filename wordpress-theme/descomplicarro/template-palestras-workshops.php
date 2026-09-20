<?php
/**
 * Template Name: Palestras & Workshops
 * Reproduz fielmente a página Palestras & Workshops aprovada da V1.
 */
get_header();

$formatos_padrao = array(
	array( 'label' => 'Palestras' ), array( 'label' => 'Workshops' ), array( 'label' => 'Treinamentos' ),
	array( 'label' => 'Mediação de painéis' ), array( 'label' => 'Apresentação de temas' ),
	array( 'label' => 'Condução de debates' ), array( 'label' => 'Cerimonial' ),
);
$formatos = dc_repeater( 'dc_formatos_lista' );
$formatos = ! empty( $formatos ) ? $formatos : $formatos_padrao;

$temas_padrao = array(
	array( 'titulo' => 'Educação automotiva para motoristas', 'desc' => 'Conhecimento para ajudar pessoas a compreender melhor seus veículos, manutenção, segurança e decisões relacionadas ao carro.' ),
	array( 'titulo' => 'Atendimento e experiência do cliente', 'desc' => 'Conteúdos voltados à relação entre empresas, profissionais e clientes, passando por atendimento, confiança, comunicação, experiência e relacionamento.' ),
	array( 'titulo' => 'Comunicação e tradução técnica', 'desc' => 'Como transformar conhecimento especializado em informação clara, responsável e compreensível — sem perder profundidade técnica.' ),
	array( 'titulo' => 'Conteúdo e tecnologia automotiva', 'desc' => 'Discussões sobre veículos, tecnologias, mercado e comunicação automotiva, conectando conhecimento técnico àquilo que realmente importa para o público.' ),
);
$temas = dc_repeater( 'dc_temas_lista' );
$temas = ! empty( $temas ) ? $temas : $temas_padrao;

$dominium_galeria_padrao = array(
	array( 'imagem' => 0, 'legenda' => 'Foto DOMINIUM 01' ), array( 'imagem' => 0, 'legenda' => 'Foto DOMINIUM 02' ), array( 'imagem' => 0, 'legenda' => 'Foto DOMINIUM 03' ),
);
$dominium_galeria = dc_repeater( 'dc_dominium_galeria' );
$dominium_galeria = ! empty( $dominium_galeria ) ? $dominium_galeria : $dominium_galeria_padrao;

$experiencias_padrao = array(
	array( 'imagem' => 0, 'legenda' => 'Evento 01' ), array( 'imagem' => 0, 'legenda' => 'Evento 02' ),
	array( 'imagem' => 0, 'legenda' => 'Evento 03' ), array( 'imagem' => 0, 'legenda' => 'Evento 04' ),
);
$experiencias = dc_repeater( 'dc_experiencias_galeria' );
$experiencias = ! empty( $experiencias ) ? $experiencias : $experiencias_padrao;

function dc_repeater_image_url( $item, $fallback = 'placeholder-photo.svg' ) {
	$id = isset( $item['imagem'] ) ? (int) $item['imagem'] : 0;
	if ( $id ) {
		$url = wp_get_attachment_image_url( $id, 'large' );
		if ( $url ) {
			return $url;
		}
	}
	return get_template_directory_uri() . '/assets/images/' . $fallback;
}
?>

  <!-- BLOCO N.00 — ABERTURA -->
  <section class="section section--abertura section--graphite" aria-labelledby="palestras-hero-heading">
    <div class="container section--abertura__inner">
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>ABERTURA</span></div>
      <div class="abertura__content">
        <span class="perfil__role">Palestras &amp; Workshops</span>
        <h1 id="palestras-hero-heading" class="abertura__heading"><?php dc_text( 'dc_palestras_titulo', 'CONHECIMENTO TÉCNICO TAMBÉM PODE SER UMA EXPERIÊNCIA.' ); ?></h1>
        <?php
        dc_paragraphs_lines( 'dc_palestras_paragrafos', array(
          'O DESCOMPLICARRO transforma conhecimento técnico, experiência prática e comunicação em conteúdos e experiências construídos para diferentes públicos.',
          'Palestras, workshops, treinamentos, mediações e apresentações podem ser desenvolvidos de acordo com o contexto, o público e os objetivos de cada projeto.',
        ), null, 'abertura__paragraph' );
        ?>
        <p class="abertura__paragraph is-forte"><?php dc_text( 'dc_palestras_forte', 'Mais do que ocupar um palco, queremos fazer a informação chegar de uma forma que possa ser compreendida, questionada e utilizada.' ); ?></p>
      </div>
      <div class="abertura__figure">
        <img src="<?php echo esc_url( dc_image_url( 'dc_palestras_imagem' ) ); ?>" alt="Fotografia editorial de evento/palco — imagem oficial a ser adicionada posteriormente" class="abertura__image">
        <span class="abertura__frame-mark abertura__frame-mark--tl"></span>
        <span class="abertura__frame-mark abertura__frame-mark--br"></span>
        <p class="figure-caption figure-caption--on-dark">Foto — evento / palco</p>
      </div>
    </div>
  </section>

  <!-- BLOCO N.01 — FORMATOS -->
  <section class="section section--formatos section--offwhite" aria-labelledby="formatos-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="120" x2="1200" y2="120" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <line x1="1040" y1="0" x2="1040" y2="900" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="1040" cy="120" r="4" fill="#C65A32" opacity="0.55"></circle>
      <circle cx="60" cy="820" r="220" fill="none" stroke="#252525" stroke-width="1" opacity="0.07"></circle>
    </svg>
    <div class="container">
      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>FORMATOS</span></div>
      <div class="section-heading-row"><h2 id="formatos-heading" class="section-heading"><?php dc_text( 'dc_formatos_heading', 'DIFERENTES FORMAS DE LEVAR CONHECIMENTO PARA O SEU PÚBLICO.' ); ?></h2></div>
      <div class="formatos-grid">
        <?php foreach ( $formatos as $i => $item ) : ?>
        <div class="formato-item"><span class="formato-item__indice"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span class="formato-item__label"><?php echo esc_html( $item['label'] ); ?></span></div>
        <?php endforeach; ?>
      </div>
      <p class="nota-discreta"><?php dc_text( 'dc_formatos_nota', 'Cada projeto pode ser construído conforme a necessidade do contratante.' ); ?></p>
    </div>
  </section>

  <!-- BLOCO N.02 — TEMAS -->
  <section class="section section--temas section--offwhite" aria-labelledby="temas-heading">
    <div class="container">
      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>TEMAS</span></div>
      <div class="section-heading-row"><h2 id="temas-heading" class="section-heading"><?php dc_text( 'dc_temas_heading', 'CONHECIMENTO QUE SE ADAPTA A QUEM ESTÁ OUVINDO.' ); ?></h2></div>
      <div class="bloco-texto__col">
        <?php
        $temas_intro_raw = dc_field_raw( 'dc_temas_intro', '' );
        if ( $temas_intro_raw !== '' ) {
          $linhas = preg_split( '/\r\n|\r|\n/', trim( $temas_intro_raw ) );
          foreach ( $linhas as $idx => $linha ) {
            $linha = trim( $linha );
            if ( $linha === '' ) { continue; }
            echo '<p' . ( $idx === 0 ? ' class="is-lead"' : '' ) . '>' . esc_html( $linha ) . '</p>';
          }
        } else {
          echo '<p class="is-lead">Nem todo público precisa da mesma informação — e nem da mesma forma.</p>';
          echo '<p>As palestras, workshops e treinamentos do DESCOMPLICARRO podem ser construídos a partir de diferentes temas e adaptados ao público, ao contexto e aos objetivos de cada projeto.</p>';
        }
        ?>
      </div>
      <div class="principios-list">
        <p class="principios-list__eyebrow">Territórios de atuação</p>
        <?php foreach ( $temas as $i => $tema ) : ?>
        <div class="principio">
          <span class="principio__index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
          <div>
            <h3 class="principio__title"><?php echo esc_html( $tema['titulo'] ); ?></h3>
            <p class="principio__desc"><?php echo esc_html( $tema['desc'] ); ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <p class="solucao__assinatura"><?php dc_text( 'dc_temas_assinatura_parte1', 'O tema' ); ?> <span><?php dc_text( 'dc_temas_assinatura_parte2', 'não precisa estar nesta lista.' ); ?></span></p>
      <div class="bloco-texto__col"><p><?php dc_text( 'dc_temas_fechamento', 'Projetos personalizados podem ser desenvolvidos de acordo com a necessidade da empresa, evento ou público.' ); ?></p></div>
    </div>
  </section>

  <!-- BLOCO N.03 — DOMINIUM -->
  <section class="section section--dominium section--graphite" aria-labelledby="dominium-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="520" y1="150" x2="760" y2="65" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="760" y1="65" x2="980" y2="135" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="520" cy="150" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="980" cy="135" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="1150" cy="55" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.03</span><span class="section-index__line"></span><span>DOMINIUM</span></div>
      <div class="section-heading-row"><h2 id="dominium-heading" class="section-heading section-heading--on-dark">DOMINIUM</h2></div>
      <div class="manifesto">
        <p class="manifesto__lead"><?php dc_text( 'dc_dominium_lead', 'Mecânica básica para mulheres. Na prática.' ); ?></p>
        <div class="manifesto__body">
          <?php
          dc_paragraphs_lines( 'dc_dominium_texto', array(
            'DOMINIUM é um workshop de educação automotiva criado para mulheres que querem compreender melhor o próprio carro e ter mais autonomia nas decisões do dia a dia.',
            'Uma experiência prática e acessível, que aproxima as participantes do veículo, explica conceitos que muitas vezes parecem complicados e mostra o que toda motorista deveria conhecer para dirigir, cuidar e conversar sobre o próprio carro com mais segurança.',
          ) );
          ?>
        </div>
        <div class="fechamento__detalhe">
          <p><strong>Entender</strong> <?php dc_text( 'dc_dominium_pilar1', 'Conhecer o carro e seus principais sistemas.' ); ?></p>
          <p><strong>Identificar</strong> <?php dc_text( 'dc_dominium_pilar2', 'Perceber sinais, alertas e necessidades básicas.' ); ?></p>
          <p><strong>Decidir</strong> <?php dc_text( 'dc_dominium_pilar3', 'Ter mais informação para agir e fazer perguntas.' ); ?></p>
        </div>
        <div class="destaque-linhas destaque-linhas--on-dark">
          <p>Entender.</p><p>Identificar.</p><p>Decidir.</p>
        </div>
        <div class="galeria">
          <?php foreach ( $dominium_galeria as $item ) : ?>
          <div class="galeria__item">
            <img src="<?php echo esc_url( dc_repeater_image_url( $item ) ); ?>" alt="Registro do workshop DOMINIUM — imagem oficial a ser adicionada posteriormente" class="galeria__image">
            <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
            <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
            <p class="figure-caption figure-caption--on-dark"><?php echo esc_html( $item['legenda'] ); ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="solucao__cta">
          <a href="<?php echo esc_url( dc_whatsapp_link( 'Olá! Conheci o DOMINIUM pelo site do DESCOMPLICARRO e gostaria de saber mais sobre a contratação.' ) ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
            <span class="btn-bracket__bracket" aria-hidden="true">[</span>
            <span class="btn-bracket__label"><?php dc_text( 'dc_dominium_botao_label', 'QUERO LEVAR O DOMINIUM' ); ?></span>
            <span class="btn-bracket__bracket" aria-hidden="true">]</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOCO N.04 — QUEM LEVA O CONTEÚDO -->
  <section class="section section--equipe-palestras section--offwhite" aria-labelledby="equipe-heading">
    <div class="container">
      <div class="section-index"><span>N.04</span><span class="section-index__line"></span><span>QUEM LEVA O CONTEÚDO</span></div>
      <div class="section-heading-row"><h2 id="equipe-heading" class="section-heading"><?php echo wp_kses( dc_field_raw( 'dc_equipe_heading', 'DIFERENTES EXPERIÊNCIAS.<br>DIFERENTES FORMATOS.' ), array( 'br' => array() ) ); ?></h2></div>
      <div class="bloco-texto__col"><p><?php dc_text( 'dc_equipe_intro', 'O DESCOMPLICARRO reúne experiência prática, conhecimento técnico e comunicação para construir entregas adequadas a diferentes públicos e eventos.' ); ?></p></div>

      <div class="perfil perfil--espacado">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_equipe_giovana_imagem' ) ); ?>" alt="Foto de Giovana Toso — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <p class="figure-caption">Foto — Giovana Toso</p>
        </div>
        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_equipe_giovana_role', 'Palestras · Workshops · Treinamentos' ); ?></span>
          <h3 class="perfil__name">Giovana Toso</h3>
          <p class="perfil__text"><?php dc_text( 'dc_equipe_giovana_texto', 'Conteúdos conduzidos a partir de sua experiência prática no setor automotivo, educação automotiva, atendimento, comunicação e tradução técnica.' ); ?></p>
        </div>
      </div>

      <div class="perfil perfil--reverse perfil--espacado">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_equipe_vitor_imagem' ) ); ?>" alt="Foto de Vitor Lima — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <p class="figure-caption">Foto — Vitor Lima</p>
        </div>
        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_equipe_vitor_role', 'Mediação · Apresentação · Cerimonial · Condução de debates' ); ?></span>
          <h3 class="perfil__name">Vitor Lima</h3>
          <p class="perfil__text"><?php dc_text( 'dc_equipe_vitor_texto', 'Engenheiro mecânico com experiência editorial e comunicação automotiva, Vitor pode atuar na mediação de painéis e debates, apresentação de temas, condução de conversas e como mestre de cerimônias em eventos.' ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_experiencias_ativo' ) ) : ?>
  <!-- BLOCO N.05 — JÁ ESTIVEMOS POR AQUI -->
  <section class="section section--experiencias section--offwhite" aria-labelledby="experiencias-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="1200" y1="140" x2="740" y2="140" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <line x1="740" y1="140" x2="740" y2="420" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <circle cx="740" cy="420" r="4" fill="#C65A32" opacity="0.5"></circle>
    </svg>
    <div class="container">
      <div class="section-index"><span>N.05</span><span class="section-index__line"></span><span>JÁ ESTIVEMOS POR AQUI</span></div>
      <div class="section-heading-row"><h2 id="experiencias-heading" class="section-heading">JÁ ESTIVEMOS POR AQUI.</h2></div>
      <div class="galeria">
        <?php foreach ( $experiencias as $item ) : ?>
        <div class="galeria__item">
          <img src="<?php echo esc_url( dc_repeater_image_url( $item ) ); ?>" alt="Registro de evento — imagem oficial a ser adicionada posteriormente" class="galeria__image galeria__image--paisagem">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <p class="figure-caption"><?php echo esc_html( $item['legenda'] ); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO N.06 — CONTATO -->
  <section class="section section--contato-palestras section--graphite" aria-labelledby="contato-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="520" y1="150" x2="760" y2="65" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="760" y1="65" x2="980" y2="135" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="980" y1="135" x2="1150" y2="55" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="1150" cy="55" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.06</span><span class="section-index__line"></span><span>CONTATO</span></div>
      <div class="section-heading-row"><h2 id="contato-heading" class="section-heading section-heading--on-dark"><?php dc_text( 'dc_contato_heading', 'VAMOS CONSTRUIR ESSA ENTREGA JUNTOS?' ); ?></h2></div>
      <?php
      dc_paragraphs_lines( 'dc_contato_texto', array(
        'Conte sobre seu evento, empresa, público e objetivo.',
        'A partir disso, podemos desenvolver o formato e o conteúdo mais adequados para a sua necessidade.',
      ), null, 'abertura__paragraph' );
      ?>
      <div class="solucao__cta">
        <a href="<?php echo esc_url( dc_whatsapp_link( 'Olá! Conheci a área de Palestras & Workshops do DESCOMPLICARRO e gostaria de conversar sobre um projeto.' ) ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label"><?php dc_text( 'dc_contato_botao_label', 'FALE COM O DESCOMPLICARRO' ); ?></span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
