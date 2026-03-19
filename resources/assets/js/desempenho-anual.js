'use strict';

$(document).ready(function () {
  let chartEvolucaoMensal = null;
  let chartTaxaConversao = null;
  let chartOperadoras = null;

  // Detect dark mode
  const isDarkMode = document.documentElement.classList.contains('dark-style');

  // Theme colors
  const colors = {
    primary: '#7C3AED',
    primaryLight: '#A78BFA',
    success: '#10B981',
    successLight: '#34D399',
    info: '#06B6D4',
    infoLight: '#22D3EE',
    warning: '#F59E0B',
    warningLight: '#FBBF24',
    danger: '#EF4444',
    text: isDarkMode ? '#F9FAFB' : '#1F2937',
    textMuted: isDarkMode ? '#9CA3AF' : '#6B7280',
    gridColor: isDarkMode ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)',
    bgLight: isDarkMode ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.02)'
  };

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

    const configs = [
      { class: 'trim-q1', textClass: 'text-q1', highlightClass: 'highlight-q1' },
      { class: 'trim-q2', textClass: 'text-q2', highlightClass: 'highlight-q2' },
      { class: 'trim-q3', textClass: 'text-q3', highlightClass: 'highlight-q3' },
      { class: 'trim-q4', textClass: 'text-q4', highlightClass: 'highlight-q4' }
    ];

    trimestres.forEach((trimestre, index) => {
      const config = configs[index];
      const card = `
        <div class="da-trimestre-card ${config.class}">
          <div class="da-trim-header">
            <h4 class="da-trim-title ${config.textClass}">${trimestre.periodo}</h4>
          </div>
          <div class="da-trim-stats">
            <div class="da-trim-row">
              <span class="da-trim-label">Leads</span>
              <span class="da-trim-value">${formatarNumero(trimestre.leads_unicos)}</span>
            </div>
            <div class="da-trim-row">
              <span class="da-trim-label">Vendas</span>
              <span class="da-trim-value">${formatarNumero(trimestre.total_vendas)}</span>
            </div>
            <div class="da-trim-row">
              <span class="da-trim-label">Angariação</span>
              <span class="da-trim-value">${formatarMoeda(trimestre.valor_angariacao)}</span>
            </div>
            <div class="da-trim-row">
              <span class="da-trim-label">Valor</span>
              <span class="da-trim-value">${formatarMoeda(trimestre.valor_total)}</span>
            </div>
            <div class="da-trim-row">
              <span class="da-trim-label">Ticket</span>
              <span class="da-trim-value">${formatarMoeda(trimestre.ticket_medio)}</span>
            </div>
            <div class="da-trim-row">
              <span class="da-trim-label">Conversão</span>
              <span class="da-trim-value highlight ${config.highlightClass}">${trimestre.taxa_conversao}%</span>
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
            borderColor: colors.primary,
            backgroundColor: `${colors.primary}15`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.primary,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'y'
          },
          {
            label: 'Vendas Realizadas',
            data: dataVendas,
            borderColor: colors.success,
            backgroundColor: `${colors.success}15`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.success,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'y'
          },
          {
            label: 'Valor Total (R$)',
            data: dataValor,
            borderColor: colors.info,
            backgroundColor: `${colors.info}15`,
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: colors.info,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            yAxisID: 'y1',
            hidden: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: 'index',
          intersect: false
        },
        plugins: {
          legend: {
            position: 'top',
            labels: {
              color: colors.textMuted,
              usePointStyle: true,
              pointStyle: 'circle',
              padding: 20,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 12,
                weight: 500
              }
            }
          },
          tooltip: {
            backgroundColor: isDarkMode ? '#1e202f' : '#fff',
            titleColor: colors.text,
            bodyColor: colors.textMuted,
            borderColor: colors.gridColor,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            titleFont: {
              family: "'Plus Jakarta Sans', sans-serif",
              size: 13,
              weight: 600
            },
            bodyFont: {
              family: "'Plus Jakarta Sans', sans-serif",
              size: 12
            },
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
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11
              }
            }
          },
          y: {
            type: 'linear',
            display: true,
            position: 'left',
            grid: {
              color: colors.gridColor
            },
            ticks: {
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11
              }
            },
            title: {
              display: true,
              text: 'Quantidade',
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11,
                weight: 600
              }
            }
          },
          y1: {
            type: 'linear',
            display: true,
            position: 'right',
            grid: {
              drawOnChartArea: false
            },
            ticks: {
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11
              }
            },
            title: {
              display: true,
              text: 'Valor (R$)',
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11,
                weight: 600
              }
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

    // Calcular média anual
    const mediaAnual = taxas.reduce((sum, item) => sum + item.taxa, 0) / taxas.length;

    // Update center text
    $('#donutValue').text(mediaAnual.toFixed(2) + '%');

    chartTaxaConversao = new Chart(ctx, {
      type: 'doughnut',
      data: {
        labels: ['Taxa de Conversão', 'Não Convertidos'],
        datasets: [
          {
            data: [mediaAnual, 100 - mediaAnual],
            backgroundColor: [colors.success, colors.bgLight],
            borderWidth: 0,
            borderRadius: 4
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
            backgroundColor: isDarkMode ? '#1e202f' : '#fff',
            titleColor: colors.text,
            bodyColor: colors.textMuted,
            borderColor: colors.gridColor,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              label: function (context) {
                return context.label + ': ' + context.parsed.toFixed(2) + '%';
              }
            }
          }
        },
        cutout: '75%'
      }
    });
  }

  function renderizarTopPlanos(planos) {
    const tbody = $('#tabelaTopPlanos tbody');
    tbody.empty();

    if (planos.length === 0) {
      tbody.append('<tr><td colspan="4" style="text-align: center; padding: 2rem; color: var(--da-text-muted);">Nenhum dado encontrado</td></tr>');
      return;
    }

    planos.forEach(plano => {
      const row = `
        <tr>
          <td><strong>${plano.nome_plano}</strong></td>
          <td>${plano.operadora}</td>
          <td style="text-align: center;"><span class="da-badge badge-primary">${formatarNumero(plano.total_vendas)}</span></td>
          <td style="text-align: right;" class="da-money">${formatarMoeda(plano.valor_total)}</td>
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

    // Gradient colors
    const backgroundColors = [
      colors.primary,
      colors.success,
      colors.info,
      colors.warning,
      colors.danger,
      colors.primaryLight,
      colors.successLight
    ];

    chartOperadoras = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Valor Total (R$)',
            data: dataValores,
            backgroundColor: backgroundColors.slice(0, labels.length),
            borderWidth: 0,
            borderRadius: 8,
            borderSkipped: false
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
            backgroundColor: isDarkMode ? '#1e202f' : '#fff',
            titleColor: colors.text,
            bodyColor: colors.textMuted,
            borderColor: colors.gridColor,
            borderWidth: 1,
            padding: 12,
            cornerRadius: 8,
            callbacks: {
              label: function (context) {
                return 'Valor: ' + formatarMoeda(context.parsed.y);
              }
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: colors.gridColor
            },
            ticks: {
              color: colors.textMuted,
              font: {
                family: "'Plus Jakarta Sans', sans-serif",
                size: 11
              },
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
      tbody.append('<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--da-text-muted);">Nenhum dado encontrado</td></tr>');
      return;
    }

    ranking.forEach((vendedor, index) => {
      let medalha;
      if (index === 0) {
        medalha = '<span class="da-medal medal-gold">🥇</span>';
      } else if (index === 1) {
        medalha = '<span class="da-medal medal-silver">🥈</span>';
      } else if (index === 2) {
        medalha = '<span class="da-medal medal-bronze">🥉</span>';
      } else {
        medalha = `<span class="da-rank-number">${index + 1}</span>`;
      }

      const badgeClass = vendedor.taxa_conversao >= 10
        ? 'badge-success'
        : vendedor.taxa_conversao >= 5
          ? 'badge-warning'
          : 'badge-danger';

      const row = `
        <tr>
          <td style="text-align: center;">${medalha}</td>
          <td><strong>${vendedor.vendedor}</strong></td>
          <td style="text-align: center;">${formatarNumero(vendedor.total_leads)}</td>
          <td style="text-align: center;"><span class="da-badge badge-success">${formatarNumero(vendedor.total_vendas)}</span></td>
          <td style="text-align: right;" class="da-money">${formatarMoeda(vendedor.valor_total)}</td>
          <td style="text-align: right;" class="da-money">${formatarMoeda(vendedor.ticket_medio)}</td>
          <td style="text-align: center;"><span class="da-badge ${badgeClass}">${vendedor.taxa_conversao}%</span></td>
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
      tbody.append('<tr><td colspan="3" style="text-align: center; padding: 2rem; color: var(--da-text-muted);">Nenhum dado encontrado</td></tr>');
      return;
    }

    detalhes.planos_favoritos.forEach(plano => {
      const row = `
        <tr>
          <td><strong>${plano.nome_plano}</strong></td>
          <td>${plano.operadora}</td>
          <td style="text-align: center;"><span class="da-badge badge-primary">${formatarNumero(plano.total)}</span></td>
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
