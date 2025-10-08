'use strict';

$(document).ready(function () {
  let chartEvolucaoMensal = null;
  let chartTaxaConversao = null;
  let chartOperadoras = null;

  // Carregar dados ao iniciar
  carregarDados();

  // Eventos dos botões
  $('#btnFiltrar').on('click', function () {
    carregarDados();
  });

  $('#btnLimpar').on('click', function () {
    $('#filtroAno').val($('#filtroAno option:first').val());
    $('#filtroVendedor').val('');
    carregarDados();
  });

  function carregarDados() {
    const ano = $('#filtroAno').val();
    const vendedorId = $('#filtroVendedor').val();

    // Mostrar loading
    $('#loading').show();
    $('#conteudoRelatorio').hide();

    $.ajax({
      url: '/relatorios/desempenho-anual/dados',
      method: 'GET',
      data: {
        ano: ano,
        vendedor_id: vendedorId
      },
      success: function (response) {
        if (response.success) {
          renderizarDados(response.data);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: response.message || 'Erro ao carregar dados'
          });
        }
      },
      error: function (xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Erro!',
          text: 'Erro ao carregar dados do relatório'
        });
      },
      complete: function () {
        $('#loading').hide();
        $('#conteudoRelatorio').show();
      }
    });
  }

  function renderizarDados(data) {
    // 1. Estatísticas gerais
    renderizarEstatisticasGerais(data.estatisticas_gerais);

    // 2. Dados por trimestre
    renderizarDadosTrimestre(data.dados_trimestre);

    // 3. Evolução mensal
    renderizarEvolucaoMensal(data.evolucao_mensal);

    // 4. Taxa de conversão
    renderizarTaxaConversao(data.taxa_conversao);

    // 5. Top planos
    renderizarTopPlanos(data.top_planos);

    // 6. Distribuição por operadora
    renderizarDistribuicaoOperadora(data.distribuicao_operadora);

    // 7. Ranking de vendedores ou detalhes do vendedor
    if (data.ranking_vendedores) {
      renderizarRankingVendedores(data.ranking_vendedores);
      $('#rankingSection').show();
      $('#detalhesVendedorSection').hide();
    } else if (data.detalhes_vendedor) {
      renderizarDetalhesVendedor(data.detalhes_vendedor);
      $('#rankingSection').hide();
      $('#detalhesVendedorSection').show();
    } else {
      $('#rankingSection').hide();
      $('#detalhesVendedorSection').hide();
    }
  }

  function renderizarEstatisticasGerais(stats) {
    $('#totalLeadsUnicos').text(formatarNumero(stats.total_leads_unicos));
    $('#totalVendas').text(formatarNumero(stats.total_vendas));
    $('#valorTotal').text(formatarMoeda(stats.valor_total));
    $('#ticketMedio').text(formatarMoeda(stats.ticket_medio));
  }

  function renderizarDadosTrimestre(trimestres) {
    const container = $('#dadosTrimestre');
    container.empty();

    const cores = ['primary', 'success', 'info', 'warning'];

    trimestres.forEach((trimestre, index) => {
      const cor = cores[index];
      const card = `
        <div class="col-md-3 col-sm-6 mb-3">
          <div class="card border-start border-${cor} border-3">
            <div class="card-body">
              <h6 class="card-title text-${cor}">${trimestre.periodo}</h6>
              <div class="mt-3">
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Leads:</span>
                  <strong>${formatarNumero(trimestre.leads_unicos)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Vendas:</span>
                  <strong>${formatarNumero(trimestre.total_vendas)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Valor:</span>
                  <strong>${formatarMoeda(trimestre.valor_total)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Ticket:</span>
                  <strong>${formatarMoeda(trimestre.ticket_medio)}</strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Conversão:</span>
                  <strong class="text-${cor}">${trimestre.taxa_conversao}%</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
      container.append(card);
    });
  }

  function renderizarEvolucaoMensal(evolucao) {
    const ctx = document.getElementById('chartEvolucaoMensal');

    if (chartEvolucaoMensal) {
      chartEvolucaoMensal.destroy();
    }

    const labels = evolucao.map(item => item.nome_mes);
    const dataLeads = evolucao.map(item => item.leads_unicos);
    const dataVendas = evolucao.map(item => item.total_vendas);
    const dataValor = evolucao.map(item => item.valor_total);

    chartEvolucaoMensal = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Leads Trabalhados',
            data: dataLeads,
            borderColor: 'rgb(105, 108, 255)',
            backgroundColor: 'rgba(105, 108, 255, 0.1)',
            tension: 0.4,
            yAxisID: 'y'
          },
          {
            label: 'Vendas Realizadas',
            data: dataVendas,
            borderColor: 'rgb(113, 221, 55)',
            backgroundColor: 'rgba(113, 221, 55, 0.1)',
            tension: 0.4,
            yAxisID: 'y'
          },
          {
            label: 'Valor Total (R$)',
            data: dataValor,
            borderColor: 'rgb(3, 195, 236)',
            backgroundColor: 'rgba(3, 195, 236, 0.1)',
            tension: 0.4,
            yAxisID: 'y1',
            hidden: true
          }
        ]
      },
      options: {
        responsive: true,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'top'
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.dataset.yAxisID === 'y1') {
                  label += formatarMoeda(context.parsed.y);
                } else {
                  label += formatarNumero(context.parsed.y);
                }
                return label;
              }
            }
          }
        },
        scales: {
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            title: {
              display: true,
              text: 'Quantidade'
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            title: {
              display: true,
              text: 'Valor (R$)'
            },
            grid: {
              drawOnChartArea: false
            }
          }
        }
      }
    });
  }

  function renderizarTaxaConversao(taxas) {
    const ctx = document.getElementById('chartTaxaConversao');

    if (chartTaxaConversao) {
      chartTaxaConversao.destroy();
    }

    // Remover texto central existente antes de recriar
    $('#chartTaxaConversao').parent().find('.chart-center-text').remove();

    // Calcular média anual
    const mediaAnual = taxas.reduce((sum, item) => sum + item.taxa, 0) / taxas.length;

    chartTaxaConversao = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Taxa de Conversão', 'Não Convertidos'],
        datasets: [
          {
            data: [mediaAnual, 100 - mediaAnual],
            backgroundColor: ['rgb(113, 221, 55)', 'rgb(234, 234, 255)'],
            borderWidth: 0
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return context.label + ': ' + context.parsed.toFixed(2) + '%';
              }
            }
          }
        },
        cutout: '70%'
      }
    });

    // Adicionar texto no centro
    const centerText = `
      <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
        <div style="font-size: 28px; font-weight: bold; color: #71dd37;">${mediaAnual.toFixed(2)}%</div>
        <div style="font-size: 12px; color: #999;">Taxa Média</div>
      </div>
    `;

    $('#chartTaxaConversao')
      .parent()
      .append(`<div class="chart-center-text">${centerText}</div>`);
  }

  function renderizarTopPlanos(planos) {
    const tbody = $('#tabelaTopPlanos tbody');
    tbody.empty();

    if (planos.length === 0) {
      tbody.append('<tr><td colspan="4" class="text-center">Nenhum dado encontrado</td></tr>');
      return;
    }

    planos.forEach(plano => {
      const row = `
        <tr>
          <td>${plano.nome_plano}</td>
          <td>${plano.operadora}</td>
          <td class="text-center"><span class="badge bg-primary">${formatarNumero(plano.total_vendas)}</span></td>
          <td class="text-end">${formatarMoeda(plano.valor_total)}</td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderizarDistribuicaoOperadora(operadoras) {
    const ctx = document.getElementById('chartOperadoras');

    if (chartOperadoras) {
      chartOperadoras.destroy();
    }

    const labels = operadoras.map(item => item.operadora);
    const dataValores = operadoras.map(item => parseFloat(item.valor_total));

    // Cores dinâmicas
    const cores = [
      'rgb(105, 108, 255)',
      'rgb(113, 221, 55)',
      'rgb(3, 195, 236)',
      'rgb(255, 171, 0)',
      'rgb(255, 62, 29)',
      'rgb(105, 108, 255)',
      'rgb(113, 221, 55)'
    ];

    chartOperadoras = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Valor Total (R$)',
            data: dataValores,
            backgroundColor: cores.slice(0, labels.length),
            borderWidth: 0
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return 'Valor: ' + formatarMoeda(context.parsed.y);
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return 'R$ ' + value.toLocaleString('pt-BR');
              }
            }
          }
        }
      }
    });
  }

  function renderizarRankingVendedores(ranking) {
    const tbody = $('#tabelaRankingVendedores tbody');
    tbody.empty();

    if (ranking.length === 0) {
      tbody.append('<tr><td colspan="7" class="text-center">Nenhum dado encontrado</td></tr>');
      return;
    }

    ranking.forEach((vendedor, index) => {
      const medalha =
        index === 0
          ? '<i class="ri-medal-fill text-warning"></i>'
          : index === 1
            ? '<i class="ri-medal-fill text-secondary"></i>'
            : index === 2
              ? '<i class="ri-medal-fill" style="color: #cd7f32;"></i>'
              : index + 1;

      const row = `
        <tr>
          <td class="text-center">${medalha}</td>
          <td><strong>${vendedor.vendedor}</strong></td>
          <td class="text-center">${formatarNumero(vendedor.total_leads)}</td>
          <td class="text-center"><span class="badge bg-success">${formatarNumero(vendedor.total_vendas)}</span></td>
          <td class="text-end"><strong>${formatarMoeda(vendedor.valor_total)}</strong></td>
          <td class="text-end">${formatarMoeda(vendedor.ticket_medio)}</td>
          <td class="text-center">
            <span class="badge ${
              vendedor.taxa_conversao >= 10
                ? 'bg-success'
                : vendedor.taxa_conversao >= 5
                  ? 'bg-warning'
                  : 'bg-danger'
            }">
              ${vendedor.taxa_conversao}%
            </span>
          </td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  function renderizarDetalhesVendedor(detalhes) {
    $('#nomeVendedor').text(detalhes.nome);

    const tbody = $('#tabelaPlanosFavoritos tbody');
    tbody.empty();

    if (detalhes.planos_favoritos.length === 0) {
      tbody.append('<tr><td colspan="3" class="text-center">Nenhum dado encontrado</td></tr>');
      return;
    }

    detalhes.planos_favoritos.forEach(plano => {
      const row = `
        <tr>
          <td>${plano.nome_plano}</td>
          <td>${plano.operadora}</td>
          <td class="text-center"><span class="badge bg-primary">${formatarNumero(plano.total)}</span></td>
        </tr>
      `;
      tbody.append(row);
    });
  }

  // Funções auxiliares
  function formatarMoeda(valor) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(valor || 0);
  }

  function formatarNumero(valor) {
    return new Intl.NumberFormat('pt-BR').format(valor || 0);
  }
});
