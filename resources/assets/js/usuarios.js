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

// ========== Carregar KPIs ==========
function loadKPIs() {
  $.ajax({
    url: '/usuarios/stats',
    method: 'GET',
    success: function(data) {
      $('#kpi-total').text(data.total);
      $('#kpi-ativos').text(data.ativos);
      $('#kpi-inativos').text(data.inativos);
      $('#kpi-novos').text(data.novos_mes);
    },
    error: function() {
      $('#kpi-total, #kpi-ativos, #kpi-inativos, #kpi-novos').text('—');
      toastr.error('Erro ao carregar estatísticas');
    }
  });
}

// Carregar KPIs ao iniciar
$(document).ready(function() {
  loadKPIs();
});

// ========== Helper para gerar avatar com iniciais ==========
function getInitials(name) {
  if (!name) return '?';
  const parts = name.trim().split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function getAvatarColor(index) {
  const colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary'];
  return colors[index % colors.length];
}

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

  // customers datatable
  if (dt_customer_table.length) {
    table = dt_customer_table.DataTable({
      ajax: {
        url: 'usuarios/getUsers',
        dataSrc: 'data',
        complete: function (jqXHR, textStatus) { },
        error: function (jqXHR, textStatus, errorThrown) { }
      },
      columns: [
        { data: 'id' },
        {
          // Nome com avatar
          targets: 1,
          render: function (data, type, full, meta) {
            var $name = full['name'];
            var initials = getInitials($name);
            var colorClass = getAvatarColor(meta.row);

            return (
              '<div class="d-flex align-items-center">' +
              '<div class="avatar avatar-sm me-3">' +
              '<span class="avatar-initial rounded-circle bg-label-' + colorClass + '">' + initials + '</span>' +
              '</div>' +
              '<div class="d-flex flex-column">' +
              '<span class="fw-medium text-heading">' + $name + '</span>' +
              '</div>' +
              '</div>'
            );
          }
        },
        {
          // User email
          targets: 3,
          render: function (data, type, full, meta) {
            var $email = full['email'];
            return '<span class="text-muted">' + $email + '</span>';
          }
        },
        {
          title: 'FUNÇÃO',
          targets: 4,
          render: function (data, type, full, meta) {
            var $role = full['tipo_usuario'];
            var roleBadgeObj = {
              VENDEDOR: '<i class="ri-user-line ri-22px text-primary me-2"></i>',
              ADMINISTRATIVO: '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
              SUPERVISOR: '<i class="ri-pie-chart-line ri-22px text-success me-2"></i>',
              BACKOFFICE: '<i class="ri-computer-line ri-22px text-danger me-2"></i>',
              DEVELOPER: '<i class="ri-vip-crown-line ri-22px text-warning me-2"></i>'
            };
            return (
              "<span class='text-truncate d-flex align-items-center text-heading'>" +
              roleBadgeObj[$role] +
              $role +
              '</span>'
            );
          }
        },
        {
          title: 'STATUS',
          targets: 6,
          render: function (data, type, full, meta) {
            var $status = full['ativo'];
            var $userId = full['id'];
            var $userName = full['name'];

            // Debug: verificar o valor exato que está vindo do banco
            // Remover após confirmar que está funcionando
            if (meta.row === 0) {
              console.log('Debug Status - Primeiro usuário:', {
                nome: $userName,
                status_raw: $status,
                status_type: typeof $status,
                status_trim: String($status).trim(),
                is_Y: $status === 'Y',
                is_Y_loose: $status == 'Y'
              });
            }

            // Normaliza o status (trim para remover espaços em branco)
            $status = String($status).trim().toUpperCase();
            var isChecked = $status === 'Y' ? 'checked' : '';

            // Label e badge baseado no status
            var statusLabel = $status === 'Y' ? 'ATIVO' : 'INATIVO';
            var statusClass = $status === 'Y' ? 'text-success' : 'text-muted';
            var badgeClass = $status === 'Y' ? 'bg-label-success' : 'bg-label-secondary';
            var icon = $status === 'Y' ? 'ri-checkbox-circle-line' : 'ri-close-circle-line';

            return (
              '<div class="d-flex align-items-center gap-2">' +
              '<div class="form-check form-switch mb-0">' +
              '<input class="form-check-input js-toggle-status" type="checkbox" ' +
              'data-user-id="' + $userId + '" ' +
              'data-user-name="' + $userName + '" ' +
              'data-current-status="' + $status + '" ' +
              isChecked + '>' +
              '</div>' +
              '<span class="badge rounded-pill ' + badgeClass + '">' +
              '<i class="' + icon + ' me-1"></i>' + statusLabel +
              '</span>' +
              '</div>'
            );
          }
        },
        { data: 'created_at' },
        {
          // Actions
          targets: -1,
          title: 'AÇÕES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="d-flex align-items-center">' +
              '<button class="btn btn-sm btn-icon btn-text-secondary rounded-pill waves-effect dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="ri-more-2-line ri-22px"></i></button>' +
              '<div class="dropdown-menu dropdown-menu-end m-0">' +
              // ✳️ NOVO: botão para modal de conta
              '<button type="button" class="dropdown-item js-open-conta" data-user-id="' + full['id'] + '" data-user-name="' + (full['name'] || '') + '">' +
              '<i class="ri-bank-card-line me-2"></i><span>Nova conta bancária</span>' +
              '</button>' +
              '<a href="usuarios/editar-usuario/' + full['id'] + '" class="dropdown-item">' +
              '<i class="ri-edit-box-line me-2"></i><span>Edit</span>' +
              '</a>' +
              '</div>' +
              '</div>'
            );
          }

        }
      ],
      order: [[2, 'desc']],
      dom:
        '<"card-header d-flex rounded-0 flex-wrap py-0 pb-5 pb-md-0"' +
        '<"me-5 pe-5 ms-n1_5 ps-2"f>' +
        '<"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex align-items-start align-items-md-center justify-content-sm-center gap-4"lB>>' +
        '>t' +
        '<"row mx-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Pesquisar Usuario'
      },
      // Botões com Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-outline-secondary dropdown-toggle me-4 waves-effect waves-light',
          text: '<i class="ri-download-line ri-16px me-1 align-baseline"></i> <span class="d-none d-sm-inline-block">Baixar</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ri-printer-line me-1"></i>Print',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              },
              customize: function (win) {
                // Customizar a visualização de impressão para tema escuro
                $(win.document.body)
                  .css('color', headingColor)
                  .css('border-color', borderColor)
                  .css('background-color', bodyBg);
                $(win.document.body)
                  .find('table')
                  .addClass('compact')
                  .css('color', 'inherit')
                  .css('border-color', 'inherit')
                  .css('background-color', 'inherit');
              }
            },
            {
              extend: 'csv',
              text: '<i class="ri-file-text-line me-1" ></i>Csv',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ri-file-excel-line me-1"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ri-file-pdf-line me-1"></i>Pdf',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            },
            {
              extend: 'copy',
              text: '<i class="ri-file-copy-line me-1"></i>Copy',
              className: 'dropdown-item',
              exportOptions: {
                columns: [0, 1, 2, 3, 4, 5],
                format: {
                  body: function (inner, coldex, rowdex) {
                    if (inner.length <= 0) return inner;
                    var el = $.parseHTML(inner);
                    var result = '';
                    $.each(el, function (index, item) {
                      if (item.innerText === undefined) {
                        result = result + item.textContent;
                      } else {
                        result = result + item.innerText;
                      }
                    });
                    return result;
                  }
                }
              }
            }
          ]
        },
        {
          text: '<i class="ri-add-line ri-16px me-0 me-sm-1_5 align-baseline"></i><span class="d-none d-sm-inline-block">Criar Usuario</span>',
          className: 'add-new btn btn-primary waves-effect waves-light',
          attr: {
            'data-bs-toggle': 'offcanvas',
            'data-bs-target': '#offcanvasEcommerceCustomerAdd'
          }
        }
      ],
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
  }

  // ========== Event: Toggle Status ==========
  $(document).on('change', '.js-toggle-status', function() {
    const $switch = $(this);
    const userId = $switch.data('user-id');
    const userName = $switch.data('user-name');
    const currentStatus = String($switch.data('current-status')).trim().toUpperCase();
    const newStatus = $switch.is(':checked') ? 'Y' : 'N';

    console.log('Toggle Status:', {
      userId: userId,
      currentStatus: currentStatus,
      newStatus: newStatus,
      isChecked: $switch.is(':checked')
    });

    // Confirmação ao desativar usuário
    if (newStatus === 'N') {
      if (!confirm(`Tem certeza que deseja desativar o usuário "${userName}"?`)) {
        // Reverte o switch se o usuário cancelar
        $switch.prop('checked', true);
        return;
      }
    }

    // Desabilita o switch enquanto processa
    $switch.prop('disabled', true);

    $.ajax({
      url: `/usuarios/${userId}/toggle-status`,
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      },
      success: function(response) {
        if (response.success) {
          toastr.success(response.message);
          // Atualiza o data attribute
          $switch.data('current-status', response.status);

          // Recarrega a tabela e os KPIs
          if (table) {
            table.ajax.reload(null, false);
          }
          loadKPIs();
        } else {
          toastr.error(response.message || 'Erro ao alterar status do usuário');
          // Reverte o switch em caso de erro
          $switch.prop('checked', currentStatus === 'Y');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr?.responseJSON?.message || 'Erro ao alterar status do usuário';
        toastr.error(errorMsg);
        // Reverte o switch em caso de erro
        $switch.prop('checked', currentStatus === 'Y');
      },
      complete: function() {
        // Reabilita o switch
        $switch.prop('disabled', false);
      }
    });
  });

  // ========== Event: Filtros de Status ==========
  $('#status-filter-buttons button').on('click', function() {
    const $btn = $(this);
    const status = $btn.data('status');

    // Atualiza visual dos botões
    $('#status-filter-buttons button').each(function() {
      const $thisBtn = $(this);
      if ($thisBtn.data('status') === status) {
        $thisBtn.removeClass('btn-outline-success btn-outline-secondary btn-outline-primary')
                .addClass('btn-' + ($thisBtn.data('status') === 'Y' ? 'success' : ($thisBtn.data('status') === 'N' ? 'secondary' : 'primary')));
      } else {
        const originalClass = $thisBtn.data('status') === 'Y' ? 'success' : ($thisBtn.data('status') === 'N' ? 'secondary' : 'primary');
        $thisBtn.removeClass('btn-success btn-secondary btn-primary')
                .addClass('btn-outline-' + originalClass);
      }
    });

    // Aplica filtro na tabela
    if (table) {
      if (status === 'all') {
        table.column(4).search('').draw(); // Coluna de status é a 5ª (índice 4)
      } else {
        const searchText = status === 'Y' ? 'ATIVO' : 'INATIVO';
        table.column(4).search(searchText).draw();
      }
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


  const $rootContas = $('#contas-root');
  if (!$rootContas.length) return;

  const SAVE_CONTA_BASE = String($rootContas.data('save-conta-base') || ''); // .../usuarios/USER_ID/contas/salvar
  const modalEl = document.getElementById('modalConta');
  const modal = new bootstrap.Modal(modalEl);

  const inpUserId = document.getElementById('conta_user_id');
  const lblUserName = document.getElementById('conta_user_name');

  const inpDescricao = document.getElementById('conta_descricao');
  const inpBanco = document.getElementById('conta_banco');
  const inpPix = document.getElementById('conta_pix');
  const inpAgencia = document.getElementById('conta_agencia');
  const inpConta = document.getElementById('conta_conta');
  const inpDigito = document.getElementById('conta_digito');

  // Abre modal a partir da linha do usuário
  $(document).on('click', '.js-open-conta', function() {
    const userId = this.getAttribute('data-user-id');
    const userName = this.getAttribute('data-user-name') || '—';

    // limpa campos
    inpUserId.value = userId;
    lblUserName.textContent = userName;
    inpDescricao.value = '';
    inpBanco.value = '';
    inpPix.value = '';
    inpAgencia.value = '';
    inpConta.value = '';
    inpDigito.value = '';

    modal.show();
  });

  // Envia formulário
  document.getElementById('formConta').addEventListener('submit', function(e) {
    e.preventDefault();

    const userId = inpUserId.value;
    if (!userId) {
      toastr.error('Usuário inválido.');
      return;
    }

    // Regras mínimas: precisa ter PIX OU (Agência e Conta)
    const hasPix = (inpPix.value || '').trim().length > 0;
    const hasBank = (inpAgencia.value || '').trim().length > 0 && (inpConta.value || '').trim().length > 0;

    if (!hasPix && !hasBank) {
      toastr.warning('Informe uma Chave PIX ou Agência + Conta.');
      return;
    }

    const url = SAVE_CONTA_BASE.replace('USER_ID', encodeURIComponent(userId));
    const payload = {
      // sempre default = 1
      is_default: 1,
      descricao: (inpDescricao.value || '').trim() || null,
      banco: (inpBanco.value || '').trim() || null,
      chave_pix: (inpPix.value || '').trim() || null,
      agencia: (inpAgencia.value || '').trim() || null,
      conta: (inpConta.value || '').trim() || null,
      digito: (inpDigito.value || '').trim() || null
    };

    // CSRF
    const CSRF = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      url: url,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF },
      data: payload, // form-urlencoded
      success: function(resp) {
        toastr.success(resp?.message || 'Conta salva com sucesso.');
        modal.hide();
        // se quiser, recarrega a tabela de usuários:
        // if (window.table?.ajax) table.ajax.reload(null, false);
      },
      error: function(xhr) {
        const msg = xhr?.responseJSON?.message || 'Falha ao salvar a conta.';
        toastr.error(msg);
      }
    });
  });
})();
