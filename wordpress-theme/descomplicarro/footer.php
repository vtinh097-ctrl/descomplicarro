<?php
/**
 * COMPONENTE: FOOTER — global, idêntico em todas as páginas.
 * Contato (WhatsApp/E-mail), redes sociais e links institucionais vêm de
 * Configurações Descomplicarro (dc_option()) — nunca hardcoded, para que
 * atualizar um dado ali reflita automaticamente aqui.
 */
?>
</main>

<footer class="site-footer" role="contentinfo">
  <div class="container site-footer__inner">

    <div class="site-footer__col" id="contato">
      <h2 class="site-footer__heading">Vamos conversar?</h2>
      <p class="footer-contato__texto">Dúvidas, propostas, parcerias ou projetos? Fale com o DESCOMPLICARRO.</p>
      <div class="footer-contato__ctas">
        <a href="<?php echo esc_url( dc_whatsapp_link( 'Olá! Vim pelo site do DESCOMPLICARRO.' ) ); ?>" class="btn-bracket btn-bracket--on-dark" target="_blank" rel="noopener noreferrer">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label">WHATSAPP</span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
        <a href="<?php echo esc_attr( dc_email_link() ); ?>" class="btn-bracket btn-bracket--on-dark">
          <span class="btn-bracket__bracket" aria-hidden="true">[</span>
          <span class="btn-bracket__label">E-MAIL</span>
          <span class="btn-bracket__bracket" aria-hidden="true">]</span>
        </a>
      </div>
    </div>

    <div class="site-footer__col">
      <h2 class="site-footer__heading">Redes sociais</h2>
      <ul class="site-footer__list site-footer__list--inline">
        <?php
        $redes = array(
          'instagram' => 'Instagram',
          'youtube'   => 'YouTube',
          'linkedin'  => 'LinkedIn',
          'tiktok'    => 'TikTok',
        );
        foreach ( $redes as $key => $label ) :
          $url = dc_option( $key, '' );
          if ( $key === 'tiktok' && $url === '' ) {
            continue; // TikTok só aparece no footer quando cadastrado — não fazia parte da V1 aprovada.
          }
          ?>
          <li><a href="<?php echo $url ? esc_url( $url ) : '#'; ?>"<?php echo $url ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="site-footer__col">
      <h2 class="site-footer__heading">Institucional</h2>
      <ul class="site-footer__list">
        <!-- PÁGINAS LEGAIS: substituir "#" pelas páginas definitivas quando criadas -->
        <li><a href="#">Política de Privacidade</a></li>
        <li><a href="#">Termos de Uso</a></li>
        <li><a href="#">Cookies</a></li>
      </ul>
    </div>

  </div>

  <div class="site-footer__bottom">
    <div class="container">
      <p>© <?php bloginfo( 'name' ); ?> — Todos os direitos reservados.</p>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
