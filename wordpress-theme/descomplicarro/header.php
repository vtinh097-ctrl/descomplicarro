<?php
/**
 * COMPONENTE: HEADER / NAVEGAÇÃO PRINCIPAL — global, idêntico em todas as páginas.
 * Reproduz exatamente o header.php previsto nos comentários da V1.
 * O menu vem de wp_nav_menu() (Aparência → Menus, local "Navegação Principal"),
 * então alterações no menu, no logotipo ou em links globais refletem
 * automaticamente em todas as páginas sem editar código.
 *
 * Logotipo: lido exclusivamente de Configurações Descomplicarro → Identidade
 * visual → Logotipo principal (dc_option('logo_principal'), um ID de anexo da
 * Biblioteca de Mídia). Não usa o "Logo" nativo do Personalizar do WordPress
 * — são mecanismos diferentes; o campo do painel do DESCOMPLICARRO é o único
 * caminho de substituição. Sem logotipo cadastrado, mantém o monograma +
 * wordmark de texto aprovados na V1.
 */
$dc_logo_id  = (int) dc_option( 'logo_principal', 0 );
$dc_logo_src = $dc_logo_id ? wp_get_attachment_image_src( $dc_logo_id, 'full' ) : false;
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#conteudo-principal">Ir para o conteúdo principal</a>

<header class="site-header" id="topo">
  <div class="container site-header__inner">

    <?php if ( $dc_logo_src ) : ?>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo site-logo--custom" aria-label="<?php bloginfo( 'name' ); ?> — página inicial">
        <img
          src="<?php echo esc_url( $dc_logo_src[0] ); ?>"
          alt="<?php bloginfo( 'name' ); ?>"
          class="site-logo__custom-image"
          <?php if ( $dc_logo_src[1] && $dc_logo_src[2] ) : ?>
          width="<?php echo esc_attr( $dc_logo_src[1] ); ?>"
          height="<?php echo esc_attr( $dc_logo_src[2] ); ?>"
          <?php endif; ?>
        >
      </a>
    <?php else : ?>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" aria-label="<?php bloginfo( 'name' ); ?> — página inicial">
        <!-- LOGO OFICIAL: enviar em Configurações Descomplicarro → Identidade
             visual → Logotipo principal. Enquanto nenhum logotipo for
             enviado, mantém-se o monograma/wordmark de texto aprovado na V1. -->
        <span class="site-logo__mark" aria-hidden="true"></span>
        <span class="site-logo__text">
          <span class="site-logo__word"><?php bloginfo( 'name' ); ?></span>
          <span class="site-logo__tagline">Entenda. Questione. Decida.</span>
        </span>
      </a>
    <?php endif; ?>

    <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav">
      <span class="nav-toggle__box">
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
        <span class="nav-toggle__bar"></span>
      </span>
      <span class="nav-toggle__label">Menu</span>
    </button>

    <nav class="site-nav" id="siteNav" aria-label="Navegação principal">
      <?php
      if ( has_nav_menu( 'principal' ) ) {
        wp_nav_menu( array(
          'theme_location' => 'principal',
          'container'      => false,
          'items_wrap'     => '<ul class="site-nav__list">%3$s</ul>',
          'walker'         => new DC_Nav_Walker(),
        ) );
      } else {
        dc_fallback_menu();
      }
      ?>
    </nav>

  </div>
</header>

<main id="conteudo-principal">
