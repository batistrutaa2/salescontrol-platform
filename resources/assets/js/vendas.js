'use strict';

$(function () {
  var dt_ajax_table = $('.datatables-ajax');

  // Função para capturar e aplicar o filtro
  function applyFilter() {
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();

    // Recarregar a DataTable com os filtros
    dt_ajax_table.DataTable().ajax.reload();
  }

  if (dt_ajax_table.length) {
    var dt_ajax = dt_ajax_table.dataTable({
      processing: true,
      ajax: {
        url: '/back-office/lista-vendas-filtro',
        data: function (d) {
          // Adicionar as datas ao objeto de dados da requisição
          d.start_date = $('#start_date').val();
          d.end_date = $('#end_date').val();
        }
      },
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
              '" class="dropdown-item"><i class="ri-edit-box-line me-2"></i><span>Ver contrato</span></a>' +
              '</div>' +
              '</div>'
            );
          }
        }
      ]
    });
  }

  // Ao clicar no botão "Filtrar", aplica o filtro
  $('#filter_button').on('click', function () {
    applyFilter();
  });

  // Ao clicar no botão "Limpar", limpa os campos de filtro e recarrega a tabela
  $('#clear_filter').on('click', function () {
    $('#start_date').val('');
    $('#end_date').val('');
    applyFilter();
  });
});
