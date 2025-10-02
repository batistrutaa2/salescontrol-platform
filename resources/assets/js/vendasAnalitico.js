'use strict';

$(function () {
  // Variáveis globais
  let chartInstances = {};
  let currentPage = 1;
  let currentFilters = {};

  // Aguardar DOM estar completamente carregado
  $(document).ready(function () {
    init();
  });

  // Event listeners
  $(document).on('submit', '#filtrosForm', handleFiltroSubmit);
  $(document).on('click', '#exportarBtn', exportarRelatorio);
  $(document).on('click', '.pagination a', handlePaginationClick);
  $(document).on('change', '#filtroStatusTabela', handleFiltroStatusTabela);

  function init() {
    // Verificar se os elementos existem antes de inicializar
    if ($('#filtrosForm').length === 0) {
      console.warn('Formulário de filtros não encontrado');
      return;
    }

    carregarDadosIniciais();
  }

  function carregarDadosIniciais() {
    showLoading();

    $.ajax({
      url: '/vendas/dados',
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json',
        'Content-Type': 'application/json'
      },
      success: function (response) {
        console.log('Dados carregados:', response);
        if (response.success) {
          popularFiltros(response.data);
          atualizarDashboard(response.data);
          carregarVendas();
        } else {
          showToast('Erro ao carregar dados', 'error');
        }
      },
      error: function (xhr, status, error) {
        console.error('Erro ao carregar dados:', { xhr, status, error });
        let message = 'Erro ao carregar dados';

        if (xhr.responseJSON && xhr.responseJSON.message) {
          message = xhr.responseJSON.message;
        } else if (xhr.status === 404) {
          message = 'Rota não encontrada. Verifique as rotas do sistema.';
        } else if (xhr.status === 500) {
          message = 'Erro interno do servidor';
        }

        showToast(message, 'error');
      },
      complete: function () {
        hideLoading();
      }
    });
  }

  function popularFiltros(data) {
    try {
      // Popular anos
      const $filtroAno = $('#filtroAno');
      if ($filtroAno.length) {
        $filtroAno.empty().append('<option value="">Todos</option>');
        if (data.anos_disponiveis && Array.isArray(data.anos_disponiveis)) {
          data.anos_disponiveis.forEach(ano => {
            $filtroAno.append(`<option value="${ano}">${ano}</option>`);
          });
        }
      }

      // Popular vendedores
      const $filtroVendedor = $('#filtroVendedor');
      if ($filtroVendedor.length) {
        $filtroVendedor.empty().append('<option value="">Todos</option>');
        if (data.vendedores && Array.isArray(data.vendedores)) {
          data.vendedores.forEach(vendedor => {
            $filtroVendedor.append(`<option value="${vendedor.id}">${vendedor.name}</option>`);
          });
        }
      }

      // Popular operadoras
      const $filtroOperadora = $('#filtroOperadora');
      if ($filtroOperadora.length) {
        $filtroOperadora.empty().append('<option value="">Todas</option>');
        if (data.operadoras && Array.isArray(data.operadoras)) {
          data.operadoras.forEach(operadora => {
            $filtroOperadora.append(`<option value="${operadora}">${operadora}</option>`);
          });
        }
      }

      // Popular status da tabela
      const $filtroStatusTabela = $('#filtroStatusTabela');
      if ($filtroStatusTabela.length) {
        $filtroStatusTabela.empty().append('<option value="">Todos os status</option>');
        if (data.status_disponiveis && Array.isArray(data.status_disponiveis)) {
          data.status_disponiveis.forEach(status => {
            $filtroStatusTabela.append(`<option value="${status.id}">${status.descricao}</option>`);
          });
        }
      }
    } catch (error) {
      console.error('Erro ao popular filtros:', error);
    }
  }

  function handleFiltroSubmit(e) {
    e.preventDefault();
    currentFilters = $(this).serialize();
    currentPage = 1;
    aplicarFiltros();
  }

  function aplicarFiltros() {
    showLoading();

    $.ajax({
      url: '/vendas/dados',
      method: 'GET',
      data: currentFilters,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json'
      },
      success: function (response) {
        if (response.success) {
          atualizarDashboard(response.data);
          carregarVendas();
        } else {
          showToast('Erro ao aplicar filtros', 'error');
        }
      },
      error: function (xhr, status, error) {
        console.error('Erro ao aplicar filtros:', { xhr, status, error });
        showToast('Erro ao aplicar filtros', 'error');
      },
      complete: function () {
        hideLoading();
      }
    });
  }

  function carregarVendas() {
    const statusTabela = $('#filtroStatusTabela').val();
    let params = currentFilters + `&page=${currentPage}`;

    if (statusTabela) {
      params += `&status=${statusTabela}`;
    }

    $.ajax({
      url: '/vendas/listar',
      method: 'GET',
      data: params,
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        Accept: 'application/json'
      },
      success: function (response) {
        if (response.success) {
          renderizarTabelaVendas(response.data);
          renderizarPaginacao(response.pagination);
        }
      },
      error: function (xhr) {
        console.error('Erro ao carregar vendas:', xhr);
        showToast('Erro ao carregar lista de vendas', 'error');
      }
    });
  }

  function handleFiltroStatusTabela() {
    currentPage = 1;
    carregarVendas();
  }

  function exportarRelatorio() {
    const params = currentFilters;
    window.open(`/vendas/exportar?${params}`, '_blank');
  }

  function atualizarDashboard(data) {
    try {
      // Atualizar cards de resumo
      if (data.resumo_geral) {
        $('#totalContratos').text(formatNumber(data.resumo_geral.total_contratos || 0));
        $('#valorTotal').text(formatCurrency(data.resumo_geral.valor_total || 0));
        $('#valorImplantado').text(formatCurrency(data.resumo_geral.valor_implantado || 0));
        $('#totalVidas').text(formatNumber(data.resumo_geral.total_vidas || 0));
        $('#ticketMedio').text(formatCurrency(data.resumo_geral.ticket_medio || 0));
      }

      // Atualizar gráficos
      atualizarGraficos(data);
    } catch (error) {
      console.error('Erro ao atualizar dashboard:', error);
    }
  }

  function atualizarGraficos(data) {
    try {
      // Destruir gráficos existentes
      Object.values(chartInstances).forEach(chart => {
        if (chart && typeof chart.destroy === 'function') {
          chart.destroy();
        }
      });
      chartInstances = {};

      // Criar novos gráficos apenas se os elementos existirem
      if (document.getElementById('vendasPorMesChart')) {
        criarGraficoVendasPorMes(data.vendas_por_mes || []);
      }

      if (document.getElementById('vendasPorVendedorChart')) {
        criarGraficoVendasPorVendedor(data.vendas_por_vendedor || []);
      }

      if (document.getElementById('vendasPorOperadoraChart')) {
        criarGraficoVendasPorOperadora(data.vendas_por_operadora || []);
      }

      if (document.getElementById('vendasPorPlanoChart')) {
        criarGraficoVendasPorPlano(data.vendas_por_plano || []);
      }
    } catch (error) {
      console.error('Erro ao atualizar gráficos:', error);
    }
  }

  // FUNÇÕES DE GRÁFICOS
  function criarGraficoVendasPorMes(dados) {
    try {
      const ctx = document.getElementById('vendasPorMesChart');
      if (!ctx) return;

      const labels = dados.map(item => `${String(item.mes).padStart(2, '0')}/${item.ano}`);
      const valores = dados.map(item => parseFloat(item.valor_total) || 0);
      const quantidades = dados.map(item => parseInt(item.total_vendas) || 0);

      chartInstances.vendasPorMes = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Valor Total (R$)',
              data: valores,
              borderColor: 'rgb(75, 192, 192)',
              backgroundColor: 'rgba(75, 192, 192, 0.1)',
              tension: 0.1,
              yAxisID: 'y'
            },
            {
              label: 'Quantidade',
              data: quantidades,
              borderColor: 'rgb(255, 99, 132)',
              backgroundColor: 'rgba(255, 99, 132, 0.1)',
              tension: 0.1,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              type: 'linear',
              display: true,
              position: 'left',
              title: {
                display: true,
                text: 'Valor (R$)'
              }
            },
            y1: {
              type: 'linear',
              display: true,
              position: 'right',
              title: {
                display: true,
                text: 'Quantidade'
              },
              grid: {
                drawOnChartArea: false
              }
            }
          }
        }
      });
    } catch (error) {
      console.error('Erro ao criar gráfico vendas por mês:', error);
    }
  }

  function criarGraficoVendasPorVendedor(dados) {
    try {
      const ctx = document.getElementById('vendasPorVendedorChart');
      if (!ctx) return;

      const labels = dados.map(item => item.vendedor);
      const valores = dados.map(item => parseFloat(item.valor_total) || 0);

      chartInstances.vendasPorVendedor = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Valor Total (R$)',
              data: valores,
              backgroundColor: 'rgba(54, 162, 235, 0.6)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Valor (R$)'
              }
            }
          }
        }
      });
    } catch (error) {
      console.error('Erro ao criar gráfico vendas por vendedor:', error);
    }
  }

  function criarGraficoVendasPorOperadora(dados) {
    try {
      const ctx = document.getElementById('vendasPorOperadoraChart');
      if (!ctx) return;

      const labels = dados.map(item => item.operadora);
      const valores = dados.map(item => parseFloat(item.valor_total) || 0);
      const cores = [
        'rgba(255, 99, 132, 0.6)',
        'rgba(54, 162, 235, 0.6)',
        'rgba(255, 205, 86, 0.6)',
        'rgba(75, 192, 192, 0.6)',
        'rgba(153, 102, 255, 0.6)',
        'rgba(255, 159, 64, 0.6)'
      ];

      chartInstances.vendasPorOperadora = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Valor Total (R$)',
              data: valores,
              backgroundColor: cores.slice(0, dados.length),
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      });
    } catch (error) {
      console.error('Erro ao criar gráfico vendas por operadora:', error);
    }
  }

  function criarGraficoVendasPorPlano(dados) {
    try {
      const ctx = document.getElementById('vendasPorPlanoChart');
      if (!ctx) return;

      const labels = dados.map(item => item.nome_plano);
      const quantidades = dados.map(item => parseInt(item.total_vendas) || 0);

      chartInstances.vendasPorPlano = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Quantidade de Vendas',
              data: quantidades,
              backgroundColor: 'rgba(153, 102, 255, 0.6)',
              borderColor: 'rgba(153, 102, 255, 1)',
              borderWidth: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          indexAxis: 'y',
          scales: {
            x: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Quantidade'
              }
            }
          }
        }
      });
    } catch (error) {
      console.error('Erro ao criar gráfico vendas por plano:', error);
    }
  }

  // FUNÇÕES DE TABELA E PAGINAÇÃO
  function renderizarTabelaVendas(vendas) {
    const $tbody = $('#vendasTableBody');
    if (!$tbody.length) return;

    $tbody.empty();

    if (!vendas || vendas.length === 0) {
      $tbody.append('<tr><td colspan="8" class="text-center">Nenhuma venda encontrada</td></tr>');
      return;
    }

    vendas.forEach(venda => {
      const status = venda.contato_corretor && venda.contato_corretor.tabulacao
        ? venda.contato_corretor.tabulacao.descricao
        : '-';

      const row = `
                <tr>
                    <td>${formatDate(venda.data_vigencia)}</td>
                    <td>${venda.nome_contrato || '-'}</td>
                    <td>${venda.cpf_cnpj || '-'}</td>
                    <td>${venda.user ? venda.user.name : '-'}</td>
                    <td>${venda.operadora || '-'}</td>
                    <td>${venda.nome_plano || '-'}</td>
                    <td>${formatCurrency(venda.valor_contrato)}</td>
                    <td>${status}</td>
                </tr>
            `;
      $tbody.append(row);
    });
  }

  function renderizarPaginacao(pagination) {
    const $paginacao = $('#paginacao');
    if (!$paginacao.length) return;

    $paginacao.empty();

    if (!pagination || pagination.last_page <= 1) return;

    // Botão anterior
    if (pagination.current_page > 1) {
      $paginacao.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">Anterior</a>
                </li>
            `);
    }

    // Páginas
    for (let i = 1; i <= pagination.last_page; i++) {
      const active = i === pagination.current_page ? 'active' : '';
      $paginacao.append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
    }

    // Botão próximo
    if (pagination.current_page < pagination.last_page) {
      $paginacao.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">Próximo</a>
                </li>
            `);
    }
  }

  function handlePaginationClick(e) {
    e.preventDefault();
    const page = $(this).data('page');
    if (page && page !== currentPage) {
      currentPage = page;
      carregarVendas();
    }
  }

  // FUNÇÕES UTILITÁRIAS
  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value || 0);
  }

  function formatNumber(value) {
    return new Intl.NumberFormat('pt-BR').format(value || 0);
  }

  function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
  }

  function showLoading() {
    const $loading = $('#loadingOverlay');
    if ($loading.length) {
      $loading.removeClass('d-none');
    }
  }

  function hideLoading() {
    const $loading = $('#loadingOverlay');
    if ($loading.length) {
      $loading.addClass('d-none');
    }
  }

  function showToast(message, type = 'info') {
    console.log(`${type.toUpperCase()}: ${message}`);
    // Implementar notificação toast se necessário
    if (typeof toastr !== 'undefined') {
      toastr[type](message);
    } else {
      alert(message);
    }
  }
});
