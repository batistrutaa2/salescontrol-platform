'use strict';

(function () {})();

document.addEventListener('DOMContentLoaded', function (e) {
  (function () {
    // Monetary field mask
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

    // CPF/CNPJ mask
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

    // Phone masks
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

    // Flatpickr - Data de Nascimento
    const dataNascimentoInput = document.getElementById('data_nascimento');
    if (dataNascimentoInput) {
      flatpickr(dataNascimentoInput, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        locale: 'pt'
      });
    }

    // ============================================
    // Campanha Alice Toggle
    // ============================================
    const campanhaAliceCheckbox = document.getElementById('campanhaAlice');
    const campanhaAliceContainer = document.getElementById('campanhaAliceContainer');
    const nomeClienteInput = document.getElementById('nome_cliente');

    if (campanhaAliceCheckbox && nomeClienteInput) {
      campanhaAliceCheckbox.addEventListener('change', function () {
        if (this.checked) {
          nomeClienteInput.dataset.previousValue = nomeClienteInput.value;
          nomeClienteInput.value = 'CAMPANHA_ALICE';
          nomeClienteInput.disabled = true;
          nomeClienteInput.classList.add('campanha-disabled');
          campanhaAliceContainer.classList.add('active');
        } else {
          nomeClienteInput.value = nomeClienteInput.dataset.previousValue || '';
          nomeClienteInput.disabled = false;
          nomeClienteInput.classList.remove('campanha-disabled');
          campanhaAliceContainer.classList.remove('active');
        }
      });
    }

    // Re-habilitar campo no submit (disabled não envia valor no POST)
    const form = document.getElementById('formCriarLead');
    if (form) {
      form.addEventListener('submit', function () {
        if (nomeClienteInput && nomeClienteInput.disabled) {
          nomeClienteInput.disabled = false;
        }
      });
    }
  })();
});
