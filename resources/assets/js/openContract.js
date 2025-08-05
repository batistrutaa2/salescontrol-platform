'use strict';

$(function () {
  // Máscara telefone
  document.querySelectorAll('.mask-telefone').forEach(mask => {
    new Cleave(mask, {
      delimiters: ['(', ') ', '-', ''],
      blocks: [0, 2, 5, 4],
      numericOnly: true
    });
  });

  // Máscara monetária
  document.querySelectorAll('.monetary-field').forEach(field => {
    new Cleave(field, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',',
      delimiter: '.',
      prefix: 'R$ ',
      numeralDecimalScale: 2
    });
  });

  // Máscara CPF/CNPJ
  const inputCpf = document.querySelector('#cpf_cnpj');
  function applyMaskBasedOnLength(value) {
    const cleanValue = value.replace(/[.\-\/]/g, '');
    return cleanValue.length > 11
      ? { delimiters: ['.', '.', '/', '-'], blocks: [2, 3, 3, 4, 2] }
      : { delimiters: ['.', '.', '-'], blocks: [3, 3, 3, 2] };
  }
  if (inputCpf) {
    let cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value || ''));
    inputCpf.addEventListener('input', function () {
      cleave.destroy();
      cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value));
    });
  }

  // Inicializa Select2 no campo Operadora
  $('#operadoraSelect').select2();

  // Função para buscar planos
  function carregarPlanos(operadoraId, planoSelecionado = null) {
    let $planoSelect = $('#planoSelect');
    let $acomodacaoField = $('#acomodacao');

    $planoSelect.empty().append('<option value="">Carregando...</option>');
    $acomodacaoField.val('');

    if (operadoraId) {
      $.get(`/comercial/getPlansByOperator/${encodeURIComponent(operadoraId)}`, function (data) {
        $planoSelect.empty().append('<option value="">Selecione...</option>');
        data.forEach(plano => {
          let selected = planoSelecionado && plano.id == planoSelecionado ? 'selected' : '';
          $planoSelect.append(
            `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}" ${selected}>${plano.nome.toUpperCase()}</option>`
          );
        });

        // Se já houver plano selecionado, preencher acomodação automaticamente
        if (planoSelecionado) {
          let acomodacao = $planoSelect.find(':selected').data('acomodacao') || '';
          $acomodacaoField.val(acomodacao);
        }
      }).fail(function () {
        $planoSelect.empty().append('<option value="">Erro ao carregar planos</option>');
      });
    } else {
      $planoSelect.empty().append('<option value="">Selecione a operadora primeiro</option>');
    }
  }

  // Evento de mudança da operadora
  $(document).on('change', '#operadoraSelect', function () {
    carregarPlanos($(this).val());
  });

  // Evento de mudança do plano
  $(document).on('change', '#planoSelect', function () {
    let acomodacao = $(this).find(':selected').data('acomodacao') || '';
    $('#acomodacao').val(acomodacao);
  });

  // --- Ao carregar a tela já busca os planos da operadora do contrato ---
  let operadoraInicial = $('#operadoraSelect').val();
  let planoInicial = $('#planoSelect').val();
  if (operadoraInicial) {
    carregarPlanos(operadoraInicial, planoInicial);
  }
});
