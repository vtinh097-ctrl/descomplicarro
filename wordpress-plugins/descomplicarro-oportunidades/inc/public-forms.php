<?php
/**
 * DESCOMPLICARRO Oportunidades — formulários públicos (autocadastro,
 * confirmação por e-mail, gestão por link mágico, solicitar contato,
 * candidatar-se e denúncia), todos reunidos num único shortcode
 * [dco_oportunidades], que o TEMA insere numa página comum — o tema
 * continua cuidando da apresentação (header/footer/estilo da página), o
 * plugin só do conteúdo funcional de dentro dela.
 *
 * E-MAIL: usa wp_mail() nativo do WordPress. Em hospedagem compartilhada
 * (caso da Hostinger) a entrega nativa costuma ser pouco confiável — a
 * recomendação de um serviço de envio dedicado é uma aprovação pendente à
 * parte (ver plano técnico); nada foi contratado.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'dco_oportunidades', 'dco_shortcode_oportunidades' );
function dco_shortcode_oportunidades( $atts ) {
	ob_start();
	$acao = isset( $_GET['dco_acao'] ) ? sanitize_key( wp_unslash( $_GET['dco_acao'] ) ) : '';
	echo '<div class="dco-oportunidades">';
	switch ( $acao ) {
		case 'cadastrar_curriculo':
			dco_view_cadastrar_curriculo();
			break;
		case 'cadastrar_vaga':
			dco_view_cadastrar_vaga();
			break;
		case 'confirmar':
			dco_view_confirmar();
			break;
		case 'gerenciar':
			dco_view_gerenciar();
			break;
		case 'solicitar_gestao':
			dco_view_solicitar_gestao();
			break;
		case 'solicitar_contato':
			dco_view_solicitar_contato();
			break;
		case 'denunciar':
			dco_view_denunciar();
			break;
		default:
			dco_view_painel_inicial();
			break;
	}
	echo '</div>';
	return ob_get_clean();
}

add_action( 'wp_enqueue_scripts', 'dco_enqueue_public_assets' );
function dco_enqueue_public_assets() {
	if ( ! is_singular() || ! has_shortcode( get_post()->post_content, 'dco_oportunidades' ) ) {
		return;
	}
	// Não assume que o tema ativo carrega Space Grotesk/DM Sans (o plugin
	// precisa continuar com a identidade certa mesmo com outro tema) — se o
	// tema DESCOMPLICARRO já carregou (handle "google-fonts-descomplicarro"),
	// o navegador só reaproveita o arquivo já em cache, sem pedir de novo.
	wp_enqueue_style(
		'descomplicarro-oportunidades-fonts',
		'https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=DM+Sans:wght@400;500;700&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'descomplicarro-oportunidades-public', DCO_PLUGIN_URL . 'assets/css/public.css', array(), DCO_VERSION );
	wp_enqueue_script( 'descomplicarro-oportunidades-public', DCO_PLUGIN_URL . 'assets/js/public.js', array(), DCO_VERSION, true );
	wp_localize_script( 'descomplicarro-oportunidades-public', 'dcoPublico', array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
	) );
}

/* ==========================================================================
   PAINEL INICIAL — links de cadastro + busca
   ========================================================================== */
function dco_view_painel_inicial() {
	echo '<p class="dco-acoes">';
	echo '<a class="dco-btn" href="' . esc_url( add_query_arg( 'dco_acao', 'cadastrar_curriculo' ) ) . '">Cadastrar meu currículo</a> ';
	echo '<a class="dco-btn" href="' . esc_url( add_query_arg( 'dco_acao', 'cadastrar_vaga' ) ) . '">Publicar uma vaga</a> ';
	echo '<a class="dco-btn dco-btn--secundario" href="' . esc_url( add_query_arg( 'dco_acao', 'solicitar_gestao' ) ) . '">Já tenho cadastro — gerenciar</a>';
	echo '</p>';
	dco_renderizar_busca_vagas();
	dco_renderizar_busca_curriculos();
}

/* ==========================================================================
   CADASTRAR CURRÍCULO
   ========================================================================== */
function dco_view_cadastrar_curriculo() {
	$erros   = array();
	$sucesso = false;

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['dco_form'] ) && $_POST['dco_form'] === 'cadastrar_curriculo' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_cadastrar_curriculo' ) ) {
			$erros[] = 'Sessão expirada, tente novamente.';
		} elseif ( ! dco_campo_isca_valido() ) {
			$erros[] = 'Não foi possível processar seu envio. Tente novamente.';
		} elseif ( dco_limite_por_ip_excedido( 'cadastrar_curriculo' ) ) {
			$erros[] = 'Muitos envios recentes deste endereço. Tente novamente mais tarde.';
		} else {
			$nome     = sanitize_text_field( wp_unslash( $_POST['dco_nome'] ?? '' ) );
			$cargo    = sanitize_text_field( wp_unslash( $_POST['dco_cargo'] ?? '' ) );
			$estado   = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ?? '' ) );
			$cidade   = sanitize_text_field( wp_unslash( $_POST['dco_cidade'] ?? '' ) );
			$telefone = sanitize_text_field( wp_unslash( $_POST['dco_telefone'] ?? '' ) );
			$email    = sanitize_email( wp_unslash( $_POST['dco_email'] ?? '' ) );
			$resumo   = sanitize_textarea_field( wp_unslash( $_POST['dco_resumo'] ?? '' ) );
			$especialidades = sanitize_text_field( wp_unslash( $_POST['dco_especialidades'] ?? '' ) );
			$aceite   = ! empty( $_POST['dco_aceite'] );

			if ( ! $nome ) {
				$erros[] = 'Informe seu nome.';
			}
			if ( ! $cargo ) {
				$erros[] = 'Informe o cargo ou função desejada.';
			}
			if ( ! array_key_exists( $estado, dco_uf_list() ) ) {
				$erros[] = 'Selecione um estado válido.';
			} elseif ( ! $cidade || ! dco_cidade_valida( $estado, $cidade ) ) {
				$erros[] = 'Selecione uma cidade válida do estado escolhido.';
			}
			if ( ! $email || ! is_email( $email ) ) {
				$erros[] = 'Informe um e-mail válido.';
			}
			if ( ! $aceite ) {
				$erros[] = 'É necessário aceitar a Política de Privacidade e os Termos de Uso.';
			}

			$nome_arquivo_pdf = null;
			if ( empty( $erros ) ) {
				$resultado = dco_processar_upload_pdf( $_FILES['dco_curriculo_pdf_arquivo'] ?? null );
				if ( is_wp_error( $resultado ) ) {
					$erros[] = $resultado->get_error_message();
				} else {
					$nome_arquivo_pdf = $resultado;
				}
			}

			if ( empty( $erros ) ) {
				$post_id = wp_insert_post( array(
					'post_type'   => 'dco_curriculo',
					'post_title'  => $nome,
					'post_status' => 'pending',
				), true );
				if ( is_wp_error( $post_id ) ) {
					dco_excluir_pdf_protegido( $nome_arquivo_pdf );
					$erros[] = 'Não foi possível salvar seu cadastro. Tente novamente.';
				} else {
					update_post_meta( $post_id, 'dco_cargo', $cargo );
					update_post_meta( $post_id, 'dco_estado', $estado );
					update_post_meta( $post_id, 'dco_cidade', $cidade );
					if ( $telefone ) {
						update_post_meta( $post_id, 'dco_telefone', $telefone );
					}
					update_post_meta( $post_id, 'dco_email', $email );
					if ( $resumo ) {
						update_post_meta( $post_id, 'dco_resumo', $resumo );
					}
					if ( $especialidades ) {
						update_post_meta( $post_id, 'dco_especialidades', $especialidades );
					}
					update_post_meta( $post_id, 'dco_curriculo_pdf', $nome_arquivo_pdf );
					dco_email_confirmacao_curriculo( $post_id );
					$sucesso = true;
				}
			}
		}
	}

	if ( $sucesso ) {
		echo '<div class="dco-aviso dco-aviso--sucesso"><p>Cadastro recebido! Enviamos um e-mail de confirmação — clique no link para publicar seu currículo.</p></div>';
		return;
	}

	dco_exibir_erros( $erros );
	?>
	<h3>Cadastrar meu currículo</h3>
	<form method="post" enctype="multipart/form-data" class="dco-form">
		<input type="hidden" name="dco_form" value="cadastrar_curriculo">
		<?php wp_nonce_field( 'dco_cadastrar_curriculo', 'dco_nonce' ); ?>
		<?php dco_campo_isca_html(); ?>
		<p><label>Nome completo<br><input type="text" name="dco_nome" required></label></p>
		<p><label>Cargo / função desejada<br><input type="text" name="dco_cargo" required></label></p>
		<p><label>Estado<br>
			<select name="dco_estado" id="dco_estado_curriculo" required>
				<option value="">— Selecione —</option>
				<?php foreach ( dco_uf_list() as $uf => $nome_uf ) : ?>
					<option value="<?php echo esc_attr( $uf ); ?>"><?php echo esc_html( $uf . ' — ' . $nome_uf ); ?></option>
				<?php endforeach; ?>
			</select>
		</label></p>
		<p><label>Cidade<br>
			<select name="dco_cidade" id="dco_cidade_curriculo" required>
				<option value="">Selecione o estado primeiro</option>
			</select>
		</label></p>
		<p><label>Telefone / WhatsApp<br><input type="text" name="dco_telefone"></label></p>
		<p><label>E-mail<br><input type="email" name="dco_email" required></label></p>
		<p><label>Resumo profissional (opcional)<br><textarea name="dco_resumo" rows="3"></textarea></label></p>
		<p><label>Especialidades (opcional, separadas por vírgula)<br><input type="text" name="dco_especialidades"></label></p>
		<p class="dco-aviso-privacidade">Na busca pública mostramos só o cargo, a localização, o resumo e as especialidades acima — seu nome, telefone, e-mail e o PDF nunca aparecem publicamente.</p>
		<p><label>Currículo em PDF (até 5 MB)<br><input type="file" name="dco_curriculo_pdf_arquivo" accept="application/pdf" required></label></p>
		<p><label><input type="checkbox" name="dco_aceite" value="1"> Li e aceito a <a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>" target="_blank" rel="noopener">Política de Privacidade</a> e os <a href="<?php echo esc_url( home_url( '/termos-de-uso/' ) ); ?>" target="_blank" rel="noopener">Termos de Uso</a>.</label></p>
		<p><button type="submit">Enviar cadastro</button></p>
	</form>
	<?php
}

/* ==========================================================================
   CADASTRAR VAGA
   ========================================================================== */
function dco_view_cadastrar_vaga() {
	$erros   = array();
	$sucesso = false;

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['dco_form'] ) && $_POST['dco_form'] === 'cadastrar_vaga' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_cadastrar_vaga' ) ) {
			$erros[] = 'Sessão expirada, tente novamente.';
		} elseif ( ! dco_campo_isca_valido() ) {
			$erros[] = 'Não foi possível processar seu envio. Tente novamente.';
		} elseif ( dco_limite_por_ip_excedido( 'cadastrar_vaga' ) ) {
			$erros[] = 'Muitos envios recentes deste endereço. Tente novamente mais tarde.';
		} else {
			$empresa    = sanitize_text_field( wp_unslash( $_POST['dco_empresa'] ?? '' ) );
			$cargo      = sanitize_text_field( wp_unslash( $_POST['dco_cargo'] ?? '' ) );
			$estado     = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ?? '' ) );
			$cidade     = sanitize_text_field( wp_unslash( $_POST['dco_cidade'] ?? '' ) );
			$descricao  = sanitize_textarea_field( wp_unslash( $_POST['dco_descricao'] ?? '' ) );
			$requisitos = sanitize_textarea_field( wp_unslash( $_POST['dco_requisitos'] ?? '' ) );
			$beneficios = sanitize_textarea_field( wp_unslash( $_POST['dco_beneficios'] ?? '' ) );
			$email      = sanitize_email( wp_unslash( $_POST['dco_email_candidatura'] ?? '' ) );
			$aceite     = ! empty( $_POST['dco_aceite'] );

			if ( ! $empresa ) {
				$erros[] = 'Informe o nome da empresa.';
			}
			if ( ! $cargo ) {
				$erros[] = 'Informe o cargo da vaga.';
			}
			if ( ! array_key_exists( $estado, dco_uf_list() ) ) {
				$erros[] = 'Selecione um estado válido.';
			} elseif ( ! $cidade || ! dco_cidade_valida( $estado, $cidade ) ) {
				$erros[] = 'Selecione uma cidade válida do estado escolhido.';
			}
			if ( ! $descricao ) {
				$erros[] = 'Descreva a vaga.';
			}
			if ( ! $email || ! is_email( $email ) ) {
				$erros[] = 'Informe um e-mail de candidatura válido.';
			}
			if ( ! $aceite ) {
				$erros[] = 'É necessário aceitar a Política de Privacidade e os Termos de Uso.';
			}

			if ( empty( $erros ) ) {
				$post_id = wp_insert_post( array(
					'post_type'   => 'dco_vaga',
					'post_title'  => $cargo . ' — ' . $empresa,
					'post_status' => 'pending',
				), true );
				if ( is_wp_error( $post_id ) ) {
					$erros[] = 'Não foi possível salvar a vaga. Tente novamente.';
				} else {
					update_post_meta( $post_id, 'dco_empresa', $empresa );
					update_post_meta( $post_id, 'dco_cargo', $cargo );
					update_post_meta( $post_id, 'dco_estado', $estado );
					update_post_meta( $post_id, 'dco_cidade', $cidade );
					update_post_meta( $post_id, 'dco_descricao', $descricao );
					update_post_meta( $post_id, 'dco_requisitos', $requisitos );
					update_post_meta( $post_id, 'dco_beneficios', $beneficios );
					update_post_meta( $post_id, 'dco_email_candidatura', $email );
					dco_email_confirmacao_vaga( $post_id );
					$sucesso = true;
				}
			}
		}
	}

	if ( $sucesso ) {
		echo '<div class="dco-aviso dco-aviso--sucesso"><p>Vaga recebida! Enviamos um e-mail de confirmação — clique no link para publicá-la.</p></div>';
		return;
	}

	dco_exibir_erros( $erros );
	?>
	<h3>Publicar uma vaga</h3>
	<form method="post" class="dco-form">
		<input type="hidden" name="dco_form" value="cadastrar_vaga">
		<?php wp_nonce_field( 'dco_cadastrar_vaga', 'dco_nonce' ); ?>
		<?php dco_campo_isca_html(); ?>
		<p><label>Empresa<br><input type="text" name="dco_empresa" required></label></p>
		<p><label>Cargo<br><input type="text" name="dco_cargo" required></label></p>
		<p><label>Estado<br>
			<select name="dco_estado" id="dco_estado_vaga" required>
				<option value="">— Selecione —</option>
				<?php foreach ( dco_uf_list() as $uf => $nome_uf ) : ?>
					<option value="<?php echo esc_attr( $uf ); ?>"><?php echo esc_html( $uf . ' — ' . $nome_uf ); ?></option>
				<?php endforeach; ?>
			</select>
		</label></p>
		<p><label>Cidade<br>
			<select name="dco_cidade" id="dco_cidade_vaga" required>
				<option value="">Selecione o estado primeiro</option>
			</select>
		</label></p>
		<p><label>Descrição da vaga<br><textarea name="dco_descricao" rows="4" required></textarea></label></p>
		<p><label>Requisitos<br><textarea name="dco_requisitos" rows="3"></textarea></label></p>
		<p><label>Benefícios<br><textarea name="dco_beneficios" rows="3"></textarea></label></p>
		<p><label>E-mail para candidatura<br><input type="email" name="dco_email_candidatura" required></label></p>
		<p><label><input type="checkbox" name="dco_aceite" value="1"> Li e aceito a <a href="<?php echo esc_url( home_url( '/politica-de-privacidade/' ) ); ?>" target="_blank" rel="noopener">Política de Privacidade</a> e os <a href="<?php echo esc_url( home_url( '/termos-de-uso/' ) ); ?>" target="_blank" rel="noopener">Termos de Uso</a>.</label></p>
		<p><button type="submit">Enviar vaga</button></p>
	</form>
	<?php
}

/* ==========================================================================
   CONFIRMAR (link de e-mail, uso único)
   ========================================================================== */
function dco_view_confirmar() {
	$tipo      = isset( $_GET['dco_tipo'] ) ? sanitize_key( wp_unslash( $_GET['dco_tipo'] ) ) : '';
	$id        = isset( $_GET['dco_id'] ) ? (int) $_GET['dco_id'] : 0;
	$token     = isset( $_GET['dco_token'] ) ? sanitize_text_field( wp_unslash( $_GET['dco_token'] ) ) : '';
	$tipo_post = $tipo === 'vaga' ? 'dco_vaga' : 'dco_curriculo';

	if ( ! $id || ! $token || get_post_type( $id ) !== $tipo_post ) {
		echo '<p>Link de confirmação inválido.</p>';
		return;
	}
	if ( ! dco_token_valido( $id, 'confirmacao', $token ) ) {
		echo '<p>Este link de confirmação é inválido ou já expirou. Se precisar, cadastre-se novamente.</p>';
		return;
	}

	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	update_post_meta( $id, 'dco_email_confirmado', 1 );
	if ( ! get_post_meta( $id, 'dco_expira_em', true ) ) {
		$prazo = $tipo_post === 'dco_vaga' ? '+30 days' : '+12 months';
		update_post_meta( $id, 'dco_expira_em', gmdate( 'Y-m-d', strtotime( $prazo ) ) );
	}
	dco_invalidar_token( $id, 'confirmacao' );
	dco_email_link_gestao( $id, $tipo_post === 'dco_vaga' ? 'vaga' : 'curriculo' );

	echo '<div class="dco-aviso dco-aviso--sucesso"><p>Cadastro confirmado e publicado! Também enviamos por e-mail um link para gerenciar seu cadastro (editar, renovar ou excluir) — ele funciona uma única vez e expira em 30 minutos; se precisar depois, é só solicitar um novo link a qualquer momento.</p></div>';
}

/* ==========================================================================
   GERENCIAR (link de e-mail, uso múltiplo dentro da validade)
   ========================================================================== */
function dco_view_gerenciar() {
	// O POST tem prioridade sobre a URL: o formulário de gestão sempre
	// embute o token MAIS RECENTE (renovado a cada carregamento — ver
	// abaixo) no corpo do POST, mas a URL da própria página (inalterada
	// desde o link original do e-mail) ainda carrega o token ANTIGO na
	// query string. Sem essa prioridade, o token já consumido da URL
	// venceria o novo token do formulário e toda ação dentro da mesma
	// visita falharia logo na primeira tentativa.
	$tipo = isset( $_POST['dco_tipo'] ) ? sanitize_key( wp_unslash( $_POST['dco_tipo'] ) ) : ( isset( $_GET['dco_tipo'] ) ? sanitize_key( wp_unslash( $_GET['dco_tipo'] ) ) : '' );
	$id   = isset( $_POST['dco_id'] ) ? (int) $_POST['dco_id'] : ( isset( $_GET['dco_id'] ) ? (int) $_GET['dco_id'] : 0 );
	$token = isset( $_POST['dco_token'] ) ? sanitize_text_field( wp_unslash( $_POST['dco_token'] ) ) : ( isset( $_GET['dco_token'] ) ? sanitize_text_field( wp_unslash( $_GET['dco_token'] ) ) : '' );
	$tipo_post = $tipo === 'vaga' ? 'dco_vaga' : 'dco_curriculo';

	// Validação do token: SEMPRE contra o $id desta mesma requisição — nunca
	// uma ação afeta um post diferente do que o link/token autoriza.
	if ( ! $id || ! $token || get_post_type( $id ) !== $tipo_post || ! dco_token_valido( $id, 'gestao', $token ) ) {
		dco_exibir_formulario_solicitar_gestao( 'Este link de gestão é inválido, já foi usado ou expirou (os links de gestão valem uma única vez, por 30 minutos). Solicite um novo abaixo.' );
		return;
	}

	// Token válido: consome-o e emite um novo no lugar (uso único, 30
	// minutos) — $token passa a valer o NOVO, embutido no formulário abaixo
	// para as próximas ações dentro desta mesma visita, sem precisar de um
	// novo e-mail a cada clique.
	$token = dco_renovar_token_gestao( $id );

	$mensagem = '';
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_gerenciar_' . $id ) ) {
			$mensagem = 'Sessão expirada — recarregue a página pelo link do seu e-mail.';
		} else {
			$acao_gestao = isset( $_POST['dco_acao_gestao'] ) ? sanitize_key( wp_unslash( $_POST['dco_acao_gestao'] ) ) : '';

			if ( $acao_gestao === 'excluir' ) {
				if ( ! empty( $_POST['dco_confirma_exclusao'] ) ) {
					wp_delete_post( $id, true ); // exclusão definitiva: dispara before_delete_post, que remove SÓ o PDF deste post.
					echo '<div class="dco-aviso dco-aviso--sucesso"><p>Seu cadastro e o arquivo associado foram excluídos definitivamente.</p></div>';
					return;
				}
				$mensagem = 'Marque a caixa de confirmação para excluir definitivamente.';
			} elseif ( $acao_gestao === 'renovar' ) {
				$prazo = $tipo_post === 'dco_vaga' ? '+30 days' : '+12 months';
				update_post_meta( $id, 'dco_expira_em', gmdate( 'Y-m-d', strtotime( $prazo ) ) );
				if ( get_post_status( $id ) === 'expirada' ) {
					wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
				}
				$mensagem = 'Cadastro renovado com sucesso.';
			} elseif ( $acao_gestao === 'salvar' ) {
				if ( $tipo_post === 'dco_curriculo' ) {
					dco_salvar_campos_texto( $id, array( 'dco_cargo', 'dco_telefone', 'dco_especialidades' ) );
					dco_salvar_campos_textarea( $id, array( 'dco_resumo' ) );
					$estado = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ?? '' ) );
					$cidade = sanitize_text_field( wp_unslash( $_POST['dco_cidade'] ?? '' ) );
					if ( array_key_exists( $estado, dco_uf_list() ) && dco_cidade_valida( $estado, $cidade ) ) {
						update_post_meta( $id, 'dco_estado', $estado );
						update_post_meta( $id, 'dco_cidade', $cidade );
					}
					if ( ! empty( $_FILES['dco_curriculo_pdf_arquivo']['name'] ) ) {
						$resultado = dco_processar_upload_pdf( $_FILES['dco_curriculo_pdf_arquivo'] );
						if ( is_wp_error( $resultado ) ) {
							$mensagem = $resultado->get_error_message();
						} else {
							$anterior = get_post_meta( $id, 'dco_curriculo_pdf', true );
							update_post_meta( $id, 'dco_curriculo_pdf', $resultado );
							if ( $anterior && $anterior !== $resultado ) {
								dco_excluir_pdf_protegido( $anterior );
							}
						}
					}
				} else {
					dco_salvar_campos_texto( $id, array( 'dco_empresa', 'dco_cargo' ) );
					dco_salvar_campos_textarea( $id, array( 'dco_descricao', 'dco_requisitos', 'dco_beneficios' ) );
					$estado = sanitize_text_field( wp_unslash( $_POST['dco_estado'] ?? '' ) );
					$cidade = sanitize_text_field( wp_unslash( $_POST['dco_cidade'] ?? '' ) );
					if ( array_key_exists( $estado, dco_uf_list() ) && dco_cidade_valida( $estado, $cidade ) ) {
						update_post_meta( $id, 'dco_estado', $estado );
						update_post_meta( $id, 'dco_cidade', $cidade );
					}
				}
				if ( ! $mensagem ) {
					$mensagem = 'Alterações salvas com sucesso.';
				}
			}
		}
	}

	dco_exibir_mensagem_gestao( $mensagem );

	// Recarrega os dados atuais (podem ter mudado na ação acima) para preencher o formulário.
	$expira = get_post_meta( $id, 'dco_expira_em', true );
	$status = get_post_status( $id );
	?>
	<h3>Gerenciar meu cadastro</h3>
	<p>Status atual: <strong><?php echo esc_html( dco_rotulo_status( $status ) ); ?></strong> · Expira em: <strong><?php echo esc_html( $expira ?: '—' ); ?></strong></p>

	<form method="post" enctype="multipart/form-data" class="dco-form">
		<input type="hidden" name="dco_tipo" value="<?php echo esc_attr( $tipo ); ?>">
		<input type="hidden" name="dco_id" value="<?php echo (int) $id; ?>">
		<input type="hidden" name="dco_token" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="dco_acao_gestao" value="salvar">
		<?php wp_nonce_field( 'dco_gerenciar_' . $id, 'dco_nonce' ); ?>

		<?php if ( $tipo_post === 'dco_curriculo' ) : ?>
			<p><label>Cargo / função desejada<br><input type="text" name="dco_cargo" value="<?php echo esc_attr( get_post_meta( $id, 'dco_cargo', true ) ); ?>"></label></p>
			<p><label>Estado<br>
				<select name="dco_estado" id="dco_estado_gestao">
					<?php foreach ( dco_uf_list() as $uf => $nome_uf ) : ?>
						<option value="<?php echo esc_attr( $uf ); ?>" <?php selected( get_post_meta( $id, 'dco_estado', true ), $uf ); ?>><?php echo esc_html( $uf . ' — ' . $nome_uf ); ?></option>
					<?php endforeach; ?>
				</select>
			</label></p>
			<p><label>Cidade<br><input type="text" name="dco_cidade" id="dco_cidade_gestao" value="<?php echo esc_attr( get_post_meta( $id, 'dco_cidade', true ) ); ?>"></label></p>
			<p><label>Telefone / WhatsApp<br><input type="text" name="dco_telefone" value="<?php echo esc_attr( get_post_meta( $id, 'dco_telefone', true ) ); ?>"></label></p>
			<p><label>Resumo profissional (opcional, exibido na busca)<br><textarea name="dco_resumo" rows="3"><?php echo esc_textarea( get_post_meta( $id, 'dco_resumo', true ) ); ?></textarea></label></p>
			<p><label>Especialidades (opcional, exibidas na busca)<br><input type="text" name="dco_especialidades" value="<?php echo esc_attr( get_post_meta( $id, 'dco_especialidades', true ) ); ?>"></label></p>
			<p><label>Substituir currículo em PDF (opcional)<br><input type="file" name="dco_curriculo_pdf_arquivo" accept="application/pdf"></label></p>
		<?php else : ?>
			<p><label>Empresa<br><input type="text" name="dco_empresa" value="<?php echo esc_attr( get_post_meta( $id, 'dco_empresa', true ) ); ?>"></label></p>
			<p><label>Cargo<br><input type="text" name="dco_cargo" value="<?php echo esc_attr( get_post_meta( $id, 'dco_cargo', true ) ); ?>"></label></p>
			<p><label>Estado<br>
				<select name="dco_estado">
					<?php foreach ( dco_uf_list() as $uf => $nome_uf ) : ?>
						<option value="<?php echo esc_attr( $uf ); ?>" <?php selected( get_post_meta( $id, 'dco_estado', true ), $uf ); ?>><?php echo esc_html( $uf . ' — ' . $nome_uf ); ?></option>
					<?php endforeach; ?>
				</select>
			</label></p>
			<p><label>Cidade<br><input type="text" name="dco_cidade" value="<?php echo esc_attr( get_post_meta( $id, 'dco_cidade', true ) ); ?>"></label></p>
			<p><label>Descrição<br><textarea name="dco_descricao" rows="4"><?php echo esc_textarea( get_post_meta( $id, 'dco_descricao', true ) ); ?></textarea></label></p>
			<p><label>Requisitos<br><textarea name="dco_requisitos" rows="3"><?php echo esc_textarea( get_post_meta( $id, 'dco_requisitos', true ) ); ?></textarea></label></p>
			<p><label>Benefícios<br><textarea name="dco_beneficios" rows="3"><?php echo esc_textarea( get_post_meta( $id, 'dco_beneficios', true ) ); ?></textarea></label></p>
		<?php endif; ?>
		<p><button type="submit">Salvar alterações</button></p>
	</form>

	<form method="post" class="dco-form dco-form--inline">
		<input type="hidden" name="dco_tipo" value="<?php echo esc_attr( $tipo ); ?>">
		<input type="hidden" name="dco_id" value="<?php echo (int) $id; ?>">
		<input type="hidden" name="dco_token" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="dco_acao_gestao" value="renovar">
		<?php wp_nonce_field( 'dco_gerenciar_' . $id, 'dco_nonce' ); ?>
		<button type="submit">Renovar por mais <?php echo esc_html( $tipo_post === 'dco_vaga' ? '30 dias' : '12 meses' ); ?></button>
	</form>

	<form method="post" class="dco-form dco-form--perigo" onsubmit="return confirm('Tem certeza? Esta ação não pode ser desfeita.');">
		<input type="hidden" name="dco_tipo" value="<?php echo esc_attr( $tipo ); ?>">
		<input type="hidden" name="dco_id" value="<?php echo (int) $id; ?>">
		<input type="hidden" name="dco_token" value="<?php echo esc_attr( $token ); ?>">
		<input type="hidden" name="dco_acao_gestao" value="excluir">
		<?php wp_nonce_field( 'dco_gerenciar_' . $id, 'dco_nonce' ); ?>
		<p><label><input type="checkbox" name="dco_confirma_exclusao" value="1"> Confirmo que quero excluir definitivamente meu cadastro<?php echo $tipo_post === 'dco_curriculo' ? ' e o arquivo em PDF' : ''; ?>.</label></p>
		<p><button type="submit">Excluir definitivamente</button></p>
	</form>
	<?php
}

function dco_rotulo_status( $status ) {
	$rotulos = array(
		'publish' => 'Publicado',
		'pending' => 'Em avaliação',
		'suspensa' => 'Suspenso',
		'expirada' => 'Expirado',
	);
	return $rotulos[ $status ] ?? $status;
}

/* ==========================================================================
   SOLICITAR NOVO LINK DE GESTÃO (quando o anterior expirou/já foi usado)
   ========================================================================== */
function dco_exibir_formulario_solicitar_gestao( $aviso = '' ) {
	if ( $aviso ) {
		echo '<div class="dco-aviso"><p>' . esc_html( $aviso ) . '</p></div>';
	}
	dco_view_solicitar_gestao( false );
}

function dco_view_solicitar_gestao( $com_titulo = true ) {
	$erros   = array();
	$sucesso = false;

	if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['dco_form'] ) && $_POST['dco_form'] === 'solicitar_gestao' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_solicitar_gestao' ) ) {
			$erros[] = 'Sessão expirada, tente novamente.';
		} elseif ( ! dco_campo_isca_valido() ) {
			$erros[] = 'Não foi possível processar seu envio.';
		} elseif ( dco_limite_por_ip_excedido( 'solicitar_gestao', 10 ) ) {
			$erros[] = 'Muitos pedidos recentes deste endereço. Tente novamente mais tarde.';
		} else {
			$email = sanitize_email( wp_unslash( $_POST['dco_email'] ?? '' ) );
			if ( ! $email || ! is_email( $email ) ) {
				$erros[] = 'Informe um e-mail válido.';
			} else {
				// Procura em AMBOS os tipos — a pessoa pode ter currículo, vaga ou os dois com o mesmo e-mail.
				foreach ( array(
					array( 'post_type' => 'dco_curriculo', 'meta_key' => 'dco_email', 'tipo' => 'curriculo' ),
					array( 'post_type' => 'dco_vaga', 'meta_key' => 'dco_email_candidatura', 'tipo' => 'vaga' ),
				) as $busca ) {
					$encontrados = get_posts( array(
						'post_type'      => $busca['post_type'],
						'post_status'    => array( 'publish', 'pending', 'suspensa', 'expirada' ),
						'posts_per_page' => -1,
						'meta_query'     => array( array( 'key' => $busca['meta_key'], 'value' => $email ) ),
						'no_found_rows'  => true,
					) );
					foreach ( $encontrados as $cadastro ) {
						dco_email_link_gestao( $cadastro->ID, $busca['tipo'] );
					}
				}
				// Mensagem SEMPRE igual, independente de ter encontrado algo ou
				// não — nunca revela publicamente se um e-mail tem cadastro.
				$sucesso = true;
			}
		}
	}

	if ( $sucesso ) {
		echo '<div class="dco-aviso dco-aviso--sucesso"><p>Se esse e-mail tiver algum cadastro conosco, um novo link de gestão foi enviado — ele vale uma única vez, por 30 minutos.</p></div>';
		return;
	}

	dco_exibir_erros( $erros );
	if ( $com_titulo ) {
		echo '<h3>Gerenciar meu cadastro</h3><p>Informe o e-mail usado no cadastro — enviaremos um novo link de gestão.</p>';
	}
	?>
	<form method="post" class="dco-form">
		<input type="hidden" name="dco_form" value="solicitar_gestao">
		<?php wp_nonce_field( 'dco_solicitar_gestao', 'dco_nonce' ); ?>
		<?php dco_campo_isca_html(); ?>
		<p><label>E-mail do cadastro<br><input type="email" name="dco_email" required></label></p>
		<p><button type="submit">Enviar novo link</button></p>
	</form>
	<?php
}

/* ==========================================================================
   SOLICITAR CONTATO (currículos)
   ========================================================================== */
function dco_view_solicitar_contato() {
	$id = isset( $_GET['dco_id'] ) ? (int) $_GET['dco_id'] : ( isset( $_POST['dco_id'] ) ? (int) $_POST['dco_id'] : 0 );
	if ( ! $id || get_post_type( $id ) !== 'dco_curriculo' || get_post_status( $id ) !== 'publish' ) {
		echo '<p>Currículo não encontrado.</p>';
		return;
	}

	$erros = array();
	$sucesso = false;
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_solicitar_contato_' . $id ) ) {
			$erros[] = 'Sessão expirada, tente novamente.';
		} elseif ( ! dco_campo_isca_valido() ) {
			$erros[] = 'Não foi possível processar seu envio.';
		} elseif ( dco_limite_por_ip_excedido( 'solicitar_contato', 10 ) ) {
			$erros[] = 'Muitos envios recentes. Tente novamente mais tarde.';
		} else {
			$nome     = sanitize_text_field( wp_unslash( $_POST['dco_nome'] ?? '' ) );
			$email    = sanitize_email( wp_unslash( $_POST['dco_email'] ?? '' ) );
			$mensagem = sanitize_textarea_field( wp_unslash( $_POST['dco_mensagem'] ?? '' ) );
			if ( ! $nome ) {
				$erros[] = 'Informe seu nome.';
			}
			if ( ! $email || ! is_email( $email ) ) {
				$erros[] = 'Informe um e-mail válido.';
			}
			if ( ! $mensagem ) {
				$erros[] = 'Escreva uma mensagem.';
			}
			if ( empty( $erros ) ) {
				dco_email_solicitar_contato( $id, $nome, $email, $mensagem );
				$sucesso = true;
			}
		}
	}

	if ( $sucesso ) {
		echo '<div class="dco-aviso dco-aviso--sucesso"><p>Mensagem enviada! Se a pessoa quiser responder, ela decide como e quando entrar em contato.</p></div>';
		return;
	}

	dco_exibir_erros( $erros );
	?>
	<?php
	$cargo_contato = get_post_meta( $id, 'dco_cargo', true );
	$cidade_contato = get_post_meta( $id, 'dco_cidade', true );
	$uf_contato = get_post_meta( $id, 'dco_estado', true );
	?>
	<h3>Solicitar contato — <?php echo esc_html( $cargo_contato ); ?><?php echo $cidade_contato ? ' (' . esc_html( trim( $cidade_contato . ( $uf_contato ? ' / ' . $uf_contato : '' ) ) ) . ')' : ''; ?></h3>
	<p>Seus dados só são compartilhados com esta pessoa — a equipe DESCOMPLICARRO não intermedia nem armazena esta conversa.</p>
	<form method="post" class="dco-form">
		<input type="hidden" name="dco_id" value="<?php echo (int) $id; ?>">
		<?php wp_nonce_field( 'dco_solicitar_contato_' . $id, 'dco_nonce' ); ?>
		<?php dco_campo_isca_html(); ?>
		<p><label>Seu nome<br><input type="text" name="dco_nome" required></label></p>
		<p><label>Seu e-mail<br><input type="email" name="dco_email" required></label></p>
		<p><label>Mensagem<br><textarea name="dco_mensagem" rows="4" required></textarea></label></p>
		<p><button type="submit">Enviar</button></p>
	</form>
	<?php
}

/* ==========================================================================
   DENUNCIAR
   ========================================================================== */
function dco_view_denunciar() {
	$tipo      = isset( $_GET['dco_tipo'] ) ? sanitize_key( wp_unslash( $_GET['dco_tipo'] ) ) : '';
	$id        = isset( $_GET['dco_id'] ) ? (int) $_GET['dco_id'] : ( isset( $_POST['dco_id'] ) ? (int) $_POST['dco_id'] : 0 );
	$tipo_post = $tipo === 'vaga' ? 'dco_vaga' : 'dco_curriculo';

	if ( ! $id || get_post_type( $id ) !== $tipo_post ) {
		echo '<p>Cadastro não encontrado.</p>';
		return;
	}

	$erros   = array();
	$sucesso = false;
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		if ( ! isset( $_POST['dco_nonce'] ) || ! wp_verify_nonce( $_POST['dco_nonce'], 'dco_denunciar_' . $id ) ) {
			$erros[] = 'Sessão expirada, tente novamente.';
		} elseif ( ! dco_campo_isca_valido() ) {
			$erros[] = 'Não foi possível processar seu envio.';
		} elseif ( dco_limite_por_ip_excedido( 'denunciar', 10 ) ) {
			$erros[] = 'Muitas denúncias recentes deste endereço. Tente novamente mais tarde.';
		} else {
			$motivo = sanitize_textarea_field( wp_unslash( $_POST['dco_motivo'] ?? '' ) );
			$total  = (int) get_post_meta( $id, 'dco_denuncias', true );
			update_post_meta( $id, 'dco_denuncias', $total + 1 );
			dco_email_notificar_denuncia( $id, $tipo_post === 'dco_vaga' ? 'vaga' : 'curriculo', $motivo );
			$sucesso = true;
		}
	}

	if ( $sucesso ) {
		echo '<div class="dco-aviso dco-aviso--sucesso"><p>Denúncia enviada. Nossa equipe vai revisar este cadastro — a suspensão ou remoção é sempre decidida por uma pessoa, nunca automática.</p></div>';
		return;
	}

	dco_exibir_erros( $erros );
	?>
	<h3>Denunciar este cadastro</h3>
	<form method="post" class="dco-form">
		<input type="hidden" name="dco_tipo" value="<?php echo esc_attr( $tipo ); ?>">
		<input type="hidden" name="dco_id" value="<?php echo (int) $id; ?>">
		<?php wp_nonce_field( 'dco_denunciar_' . $id, 'dco_nonce' ); ?>
		<?php dco_campo_isca_html(); ?>
		<p><label>Motivo (opcional)<br><textarea name="dco_motivo" rows="3"></textarea></label></p>
		<p><button type="submit">Enviar denúncia</button></p>
	</form>
	<?php
}

/* ==========================================================================
   HELPERS DE EXIBIÇÃO
   ========================================================================== */
function dco_exibir_erros( $erros ) {
	if ( empty( $erros ) ) {
		return;
	}
	echo '<div class="dco-aviso dco-aviso--erro"><ul>';
	foreach ( $erros as $erro ) {
		echo '<li>' . esc_html( $erro ) . '</li>';
	}
	echo '</ul></div>';
}

function dco_exibir_mensagem_gestao( $mensagem ) {
	if ( ! $mensagem ) {
		return;
	}
	echo '<div class="dco-aviso"><p>' . esc_html( $mensagem ) . '</p></div>';
}
