<?php
/**
 * DESCOMPLICARRO — Métricas e Integrações (GA4, GTM, Search Console).
 *
 * Nada aqui carrega enquanto os identificadores não forem cadastrados em
 * Configurações Descomplicarro → Métricas e Integrações (ver
 * inc/options-page.php) E a caixa "Ativar GA4/GTM/GSC no site" estiver
 * marcada — por padrão, as duas coisas vêm desligadas.
 *
 * CONSENTIMENTO — O site hoje não tem nenhum banner de aceite de cookies
 * (só um link para a Política de Cookies). Em vez de carregar o GTM/GA4 sem
 * nenhum controle de consentimento, esta implementação usa o Consent Mode
 * v2 do Google: antes de qualquer tag carregar, o navegador recebe um sinal
 * "consentimento negado" para analytics e anúncios (ver dc_consent_mode_js()
 * abaixo). O GTM carrega (o contêiner em si não grava cookies de
 * identificação), mas a tag do GA4 só processa dados de forma limitada/
 * modelada (sem cookies, sem identificar o visitante) até que o site
 * implemente uma forma real de perguntar e guardar a escolha do visitante
 * (gtag('consent','update',...)) — isso é indicado como pendência no
 * relatório de entrega, não foi implementado aqui porque mexe no mecanismo
 * de consentimento, que pede autorização separada antes de ser alterado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dc_metricas_ativo() {
	return (int) dc_option( 'metricas_ativo', 0 ) === 1;
}

function dc_ga4_id() {
	return dc_metricas_ativo() ? dc_option( 'ga4_id', '' ) : '';
}

function dc_gtm_id() {
	return dc_metricas_ativo() ? dc_option( 'gtm_id', '' ) : '';
}

/** Sinal padrão de consentimento negado (Consent Mode v2) — sempre impresso antes de qualquer tag, mesmo que GA4/GTM estejam desligados, para já deixar o comportamento correto preparado. */
function dc_consent_mode_js() {
	?>
	<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){ dataLayer.push(arguments); }
	gtag('consent', 'default', {
		'ad_storage': 'denied',
		'ad_user_data': 'denied',
		'ad_personalization': 'denied',
		'analytics_storage': 'denied'
	});
	</script>
	<?php
}

add_action( 'wp_head', 'dc_metricas_head', 1 );
function dc_metricas_head() {
	// Verificação do Search Console — independe de GA4/GTM estarem ativos.
	$gsc = dc_option( 'gsc_verificacao', '' );
	if ( $gsc ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}

	$gtm_id = dc_gtm_id();
	$ga4_id = dc_ga4_id();
	if ( ! $gtm_id && ! $ga4_id ) {
		return;
	}

	dc_consent_mode_js();

	if ( $gtm_id ) {
		?>
		<script>
		(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');
		</script>
		<?php
	}

	// Carregar gtag do GA4 diretamente só faz sentido quando NÃO há GTM
	// cuidando dele (ver instruções de entrega sobre evitar duplicidade).
	if ( $ga4_id && ! $gtm_id ) {
		?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $ga4_id ); ?>"></script>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){ dataLayer.push(arguments); }
		gtag('js', new Date());
		gtag('config', '<?php echo esc_js( $ga4_id ); ?>');
		</script>
		<?php
	}
}

add_action( 'wp_body_open', 'dc_metricas_body_open', 1 );
function dc_metricas_body_open() {
	$gtm_id = dc_gtm_id();
	if ( ! $gtm_id ) {
		return;
	}
	?>
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<?php
}
