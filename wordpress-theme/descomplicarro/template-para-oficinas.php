<?php
/**
 * Template Name: Para Oficinas
 * Reproduz fielmente a página Para Oficinas aprovada da V1.
 */
get_header();

$formacao_padrao = array(
	array( 'jornada_tag' => 'Evidenciar', 'titulo' => 'Checklist Vendedor', 'conceito' => 'Inspeção que gera evidências.',
		'texto' => 'Um treinamento para ensinar o mecânico a realizar um checklist rápido e assertivo e utilizar recursos visuais e ferramentas lúdicas para produzir evidências e vídeos que ajudem o consultor no processo comercial.',
		'destaque1' => 'O mecânico não precisa vender.', 'destaque2' => 'Precisa entregar boas evidências para quem vende.',
		'jornada_visual' => '', 'assinatura_parte1' => '', 'assinatura_parte2' => '',
		'botao_label' => 'QUERO CONHECER O CHECKLIST VENDEDOR', 'botao_url' => '' ),
	array( 'jornada_tag' => 'Traduzir e vender', 'titulo' => 'Método T3V', 'conceito' => 'Teoria. Técnica. Tradução. Venda.',
		'texto' => 'Um método voltado ao consultor, para ajudá-lo a compreender a informação técnica, traduzi-la para uma linguagem que o cliente consiga entender e conduzir a apresentação dos serviços de forma mais clara e estruturada.',
		'destaque1' => '', 'destaque2' => '', 'jornada_visual' => "Teoria\nTécnica\nTradução\nVenda", 'assinatura_parte1' => '', 'assinatura_parte2' => '',
		'botao_label' => 'QUERO CONHECER O T3V', 'botao_url' => '' ),
	array( 'jornada_tag' => 'Atender', 'titulo' => 'Método MAIS', 'conceito' => 'Atendimento a mulheres.',
		'texto' => 'Uma metodologia para compreender melhor a cliente mulher, suas expectativas e sua relação com o ambiente automotivo, ajudando oficinas a construir uma experiência de atendimento baseada em clareza, respeito e confiança.',
		'destaque1' => '', 'destaque2' => '', 'jornada_visual' => '', 'assinatura_parte1' => '', 'assinatura_parte2' => '',
		'botao_label' => 'QUERO CONHECER O MÉTODO MAIS', 'botao_url' => '' ),
	array( 'jornada_tag' => 'Relacionar e reter', 'titulo' => 'Depois da Entrega', 'conceito' => 'O pós-venda que mantém o cliente perto da oficina.',
		'texto' => 'Um treinamento sobre o que acontece depois que o veículo é entregue: acompanhamento, relacionamento, experiência e processos que ajudam a aumentar a retenção e fazer com que bons clientes continuem voltando.',
		'destaque1' => '', 'destaque2' => '', 'jornada_visual' => '', 'assinatura_parte1' => 'O relacionamento com o cliente', 'assinatura_parte2' => 'não termina quando o carro sai da oficina.',
		'botao_label' => 'QUERO CONHECER DEPOIS DA ENTREGA', 'botao_url' => '' ),
);
$formacao = dc_repeater( 'dc_oficinas_formacao' );
$formacao = ! empty( $formacao ) ? $formacao : $formacao_padrao;

$ml_padrao = array(
	array( 'titulo' => 'ML Repertório', 'desc' => 'Aprendizado e aplicação. Conteúdo para ampliar repertório e transformar conhecimento em aplicação na rotina.', 'botao_url' => '' ),
	array( 'titulo' => 'ML Tração', 'desc' => 'Orientação e troca em grupo. Desenvolvimento coletivo com orientação, discussão e troca de experiências entre profissionais.', 'botao_url' => '' ),
	array( 'titulo' => 'ML Comando', 'desc' => 'Acompanhamento individual. Desenvolvimento mais próximo e direcionado aos desafios e necessidades de cada oficina.', 'botao_url' => '' ),
);
$ml_acessos = dc_repeater( 'dc_ml_acessos' );
$ml_acessos = ! empty( $ml_acessos ) ? $ml_acessos : $ml_padrao;
?>

  <!-- BLOCO N.00 — ABERTURA -->
  <section class="section section--abertura section--graphite" aria-labelledby="oficinas-heading">
    <div class="container section--abertura__inner">
      <div class="section-index" aria-hidden="true"><span>N.00</span><span class="section-index__line"></span><span>ABERTURA</span></div>
      <div class="abertura__content">
        <span class="perfil__role">Para oficinas</span>
        <h1 id="oficinas-heading" class="abertura__heading"><?php dc_text( 'dc_oficinas_titulo', 'CONHECIMENTO PARA OFICINAS QUE QUEREM IR ALÉM DO REPARO.' ); ?></h1>
        <?php
        dc_paragraphs_lines( 'dc_oficinas_paragrafos', array(
          'Uma oficina não entrega apenas um serviço técnico. Ela precisa diagnosticar, registrar, comunicar, orientar, gerar confiança e construir relacionamentos que façam o cliente querer voltar.',
          'No DESCOMPLICARRO, acreditamos que conhecimento técnico, processos e comunicação podem transformar a forma como uma oficina trabalha — e também a forma como ela é percebida pelo cliente.',
        ), null, 'abertura__paragraph' );
        ?>
        <p class="abertura__paragraph is-forte"><?php dc_text( 'dc_oficinas_forte', 'Por isso, desenvolvemos conteúdos, treinamentos e ferramentas para profissionais e empresas que querem aprimorar sua operação, seu atendimento e a maneira como entregam valor.' ); ?></p>
      </div>
      <div class="abertura__figure">
        <img src="<?php echo esc_url( dc_image_url( 'dc_oficinas_imagem' ) ); ?>" alt="Fotografia editorial de ambiente de oficina — imagem oficial a ser adicionada posteriormente" class="abertura__image">
        <span class="abertura__frame-mark abertura__frame-mark--tl"></span>
        <span class="abertura__frame-mark abertura__frame-mark--br"></span>
        <?php dc_caption_html( 'dc_oficinas_imagem_legenda', 'Foto — ambiente de oficina', 'figure-caption figure-caption--on-dark' ); ?>
      </div>
    </div>
  </section>

  <!-- BLOCO N.01 — DA ENTRADA AO PÓS-VENDA -->
  <section class="section section--jornada-oficinas section--offwhite" aria-labelledby="jornada-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="0" y1="120" x2="1200" y2="120" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <line x1="1040" y1="0" x2="1040" y2="900" stroke="#252525" stroke-width="1" opacity="0.06"></line>
      <circle cx="1040" cy="120" r="4" fill="#C65A32" opacity="0.55"></circle>
      <circle cx="60" cy="820" r="220" fill="none" stroke="#252525" stroke-width="1" opacity="0.07"></circle>
    </svg>
    <div class="container">
      <div class="section-index"><span>N.01</span><span class="section-index__line"></span><span>DA ENTRADA AO PÓS-VENDA</span></div>
      <div class="section-heading-row"><h2 id="jornada-heading" class="section-heading"><?php dc_text( 'dc_oficinas_jornada_heading', 'UM BOM SERVIÇO PASSA POR MUITO MAIS DO QUE O ELEVADOR.' ); ?></h2></div>
      <div class="bloco-texto__col">
        <?php
        dc_paragraphs_lines( 'dc_oficinas_jornada_texto', array(
          'Cada etapa da jornada influencia a próxima. Da forma como o cliente é recebido à maneira como o diagnóstico é registrado, apresentado e acompanhado depois da entrega.',
          'Por isso, nossas soluções atuam em diferentes pontos dessa jornada.',
        ) );
        ?>
      </div>
      <div class="jornada" aria-label="Jornada do atendimento ao pós-venda">
        <?php
        $etapas_padrao = array( 'Atendimento', 'Checklist', 'Evidências', 'Tradução', 'Venda', 'Entrega', 'Pós-venda', 'Retenção' );
        $etapas_raw = dc_field_raw( 'dc_oficinas_jornada_etapas', '' );
        $etapas = $etapas_raw !== '' ? array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $etapas_raw ) ) ) : $etapas_padrao;
        $etapas = array_values( $etapas );
        $total  = count( $etapas );
        foreach ( $etapas as $i => $etapa ) :
          ?>
          <span class="jornada__etapa"><span class="jornada__indice"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span class="jornada__label"><?php echo esc_html( $etapa ); ?></span></span>
          <?php if ( $i < $total - 1 ) : ?>
            <span class="jornada__seta<?php echo $i === 4 ? ' jornada__seta--intervencao' : ''; ?>" aria-hidden="true">→</span>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- BLOCO N.02 — FORMAÇÃO -->
  <section class="section section--formacao section--offwhite" aria-labelledby="formacao-heading">
    <div class="container">
      <div class="section-index"><span>N.02</span><span class="section-index__line"></span><span>FORMAÇÃO</span></div>
      <div class="section-heading-row"><h2 id="formacao-heading" class="section-heading">FORMAÇÃO</h2></div>
      <div class="formacao">
        <?php foreach ( $formacao as $i => $item ) : ?>
        <div class="formacao-item">
          <div class="formacao-item__head">
            <span class="formacao-item__indice"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
            <span class="formacao-item__jornada-tag"><?php echo esc_html( $item['jornada_tag'] ); ?></span>
          </div>
          <span class="perfil__role">Treinamento Descomplicarro</span>
          <h3 class="perfil__name"><?php echo esc_html( $item['titulo'] ); ?></h3>
          <p class="formacao-item__conceito"><?php echo esc_html( $item['conceito'] ); ?></p>
          <div class="bloco-texto__col"><p><?php echo esc_html( $item['texto'] ); ?></p></div>
          <?php if ( ! empty( $item['jornada_visual'] ) ) :
            $etapas_metodo = array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $item['jornada_visual'] ) ) ) );
            ?>
          <div class="jornada jornada--compacta" aria-label="<?php echo esc_attr( $item['titulo'] ); ?>">
            <?php foreach ( $etapas_metodo as $ei => $etapa ) : ?>
              <span class="jornada__etapa"><span class="jornada__label"><?php echo esc_html( $etapa ); ?></span></span>
              <?php if ( $ei < count( $etapas_metodo ) - 1 ) : ?><span class="jornada__seta" aria-hidden="true">→</span><?php endif; ?>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          <?php if ( ! empty( $item['destaque1'] ) || ! empty( $item['destaque2'] ) ) : ?>
          <div class="destaque-linhas">
            <?php if ( ! empty( $item['destaque1'] ) ) : ?><p><?php echo esc_html( $item['destaque1'] ); ?></p><?php endif; ?>
            <?php if ( ! empty( $item['destaque2'] ) ) : ?><p><?php echo esc_html( $item['destaque2'] ); ?></p><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if ( ! empty( $item['assinatura_parte1'] ) || ! empty( $item['assinatura_parte2'] ) ) : ?>
          <p class="solucao__assinatura"><?php echo esc_html( $item['assinatura_parte1'] ?? '' ); ?> <span><?php echo esc_html( $item['assinatura_parte2'] ?? '' ); ?></span></p>
          <?php endif; ?>
          <div class="solucao__cta">
            <a href="<?php echo esc_url( $item['botao_url'] ? $item['botao_url'] : '#' ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
              <span class="btn-bracket__bracket" aria-hidden="true">[</span>
              <span class="btn-bracket__label"><?php echo esc_html( $item['botao_label'] ); ?></span>
              <span class="btn-bracket__bracket" aria-hidden="true">]</span>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_oficinas_ad_ativo' ) ) : ?>
  <section class="ad-slot" aria-label="Espaço publicitário" data-ad-slot="oficinas-formacao">
    <div class="container">
      <?php dc_ad_slot_unit( 'dc_oficinas_ad' ); ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO N.03 — PACK DE PAPELARIA -->
  <section class="section section--pack section--graphite" aria-labelledby="pack-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="520" y1="150" x2="760" y2="65" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="760" y1="65" x2="980" y2="135" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="520" cy="150" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="980" cy="135" r="3" fill="#F4F1EA" opacity="0.28"></circle>
      <circle cx="1150" cy="55" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.03</span><span class="section-index__line"></span><span>FERRAMENTAS PARA A ROTINA</span></div>
      <div class="section-heading-row"><h2 id="pack-heading" class="section-heading section-heading--on-dark">PACK DE PAPELARIA PARA OFICINAS</h2></div>
      <div class="manifesto">
        <p class="manifesto__lead"><?php dc_text( 'dc_pack_lead', 'Conhecimento que vira processo.' ); ?></p>
        <div class="manifesto__body">
          <?php
          dc_paragraphs_lines( 'dc_pack_texto', array(
            'Um conjunto de materiais desenvolvidos para facilitar e organizar diferentes etapas da rotina da oficina.',
            'Além dos materiais operacionais, o pack poderá reunir workbooks dos treinamentos do DESCOMPLICARRO vendidos separadamente. Os cursos completos possuem seus próprios materiais didáticos — aqui, o foco é a comercialização dos workbooks e ferramentas práticas separadamente.',
          ) );
          ?>
        </div>
        <div class="pack-tags">
          <?php
          $itens_padrao = array( 'Check-in', 'Check-out', 'Checklists', 'Atendimento', 'Pós-venda', 'Workbooks', 'Ferramentas de processo' );
          $itens_raw = dc_field_raw( 'dc_pack_itens', '' );
          $itens = $itens_raw !== '' ? array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $itens_raw ) ) ) : $itens_padrao;
          foreach ( $itens as $item ) :
            ?>
            <span class="pack-tags__item"><?php echo esc_html( $item ); ?></span>
          <?php endforeach; ?>
        </div>
        <div class="solucao__cta">
          <a href="<?php dc_url( 'dc_pack_botao_url' ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
            <span class="btn-bracket__bracket" aria-hidden="true">[</span>
            <span class="btn-bracket__label"><?php dc_text( 'dc_pack_botao_label', 'QUERO CONHECER O PACK' ); ?></span>
            <span class="btn-bracket__bracket" aria-hidden="true">]</span>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- BLOCO N.04 — GI CONECTA (credenciamento) -->
  <section class="section section--gi-conecta-oficinas section--offwhite" aria-labelledby="gi-conecta-oficinas-heading">
    <div class="container">
      <div class="section-index"><span>N.04</span><span class="section-index__line"></span><span>GI CONECTA</span></div>
      <div class="section-heading-row"><h2 id="gi-conecta-oficinas-heading" class="section-heading">SUA OFICINA NO GI CONECTA</h2></div>
      <div class="bloco-texto__col">
        <?php
        dc_paragraphs_lines( 'dc_oficinas_gi_texto', array(
          'O GI CONECTA está sendo desenvolvido para aproximar motoristas de oficinas que valorizam atendimento, transparência e uma boa experiência para o cliente.',
          'Se sua oficina acredita nesses mesmos princípios, você poderá solicitar a participação no processo de credenciamento.',
        ) );
        ?>
      </div>
      <div class="solucao__cta">
        <a href="<?php dc_url( 'dc_oficinas_gi_botao_url' ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label"><?php dc_text( 'dc_oficinas_gi_botao_label', 'QUERO ME CREDENCIAR' ); ?></span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
      <p class="nota-discreta">Credenciamento sujeito aos critérios de curadoria do GI Conecta.</p>
    </div>
  </section>

  <?php if ( dc_section_active( 'dc_parceiros_ativo' ) ) : ?>
  <!-- BLOCO N.05 — PARCEIROS EM QUE ACREDITAMOS -->
  <section class="section section--parceiros section--offwhite" aria-labelledby="parceiros-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="1200" y1="140" x2="740" y2="140" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <line x1="740" y1="140" x2="740" y2="420" stroke="#252525" stroke-width="1" opacity="0.07"></line>
      <circle cx="740" cy="420" r="4" fill="#C65A32" opacity="0.5"></circle>
    </svg>
    <div class="container">
      <div class="section-index"><span>N.05</span><span class="section-index__line"></span><span>PARCEIROS EM QUE ACREDITAMOS</span></div>
      <div class="section-heading-row"><h2 id="parceiros-heading" class="section-heading">PARCEIROS EM QUE ACREDITAMOS</h2></div>
      <div class="bloco-texto__col">
        <p class="is-lead">Boas soluções também fazem parte de uma oficina melhor.</p>
        <?php dc_paragraph( 'dc_parceiros_texto', 'O DESCOMPLICARRO também abre espaço para empresas, ferramentas e projetos de terceiros que conhecemos e acreditamos que podem contribuir com o desenvolvimento das oficinas.' ); ?>
      </div>
      <p class="nota-discreta">As soluções apresentadas nesta seção são de empresas parceiras e não são produtos do DESCOMPLICARRO.</p>

      <div class="perfil perfil--espacado">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_qrcar_imagem' ) ); ?>" alt="Logo do QR Car — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_qrcar_imagem_legenda', 'Logo — QR Car' ); ?>
        </div>
        <div class="perfil__body">
          <span class="badge-tag badge-tag--on-light">Parceiro Descomplicarro</span>
          <h3 class="perfil__name">QR Car</h3>
          <p class="perfil__text"><?php dc_text( 'dc_qrcar_texto', '[ TEXTO OFICIAL QR CAR — INSERIR ]' ); ?></p>
          <div class="solucao__cta">
            <a href="<?php dc_url( 'dc_qrcar_url' ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
              <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">CONHEÇA O QR CAR</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
            </a>
          </div>
        </div>
      </div>

      <div class="perfil perfil--reverse perfil--espacado">
        <div class="perfil__figure">
          <img src="<?php echo esc_url( dc_image_url( 'dc_ml_imagem' ) ); ?>" alt="Logo do Mecânico Que Lucra — imagem oficial a ser adicionada posteriormente" class="perfil__image">
          <span class="perfil__frame-mark perfil__frame-mark--tl" aria-hidden="true"></span>
          <span class="perfil__frame-mark perfil__frame-mark--br" aria-hidden="true"></span>
          <?php dc_caption_html( 'dc_ml_imagem_legenda', 'Logo — Mecânico Que Lucra' ); ?>
        </div>
        <div class="perfil__body">
          <span class="badge-tag badge-tag--on-light">Parceiro Descomplicarro</span>
          <h3 class="perfil__name">Mecânico Que Lucra</h3>
          <p class="solucao__assinatura">Uma marca. <span>Três formas de avançar.</span></p>
        </div>
      </div>

      <div class="avancar-grid">
        <?php foreach ( $ml_acessos as $i => $item ) : ?>
        <div class="avancar-item">
          <span class="avancar-item__index"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
          <h4 class="avancar-item__title"><?php echo esc_html( $item['titulo'] ); ?></h4>
          <p class="avancar-item__desc"><?php echo esc_html( $item['desc'] ); ?></p>
          <div class="avancar-item__cta">
            <a href="<?php echo esc_url( $item['botao_url'] ? $item['botao_url'] : '#' ); ?>" class="btn-bracket" target="_blank" rel="noopener noreferrer">
              <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">CONHEÇA</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- BLOCO N.06 — PALESTRAS & WORKSHOPS (ponte) -->
  <section class="section section--palestras-bridge section--graphite" aria-labelledby="palestras-heading">
    <svg class="section-bg-graphic" viewBox="0 0 1200 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <line x1="520" y1="150" x2="760" y2="65" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="760" y1="65" x2="980" y2="135" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <line x1="980" y1="135" x2="1150" y2="55" stroke="#F4F1EA" stroke-width="1.5" opacity="0.1"></line>
      <circle cx="1150" cy="55" r="5" fill="#C65A32" opacity="0.7"></circle>
    </svg>
    <div class="container">
      <div class="section-index section-index--on-dark"><span>N.06</span><span class="section-index__line"></span><span>PALESTRAS &amp; WORKSHOPS</span></div>
      <div class="section-heading-row"><h2 id="palestras-heading" class="section-heading section-heading--on-dark">QUER LEVAR ESSE CONHECIMENTO PARA DENTRO DA SUA EMPRESA?</h2></div>
      <p class="abertura__paragraph">O DESCOMPLICARRO também desenvolve palestras, workshops, treinamentos e experiências personalizadas para empresas e equipes do setor automotivo.</p>
      <div class="solucao__cta">
        <a href="<?php echo esc_url( home_url( '/palestras-workshops' ) ); ?>" class="btn-bracket btn-bracket--on-dark">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span><span class="btn-bracket__label">CONHEÇA PALESTRAS &amp; WORKSHOPS</span><span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>
  </section>

<?php get_footer(); ?>
