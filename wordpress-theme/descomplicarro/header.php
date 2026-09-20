<?php
/**
 * COMPONENTE: HEADER / NAVEGAÇÃO PRINCIPAL — global, idêntico em todas as páginas.
 * Reproduz exatamente o header.php previsto nos comentários da V1.
 * O menu vem de wp_nav_menu() (Aparência → Menus, local "Navegação Principal"),
 * então alterações no menu, no logotipo ou em links globais refletem
 * automaticamente em todas as páginas sem editar código.
 */
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

    <?php if ( has_custom_logo() ) : ?>
      <div class="site-logo-wp"><?php the_custom_logo(); ?></div>
    <?php else : ?>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo" aria-label="<?php bloginfo( 'name' ); ?> — página inicial">
        <!-- LOGO OFICIAL: enviar em Configurações Descomplicarro → Identidade
             visual, ou em Personalizar → Identidade do site. Enquanto
             nenhum logotipo for enviado, mantém-se o monograma/wordmark de
             texto aprovado na V1. -->
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
