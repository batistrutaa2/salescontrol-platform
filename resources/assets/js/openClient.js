/**
 * App eCommerce Add Product Script
 */
'use strict';

// Javascript to handle the e-commerce product add page

(function () {
  setupcomponentesCreateSale();
  const inputCpf = document.querySelector('#cpf');

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

  const telefones = document.querySelectorAll('.mask-telefone');

  telefones.forEach(mask => {
    mask.addEventListener('input', () => {
      let numero = mask.value.replace(/\D/g, ''); // Remove caracteres não numéricos
      let options;

      if (numero.startsWith('55')) {
        // Máscara para números com código do país
        options = {
          delimiters: [' ', ' (', ') ', '-'],
          blocks: [2, 2, 5, 4], // 55 (11) 99678-3883
          numericOnly: true
        };
      } else {
        // Máscara para números sem código do país
        options = {
          delimiters: [' ', '-'],
          blocks: [2, 5, 4], // 11 99678-3883
          numericOnly: true
        };
      }

      new Cleave(mask, options);
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

  select2 = $('.select2');
  if (select2.length) {
    function renderLabels(option) {
      if (!option.id) {
        return option.text;
      }
      var $badge = "<div class='badge " + $(option.element).data('color') + " rounded-pill'> " + option.text + '</div>';
      return $badge;
    }

    select2.each(function () {
      var $this = $(this);
      select2Focus($this);
      $this.wrap("<div class='position-relative'></div>").select2({
        placeholder: 'Select Label',
        dropdownParent: $this.parent(),
        templateResult: renderLabels,
        templateSelection: renderLabels,
        escapeMarkup: function (es) {
          return es;
        }
      });
    });
  }

  const commentEditorElement = document.querySelector('.comment-editor');
  let quill;

  function isEditorContentEmpty(html) {
    const tmp = document.createElement('div');
    tmp.innerHTML = html;
    return tmp.textContent.trim().length === 0;
  }

  if (commentEditorElement) {
    quill = new Quill(commentEditorElement, {
      modules: {
        toolbar: '.comment-toolbar'
      },
      placeholder: 'Atualize sua negociação..',
      theme: 'snow'
    });

    commentEditorElement.addEventListener('paste', () => {
      setTimeout(() => {
        const html = quill.root.innerHTML;
        if (!isEditorContentEmpty(html)) {
          console.log('Conteúdo colado:', html);
        }
      }, 50);
    });
  }

  document.getElementById('js-importContatos').addEventListener('click', function () {
    var cpf = document.getElementById('cpf').value;
    fetch('/comercial/getCommentsLegacy/' + cpf, {
      method: 'GET'
    })
      .then(response => response.json())
      .then(data => {
        data.forEach(item => {
          updateTimeline(data);
        });
      })
      .catch(error => {
        console.error('Erro:', error);
      });
  });

  if (document.getElementById('cotacoes-dropzone')) {
    Dropzone.autoDiscover = false;
    const cotacoesDropzone = new Dropzone('#cotacoes-dropzone', {
      autoProcessQueue: false,
      paramName: 'file',
      maxFilesize: 10,
      acceptedFiles: '.pdf,.jpg,.jpeg,.png',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('#cotacoes-dropzone input[name="_token"]').value
      }
    });

    document.getElementById('cotacao-upload-btn').addEventListener('click', function () {
      if (cotacoesDropzone.getQueuedFiles().length > 0) {
        cotacoesDropzone.processQueue();
      }
    });

    cotacoesDropzone.on('success', function (file, response) {
      const list = document.getElementById('cotacoes-list');
      if (list && response.url && response.name) {
        const li = document.createElement('li');
        li.className = 'list-group-item';
        const link = document.createElement('a');
        link.href = response.url;
        link.target = '_blank';
        link.textContent = response.name;
        li.appendChild(link);
        list.appendChild(li);
      }
    });

    cotacoesDropzone.on('queuecomplete', function () {
      cotacoesDropzone.removeAllFiles();
    });
  }

  function updateTimeline(newData) {
    const timelineList = document.getElementById('timeline-list');
    timelineList.innerHTML = '';
    newData.forEach(item => {
      const timelineItem = createTimelineItem(item);
      timelineList.appendChild(timelineItem);
    });
  }

  function truncate(text, limit) {
    if (text && text.length > limit) {
      return text.substring(0, limit) + '...';
    }
    return text || '';
  }

  function createTimelineItem(item) {
    const li = document.createElement('li');
    li.className = 'timeline-item timeline-item-transparent border-primary';
    li.innerHTML = `
        <span class="timeline-point timeline-point-primary"></span>
        <div class="timeline-event">
            <div class="timeline-header mb-1">
                <h6 class="mb-0">Feito por:${truncate(item.nome_autor, 10)}
                    <span class="badge bg-label-success">Sistema legado</span>
                </h6>
              <small class="text-muted">${item.created_at || 'Data não disponível'}</small>
            </div>
            <p class="mt-1 mb-3">${item.anotacao || 'Nenhuma anotação'}</p>
        </div>
    `;
    return li;
  }

  document.getElementById('saveComment').addEventListener('submit', function (event) {
    event.preventDefault();

    const htmlContent = quill ? quill.root.innerHTML.trim() : '';
    if (isEditorContentEmpty(htmlContent)) {
      toastr.warning('Preencha o comentário antes de salvar.');
      return;
    }

    const form = event.target;
    const formData = new FormData(form);
    formData.append('anotacao', htmlContent);

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
      },
      body: formData
    })
      .then(response => response.json())
      .then(data => {
        if (!data.error) {
          window.location.reload();
        } else {
          toastr.error('Erro ao salvar comentário.');
        }
      })
      .catch(error => {
        toastr.error('Erro ao salvar comentário.');
        console.error(error);
      });
  });

  document.addEventListener('DOMContentLoaded', function () {
    const callButton = document.getElementById('callButton');

    callButton.addEventListener('click', function () {
      makeCall();
    });
  });

  function makeCall() {
    const telefone = document.getElementById('phone_number').value.trim();
    const contatoId = document.getElementById('contato_id_pabx').value.trim();

    if (!telefone) {
      alert('Por favor, insira um número de telefone válido.');
      return;
    }

    const data = {
      telefone: telefone,
      contato_id: contatoId
    };

    fetch('/pabx/clickToCall', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // CSRF Token do Laravel
      },
      body: JSON.stringify(data)
    })
      .then(response => response.json())
      .then(result => {
        if (!result.error) {
          toastr.info(`${result.message}`);
        } else {
          toastr.info(`${result.message}`);
        }
      })
      .catch(error => {
        console.error('Erro ao fazer a chamada:', error);
        alert('Erro ao enviar a solicitação ao servidor.');
      });
  }

  function setupcomponentesCreateSale() {
    let inputcpf = document.getElementById('cpf_cnpj');

    let cleave = new Cleave(inputcpf, applyMaskBasedOnLength(inputcpf.value));

    inputcpf.addEventListener('input', function () {
      const currentMask = applyMaskBasedOnLength(inputcpf.value);

      cleave.destroy();
      cleave = new Cleave(inputcpf, currentMask);
    });

    const telefones = document.querySelectorAll('.mask-telefone');

    telefones.forEach(mask => {
      mask.addEventListener('input', () => {
        let numero = mask.value.replace(/\D/g, ''); // Remove caracteres não numéricos
        let options;

        if (numero.startsWith('55')) {
          // Máscara para números com código do país
          options = {
            delimiters: [' ', ' (', ') ', '-'],
            blocks: [2, 2, 5, 4], // 55 (11) 99678-3883
            numericOnly: true
          };
        } else {
          // Máscara para números sem código do país
          options = {
            delimiters: [' ', '-'],
            blocks: [2, 5, 4], // 11 99678-3883
            numericOnly: true
          };
        }

        new Cleave(mask, options);
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
  }

  function showModalCadastroVenda() {
    var myModal = new bootstrap.Modal(document.getElementById('addNewAddress'));
    myModal.show();
  }

  const selectElement = document.getElementById('label');
  const oldValue = selectElement.value;

  selectElement.addEventListener('focus', function () {
    oldValue = selectElement.value;
  });

  selectElement.addEventListener('change', function (event) {
    const selectedValue = event.target.value;
    if (selectedValue == 5) {
      Swal.fire({
        title: '🎉 Parabéns Pela Venda.',
        text: 'Agora é importante emitir o contrato com as informações pessoais do cliente.',
        icon: 'sucess',
        showCancelButton: true,
        confirmButtonText: 'Sim, Cadastrar!',
        customClass: {
          confirmButton: 'btn btn-primary me-3 waves-effect waves-light',
          cancelButton: 'btn btn-outline-secondary waves-effect'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.value) {
          showModalCadastroVenda();
        } else {
          selectElement.value = oldValue;
          selectElement.dispatchEvent(new Event('change'));
        }
      });
    }
  });
})();

$(function () { });
