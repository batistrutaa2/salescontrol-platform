'use strict';

$(function () {
  const table = $('#contracts_table').DataTable({
    processing: true,
    serverSide: true,
    deferRender: true, // Renderiza os dados apenas quando necessário
    ajax: {
      url: '/back-office/lista-vendas-filtro', // Rota para filtrar vendas
      data: function (d) {
        d.start_date = $('#start_date').val();
        d.end_date = $('#end_date').val();
      }
    },
    columns: [
      { data: 'id', name: 'id' },
      { data: 'nome_contrato', name: 'nome_contrato' },
      { data: 'cpf_cnpj', name: 'cpf_cnpj' },
      { data: 'telefone1', name: 'telefone1' },
      { data: 'descricao', name: 'descricao' },
      { data: 'valor_contrato', name: 'valor_contrato', render: $.fn.dataTable.render.number('.', ',', 2, 'R$ ') },
      {
        data: 'created_at',
        name: 'created_at',
        render: function (data) {
          const parts = data.split(' '); // Separa a data e a hora
          const dateParts = parts[0].split('/'); // Separa dia, mês, ano
          const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}T${parts[1]}`;
          const date = new Date(formattedDate);

          if (isNaN(date.getTime())) {
            return ''; // Retorna uma string vazia se a data for inválida
          }

          return date.toLocaleDateString('pt-BR');
        }
      },
      {
        data: null,
        name: 'actions',
        orderable: false,
        searchable: false,
        render: function (data) {
          const contractUrl = `/back-office/abrir-contrato/${data.id}`;
          const deleteButton = `<button class="btn btn-danger btn-sm delete-contract" data-id="${data.id}">Excluir</button>`;
          return `
            <div class="d-flex">
              <a href="${contractUrl}" class="btn btn-primary btn-sm me-2">Acessar</a>
              ${deleteButton}
            </div>
          `;
        }
      }
    ],
    responsive: true,
    lengthMenu: [10, 25, 50],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
    },
    dom: 'frtip',
    searchDelay: 500, // Atraso na pesquisa para evitar chamadas excessivas ao backend
    stateSave: false // Desabilitar o stateSave durante o carregamento
  });

  // Filtrar dados ao clicar no botão
  $('#filter_button').on('click', function () {
    // Usar requestAnimationFrame para otimizar a alteração do DOM
    requestAnimationFrame(function () {
      table.ajax.reload(); // Recarregar os dados da tabela com o filtro de datas
    });
  });

  // Limpar filtros e clicar no botão de filtro novamente
  $('#clear_filter').on('click', function () {
    $('#start_date').val('');
    $('#end_date').val('');

    requestAnimationFrame(function () {
      table.ajax.reload(); // Recarregar a tabela após limpar os filtros
    });
  });
});
