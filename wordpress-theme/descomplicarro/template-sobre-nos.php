<?php
/**
 * Template Name: Sobre Nós
 * Reproduz fielmente a página Sobre Nós aprovada da V1. Conteúdo editável
 * em "Conteúdo — Sobre Nós"; estrutura/HTML fixa.
 */
get_header();
?>

  <!-- BLOCO N.00 — ABERTURA INSTITUCIONAL -->
  <section class="section section--abertura section--graphite" aria-labelledby="sobre-heading">
    <div class="container section--abertura__inner">

      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>INSTITUCIONAL</span></div>

      <div class="abertura__content">
        <span class="perfil__role">Sobre nós</span>
        <h1 id="sobre-heading" class="abertura__heading"><?php echo wp_kses( dc_field_raw( 'dc_sobre_abertura_titulo', 'DUAS EXPERIÊNCIAS.<br>UM MESMO PROPÓSITO.' ), array( 'br' => array() ) ); ?></h1>
        <?php
        dc_paragraphs_lines( 'dc_sobre_abertura_paragrafos', array(
          'O DESCOMPLICARRO nasceu do encontro entre a experiência prática de oficina e a visão técnica e editorial do universo automotivo.',
          'De um lado, quem passou anos traduzindo problemas, diagnósticos e decisões para pessoas.',
          'Do outro, quem viveu a engenharia e a comunicação automotiva por dentro.',
        ), null, 'abertura__paragraph' );
        ?>
        <p class="abertura__paragraph is-forte"><?php dc_text( 'dc_sobre_abertura_forte', 'Duas trajetórias diferentes que chegaram à mesma pergunta: por que entender de carro ainda precisa ser tão complicado?' ); ?></p>
      </div>

      <div class="abertura__figure">
        <img src="<?php echo esc_url( dc_image_url( 'dc_sobre_abertura_imagem' ) ); ?>" alt="Foto de Giovana Toso e Vitor Lima juntos — imagem oficial a ser adicionada posteriormente" class="abertura__image">
        <span class="abertura__frame-mark abertura__frame-mark--tl"></span>
        <span class="abertura__frame-mark abertura__frame-mark--br"></span>
        <?php dc_caption_html( 'dc_sobre_abertura_imagem_legenda', 'Foto — Giovana + Vitor', 'figure-caption figure-caption--on-dark' ); ?>
      </div>

    </div>
  </section>

  <!-- BLOCO N.01 — O ENCONTRO QUE VIROU DESCOMPLICARRO -->
  <section class="section section--encontro section--offwhite" aria-labelledby="encontro-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="120" x2="1200" y2="120" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <line x1="1040" y1="0" x2="1040" y2="900" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="1040" cy="120" r="4" fill="#C65A32" opacity="0.55"></circle>
      <circle cx="60" cy="820" r="220" fill="none" stroke="#252525" stroke-width="1" opacity="0.07"></circle>
    </svg>

    <div class="container">

      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>O ENCONTRO</span></div>

      <div class="section-heading-row">
        <h2 id="encontro-heading" class="section-heading"><?php dc_text( 'dc_sobre_encontro_heading', 'O ENCONTRO QUE VIROU DESCOMPLICARRO' ); ?></h2>
      </div>

      <div class="bloco-texto__col">
        <p class="is-lead"><?php dc_text( 'dc_sobre_encontro_lead', 'Giovana e Vitor chegaram ao mesmo universo por caminhos diferentes.' ); ?></p>
        <?php
        dc_paragraphs_lines( 'dc_sobre_encontro_corpo1', array(
          'Ela, pela oficina. Quinze anos de experiência prática, convivendo diariamente com diagnósticos, manutenção, clientes e a necessidade de transformar linguagem técnica em algo que realmente fizesse sentido para quem estava do outro lado.',
          'Ele, pela engenharia mecânica e pelo conteúdo automotivo. Anos acompanhando de perto a produção editorial do setor e percebendo que, muitas vezes, havia um espaço enorme entre a profundidade técnica e a informação que chegava ao público.',
        ) );
        ?>

        <div class="destaque-linhas">
          <p><?php dc_text( 'dc_sobre_encontro_destaque1', 'Na oficina, faltava tradução.' ); ?></p>
          <p><?php dc_text( 'dc_sobre_encontro_destaque2', 'No conteúdo, muitas vezes faltava profundidade.' ); ?></p>
        </div>

        <?php
        $corpo2_default = array(
          'Foi desse encontro que nasceu o DESCOMPLICARRO.',
          'Uma plataforma que une experiência prática, conhecimento técnico e visão editorial para falar sobre o universo automotivo de um jeito que faça sentido para quem dirige, compra, vende, cuida, conserta ou simplesmente precisa tomar uma decisão sobre um carro.',
        );
        $corpo2_raw = dc_field_raw( 'dc_sobre_encontro_corpo2', '' );
        $corpo2 = $corpo2_raw !== '' ? preg_split( '/\r\n|\r|\n/', trim( $corpo2_raw ) ) : $corpo2_default;
        foreach ( $corpo2 as $idx => $linha ) :
          $linha = trim( $linha );
          if ( $linha === '' ) { continue; }
          ?>
          <p<?php echo $idx === 0 ? ' class="is-forte"' : ''; ?>><?php echo esc_html( $linha ); ?></p>
        <?php endforeach; ?>
      </div>

    </div>
  </section>

  <!-- BLOCO N.02 — GIOVANA TOSO -->
  <section class="section section--giovana section--offwhite" aria-labelledby="giovana-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="760" x2="480" y2="760" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <line x1="480" y1="760" x2="480" y2="480" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <circle cx="480" cy="480" r="4" fill="#C65A32" opacity="0.5"></circle>
    </svg>
    <div class="container">

      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>GIOVANA</span></div>

      <div class="perfil">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_sobre_giovana_imagem' ) ); ?>" alt="Foto de Giovana Toso — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_sobre_giovana_imagem_legenda', 'Foto — Giovana Toso' ); ?>
        </div>

        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_sobre_giovana_role', 'Bancada, oficina e atendimento' ); ?></span>
          <h2 id="giovana-heading" class="perfil__name"><?php dc_text( 'dc_sobre_giovana_nome', 'Giovana Toso' ); ?></h2>
          <p class="perfil__text"><?php dc_text( 'dc_sobre_giovana_texto', 'De um lado, Giovana, mecânica, consultora automotiva e comunicadora, com anos de experiência na bancada, na oficina e, principalmente, no contato direto com quem precisa entender o próprio carro. Sua trajetória foi construída entre diagnóstico, atendimento, gestão de problemas e a busca constante por uma forma mais clara de traduzir o conhecimento técnico.' ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOCO N.03 — VITOR -->
  <section class="section section--vitor section--offwhite" aria-labelledby="vitor-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="1200" y1="140" x2="740" y2="140" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <line x1="740" y1="140" x2="740" y2="420" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <circle cx="740" cy="420" r="4" fill="#C65A32" opacity="0.5"></circle>
    </svg>
    <div class="container">

      <div class="section-index"><span>N.03</span><span class="section-index__line"></span><span>VITOR</span></div>

      <div class="perfil perfil--reverse">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_sobre_vitor_imagem' ) ); ?>" alt="Foto de Vitor Lima — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_sobre_vitor_imagem_legenda', 'Foto — Vitor' ); ?>
        </div>

        <div class="perfil__body">
          <span class="perfil__role"><?php dc_text( 'dc_sobre_vitor_role', 'Engenharia e mercado editorial automotivo' ); ?></span>
          <h2 id="vitor-heading" class="perfil__name"><?php dc_text( 'dc_sobre_vitor_nome', 'Vitor Lima' ); ?></h2>
          <p class="perfil__text"><?php dc_text( 'dc_sobre_vitor_texto', 'Do outro, Vitor, engenheiro mecânico com experiência no mercado editorial automotivo, onde atuou por anos em contato com jornalistas, fabricantes, profissionais e as diferentes formas de comunicar o setor. Nesse caminho, percebeu também uma lacuna: muito conteúdo falava sobre carros, mas nem sempre ajudava o público a compreender de fato o que estava por trás deles.' ); ?></p>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOCO N.04 — MANIFESTO -->
  <section class="section section--manifesto section--graphite" aria-labelledby="manifesto-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="150" y1="600" x2="380" y2="520" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="380" y1="520" x2="600" y2="590" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="150" cy="600" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="380" cy="520" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="600" cy="590" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">

      <div class="section-index section-index--on-dark"><span>N.04</span><span class="section-index__line"></span><span>MANIFESTO</span></div>

      <div class="section-heading-row">
        <h2 id="manifesto-heading" class="section-heading section-heading--on-dark">MANIFESTO</h2>
      </div>

      <div class="manifesto">
        <p class="manifesto__lead"><?php dc_text( 'dc_sobre_manifesto_lead', 'Conhecimento só transforma quando pode ser compreendido.' ); ?></p>

        <div class="manifesto__body">
          <?php
          dc_paragraphs_lines( 'dc_sobre_manifesto_corpo1', array(
            'Acreditamos que profundidade e clareza podem caminhar juntas.',
            'Que falar de tecnologia não precisa significar falar difícil.',
            'Que simplificar não é retirar informação até que ela perca o sentido. É encontrar a forma certa de fazê-la chegar a quem precisa dela.',
          ) );
          ?>
        </div>

        <div class="destaque-linhas destaque-linhas--on-dark">
          <p><?php dc_text( 'dc_sobre_manifesto_destaque1', 'Acreditamos em conteúdo que vai além da superfície.' ); ?></p>
          <p><?php dc_text( 'dc_sobre_manifesto_destaque2', 'Em conhecimento técnico conectado à vida real.' ); ?></p>
          <p><?php dc_text( 'dc_sobre_manifesto_destaque3', 'Em experiência que ajuda a fazer perguntas melhores.' ); ?></p>
        </div>

        <div class="manifesto__body">
          <?php
          $corpo2_raw = dc_field_raw( 'dc_sobre_manifesto_corpo2', '' );
          if ( $corpo2_raw !== '' ) {
            dc_paragraphs_lines( 'dc_sobre_manifesto_corpo2', array() );
          } else {
            echo '<p>Porque informação não deveria servir apenas para informar.</p>';
            echo '<p>Deveria ajudar a compreender.<br>Questionar.<br>E decidir.</p>';
          }
          ?>
        </div>

        <p class="manifesto__signature">DESCOMPLICARRO.<br><span>ENTENDA. QUESTIONE. DECIDA.</span></p>
      </div>

    </div>
  </section>

  <!-- BLOCO N.05 — NOSSO JEITO DE FAZER -->
  <section class="section section--jeito section--offwhite" aria-labelledby="jeito-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="900" y1="0" x2="900" y2="900" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <line x1="900" y1="60" x2="1200" y2="60" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="900" cy="60" r="4" fill="#C65A32" opacity="0.5"></circle>
    </svg>
    <div class="container">

      <div class="section-index"><span>N.05</span><span class="section-index__line"></span><span>NOSSO JEITO DE FAZER</span></div>

      <div class="section-heading-row">
        <h2 id="jeito-heading" class="section-heading">NOSSO JEITO DE FAZER</h2>
      </div>

      <div class="jeito-intro">
        <div class="jeito-intro__item">
          <h3>Missão</h3>
          <?php
          dc_paragraphs_lines( 'dc_sobre_missao', array(
            'Transformar conhecimento técnico em informação clara, confiável e útil para ajudar pessoas e empresas a compreender, questionar e tomar melhores decisões no universo automotivo.',
            'O DESCOMPLICARRO conecta experiência prática, conhecimento técnico e visão editorial para criar conteúdos, soluções e experiências que aproximam o conhecimento de quem precisa dele.',
            'Nosso compromisso é levar informação com profundidade, mas sem barreiras desnecessárias; simplificar a compreensão, sem simplificar o conteúdo; e transformar informação em conhecimento que possa ser aplicado na vida real.',
          ) );
          ?>
        </div>
        <div class="jeito-intro__item">
          <h3>Visão</h3>
          <?php
          dc_paragraphs_lines( 'dc_sobre_visao', array(
            'Ser uma referência em informação automotiva acessível, confiável e relevante, aproximando conhecimento técnico das pessoas e contribuindo para decisões mais conscientes em toda a cadeia automotiva.',
            'Queremos construir um espaço reconhecido pela qualidade da informação, pela profundidade dos conteúdos e pela capacidade de transformar assuntos complexos em conhecimento que faça sentido na prática.',
            'Mais do que acompanhar as transformações do setor, queremos participar delas, ampliando o acesso ao conhecimento, estimulando perguntas melhores e ajudando a construir uma relação mais consciente entre pessoas, veículos, profissionais e empresas.',
          ) );
          ?>
        </div>
      </div>

      <div class="principios-list">
        <p class="principios-list__eyebrow">Valores</p>

        <?php
        $valores_padrao = array(
          array( 'titulo' => 'Clareza sem superficialidade', 'desc' => 'Acreditamos que tornar uma informação compreensível não significa reduzir sua profundidade. Explicamos de forma acessível, sem abrir mão da precisão.' ),
          array( 'titulo' => 'Conhecimento que faz sentido', 'desc' => 'Informação só tem valor quando pode ser compreendida, aplicada e relacionada à realidade. Por isso, buscamos sempre conectar teoria, prática e contexto.' ),
          array( 'titulo' => 'Curiosidade para questionar', 'desc' => 'Não nos limitamos ao que é apresentado. Investigamos, perguntamos, comparamos e buscamos entender o que está por trás de cada informação.' ),
          array( 'titulo' => 'Responsabilidade com a informação', 'desc' => 'Conhecimento gera impacto e influencia decisões. Por isso, tratamos informação com critério, transparência e responsabilidade.' ),
          array( 'titulo' => 'Experiência antes do discurso', 'desc' => 'Valorizamos aquilo que acontece na prática. Experiência de oficina, engenharia, mercado, atendimento, pesquisa e comunicação se complementam para construir conteúdos mais completos.' ),
          array( 'titulo' => 'Acessibilidade sem elitismo', 'desc' => 'Conhecimento técnico não deve ser privilégio de quem já domina o assunto. Queremos aproximar pessoas da informação, criando pontes em vez de barreiras.' ),
          array( 'titulo' => 'Evolução constante', 'desc' => 'O setor automotivo muda, a tecnologia evolui e novas perguntas surgem. Estamos sempre aprendendo, revisando certezas e buscando formas melhores de fazer e comunicar.' ),
        );
        $valores = dc_repeater( 'dc_sobre_valores' );
        $valores = ! empty( $valores ) ? $valores : $valores_padrao;
        foreach ( $valores as $i => $valor ) :
          ?>
          <div class="principio">
            <span class="principio__index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
            <div>
              <h3 class="principio__title"><?php echo esc_html( $valor['titulo'] ); ?></h3>
              <p class="principio__desc"><?php echo esc_html( $valor['desc'] ); ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <p class="jeito-assinatura">Entenda. Questione. <span>Decida.</span></p>

    </div>
  </section>

  <!-- BLOCO N.06 — FECHAMENTO -->
  <section class="section section--fechamento section--graphite" aria-labelledby="fechamento-heading">
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

      <div class="section-index section-index--on-dark"><span>N.06</span><span class="section-index__line"></span><span>FECHAMENTO</span></div>

      <p class="fechamento__intro"><?php dc_text( 'dc_sobre_fechamento_intro', 'Essa é a forma como enxergamos o conhecimento.' ); ?></p>

      <h2 id="fechamento-heading" class="fechamento__assinatura">
        <span>Entenda.</span> <span>Questione.</span> <span>Decida.</span>
      </h2>

      <div class="fechamento__detalhe">
        <p><strong>Entenda</strong> <?php dc_text( 'dc_sobre_fechamento_detalhe1', 'o que está por trás da informação. Não apenas o que um carro tem, mas como funciona, por que existe e o que aquilo significa na prática.' ); ?></p>
        <p><strong>Questione</strong> <?php dc_text( 'dc_sobre_fechamento_detalhe2', 'o que é apresentado. Compare informações, faça perguntas, procure contexto e não aceite respostas prontas como verdade absoluta.' ); ?></p>
        <p><strong>Decida</strong> <?php dc_text( 'dc_sobre_fechamento_detalhe3', 'com mais consciência. Use o conhecimento para escolher, comprar, cuidar, consertar, trabalhar e se relacionar melhor com o universo automotivo.' ); ?></p>
      </div>

      <div class="fechamento__final">
        <p><?php dc_text( 'dc_sobre_fechamento_final1', 'Porque nosso objetivo nunca foi simplesmente entregar respostas.' ); ?></p>
        <p class="is-forte"><?php dc_text( 'dc_sobre_fechamento_final2', 'É dar às pessoas conhecimento suficiente para que elas possam fazer perguntas melhores e tomar decisões melhores.' ); ?></p>
      </div>

      <div class="acessos-grid">
        <a href="<?php echo esc_url( home_url( '/para-motoristas' ) ); ?>" class="porta-card">
          <span class="porta-card__index">01</span>
          <div class="porta-card__body">
            <h3 class="porta-card__title">PARA MOTORISTAS</h3>
            <p class="porta-card__desc">Informação e soluções para quem vive o carro no dia a dia.</p>
            <span class="porta-card__arrow" aria-hidden="true">→</span>
          </div>
        </a>
        <a href="<?php echo esc_url( home_url( '/para-oficinas' ) ); ?>" class="porta-card">
          <span class="porta-card__index">02</span>
          <div class="porta-card__body">
            <h3 class="porta-card__title">PARA OFICINAS</h3>
            <p class="porta-card__desc">Conhecimento e soluções para profissionais que vivem a rotina da reparação.</p>
            <span class="porta-card__arrow" aria-hidden="true">→</span>
          </div>
        </a>
        <a href="<?php echo esc_url( home_url( '/em-foco' ) ); ?>" class="porta-card">
          <span class="porta-card__index">03</span>
          <div class="porta-card__body">
            <h3 class="porta-card__title">EM FOCO</h3>
            <p class="porta-card__desc">Notícias, reviews, dicas e análises para entender o que acontece no universo automotivo.</p>
            <span class="porta-card__arrow" aria-hidden="true">→</span>
          </div>
        </a>
      </div>

    </div>
  </section>

<?php get_footer(); ?>
