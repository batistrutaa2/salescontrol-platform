/**
 * DataTables Advanced (jquery)
 */

'use strict';

$(function () {
  var dt_ajax_table = $('.datatables-ajax');

  if (dt_ajax_table.length) {
    var dt_ajax = dt_ajax_table.dataTable({
      processing: true,
      ajax: '/back-office/lista-contratos',
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>><"table-responsive"t><"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'nome_contrato' },
        { data: 'descricao' },
        { data: 'valor_contrato', title: 'Valor', render: $.fn.dataTable.render.number('.', ',', 2, 'R$ ') },
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
              '<a href="/back-office/abrir-contrato/' +
              full['id'] +
              '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Edit</span></a>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ]
    });
  }
});
