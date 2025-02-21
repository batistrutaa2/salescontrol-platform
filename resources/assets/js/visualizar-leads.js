/**
 * app-ecommerce-product-list
 */

'use strict';

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
  var dt_product_table = $('.datatables-products');
  // E-commerce Products datatable
  if (dt_product_table.length) {
    var dt_products = dt_product_table.DataTable({
      ajax: '/mailing/getLeads',
      columns: [
        { data: 'id', title: '' },
        { data: 'id', title: 'ID' },
        { data: 'nome_corretor', title: 'Corretor' },
        { data: 'nome_cliente', title: 'Cliente' },
        { data: 'cpf', title: 'CPF' },
        { data: 'telefone', title: 'Telefone' },
        { data: 'valor_plano_atual', title: 'Valor do Plano' },
        { data: 'status', title: 'Status' },
        { data: 'Prazo', title: 'Prazo' },
        { data: 'created_at', title: 'Criado Em' },
        { data: null, title: 'Ações' }
      ],
      columnDefs: [
        {
          targets: 0,
          orderable: false,
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">',
            selectRow: true
          },
          responsivePriority: 4
        },
        {
          targets: 4,
          render: function (data, type, full, meta) {
            let value = data.toString().replace(/\D/g, '');

            if (value.length <= 11) {
              // Aplica a máscara de CPF
              value = value
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            } else {
              // Aplica a máscara de CNPJ
              value = value
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
            }

            return value;
          }
        },
        {
          targets: 5, // Coluna com telefone
          render: function (data, type, row) {
            let value = data.toString().replace(/\D/g, ''); // Remove caracteres não numéricos

            if (value.length === 11) {
              // Aplica a máscara para telefone celular (ex.: (11) 94556-7166)
              value = value.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
            } else if (value.length === 10) {
              // Aplica a máscara para telefone fixo (ex.: (11) 1234-5678)
              value = value.replace(/^(\d{2})(\d{4})(\d{4})$/, '($1) $2-$3');
            }

            return value;
          }
        },
        {
          targets: 6, // Coluna com valor
          render: function (data, type, row) {
            if (data === null || data === undefined || isNaN(parseFloat(data))) {
              return 'R$ 0,00'; // Valor padrão se o dado for null ou não numérico
            }

            let value = parseFloat(data); // Converte o valor para número
            return 'R$ ' + value.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
        },
        {
          targets: 8, // Coluna com valor
          render: function (data, type, row) {
            // Verifica o valor de 'TRATATIVA' e aplica a cor
            if (data === 'Fora do Prazo') {
              return '<span style="color: red;">' + data + '</span>';
            } else {
              return '<span style="color: green;">' + data + '</span>';
            }
          }
        },
        {
          targets: 9, // Coluna com data
          render: function (data, type, row) {
            if (!data) {
              return 'Data não disponível'; // Valor padrão se o dado for null ou undefined
            }

            let date = new Date(data);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let year = date.getFullYear();
            return `${day}/${month}/${year}`;
          }
        },
        {
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
              data.id +
              '"class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Editar Usuario</span></a>' +
              '<button type="button" class="dropdown-item js-transferir-leads" data-bs-toggle="modal" data-bs-target="#modalcomments" data-id="' +
              data.id +
              '">' +
              '<i class="ri-arrow-left-right-fill"></i><span>Transferir Contato</span></button>' +
              '<a href="/mailing/excluir-lead/' +
              full['id'] +
              '" class="dropdown-item"><i class="ri-delete-bin-line me-2"></i><span>Excluir Lead</span></a>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ],
      select: {
        style: 'multi', // Permitir múltiplas seleções
        selector: 'td:first-child input[type="checkbox"]'
      },
      order: [[2, 'asc']],
      dom:
        '<"card-header d-flex border-top rounded-0 flex-wrap py-0 pb-5 pb-md-0"' +
        '<"me-5 ms-n2"f>' +
        '<"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex align-items-start align-items-md-center justify-content-sm-center gap-4"lB>>' +
        '>t' +
        '<"row mx-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [
        [10, 25, 50, 100, -1],
        [10, 25, 50, 100, 'TODOS']
      ],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Buscar',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ entradas'
      },
      buttons: [
        {
          extend: 'collection',
          className: 'btn btn-outline-secondary dropdown-toggle me-4 waves-effect waves-light',
          text: '<i class="ri-download-line ri-16px me-2"></i><span class="d-none d-sm-inline-block">Exportar</span>',
          buttons: [
            {
              extend: 'print',
              text: '<i class="ri-printer-line me-1"></i>Imprimir',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
                format: {
                  body: function (inner, coldex, rowdex) {
                    var el = $.parseHTML(inner);
                    return $(el).text();
                  }
                }
              }
            },
            {
              extend: 'csv',
              text: '<i class="ri-file-text-line me-1"></i>CSV',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
                format: {
                  body: function (inner, coldex, rowdex) {
                    var el = $.parseHTML(inner);
                    return $(el).text();
                  }
                }
              }
            },
            {
              extend: 'excel',
              text: '<i class="ri-file-excel-line me-1"></i>Excel',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
                format: {
                  body: function (inner, coldex, rowdex) {
                    var el = $.parseHTML(inner);
                    return $(el).text();
                  }
                }
              }
            },
            {
              extend: 'pdf',
              text: '<i class="ri-file-pdf-line me-1"></i>PDF',
              className: 'dropdown-item',
              exportOptions: {
                columns: [1, 2, 3, 4, 5, 6, 7, 8, 9],
                format: {
                  body: function (inner, coldex, rowdex) {
                    var el = $.parseHTML(inner);
                    return $(el).text();
                  }
                }
              }
            }
          ]
        }
      ],
      initComplete: function () {
        this.api()
          .columns(2)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="corretorFilter" class="form-select text-capitalize"><option value="">TODOS</option></select>'
            )
              .appendTo('.product_status')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                if (d != null) {
                  select.append('<option value="' + d + '">' + d + '</option>');
                }
              });
          });

        this.api()
          .columns(7)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="statusFilter" class="form-select text-capitalize"><option value="">TODOS</option></select>'
            )
              .appendTo('.product_category')
              .on('change', function () {
                var val = $.fn.dataTable.util.escapeRegex($(this).val());
                column.search(val ? '^' + val + '$' : '', true, false).draw();
              });

            column
              .data()
              .unique()
              .sort()
              .each(function (d, j) {
                select.append('<option value="' + d + '">' + d + '</option>');
              });
          });
      }
    });
  }

  $('#btn-transferir-massa').on('click', function () {
    var selectedRows = dt_products.rows({ selected: true }).data();

    if (selectedRows.length === 0) {
      alert('Por favor, selecione pelo menos um item.');
      $('#modalTransferContacts').modal('hide');
      return;
    }

    var selectedLeads = [];

    selectedRows.each(function (data) {
      selectedLeads.push(data.id);
    });

    $('#selectedLeadIds').val(selectedLeads.join(','));
    $('#modalTransferContacts').modal('show');
  });

  document.addEventListener('click', function (event) {
    if (event.target.closest('.js-transferir-leads')) {
      var button = event.target.closest('.js-transferir-leads');
      var leadId = button.getAttribute('data-id');
      document.querySelector('#idMailing').value = leadId;
    }
  });
});
