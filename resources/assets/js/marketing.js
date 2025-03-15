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

  // customers datatable
  if (dt_customer_table.length) {
    table = dt_customer_table.DataTable({
      ajax: {
        url: 'getLeadsmarketing',
        dataSrc: '',
        complete: function (jqXHR, textStatus) {},
        error: function (jqXHR, textStatus, errorThrown) {}
      },
      columns: [
        { data: 'id' },
        { data: 'nome_cliente'},
        { data: 'email'},
        { data: 'telefone1', render: function(data, type, row) {
          if (type === 'display') {
              return data ? data.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3') : '';
          }
          return data;
      }},
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
                '<button type="button" class="btn btn-sm btn-primary rounded-pill waves-effect js-enviar-lead" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
                full.id +
                '">' +
                '<span>Enviar Lead</span>' +
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
        searchPlaceholder: 'Pesquisar Lead'
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
                columns: [0, 1, 2, 3, 4, 5, 6],
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
                columns: [0, 1, 2, 3, 4, 5, 6],
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
                columns: [0, 1, 2, 3, 4, 5, 6],
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
                columns: [0, 1, 2, 3, 4, 5, 6],
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
                columns: [0, 1, 2, 3, 4, 5, 6],
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

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-enviar-lead')) {
      var button = event.target.closest('.js-enviar-lead');
      var leadId = button.getAttribute('data-id');
      document.querySelector('#idMailing').value = leadId;
    }
  });
});
