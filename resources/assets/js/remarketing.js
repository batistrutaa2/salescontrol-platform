/**
 * Empresa
 */

'use strict';
let table;
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

// Datatable (jquery)
$(function () {
  let borderColor, bodyBg, headingColor;

  if (isDarkStyle) {
    borderColor = config.colors_dark.borderColor;
    bodyBg = config.colors_dark.bodyBg;
    headingColor = config.colors_dark.headingColor;
  } else {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  // Variable declaration for table
  var dt_customer_table = $('.datatables-customers'),
    select2 = $('.select2'),
    statusObj = {
      Y: { title: 'ATIVO', class: 'bg-label-success' },
      N: { title: 'INATIVO', class: 'bg-label-secondary' }
    };
  if (select2.length) {
    var $this = select2;
    select2Focus($this);
    $this.wrap('<div class="position-relative"></div>').select2({
      placeholder: 'United States ',
      dropdownParent: $this.parent()
    });
  }

  $('.card-datatable').before(
    `<div class="d-flex justify-content-end gap-2 mb-3">
    <button id="descartarSelecionadosBtn" class="btn btn-danger waves-effect waves-light" disabled>
      <i class="ri-close-line me-1"></i>Descartar Selecionados
    </button>
    <button id="enviarSelecionadosBtn" class="btn btn-primary waves-effect waves-light" disabled>
      <i class="ri-arrow-right-fill me-1"></i>Enviar Selecionados para Preditiva
    </button>
  </div>`
  );


  if (dt_customer_table.length) {
    table = dt_customer_table.DataTable({
      ajax: {
        url: 'getRemarketingLeads',
        dataSrc: '',
        complete: function (jqXHR, textStatus) { },
        error: function (jqXHR, textStatus, errorThrown) { }
      },
      columns: [
        {
          // Coluna de checkbox
          data: null,
          title: '<input type="checkbox" class="form-check-input" id="selectAll">',
          orderable: false,
          searchable: false,
          width: '30px',
          render: function (data, type, full, meta) {
            return '<input type="checkbox" class="form-check-input lead-checkbox" data-id="' + full.id + '">';
          }
        },
        { data: 'id' },
        { data: 'nome_cliente' },
        {
          // User email
          data: 'motivo_remarketing',
          render: function (data, type, full, meta) {
            var $motivo = full['motivo_remarketing'];

            if ($motivo == null) {
              $motivo = 'SEM REGISTRO';
            } else {
              $motivo = full['motivo_remarketing'];
            }

            return '<span >' + $motivo + '</span>';
          }
        },
        {
          data: 'telefone1'
        },
        {
          data: 'plano'
        },
        {
          data: 'entidade'
        },
        { data: 'updated_at' },
        {
          // Actions
          targets: -1,
          title: 'AÇÕES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown">' +
              '<i class="ri-more-2-line ri-22px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end m-0">' +
              '<a href="/comercial/abrir-cliente/' +
              full.id +
              '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span> Editar Contato</span></a>' +
              '<button type="button" class="dropdown-item js-transferir-leads" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
              full.id +
              '">' +
              '<i class="ri-arrow-left-right-fill me-2"></i><span> Transferir Contato</span></button>' +
              '<button type="button" class="dropdown-item js-enviar-preditiva" data-id="' +
              full.id +
              '">' +
              '<i class="ri-arrow-right-fill me-2"></i><span> Enviar Preditiva</span></button>' +
              '<button type="button" class="dropdown-item js-descartar-cliente" data-id="' +
              full.id +
              '">' +
              '<i class="ri-close-circle-line me-2"></i><span> Descartar Cliente</span></button>' +
              '</div>' +
              '</div>'
            );
          }

        }
      ],
      order: [[2, 'desc']],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Pesquisar Usuario'
      },
      // Para popup responsivo
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['nome_fantasia'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== '' // Não mostrar linha no popup modal se o título estiver vazio (para check box)
                ? '<tr data-dt-row="' +
                col.rowIndex +
                '" data-dt-column="' +
                col.columnIndex +
                '">' +
                '<td>' +
                col.title +
                ':' +
                '</td> ' +
                '<td>' +
                col.data +
                '</td>' +
                '</tr>'
                : '';
            }).join('');

            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      }
    });
    $('.dataTables_length').addClass('my-0');
    $('.dt-action-buttons').addClass('pt-0');
    $('.dataTables_filter input').addClass('ms-0');
    $('.dt-buttons').addClass('d-flex flex-wrap');

    // Manipulador para o checkbox "Selecionar Todos"
    $(document).on('change', '#selectAll', function () {
      const isChecked = $(this).prop('checked');
      $('.lead-checkbox').prop('checked', isChecked);
      updateEnviarSelecionadosBtn();
    });

    // Manipulador para checkboxes individuais
    $(document).on('change', '.lead-checkbox', function () {
      updateEnviarSelecionadosBtn();

      // Atualizar o estado do checkbox "Selecionar Todos"
      const allChecked = $('.lead-checkbox:checked').length === $('.lead-checkbox').length;
      $('#selectAll').prop('checked', allChecked && $('.lead-checkbox').length > 0);
    });

    $(document).on('click', '.js-descartar-cliente', function () {
      const leadId = $(this).data('id');
      $.ajax({
        url: '/comercial/descartar-cliente/' + leadId,
        method: 'POST',
        data: {
          _token: $('meta[name="csrf-token"]').attr('content'), // CSRF para Laravel
        },
        success: function (response) {
          toastr.success('Cliente descartado com sucesso!');
          table.ajax.reload(null, false); // Atualiza tabela sem resetar a página
        },
        error: function (xhr) {
          toastr.error('Erro ao descartar cliente.');
        }
      });
    });




    // Função para atualizar o estado do botão "Enviar Selecionados"
    function updateEnviarSelecionadosBtn() {
      const hasChecked = $('.lead-checkbox:checked').length > 0;
      $('#enviarSelecionadosBtn, #descartarSelecionadosBtn').prop('disabled', !hasChecked);
    }

    $('#descartarSelecionadosBtn').on('click', function () {
      const selectedIds = [];

      $('.lead-checkbox:checked').each(function () {
        selectedIds.push($(this).data('id'));
      });

      if (selectedIds.length === 0) {
        toastr.warning('Nenhum lead selecionado', 'Atenção');
        return;
      }

      if (confirm(`Deseja descartar ${selectedIds.length} lead(s)?`)) {
        discardMultipleLeads(selectedIds);
      }
    });



    // Manipulador para o botão "Enviar Selecionados"
    $('#enviarSelecionadosBtn').on('click', function () {
      const selectedIds = [];

      $('.lead-checkbox:checked').each(function () {
        selectedIds.push($(this).data('id'));
      });

      if (selectedIds.length === 0) {
        toastr.warning('Nenhum lead selecionado', 'Atenção');
        return;
      }

      // Confirmar antes de enviar
      if (confirm(`Deseja enviar ${selectedIds.length} lead(s) para a fila preditiva?`)) {
        sendLeadsToPredictive(selectedIds);
      }
    });

    function discardMultipleLeads(ids) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      $.ajax({
        url: '/comercial/descartar-multiplos-leads',
        method: 'POST',
        data: { ids: ids },
        success: function (response) {
          if (response.success) {
            toastr.success(response.message || 'Leads descartados com sucesso!', 'Sucesso');
            $('.lead-checkbox, #selectAll').prop('checked', false);
            updateEnviarSelecionadosBtn();
            table.ajax.reload();
          } else {
            toastr.error(response.message || 'Erro ao descartar leads.', 'Erro');
          }
        },
        error: function (xhr) {
          const errorMsg = xhr.responseJSON?.message ?? 'Erro ao descartar leads.';
          toastr.error(errorMsg, 'Erro');
        }
      });
    }



    // Função para enviar leads para a fila preditiva
    function sendLeadsToPredictive(ids) {
      $.ajaxSetup({
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
      });

      $.ajax({
        url: '/comercial/sendMultipleLeadsPredictive',
        type: 'POST',
        data: { ids: ids },
        success: function (response) {
          if (response.success) {
            toastr.success(response.message || 'Leads enviados com sucesso!', 'Sucesso');
            // Desmarcar todos os checkboxes
            $('.lead-checkbox, #selectAll').prop('checked', false);
            updateEnviarSelecionadosBtn();
            // Recarregar a tabela
            table.ajax.reload();
          } else {
            toastr.error(response.message || 'Erro ao enviar leads', 'Erro');
          }
        },
        error: function (xhr) {
          const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : 'Erro ao enviar leads para a fila preditiva';
          toastr.error(errorMsg, 'Erro');
        }
      });
    }
  }

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-transferir-leads')) {
      var button = event.target.closest('.js-transferir-leads');
      var leadId = button.getAttribute('data-id');
      document.querySelector('#idMailing').value = leadId;
    }
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-enviar-preditiva')) {
      const button = event.target.closest('.js-enviar-preditiva');
      const id = button.getAttribute('data-id');

      // Criar um formulário temporário
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '/comercial/sendLeadPredictive';
      form.style.display = 'none';

      // Adicionar o token CSRF
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = '_token';
      csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
      form.appendChild(csrfInput);

      // Adicionar o ID
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'id';
      idInput.value = id;
      form.appendChild(idInput);

      // Adicionar o formulário ao documento e enviá-lo
      document.body.appendChild(form);
      form.submit();

      // Prevenir comportamento padrão do botão
      event.preventDefault();
    }
  });
});

// Validation & Phone mask
(function () {
  const phoneMaskList = document.querySelectorAll('.phone-mask'),
    eCommerceCustomerAddForm = document.getElementById('eCommerceCustomerAddForm');

  if (phoneMaskList) {
    phoneMaskList.forEach(function (phoneMask) {
      new Cleave(phoneMask, {
        phone: true,
        phoneRegionCode: 'BR'
      });
    });
  }

  const fv = FormValidation.formValidation(eCommerceCustomerAddForm, {
    fields: {
      customerName: {
        validators: {
          notEmpty: {
            message: 'Nome da Empresa é obrigatorio '
          }
        }
      },
      customerCnpj: {
        validators: {
          notEmpty: {
            message: 'CNPJ/CPF da Empresa é obrigatorio '
          }
        }
      },
      customerEmail: {
        validators: {
          notEmpty: {
            message: 'E-mail da Empresa é obrigatorio'
          },
          emailAddress: {
            message: 'Esse E-mail não é valido'
          }
        }
      },
      customerPassword: {
        validators: {
          notEmpty: {
            message: 'Senha é obrigatorio '
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
  });

  const submitButton = document.querySelector('.data-submit');
  submitButton.addEventListener('click', function (event) {
    fv.validate().then(function (status) {
      if (status === 'Valid') {
        const formData = {
          name: document.querySelector('[name="customerName"]').value,
          email: document.querySelector('[name="customerEmail"]').value,
          user_role_id: document.querySelector('[name="user_role_id"]').value,
          empresa_id: document.querySelector('[name="empresa_id"]').value,
          password: document.querySelector('[name="customerPassword"]').value
        };

        $.ajaxSetup({
          headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          }
        });

        $.ajax({
          url: 'usuarios/createUser',
          type: 'POST',
          data: formData,
          success: function (response) {
            table.ajax.reload();
            $('#offcanvasEcommerceCustomerAdd').offcanvas('hide');
            if (!response.error) {
              toastr.success(response.message, 'Sucesso');
            } else {
              toastr.error(response.message, 'Erro');
            }
          },
          error: function (error) {
            var errorMessage = error.responseJSON ? error.responseJSON.message : 'Ocorreu um erro desconhecido';

            toastr.error(error, errorMessage);
          }
        });
      }
    });
  });
})();
