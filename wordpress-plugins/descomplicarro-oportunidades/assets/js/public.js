/**
 * DESCOMPLICARRO Oportunidades — liga os seletores de Estado aos de Cidade
 * nos formulários públicos de cadastro, buscando a lista real de municípios
 * do estado escolhido (ver dco_ajax_municipios() em inc/municipios.php).
 * Reforça no próprio formulário o que o servidor também confere ao salvar
 * (cidade precisa ser um município real do estado selecionado).
 */
(function () {
  'use strict';

  function ligarCascata(idEstado, idCidade) {
    var estado = document.getElementById(idEstado);
    var cidade = document.getElementById(idCidade);
    if (!estado || !cidade || typeof dcoPublico === 'undefined') {
      return;
    }

    function carregarCidades() {
      var uf = estado.value;
      if (!uf) {
        cidade.innerHTML = '<option value="">Selecione o estado primeiro</option>';
        return;
      }
      cidade.innerHTML = '<option value="">Carregando…</option>';
      fetch(dcoPublico.ajaxUrl + '?action=dco_municipios&uf=' + encodeURIComponent(uf))
        .then(function (resposta) { return resposta.json(); })
        .then(function (json) {
          cidade.innerHTML = '<option value="">Selecione a cidade</option>';
          if (json && json.success && Array.isArray(json.data)) {
            json.data.forEach(function (nomeCidade) {
              var opcao = document.createElement('option');
              opcao.value = nomeCidade;
              opcao.textContent = nomeCidade;
              cidade.appendChild(opcao);
            });
          }
        })
        .catch(function () {
          cidade.innerHTML = '<option value="">Não foi possível carregar as cidades</option>';
        });
    }

    estado.addEventListener('change', carregarCidades);
  }

  document.addEventListener('DOMContentLoaded', function () {
    ligarCascata('dco_estado_curriculo', 'dco_cidade_curriculo');
    ligarCascata('dco_estado_vaga', 'dco_cidade_vaga');
  });
})();
