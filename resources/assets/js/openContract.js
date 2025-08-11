'use strict';

$(function () {
  // ===== Helpers =====
  function maskPhone(el) {
    new Cleave(el, { delimiters: ['(', ') ', '-', ''], blocks: [0, 2, 5, 4], numericOnly: true });
  }
  function maskMoney(el) {
    new Cleave(el, {
      numeral: true, numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',', delimiter: '.', prefix: 'R$ ', numeralDecimalScale: 2
    });
  }
  function maskCpfCnpj(el) {
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
    const nome = ($sel.find(':selected').data('nome') || $sel.find(':selected').text() || '').toString().trim().toUpperCase();
    return nome.startsWith('AMIL');
  }

  function copartOptionsHtml(isAmil, current) {
    const opts = isAmil
      ? ['PARCIAL', 'COMPLETA']
      : ['Y', 'N'];
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
  const cpfEl = document.getElementById('cpf_cnpj');
  if (cpfEl) maskCpfCnpj(cpfEl);

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

  // on change plano base -> preenche acomodação
  $(document).on('change', '#planoSelect', function () {
    let acomodacao = $(this).find(':selected').data('acomodacao') || '';
    $('#acomodacao').val(acomodacao);
  });

  // ===== Planos para cada titular =====
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

  // on change plano do titular -> preenche acomodação
  $(document).on('change', '.select-plano-titular', function () {
    const acom = $(this).find(':selected').data('acomodacao') || '';
    $(this).closest('form').find('.input-acomodacao').val(acom);
  });

  // ===== Coparticipação por titular (Amil => PARCIAL/COMPLETA, outras => SIM/NÃO) =====
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

  // ===== Inicialização: carregar planos (base e titulares) e coparticipação =====
  (function initLoad() {
    const operadoraInicial = $('#operadoraSelect').val();
    const planoInicial = $('#planoSelect').val();
    if (operadoraInicial) {
      carregarPlanos(operadoraInicial, planoInicial);
    }

    // Para cada titular, carrega planos conforme operadora
    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
      // armazena valor atual de copart para preservar ao mudar operadora
      const $selCop = $(this).find('.select-coparticipacao');
      $selCop.attr('data-current', ($selCop.val() || '').toString().toUpperCase());
    });

    atualizarCoparticipacaoTitulares();
  })();

  // Quando mudar a operadora, recarrega planos (base e titulares) e opções de coparticipação
  $(document).on('change', '#operadoraSelect', function () {
    const operadoraId = $(this).val();
    carregarPlanos(operadoraId, $('#planoSelect').val());

    $('.form-titular-update').each(function () {
      carregarPlanosParaTitular($(this));
    });

    atualizarCoparticipacaoTitulares();
  });

  // ===== Submit individual do titular (AJAX) =====
  // Para cada titular, só carrega via AJAX se não houver planos renderizados
  $('.form-titular-update').each(function () {
    const $form = $(this);
    const $selectPlano = $form.find('.select-plano-titular');

    if ($selectPlano.find('option').length <= 1) {
      // só 1 placeholder -> precisa buscar
      carregarPlanosParaTitular($form);
    } else {
      // já veio renderizado do servidor: apenas sincroniza acomodação
      const opt = $selectPlano.find(':selected');
      $form.find('.input-acomodacao').val(opt.data('acomodacao') || '');
    }

    // guarda coparticipação atual pra preservar se operadora mudar
    const $selCop = $form.find('.select-coparticipacao');
    $selCop.attr('data-current', ($selCop.val() || '').toString().toUpperCase());
  });


});
