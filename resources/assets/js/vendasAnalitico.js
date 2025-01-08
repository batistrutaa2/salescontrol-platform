'use strict';

$(function () {
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();

  const salesTable = $('#sales-table').DataTable({
    ajax: {
      url: `/vendas/vendasAnalitico?month=${currentMonth}&year=${currentYear}`,
      dataSrc: ''
    },
    columns: [
      { data: 'id', title: 'ID' },
      { data: 'corretor', title: 'Corretor' },
      { data: 'nome_contrato', title: 'Cliente' },
      { data: 'valor_contrato', title: 'Valor', render: $.fn.dataTable.render.number('.', ',', 2, 'R$ ') },
      {
        data: 'dataCadastro',
        title: 'Data Cadastro',
        render: function (data) {
          if (!data) return '-';
          const date = new Date(data);
          const day = String(date.getDate()).padStart(2, '0');
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const year = date.getFullYear();
          return `${day}/${month}/${year}`;
        }
      }
    ],
    language: {
      url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
    },
    responsive: true,
    order: [[4, 'desc']]
  });

  $('#filter-month, #filter-year').on('change', function () {
    const month = $('#filter-month').val();
    const year = $('#filter-year').val();

    if (month && year) {
      salesTable.ajax.url(`/vendas/vendasAnalitico?month=${month}&year=${year}`).load();
    }
  });

  $('#filter-form').on('submit', function (e) {
    e.preventDefault();

    let month = $('#filter-month').val();
    let year = $('#filter-year').val();

    if (!month) {
      const currentDate = new Date();
      month = currentDate.getMonth() + 1;
    }

    if (!year) {
      const currentDate = new Date();
      year = currentDate.getFullYear();
    }
    salesTable.ajax.url(`/vendas/vendasAnalitico?month=${month}&year=${year}`).load();
  });
});
