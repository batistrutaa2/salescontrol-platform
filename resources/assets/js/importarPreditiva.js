'use strict';

$(function () {
  const dropzoneBasic = document.querySelector('#dropzone-preditiva');
  let uploadedFile = null;

  // Configuração do Dropzone
  if (dropzoneBasic) {
    const myDropzone = new Dropzone(dropzoneBasic, {
      url: '/comercial/preditiva/importar',
      autoProcessQueue: false,
      paramName: 'file',
      maxFiles: 1,
      maxFilesize: 10,
      acceptedFiles: '.xlsx,.csv',
      addRemoveLinks: true,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      dictDefaultMessage: 'Solte os arquivos aqui ou clique para fazer upload',
      dictRemoveFile: 'Remover arquivo',
      dictCancelUpload: 'Cancelar upload',
      dictMaxFilesExceeded: 'Você só pode fazer upload de um arquivo por vez',
      init: function () {
        const dz = this;

        // Quando um arquivo é adicionado
        this.on('addedfile', function (file) {
          if (this.files.length > 1) {
            this.removeFile(this.files[0]);
          }
          uploadedFile = file;
        });

        // Quando um arquivo é removido
        this.on('removedfile', function () {
          uploadedFile = null;
        });

        // Botão de submit
        $('.submit-preditiva').on('click', function (e) {
          e.preventDefault();
          processarImportacao(false);
        });

        // Botão de confirmar importação parcial
        $('#btn-confirmar-importacao-parcial').on('click', function (e) {
          e.preventDefault();
          $('#modalImportarParcial').modal('hide');
          processarImportacao(true);
        });

        // Função para processar a importação
        function processarImportacao(ignorarDuplicados) {
          const base = $('#base').val();

          if (!base) {
            toastr.error('Por favor, informe o nome da base.', 'Erro', {
              closeButton: true,
              progressBar: true,
              timeOut: 3000
            });
            return;
          }

          if (!uploadedFile) {
            toastr.error('Por favor, selecione um arquivo para importar.', 'Erro', {
              closeButton: true,
              progressBar: true,
              timeOut: 3000
            });
            return;
          }

          // Desabilitar botão durante o upload
          $('.submit-preditiva').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Importando...');

          // Criar FormData e enviar
          const formData = new FormData();
          formData.append('file', uploadedFile);
          formData.append('base', base);

          if (ignorarDuplicados) {
            formData.append('ignorar_duplicados', '1');
          }

          $.ajax({
            url: '/comercial/preditiva/importar',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
              if (!response.error) {
                toastr.success(response.message, 'Sucesso', {
                  closeButton: true,
                  progressBar: true,
                  timeOut: 5000
                });

                // Limpar formulário
                $('#base').val('');
                dz.removeAllFiles();
                uploadedFile = null;
                $('.card-cpfs-duplicados').slideUp();

                // Redirecionar após 2 segundos
                setTimeout(function () {
                  window.location.href = '/comercial/preditiva';
                }, 2000);
              } else {
                toastr.error(response.message, 'Erro', {
                  closeButton: true,
                  progressBar: true,
                  timeOut: 5000
                });

                // Mostrar CPFs duplicados se houver
                if (response.cpfs && response.cpfs.length > 0) {
                  mostrarCpfsDuplicados(response.cpfs);
                }
              }
            },
            error: function (xhr) {
              let errorMessage = 'Erro ao importar arquivo.';

              if (xhr.responseJSON) {
                if (xhr.responseJSON.message) {
                  errorMessage = xhr.responseJSON.message;
                }

                // Se encontrou duplicados, mostrar modal de confirmação
                if (xhr.responseJSON.duplicados_encontrados) {
                  $('#total-duplicados').text(xhr.responseJSON.total_duplicados);
                  $('#total-nao-duplicados').text(xhr.responseJSON.total_nao_duplicados);
                  $('#modalImportarParcial').modal('show');

                  // Mostrar CPFs duplicados
                  if (xhr.responseJSON.cpfs && xhr.responseJSON.cpfs.length > 0) {
                    mostrarCpfsDuplicados(xhr.responseJSON.cpfs);
                  }

                  // Reabilitar botão
                  $('.submit-preditiva').prop('disabled', false).html('<i class="mdi mdi-upload me-1"></i>Importar para Preditiva');
                  return;
                }

                // Mostrar CPFs duplicados se houver (erro genérico)
                if (xhr.responseJSON.cpfs && xhr.responseJSON.cpfs.length > 0) {
                  mostrarCpfsDuplicados(xhr.responseJSON.cpfs);
                }
              }

              toastr.error(errorMessage, 'Erro', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000
              });

              // Reabilitar botão
              $('.submit-preditiva').prop('disabled', false).html('<i class="mdi mdi-upload me-1"></i>Importar para Preditiva');
            },
            complete: function () {
              // Reabilitar botão se não foi reabilitado antes
              if ($('.submit-preditiva').prop('disabled')) {
                $('.submit-preditiva').prop('disabled', false).html('<i class="mdi mdi-upload me-1"></i>Importar para Preditiva');
              }
            }
          });
        }
      }
    });
  }

  // Função para mostrar CPFs duplicados
  function mostrarCpfsDuplicados(cpfs) {
    const tbody = $('.table-cpf-duplicado');
    tbody.empty();

    cpfs.forEach(function (cpf) {
      tbody.append(`
        <tr>
          <td class="text-truncate">${cpf}</td>
        </tr>
      `);
    });

    $('.card-cpfs-duplicados').slideDown();

    // Scroll suave até a tabela
    $('html, body').animate({
      scrollTop: $('.card-cpfs-duplicados').offset().top - 100
    }, 500);
  }
});
