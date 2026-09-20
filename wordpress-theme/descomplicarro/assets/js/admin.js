/**
 * DESCOMPLICARRO — admin JS: seleção de imagem (wp.media) e repetidor
 * (adicionar/remover linhas). Vanilla JS, sem dependências além do wp.media
 * já carregado pelo WordPress (wp_enqueue_media()).
 */
(function () {
  'use strict';

  function bindImageField(scope) {
    scope.querySelectorAll('.dc-image-select').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var field = btn.closest('.dc-image-field');
        var input = field.querySelector('.dc-image-id');
        var preview = field.querySelector('.dc-image-preview');
        var frame = wp.media({
          title: 'Selecionar imagem',
          multiple: false,
          library: { type: 'image' },
        });
        frame.on('select', function () {
          var attachment = frame.state().get('selection').first().toJSON();
          input.value = attachment.id;
          var src = (attachment.sizes && (attachment.sizes.medium || attachment.sizes.thumbnail)) ? (attachment.sizes.medium || attachment.sizes.thumbnail).url : attachment.url;
          preview.innerHTML = '<img src="' + src + '" style="max-width:180px;height:auto;display:block;border:1px solid #dcdcde;" />';
        });
        frame.open();
      });
    });
    scope.querySelectorAll('.dc-image-remove').forEach(function (btn) {
      if (btn.dataset.bound) return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var field = btn.closest('.dc-image-field');
        field.querySelector('.dc-image-id').value = '0';
        field.querySelector('.dc-image-preview').innerHTML = '';
      });
    });
  }

  function bindRepeater(repeater) {
    if (repeater.dataset.bound) return;
    repeater.dataset.bound = '1';
    var rows = repeater.querySelector('.dc-repeater__rows');
    var addBtn = repeater.querySelector('.dc-repeater__add');
    var template = repeater.querySelector('.dc-repeater__template');

    addBtn.addEventListener('click', function () {
      var index = Date.now();
      var html = template.textContent.split('__INDEX__').join(String(index));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var row = wrap.firstElementChild;
      rows.appendChild(row);
      bindImageField(row);
      bindRemove(row);
    });

    function bindRemove(scope) {
      scope.querySelectorAll('.dc-repeater__remove').forEach(function (btn) {
        if (btn.dataset.bound) return;
        btn.dataset.bound = '1';
        btn.addEventListener('click', function () {
          btn.closest('.dc-repeater__row').remove();
        });
      });
    }
    bindRemove(repeater);
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindImageField(document);
    document.querySelectorAll('.dc-repeater').forEach(bindRepeater);
  });
})();
