/**
 * Usuarios - Design System
 */

'use strict';
let table;
// Configuracoes globais do Toastr
toastr.options = {
  closeButton: true,
  debug: false,
  newestOnTop: false,
  progressBar: true,
  positionClass: 'toast-top-right',
  preventDuplicates: false,
  onclick: null,
  showDuration: '300',
  hideDuration: '1000',
  timeOut: '5000',
  extendedTimeOut: '1000',
  showEasing: 'swing',
  hideEasing: 'linear',
  showMethod: 'fadeIn',
  hideMethod: 'fadeOut'
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
      toastr.error('Erro ao carregar estatisticas');
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
    select2 = $('.select2');
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
            return '<span style="color: var(--dash-text-muted)">' + $email + '</span>';
          }
        },
        {
          title: 'FUNCAO',
          targets: 4,
          render: function (data, type, full, meta) {
            var $role = full['tipo_usuario'];
            var roleClassMap = {
              VENDEDOR: 'role-vendedor',
              ADMINISTRATIVO: 'role-administrativo',
              SUPERVISOR: 'role-supervisor',
              BACKOFFICE: 'role-backoffice',
              DEVELOPER: 'role-developer'
            };
            var roleSvgMap = {
              VENDEDOR: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
              ADMINISTRATIVO: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
              SUPERVISOR: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
              BACKOFFICE: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
              DEVELOPER: '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>'
            };

            var badgeClass = roleClassMap[$role] || 'role-vendedor';
            var svgIcon = roleSvgMap[$role] || roleSvgMap['VENDEDOR'];

            return '<span class="role-badge ' + badgeClass + '">' + svgIcon + $role + '</span>';
          }
        },
        {
          title: 'STATUS',
          targets: 6,
          render: function (data, type, full, meta) {
            var $status = full['ativo'];
            var $userId = full['id'];
            var $userName = full['name'];

            // Normaliza o status
            $status = String($status).trim().toUpperCase();
            var isChecked = $status === 'Y' ? 'checked' : '';

            // Label e badge baseado no status
            var statusLabel = $status === 'Y' ? 'ATIVO' : 'INATIVO';
            var statusBadgeClass = $status === 'Y' ? 'status-ativo' : 'status-inativo';

            return (
              '<div class="d-flex align-items-center gap-2">' +
              '<div class="form-check form-switch mb-0">' +
              '<input class="form-check-input js-toggle-status" type="checkbox" ' +
              'data-user-id="' + $userId + '" ' +
              'data-user-name="' + $userName + '" ' +
              'data-current-status="' + $status + '" ' +
              isChecked + '>' +
              '</div>' +
              '<span class="status-badge ' + statusBadgeClass + '">' + statusLabel + '</span>' +
              '</div>'
            );
          }
        },
        { data: 'created_at' },
        {
          // Actions
          targets: -1,
          title: 'ACOES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return (
              '<div class="actions-cell">' +
              '<a href="usuarios/editar-usuario/' + full['id'] + '" class="btn-action btn-action-edit" title="Editar">' +
              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
              '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>' +
              '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>' +
              '</svg>' +
              '</a>' +
              '<button type="button" class="btn-action btn-action-bank js-open-conta" title="Nova conta bancaria" data-user-id="' + full['id'] + '" data-user-name="' + (full['name'] || '') + '">' +
              '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
              '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>' +
              '<line x1="1" y1="10" x2="23" y2="10"/>' +
              '</svg>' +
              '</button>' +
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
      // Botoes com Dropdown
      buttons: [
        {
          extend: 'collection',
          className: 'btn-dash btn-secondary',
          text: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> <span class="d-none d-sm-inline-block">Baixar</span>',
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
              text: '<i class="ri-file-text-line me-1"></i>Csv',
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
        }
      ],
      drawCallback: function () {
        // Atualiza badge de contagem
        var info = this.api().page.info();
        $('#badge-total-count').text(info.recordsTotal);
      },
      // Para popup responsivo
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Detalhes de ' + data['name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            var data = $.map(columns, function (col, i) {
              return col.title !== ''
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

    // Confirmacao ao desativar usuario
    if (newStatus === 'N') {
      if (!confirm(`Tem certeza que deseja desativar o usuario "${userName}"?`)) {
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
          $switch.data('current-status', response.status);

          if (table) {
            table.ajax.reload(null, false);
          }
          loadKPIs();
        } else {
          toastr.error(response.message || 'Erro ao alterar status do usuario');
          $switch.prop('checked', currentStatus === 'Y');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr?.responseJSON?.message || 'Erro ao alterar status do usuario';
        toastr.error(errorMsg);
        $switch.prop('checked', currentStatus === 'Y');
      },
      complete: function() {
        $switch.prop('disabled', false);
      }
    });
  });

  // ========== Event: Filtros de Status ==========
  $('#status-filter-buttons .filter-btn').on('click', function() {
    const $btn = $(this);
    const status = $btn.data('status');

    // Atualiza visual dos botoes
    $('#status-filter-buttons .filter-btn').removeClass('active');
    $btn.addClass('active');

    // Aplica filtro na tabela
    if (table) {
      if (status === 'all') {
        table.column(4).search('').draw();
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
            message: 'Nome do usuario e obrigatorio'
          }
        }
      },
      customerCnpj: {
        validators: {
          notEmpty: {
            message: 'CNPJ/CPF e obrigatorio'
          }
        }
      },
      customerEmail: {
        validators: {
          notEmpty: {
            message: 'E-mail e obrigatorio'
          },
          emailAddress: {
            message: 'Esse E-mail nao e valido'
          }
        }
      },
      customerPassword: {
        validators: {
          notEmpty: {
            message: 'Senha e obrigatorio'
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
          whatsapp: document.querySelector('[name="customerWhats"]').value,
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

  const SAVE_CONTA_BASE = String($rootContas.data('save-conta-base') || '');
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

  // Abre modal a partir da linha do usuario
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

  // Envia formulario
  document.getElementById('formConta').addEventListener('submit', function(e) {
    e.preventDefault();

    const userId = inpUserId.value;
    if (!userId) {
      toastr.error('Usuario invalido.');
      return;
    }

    // Regras minimas: precisa ter PIX OU (Agencia e Conta)
    const hasPix = (inpPix.value || '').trim().length > 0;
    const hasBank = (inpAgencia.value || '').trim().length > 0 && (inpConta.value || '').trim().length > 0;

    if (!hasPix && !hasBank) {
      toastr.warning('Informe uma Chave PIX ou Agencia + Conta.');
      return;
    }

    const url = SAVE_CONTA_BASE.replace('USER_ID', encodeURIComponent(userId));
    const payload = {
      is_default: 1,
      descricao: (inpDescricao.value || '').trim() || null,
      banco: (inpBanco.value || '').trim() || null,
      chave_pix: (inpPix.value || '').trim() || null,
      agencia: (inpAgencia.value || '').trim() || null,
      conta: (inpConta.value || '').trim() || null,
      digito: (inpDigito.value || '').trim() || null
    };

    const CSRF = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
      url: url,
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF },
      data: payload,
      success: function(resp) {
        toastr.success(resp?.message || 'Conta salva com sucesso.');
        modal.hide();
      },
      error: function(xhr) {
        const msg = xhr?.responseJSON?.message || 'Falha ao salvar a conta.';
        toastr.error(msg);
      }
    });
  });
})();
