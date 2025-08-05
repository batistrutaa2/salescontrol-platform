'use strict';

$(function () {
  // Máscara telefone
  const telefones = document.querySelectorAll('.mask-telefone');
  telefones.forEach(mask => {
    new Cleave(mask, {
      delimiters: ['(', ') ', '-', ''],
      blocks: [0, 2, 5, 4],
      numericOnly: true
    });
  });

  // Máscara monetária
  const monetaryFields = document.querySelectorAll('.monetary-field');
  monetaryFields.forEach(function (field) {
    new Cleave(field, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',',
      delimiter: '.',
      prefix: 'R$ ',
      numeralDecimalScale: 2
    });
  });

  // Função para aplicar máscara CPF/CNPJ
  function applyMaskBasedOnLength(value) {
    const cleanValue = value.replace(/[.\-\/]/g, '');
    if (cleanValue.length > 11) {
      return {
        delimiters: ['.', '.', '/', '-'],
        blocks: [2, 3, 3, 4, 2]
      };
    } else {
      return {
        delimiters: ['.', '.', '-'],
        blocks: [3, 3, 3, 2]
      };
    }
  }

  // Máscara CPF/CNPJ
  const inputCpf = document.querySelector('#cpf_cnpj');
  if (inputCpf) {
    let cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value || ''));

    inputCpf.addEventListener('input', function () {
      const currentMask = applyMaskBasedOnLength(inputCpf.value);
      cleave.destroy();
      cleave = new Cleave(inputCpf, currentMask);
    });
  }

  // Select2 no campo Operadora
  $('#operadoraSelect').select2();

  // Alterar planos quando muda a operadora
  $(document).on('change', '#operadoraSelect', function () {
    let operadoraId = $(this).val();
    let $planoSelect = $('#planoSelect');
    let $acomodacaoField = $('#acomodacao');

    $planoSelect.empty().append('<option value="">Carregando...</option>');
    $acomodacaoField.val('');

    if (operadoraId) {
      let url = "/comercial/getPlansByOperator/" + encodeURIComponent(operadoraId);

      $.get(url, function (data) {
        $planoSelect.empty().append('<option value="">Selecione...</option>');
        data.forEach(function (plano) {
          $planoSelect.append(
            `<option value="${plano.id}" data-acomodacao="${plano.acomodacao || ''}">${plano.nome.toUpperCase()}</option>`
          );
        });
      }).fail(function () {
        $planoSelect.empty().append('<option value="">Erro ao carregar planos</option>');
      });
    } else {
      $planoSelect.empty().append('<option value="">Selecione a operadora primeiro</option>');
    }
  });

  // Preencher acomodação ao selecionar plano
  $(document).on('change', '#planoSelect', function () {
    let acomodacao = $(this).find(':selected').data('acomodacao') || '';
    $('#acomodacao').val(acomodacao);
  });

});
