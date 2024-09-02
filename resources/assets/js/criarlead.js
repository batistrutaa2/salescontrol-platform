'use strict';

(function () {})();

document.addEventListener('DOMContentLoaded', function (e) {
  (function () {
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

    const inputcpf = document.getElementById('cpf');

    let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

    inputcpf.addEventListener('input', function () {
      const cleanValue = this.value.replace(/\D/g, '');
      const currentMask = applyMaskBasedOnLength(cleanValue);

      // Atualiza a máscara dinamicamente conforme o valor do campo
      cleave.destroy();
      cleave = new Cleave(inputcpf, currentMask);

      // Define o valor limpo no input após aplicar a nova máscara
      cleave.setRawValue(cleanValue);
    });

    const telefones = document.querySelectorAll('.mask-telefone');
    telefones.forEach(mask => {
      new Cleave(mask, {
        delimiters: ['(', ') ', '-', ''],
        blocks: [0, 2, 5, 4],
        numericOnly: true
      });
    });

    function applyMaskBasedOnLength(value) {
      // Limpa o valor, mantendo apenas números
      const cleanValue = value.replace(/\D/g, '');

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
  })();
});
