/**
 * File Upload
 */

'use strict';
// Configurações globais do Toastr
toastr.options = {
  closeButton: true, // Adiciona um botão de fechar
  debug: false, // Mostra mensagens de debug (opcional)
  newestOnTop: false, // Mostra as mensagens mais recentes no topo
  progressBar: true, // Adiciona uma barra de progresso
  positionClass: 'toast-top-right', // Posição da notificação
  preventDuplicates: false, // Previne a duplicação de notificações
  onclick: null, // Função de clique (opcional)
  showDuration: '300', // Duração do efeito de exibição
  hideDuration: '1000', // Duração do efeito de ocultação
  timeOut: '5000', // Tempo de exibição em milissegundos
  extendedTimeOut: '1000', // Tempo adicional de exibição ao passar o mouse
  showEasing: 'swing', // Efeito de exibição
  hideEasing: 'linear', // Efeito de ocultação
  showMethod: 'fadeIn', // Método de exibição
  hideMethod: 'fadeOut' // Método de ocultação
};

(function () {
  const cardCpfs = document.querySelector('.card-cpfs-duplicados');
  const inputNameBase = document.getElementById('base');
  let selectTipoUser = document.querySelector('#tipo_user');
  cardCpfs.style.display = 'none';

  const previewTemplate = `
    <div class="dz-preview dz-file-preview">
      <div class="dz-details">
        <div class="dz-thumbnail">
          <img data-dz-thumbnail>
          <span class="dz-nopreview">No preview</span>
          <div class="dz-success-mark"></div>
          <div class="dz-error-mark"></div>
          <div class="dz-error-message"><span data-dz-errormessage></span></div>
          <div class="progress">
            <div class="progress-bar progress-bar-primary" role="progressbar" aria-valuemin="0" aria-valuemax="100" data-dz-uploadprogress></div>
          </div>
        </div>
        <div class="dz-filename" data-dz-name></div>
        <div class="dz-size" data-dz-size></div>
      </div>
    </div>`;

  const eCommerceCustomerAddForm = document.querySelector('#eCommerceCustomerAddForm');
  const dropzoneBasic = document.querySelector('#dropzone-basic');
  const submitButton = document.querySelector('.data-submit');

  if (dropzoneBasic) {
    const myDropzone = new Dropzone(dropzoneBasic, {
      previewTemplate: previewTemplate,
      parallelUploads: 1,
      maxFilesize: 5,
      addRemoveLinks: true,
      maxFiles: 1,
      autoProcessQueue: true,
      init: function () {
        this.on('addedfile', function () {
          submitButton.disabled = false;
        });

        this.on('removedfile', function (file) {
          if (this.getQueuedFiles().length === 0) {
            submitButton.disabled = true;
            cardCpfs.style.display = 'none';
            inputNameBase.value = '';
          }
        });

        this.on('sending', function () {
          submitButton.disabled = true;
        });

        this.on('complete', function () {
          if (this.getUploadingFiles().length === 0 && this.getQueuedFiles().length === 0) {
            submitButton.disabled = false;
          }
        });

        this.on('error', function () {
          submitButton.disabled = false;
        });

        this.on('success', function () {
          submitButton.disabled = false;
        });

        this.on('queuecomplete', function () {
          toastr.success('Concluido', 'Arquivo carregado');
        });

        this.on('maxfilesexceeded', function () {
          submitButton.disabled = false;
        });
      }
    });
  }

  // Função separada para enviar a requisição AJAX
  function sendRequest() {
    const formData = new FormData(eCommerceCustomerAddForm);

    // Adiciona os arquivos ao FormData
    Dropzone.forElement('#dropzone-basic')
      .getAcceptedFiles()
      .forEach(file => {
        formData.append('file', file);
      });

    fetch('/mailing/importaMailing', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
      .then(response => response.json())
      .then(data => {
        if (data.cpfs && data.cpfs.length > 0) {
          toastr.error(data.message, 'Erro');
          cardCpfs.style.display = 'block';

          const tableBody = document.querySelector('.table-cpf-duplicado');
          tableBody.innerHTML = '';

          data.cpfs.forEach(cpfData => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td><span class="badge bg-label-danger rounded-pill">${cpfData.nome.toUpperCase()}</span></td>
              <td><span class="badge bg-label-danger rounded-pill">${cpfData.cpf.toUpperCase()}</span></td>
            `;
            tableBody.appendChild(row);
          });
        } else if (!data.error) {
          toastr.success(data.message, 'Sucesso');

          cardCpfs.style.display = 'none';
          inputNameBase.value = '';
          selectTipoUser.querySelector('option[value=""]').selected = true;
        } else {
          toastr.error(data.message, 'Erro');
        }
      })
      .catch(error => {
        toastr.error('Ocorreu um erro ao enviar os dados.', 'Erro');
      })
      .finally(() => {
        submitButton.disabled = false;
      });
  }

  if (submitButton) {
    submitButton.addEventListener('click', function (event) {
      event.preventDefault();
      FormValidation.formValidation(eCommerceCustomerAddForm, {
        fields: {
          base: {
            validators: {
              notEmpty: {
                message: 'Informe o nome da base'
              }
            }
          },
          id_user: {
            validators: {
              notEmpty: {
                message: 'Informe um corretor'
              }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: function (field, ele) {
              return '.mb-5';
            }
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        }
      })
        .validate()
        .then(function (status) {
          if (status === 'Valid' && dropzoneBasic) {
            sendRequest();
          } else {
            toastr.error('Por favor, preencha todos os campos obrigatórios.');
          }
        });
    });
  }
})();
