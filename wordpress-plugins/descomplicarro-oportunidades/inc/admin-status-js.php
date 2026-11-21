<?php
/**
 * Inclui "Em avaliação" (pending), "Suspensa/o" e "Expirada/Encerrada" como
 * opções reais no seletor de status da tela de edição (caixa "Publicar") —
 * por padrão, o WordPress só mostra status customizados na edição em massa.
 * Mesmo mecanismo já usado em cpt-oficinas.php do tema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_footer-post.php', 'dco_admin_status_js' );
add_action( 'admin_footer-post-new.php', 'dco_admin_status_js' );
function dco_admin_status_js() {
	global $post;
	if ( ! $post || ! in_array( $post->post_type, array( 'dco_curriculo', 'dco_vaga' ), true ) ) {
		return;
	}
	$status_atual = $post->post_status;
	$rotulos = array(
		'pending'  => 'Em avaliação',
		'suspensa' => 'Suspensa/o',
		'expirada' => $post->post_type === 'dco_vaga' ? 'Encerrada' : 'Expirado',
	);
	?>
	<script>
	jQuery(function($){
		var statusAtual = <?php echo wp_json_encode( $status_atual ); ?>;
		var rotulos = <?php echo wp_json_encode( $rotulos ); ?>;
		var $select = $('#post_status');
		if (!$select.length) { return; }
		$.each(rotulos, function(valor, rotulo){
			if ($select.find('option[value="' + valor + '"]').length === 0) {
				$select.append($('<option>', { value: valor, text: rotulo }));
			}
		});
		if (rotulos[statusAtual]) {
			$select.val(statusAtual);
			$('#post-status-display').text(rotulos[statusAtual]);
		}
	});
	</script>
	<?php
}
