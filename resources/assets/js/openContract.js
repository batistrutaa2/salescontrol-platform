'use strict';

$(function () {
  // ===== Helpers =====
  function maskPhone(el) {
    if (!el) return;
    new Cleave(el, { delimiters: ['(', ') ', '-', ''], blocks: [0, 2, 5, 4], numericOnly: true });
  }
  function maskMoney(el) {
    if (!el) return;
    new Cleave(el, {
      numeral: true, numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',', delimiter: '.', prefix: 'R$ ', numeralDecimalScale: 2
    });
  }
  function maskCpfCnpj(el) {
    if (!el) return;
    const applyMask = (value) => {
      const clean = (value || '').replace(/[.\-\/]/g, '');
      return clean.length > 11
        ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2] }
        : { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2] };
    };
    let cleave = new Cleave(el, applyMask(el.value || ''));
    el.addEventListener('input', function () {
      cleave.destroy();
      cleave = new Cleave(el, applyMask(el.value || ''));
    });
  }
  function isOperadoraAmil() {
    const $sel = $('#operadoraSelect');
    const nome = ($sel.find(':selected').data('nome') || $sel.find(':selected').text() || '')
      .toString().trim().toUpperCase();
    return nome.includes('AMIL'); // considera qualquer variação
  }
  function copartOptionsHtml(isAmil, current) {
    const opts = isAmil ? ['PARCIAL', 'COMPLETA'] : ['Y', 'N'];
    let html = '<option value="">Selecione...</option>';
    opts.forEach(v => {
      const sel = (current && current.toString().toUpperCase() === v) ? 'selected' : '';
      html += `<option value="${v}" ${sel}>${isAmil ? v : (v === 'Y' ? 'SIM' : 'NÃO')}</option>`;
    });
    return html;
  }

  // ===== Aplicar máscaras iniciais =====
  document.querySelectorAll('.mask-telefone').forEach(maskPhone);
  document.querySelectorAll('.monetary-field').forEach(maskMoney);
  maskCpfCnpj(document.getElementById('cpf_cnpj'));

  // Select2 em operadora
  if ($('#operadoraSelect').length) {
    $('#operadoraSelect').select2();
  }

  // ===== Planos (apólice base) =====
  function carregarPlanos(operadoraId, planoSelecionado = null) {
    let $planoSelect = $('#planoSelect');
    let $acomodacaoField = $('#acomodacao');

    $planoSelect.empty().append('<option value="">Carregando...</option>');
    $acomodacaoField.val('');

    if (!operadoraId) {
      $planoSelect.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $planoSelect.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        const selected = planoSelecionado && Number(plano.id) === Number(planoSelecionado) ? 'selected' : '';
        $planoSelect.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}" ${selected}>${(plano.nome || '').toUpperCase()}</option>`
        );
      });

      if (planoSelecionado) {
        let acomodacao = $planoSelect.find(':selected').data('acomodacao') || '';
        $acomodacaoField.val(acomodacao);
      }
    }).fail(function () {
      $planoSelect.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  $(document).on('change', '#planoSelect', function () {
    let acomodacao = $(this).find(':selected').data('acomodacao') || '';
    $('#acomodacao').val(acomodacao);
  });

  // ===== Planos para cada titular (limitados à operadora base) =====
  function carregarPlanosParaTitular($formTitular) {
    const operadoraId = $('#operadoraSelect').val();
    const $selectPlano = $formTitular.find('.select-plano-titular');
    const $acomInput = $formTitular.find('.input-acomodacao');
    const selectedPlanoId = $selectPlano.data('plano-selecionado') || $selectPlano.val() || '';

    $selectPlano.empty().append('<option value="">Carregando...</option>');
    $acomInput.val('');

    if (!operadoraId) {
      $selectPlano.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $selectPlano.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        const selected = selectedPlanoId && Number(plano.id) === Number(selectedPlanoId) ? 'selected' : '';
        $selectPlano.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}" ${selected}>${(plano.nome || '').toUpperCase()}</option>`
        );
      });

      if (selectedPlanoId) {
        const opt = $selectPlano.find(':selected');
        $acomInput.val(opt.data('acomodacao') || '');
      }
    }).fail(function () {
      $selectPlano.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  $(document).on('change', '.select-plano-titular', function () {
    const acom = $(this).find(':selected').data('acomodacao') || '';
    $(this).closest('form').find('.input-acomodacao').val(acom);
  });

  // ===== Coparticipação por titular =====
  function atualizarCoparticipacaoTitulares() {
    const amil = isOperadoraAmil();
    $('.form-titular-update').each(function () {
      const $form = $(this);
      const $sel = $form.find('.select-coparticipacao');
      const current = ($sel.data('current') || $sel.val() || '').toString().toUpperCase();
      $sel.html(copartOptionsHtml(amil, current));
      $form.find('.label-coparticipacao').text(amil ? 'Coparticipação (Amil)' : 'Coparticipação');
    });
  }

  // ===== Modal: carregar planos conforme operadora base =====
  function carregarPlanosParaModal() {
    const operadoraId = $('#operadoraSelect').val();
    const $selectPlano = $('#modalAddTitular .select-plano-modal');

    $selectPlano.empty().append('<option value="">Carregando...</option>');

    if (!operadoraId) {
      $selectPlano.empty().append('<option value="">Selecione a operadora primeiro</option>');
      return;
    }

    $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
      $selectPlano.empty().append('<option value="">Selecione...</option>');
      (data || []).forEach(plano => {
        $selectPlano.append(
          `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}">${(plano.nome || '').toUpperCase()}</option>`
        );
      });
    }).fail(function () {
      $selectPlano.empty().append('<option value="">Erro ao carregar planos</option>');
    });
  }

  function atualizarCoparticipacaoModal() {
    const amil = isOperadoraAmil();
    const $selCop = $('#modalAddTitular .select-coparticipacao-modal');
    const $label = $('#modalAddTitular .label-coparticipacao-modal');
    $selCop.html(copartOptionsHtml(amil, null));
    $label.text(amil ? 'Coparticipação (Amil)' : 'Coparticipação');
  }

  // Ao abrir a modal, aplica máscara no telefone e carrega planos/copa
  $('#modalAddTitular').on('show.bs.modal', function () {
    maskPhone($('#modalAddTitular .mask-telefone')[0]);
    carregarPlanosParaModal();
    atualizarCoparticipacaoModal();
  });

  // ===== Inicialização =====
  (function initLoad() {
    const operadoraInicial = $('#operadoraSelect').val();
    const planoInicial = $('#planoSelect').val();
    if (operadoraInicial) {
      carregarPlanos(operadoraInicial, planoInicial);
    }

    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
      const $selCop = $(this).find('.select-coparticipacao');
      $selCop.attr('data-current', ($selCop.val() || '').toString().toUpperCase());
    });

    atualizarCoparticipacaoTitulares();
  })();

  // Quando mudar a operadora base → atualiza tudo (inclui modal)
  $(document).on('change', '#operadoraSelect', function () {
    const operadoraId = $(this).val();
    carregarPlanos(operadoraId, $('#planoSelect').val());

    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
    });

    atualizarCoparticipacaoTitulares();

    // Auto-selecionar angariação para Supermed
    const selectedName = ($(this).find(':selected').data('nome') || '').toString().trim().toUpperCase();
    const isSupermed = selectedName === 'AMIL - SUPERMED';
    const $angariacaoSelect = $('#angariacao_status');
    if ($angariacaoSelect.length) {
      $angariacaoSelect.val(isSupermed ? 'SIM' : 'NAO').trigger('change');
    }

    // Se a modal estiver aberta, atualiza também
    if ($('#modalAddTitular').hasClass('show')) {
      carregarPlanosParaModal();
      atualizarCoparticipacaoModal();
    }
  });

  // Atualizar visual do badge de angariação quando mudar
  $(document).on('change', '#angariacao_status', function () {
    const isSim = $(this).val() === 'SIM';

    // Atualizar badge
    const $badge = $('#angariacao-badge');
    if ($badge.length) {
      $badge.removeClass('badge-sim badge-nao')
        .addClass(isSim ? 'badge-sim' : 'badge-nao')
        .text(isSim ? 'Ativo' : 'Inativo');
    }

    // Atualizar indicator
    const $indicator = $('#status-indicator');
    if ($indicator.length) {
      $indicator.removeClass('indicator-sim indicator-nao')
        .addClass(isSim ? 'indicator-sim' : 'indicator-nao');
    }

    // Atualizar select styling
    $(this).removeClass('status-sim status-nao')
      .addClass(isSim ? 'status-sim' : 'status-nao');
  });

  // Se você envia a modal por POST normal (sem AJAX), não precisa JS extra no submit.
});
