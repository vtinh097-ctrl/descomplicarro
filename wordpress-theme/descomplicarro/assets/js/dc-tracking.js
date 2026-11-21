/**
 * DESCOMPLICARRO — camada de dados (dataLayer) para Google Tag Manager/GA4.
 *
 * JavaScript puro, sem dependências — não altera nada visualmente. Dois
 * mecanismos, para não duplicar código por botão/página (ver briefing,
 * "Evitar código duplicado"):
 *
 * 1) ELEMENTOS JÁ ANOTADOS NO PHP: qualquer elemento com atributo
 *    "data-dc-event" dispara exatamente aquele evento, com todos os
 *    "data-dc-*" como parâmetros — usado nos pontos que o briefing pede
 *    identificação própria (publicidade, matérias, oficinas, parceiros,
 *    modalidades do MATCH, downloads).
 * 2) DETECÇÃO AUTOMÁTICA, para todo o resto do site: olha a URL de destino
 *    (wa.me, mailto:, redes sociais, arquivos para download) e a classe do
 *    componente (cartão de matéria, cartão de parceiro, espaço
 *    publicitário, menu) para decidir o evento sozinho — é o que cobre
 *    "cliques em botões gerais" e "navegação" em todas as páginas, sem
 *    precisar marcar cada botão manualmente.
 *
 * Identificador do botão: SEMPRE um valor estável (data-dc-id quando
 * existe, senão a URL de destino) — nunca só o texto visível, que muda de
 * tradução/redação sem avisar ninguém que depende da métrica.
 */
(function () {
  'use strict';

  window.dataLayer = window.dataLayer || [];

  function dcTrack(eventName, params) {
    var payload = Object.assign(
      {
        event: eventName,
        page_location: window.location.href,
        page_path: window.location.pathname,
      },
      params || {}
    );
    window.dataLayer.push(payload);
    dcEnviarParaMetricasNativas(eventName, params || {});
  }
  window.dcTrack = dcTrack; // disponível para qualquer script futuro que precise disparar eventos próprios.

  /**
   * Painel nativo "Métricas" (Etapa 3) — totalmente independente do
   * dataLayer/GA4/GTM acima: mesmo com as duas integrações desligadas (ou
   * nunca ativadas), este contador continua funcionando sozinho. Cada
   * evento vira (área, tipo de evento, identificador estável) — nunca um
   * dado pessoal de quem clicou. "Fire-and-forget": se o endpoint não
   * existir (plugin de Oportunidades não instalado/ativo), a chamada só
   * falha silenciosamente, sem quebrar nada na página.
   */
  var DCO_AREA_POR_EVENTO = {
    ad_click: 'anuncio',
    ad_impression: 'anuncio',
    article_click: 'materia',
    partner_click: 'parceiro',
    credenciamento_click: 'oficina',
    navigation_click: 'navegacao',
  };

  function dcEnviarParaMetricasNativas(eventName, params) {
    if (typeof dcoMetricas === 'undefined' || !dcoMetricas.ajaxUrl) return;

    var area = DCO_AREA_POR_EVENTO[eventName] || 'geral';
    var identificador =
      params.campanha_id || params.materia || params.origem || params.button_id || params.destino || '';

    var corpo = new URLSearchParams({
      action: 'dco_metricas_registrar_clique',
      area: area,
      evento: eventName,
      identificador: String(identificador).slice(0, 100),
    });

    fetch(dcoMetricas.ajaxUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: corpo,
      keepalive: true,
      credentials: 'omit',
    }).catch(function () {
      // silencioso de propósito — métrica nunca pode quebrar a navegação do visitante.
    });
  }

  /** Parâmetros lidos de todo atributo data-dc-* do elemento (menos data-dc-event, que vira o nome do evento). */
  function dcParamsFromElement(el) {
    var params = {};
    if (!el || !el.attributes) return params;
    for (var i = 0; i < el.attributes.length; i++) {
      var attr = el.attributes[i];
      if (attr.name.indexOf('data-dc-') === 0 && attr.name !== 'data-dc-event') {
        var key = attr.name.replace('data-dc-', '').replace(/-/g, '_');
        params[key] = attr.value;
      }
    }
    return params;
  }

  /** Seção mais próxima (section[aria-labelledby]) — dá contexto de "de onde" veio o clique dentro da página. */
  function dcSecaoDeOrigem(el) {
    var secao = el.closest('section[aria-labelledby]');
    return secao ? secao.getAttribute('aria-labelledby') : '';
  }

  var REDES_SOCIAIS = ['instagram.com', 'youtube.com', 'youtu.be', 'linkedin.com', 'tiktok.com'];
  var EXTENSOES_DOWNLOAD = ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.zip'];

  function dcClassificarLink(href) {
    if (!href) return null;
    if (href.indexOf('wa.me') !== -1 || href.indexOf('api.whatsapp.com') !== -1) {
      return 'whatsapp_click';
    }
    if (href.indexOf('mailto:') === 0) {
      return 'email_click';
    }
    for (var i = 0; i < REDES_SOCIAIS.length; i++) {
      if (href.indexOf(REDES_SOCIAIS[i]) !== -1) {
        return 'social_click';
      }
    }
    for (var j = 0; j < EXTENSOES_DOWNLOAD.length; j++) {
      if (href.toLowerCase().indexOf(EXTENSOES_DOWNLOAD[j]) !== -1) {
        return 'file_download';
      }
    }
    return null;
  }

  document.addEventListener(
    'click',
    function (event) {
      var el = event.target.closest('a, button');
      if (!el) return;

      var eventName = el.getAttribute('data-dc-event');
      var params = dcParamsFromElement(el);
      var href = el.getAttribute('href') || '';

      if (!eventName) {
        eventName = dcClassificarLink(href);
      }
      if (!eventName) {
        if (el.closest('.ad-slot__unit')) {
          eventName = 'ad_click';
        } else if (el.closest('.emfoco-card')) {
          eventName = 'article_click';
        } else if (el.closest('.parceiro-card')) {
          eventName = 'partner_click';
        } else if (el.closest('.site-nav__list') || el.closest('.site-footer')) {
          eventName = 'navigation_click';
        } else if (el.classList.contains('btn-bracket')) {
          eventName = 'button_click';
        }
      }

      if (!eventName) return; // elemento sem relevância analítica — não rastreia tudo indiscriminadamente.

      if (!params.button_id) {
        params.button_id = href || el.id || '';
      }
      if (!params.button_text) {
        var label = el.querySelector('.btn-bracket__label');
        params.button_text = (label ? label.textContent : el.textContent || '').trim();
      }
      if (!params.section) {
        params.section = dcSecaoDeOrigem(el);
      }
      if (href && !params.destino) {
        params.destino = href;
      }

      dcTrack(eventName, params);
    },
    true
  );

  /**
   * IMPRESSÕES DE PUBLICIDADE — conta uma impressão quando pelo menos 50% do
   * banner fica visível por 1 segundo contínuo (critério do briefing), uma
   * única vez por espaço a cada carregamento de página.
   */
  function dcObservarImpressoesDeAnuncio() {
    var anuncios = document.querySelectorAll('.ad-slot__unit[data-dc-campanha], .ad-slot__unit[data-dc-institucional]');
    if (!anuncios.length || !('IntersectionObserver' in window)) return;

    var timers = new Map();

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          var el = entry.target;
          if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
            if (timers.has(el)) return;
            var timer = setTimeout(function () {
              dcTrack('ad_impression', dcParamsFromElement(el));
              observer.unobserve(el);
              timers.delete(el);
            }, 1000);
            timers.set(el, timer);
          } else if (timers.has(el)) {
            clearTimeout(timers.get(el));
            timers.delete(el);
          }
        });
      },
      { threshold: 0.5 }
    );

    anuncios.forEach(function (el) {
      observer.observe(el);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', dcObservarImpressoesDeAnuncio);
  } else {
    dcObservarImpressoesDeAnuncio();
  }
})();
