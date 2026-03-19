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
  var dt_product_table = $('.datatables-schedules');
  // E-commerce Products datatable
  if (dt_product_table.length) {
    var dt_products = dt_product_table.DataTable({
      ajax: '/comercial/getSchedules',
      columns: [
        { data: 'id' },
        { data: 'nome_corretor' },
        { data: 'nome_cliente' },
        { data: 'horario_agendamento' },
        { data: null, title: 'Ações' }
      ],
      columnDefs: [
        {
          // Actions
          targets: -1,
          title: 'AÇÕES',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            return `
                  <div class="ag-actions">
                      <a href="/comercial/abrir-cliente/${data.id}" class="ag-btn-action ag-btn-open" title="Abrir cliente">
                          <i class="ri-user-line"></i>
                      </a>
                      <button class="ag-btn-action ag-btn-reschedule reagendar" title="Reagendar" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                          <i class="ri-calendar-schedule-line"></i>
                      </button>
                      <button class="ag-btn-action ag-btn-done visto" title="Marcar como visto" data-bs-toggle="modal" data-bs-target="#backKanban">
                          <i class="ri-check-line"></i>
                      </button>
                      <button class="ag-btn-action ag-btn-discard descarte" title="Descartar" data-bs-toggle="modal" data-bs-target="#discardModal">
                          <i class="ri-delete-bin-5-line"></i>
                      </button>
                  </div>
              `;
          }
        },
        {
          targets: 3, // Coluna com data
          render: function (data, type, row) {
            if (!data) {
              return 'Data não disponível'; // Valor padrão se o dado for null ou undefined
            }

            let date = new Date(data);
            let day = String(date.getDate()).padStart(2, '0');
            let month = String(date.getMonth() + 1).padStart(2, '0');
            let year = date.getFullYear();
            let hours = String(date.getHours()).padStart(2, '0');
            let minutes = String(date.getMinutes()).padStart(2, '0');
            let seconds = String(date.getSeconds()).padStart(2, '0');

            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
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
                columns: [1, 2, 3, 4, 5],
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
                columns: [1, 2, 3, 4, 5],
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
                columns: [1, 2, 3, 4, 5],
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
                columns: [1, 2, 3, 4, 5],
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
      ]
    });
  }
});

$(document).on('click', '.reagendar', function () {
  var table = $('.datatables-schedules').DataTable();
  var rowData = table.row($(this).parents('tr')).data();
  $('#scheduleModal').find('#leadIdInputSchedule').val(rowData.id);
});

$(document).on('click', '.visto', function () {
  var table = $('.datatables-schedules').DataTable();
  var rowData = table.row($(this).parents('tr')).data();
  $('#backKanban').find('#leadInputVisto').val(rowData.id);
});

$(document).on('click', '.descarte', function () {
  var table = $('.datatables-schedules').DataTable();
  var rowData = table.row($(this).parents('tr')).data();
  $('#discardModal').find('#leadIdInputDescarte').val(rowData.id);
});
