'use strict';

$(document).ready(function () {
  const endpoint = '/vendas/getResultsBroker';
  const formatMoeda = (valor) => {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL',
      minimumFractionDigits: 2
    }).format(valor ?? 0);
  };

  function fetchData(mes, ano) {
    if (!mes || !ano) return;

    $.ajax({
      url: endpoint,
      method: 'GET',
      data: { mes, ano },
      beforeSend: function () {
        $('.js-valorCadastrado, .js-implantado, .js-quantidadeContatosImportados, .js-conversao').text('...');
      },
      success: function (res) {
        toastr.success('Dados carregados com sucesso!');
        $('.js-valorCadastrado').text(formatMoeda(res.vendasCadastradasMes));
        $('.js-implantado').text(formatMoeda(res.vendasImplantadasMes ?? '0'));
        $('.js-quantidadeContatosImportados').text(res.quantidadeContatosMes ?? '0');
        $('.js-conversao').text(res.conversao ?? '0%');
        if ($.fn.DataTable.isDataTable('#tabela-vendas-detalhadas')) {
          $('#tabela-vendas-detalhadas').DataTable().clear().rows.add(res.vendas).draw();
        } else {
          $('#tabela-vendas-detalhadas').DataTable({
            destroy: true,
            data: res.vendas,
            columns: [
              { data: 'id' },
              { data: 'nome_contrato' },
              {
                data: 'descricao',
                render: function (data, type, row) {
                  let badgeClass = '';
                  let icon = '';
                  let label = data?.toUpperCase() ?? '';

                  switch (label) {
                    case 'VENDA':
                      badgeClass = 'badge bg-label-info';
                      icon = '<i class="ri-check-line me-1"></i>';
                      break;
                    case 'IMPLANTADO':
                      badgeClass = 'badge bg-label-success';
                      icon = '<i class="ri-tools-line me-1"></i>';
                      break;
                    case 'DECLINADO':
                      badgeClass = 'badge bg-label-secondary';
                      icon = '<i class="ri-close-line me-1"></i>';
                      break;
                    case 'ESTORNO':
                      badgeClass = 'badge bg-label-danger';
                      icon = '<i class="ri-loop-left-line me-1"></i>';
                      break;
                    default:
                      badgeClass = 'badge bg-label-warning';
                      icon = '<i class="ri-question-line me-1"></i>';
                  }

                  return `<span class="${badgeClass}">${icon}${label}</span>`;
                }
              },
              {
                data: 'valor_contrato',
                render: function (data) {
                  return formatMoeda(data);
                }
              },
              {
                data: null,
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                  if (row.descricao?.toUpperCase() === 'ESTORNO') {
                    return `<button class="btn btn-sm btn-outline-danger ver-contrato" data-id="${row.id}">
                    <i class="ri-file-text-line me-1"></i> Ver contrato
                  </button>`;
                  }
                  return '';
                }
              }
            ],
            language: {
              url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
          });

        }
      },
      error: function (err) {
        console.error('Erro ao buscar relatório:', err);
        toastr.error('Erro ao carregar dados do relatório.');
      }
    });
  }

  $('#select-month, #select-year').on('change', function () {
    const mes = $('#select-month').val();
    const ano = $('#select-year').val();
    if (!isNaN(mes) && !isNaN(ano)) {
      fetchData(mes, ano);
    }
  });

  // Carrega automaticamente mês atual ao iniciar
  const currentMonth = new Date().getMonth() + 1;
  const currentYear = new Date().getFullYear();
  $('#select-month').val(currentMonth);
  $('#select-year').val(currentYear);
  fetchData(currentMonth, currentYear);

});
