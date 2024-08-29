/**
 * app-ecommerce-product-list
 */

'use strict';

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

  var dt_product_table = $('.datatables-products'),
    productAdd = baseUrl + 'app/ecommerce/product/add',
    statusObj = {
      16: { title: 'VENDA', class: 'bg-label-primary' },
      17: { title: 'IMPLANTADO', class: 'bg-label-success' },
      18: { title: 'ESTORNADO', class: 'bg-label-danger' }
    };

  if (dt_product_table.length) {
    var dt_products = dt_product_table.DataTable({
      ajax: '/vendas/lista-vendas-mes',
      columns: [
        { data: 'id', title: 'ID' },
        { data: 'nome_contrato', title: 'Nome Contrato' },
        { data: 'email', title: 'Email' },
        { data: 'valor_contrato', title: 'Valor Contrato' },
        { data: 'data_vigencia', title: 'Data Vigencia' },
        { data: 'status', title: 'Status' },
        { data: 'created_at', title: 'Feito em:' }
      ],
      columnDefs: [
        {
          title: 'Valor Contrato',
          targets: 3,
          render: function (data, type, full, meta) {
            var valorContrato = full['valor_contrato'];
            var formattedValue = new Intl.NumberFormat('pt-BR', {
              style: 'currency',
              currency: 'BRL'
            }).format(valorContrato);
            return formattedValue;
          }
        },
        {
          title: 'Feito em:',
          targets: 6,
          render: function (data, type, full, meta) {
            var createdAt = new Date(full['created_at']);
            var day = String(createdAt.getDate()).padStart(2, '0');
            var month = String(createdAt.getMonth() + 1).padStart(2, '0');
            var year = createdAt.getFullYear();
            var hours = String(createdAt.getHours()).padStart(2, '0');
            var minutes = String(createdAt.getMinutes()).padStart(2, '0');
            var seconds = String(createdAt.getSeconds()).padStart(2, '0');
            var formattedDateTime = `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
            return formattedDateTime;
          }
        },
        {
          title: 'Data Vigência',
          targets: 4,
          render: function (data, type, full, meta) {
            var dataVigencia = new Date(full['data_vigencia']);
            return dataVigencia.toLocaleDateString('pt-BR');
          }
        },
        {
          title: 'Status',
          targets: 5,
          render: function (data, type, full, meta) {
            var $status = full['status'];
            return (
              '<span class="badge rounded-pill ' +
              statusObj[$status].class +
              '" text-capitalized>' +
              statusObj[$status].title +
              '</span>'
            );
          }
        }
      ],
      order: [2, 'asc'],
      dom:
        '<"card-header d-flex border-top rounded-0 flex-wrap py-0 pb-5 pb-md-0"' +
        '<"me-5 ms-n2"f>' +
        '<"d-flex justify-content-start justify-content-md-end align-items-baseline"<"dt-action-buttons d-flex align-items-start align-items-md-center justify-content-sm-center gap-4"lB>>' +
        '>t' +
        '<"row mx-1"' +
        '<"col-sm-12 col-md-6"i>' +
        '<"col-sm-12 col-md-6"p>' +
        '>',
      lengthMenu: [7, 10, 20, 50, 70, 100],
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search',
        info: 'Displaying _START_ to _END_ of _TOTAL_ entries'
      },
      buttons: [
        // Botões de exportação
      ],
      responsive: {
        details: {
          display: $.fn.dataTable.Responsive.display.modal({
            header: function (row) {
              var data = row.data();
              return 'Details of ' + data['product_name'];
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
                    ':</td>' +
                    '<td>' +
                    col.data +
                    '</td>' +
                    '</tr>'
                : '';
            }).join('');
            return data ? $('<table class="table"/><tbody />').append(data) : false;
          }
        }
      },
      initComplete: function () {
        this.api()
          .columns(6)
          .every(function () {
            var column = this;
            var select = $(
              '<select id="ProductStatus" class="form-select text-capitalize"><option value="">Selecione um Status</option></select>'
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
                select.append('<option value="' + statusObj[d].title + '">' + statusObj[d].title + '</option>');
              });
          });
      }
    });

    $('.dataTables_length').addClass('my-0');
    $('.dt-action-buttons').addClass('pt-0');
    $('.dt-buttons').addClass('d-flex flex-wrap');

    dt_product_table.on('draw', function () {
      // Aplicar a máscara de telefone após a tabela ser desenhada
      $('.telefone-mask').each(function () {
        new Cleave(this, {
          phone: true,
          phoneRegionCode: 'BR'
        });
      });
    });
  }
});
