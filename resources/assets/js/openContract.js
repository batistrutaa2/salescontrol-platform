'use strict';

$(function () {
  applyMaskBasedOnLength;
  const telefones = document.querySelectorAll('.mask-telefone');
  telefones.forEach(mask => {
    new Cleave(mask, {
      delimiters: ['(', ') ', '-', ''],
      blocks: [0, 2, 5, 4],
      numericOnly: true
    });
  });

  const monetaryFields = document.querySelectorAll('.monetary-field');

  monetaryFields.forEach(function (field) {
    let rawValue = field.value;
    rawValue = rawValue.replace('.', ',');
    new Cleave(field, {
      numeral: true,
      numeralThousandsGroupStyle: 'thousand',
      numeralDecimalMark: ',',
      delimiter: '.',
      prefix: 'R$ ',
      numeralDecimalScale: 2
    });
  });

  const inputCpf = document.querySelector('#cpf_cnpj');

  function applyMaskBasedOnLength(value) {
    const cleanValue = value.replace(/[.-]/g, '');
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

  let cleave = new Cleave(inputCpf, applyMaskBasedOnLength(inputCpf.value));

  inputCpf.addEventListener('input', function () {
    const currentMask = applyMaskBasedOnLength(inputCpf.value);

    cleave.destroy();
    cleave = new Cleave(inputCpf, currentMask);
  });
});
