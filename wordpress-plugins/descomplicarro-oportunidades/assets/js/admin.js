/**
 * DESCOMPLICARRO Oportunidades — liga o botão "Selecionar arquivo" (ver
 * dco_admin_file() em inc/admin-fields.php) à Biblioteca de Mídia nativa do
 * WordPress (wp.media), restrita a PDF. A validação de verdade (conteúdo
 * real do arquivo, tamanho) acontece no servidor ao salvar — isto aqui é só
 * conveniência de interface.
 */
(function ($) {
  'use strict';

  $(document).on('click', '.dco-file-select', function (e) {
    e.preventDefault();
    var $campo = $(this).closest('.dco-file-field');
    var frame = wp.media({
      title: 'Selecionar currículo em PDF',
      library: { type: 'application/pdf' },
      button: { text: 'Usar este arquivo' },
      multiple: false,
    });
    frame.on('select', function () {
      var anexo = frame.state().get('selection').first().toJSON();
      $campo.find('.dco-file-id').val(anexo.id);
      $campo.find('.dco-file-preview').text(anexo.filename || anexo.title || ('Anexo #' + anexo.id));
    });
    frame.open();
  });

  $(document).on('click', '.dco-file-remove', function (e) {
    e.preventDefault();
    var $campo = $(this).closest('.dco-file-field');
    $campo.find('.dco-file-id').val(0);
    $campo.find('.dco-file-preview').text('');
  });
})(jQuery);
