'use strict';

/**
 * Backoffice Performance Report
 * Analytics dashboard for sales pipeline and bottleneck analysis
 */

(function () {
  // Theme colors configuration
  let cardColor, labelColor, headingColor, borderColor;

  if (isDarkStyle) {
    cardColor = config.colors_dark.cardColor;
    labelColor = config.colors_dark.textMuted;
    headingColor = config.colors_dark.headingColor;
    borderColor = config.colors_dark.borderColor;
  } else {
    cardColor = config.colors.cardColor;
    labelColor = config.colors.textMuted;
    headingColor = config.colors.headingColor;
    borderColor = config.colors.borderColor;
  }

  // Chart color palette
  const chartColors = {
    primary: '#6366f1',
    primaryLight: '#818cf8',
    success: '#10b981',
    successLight: '#34d399',
    warning: '#f59e0b',
    warningLight: '#fbbf24',
    danger: '#ef4444',
    dangerLight: '#f87171',
    info: '#06b6d4',
    infoLight: '#22d3ee'
  };

  // Chart instances
  let funnelChart, statusDistributionChart, timePerStageChart, monthlyEvolutionChart, operatorChart, sellerChart;
  let bottleneckTable;
  let flatpickrInicio, flatpickrFim;

  // Initialize on DOM ready
  document.addEventListener('DOMContentLoaded', function () {
    initializeDatePickers();
    initializeCharts();
    initializeDataTable();
    bindEvents();
    loadData();
  });

  /**
   * Initialize Flatpickr date pickers
   */
  function initializeDatePickers() {
    const hoje = new Date();
    const inicioAno = new Date(hoje.getFullYear(), 0, 1);

    flatpickrInicio = flatpickr('#data-inicio', {
      dateFormat: 'd/m/Y',
      defaultDate: inicioAno,
      locale: 'pt',
      allowInput: true
    });

    flatpickrFim = flatpickr('#data-fim', {
      dateFormat: 'd/m/Y',
      defaultDate: hoje,
      locale: 'pt',
      allowInput: true
    });
  }

  /**
   * Initialize all ApexCharts
   */
  function initializeCharts() {
    // Funnel Chart
    const funnelOptions = {
      chart: {
        type: 'bar',
        height: 350,
        toolbar: { show: false },
        fontFamily: 'Plus Jakarta Sans, sans-serif'
      },
      plotOptions: {
        bar: {
          borderRadius: 8,
          horizontal: true,
          distributed: true,
          barHeight: '70%',
          dataLabels: {
            position: 'bottom'
          }
        }
      },
      colors: [
        chartColors.primary,
        chartColors.info,
        chartColors.primaryLight,
        chartColors.warning,
        chartColors.infoLight,
        chartColors.success
      ],
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: {
          colors: ['#fff'],
          fontSize: '13px',
          fontWeight: 600
        },
        formatter: function (val, opt) {
          return opt.w.globals.labels[opt.dataPointIndex] + ': ' + val;
        },
        offsetX: 10
      },
      series: [{
        name: 'Contratos',
        data: []
      }],
      xaxis: {
        categories: ['Venda', 'Analise Docs', 'Analise Operadora', 'Aguard. Assinatura', 'Boleto Disponivel', 'Implantado'],
        labels: {
          style: {
            colors: labelColor,
            fontSize: '12px'
          }
        }
      },
      yaxis: {
        labels: {
          show: false
        }
      },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light',
        x: { show: false },
        y: {
          title: {
            formatter: () => ''
          }
        }
      },
      legend: { show: false },
      grid: {
        borderColor: borderColor,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } }
      }
    };

    const funnelEl = document.querySelector('#funnelChart');
    if (funnelEl) {
      funnelChart = new ApexCharts(funnelEl, funnelOptions);
      funnelChart.render();
    }

    // Status Distribution Chart (Donut)
    const statusDistOptions = {
      chart: {
        type: 'donut',
        height: 350,
        fontFamily: 'Plus Jakarta Sans, sans-serif'
      },
      series: [],
      labels: [],
      colors: [
        chartColors.primary,
        chartColors.success,
        chartColors.warning,
        chartColors.danger,
        chartColors.info,
        chartColors.primaryLight,
        chartColors.successLight,
        chartColors.warningLight
      ],
      plotOptions: {
        pie: {
          donut: {
            size: '70%',
            labels: {
              show: true,
              name: {
                show: true,
                fontSize: '14px',
                fontWeight: 600,
                color: headingColor
              },
              value: {
                show: true,
                fontSize: '24px',
                fontWeight: 700,
                color: headingColor,
                formatter: function (val) {
                  return parseInt(val);
                }
              },
              total: {
                show: true,
                label: 'Total',
                fontSize: '14px',
                fontWeight: 600,
                color: labelColor,
                formatter: function (w) {
                  return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                }
              }
            }
          }
        }
      },
      dataLabels: {
        enabled: false
      },
      legend: {
        position: 'bottom',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
          colors: labelColor,
          useSeriesColors: false
        },
        markers: {
          width: 10,
          height: 10,
          radius: 3
        },
        itemMargin: {
          horizontal: 10,
          vertical: 5
        }
      },
      stroke: {
        width: 0
      },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light'
      }
    };

    const statusDistEl = document.querySelector('#statusDistributionChart');
    if (statusDistEl) {
      statusDistributionChart = new ApexCharts(statusDistEl, statusDistOptions);
      statusDistributionChart.render();
    }

    // Time Per Stage Chart (Bar)
    const timePerStageOptions = {
      chart: {
        type: 'bar',
        height: 300,
        toolbar: { show: false },
        fontFamily: 'Plus Jakarta Sans, sans-serif'
      },
      series: [{
        name: 'Dias (Media)',
        data: []
      }],
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '50%',
          distributed: true,
          dataLabels: {
            position: 'top'
          }
        }
      },
      colors: [
        chartColors.primary,
        chartColors.info,
        chartColors.warning,
        chartColors.primaryLight,
        chartColors.success
      ],
      dataLabels: {
        enabled: true,
        formatter: function (val) {
          return val + 'd';
        },
        offsetY: -20,
        style: {
          fontSize: '12px',
          fontWeight: 600,
          colors: [headingColor]
        }
      },
      xaxis: {
        categories: [],
        labels: {
          style: {
            colors: labelColor,
            fontSize: '11px'
          },
          rotate: -45,
          rotateAlways: true
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        title: {
          text: 'Dias',
          style: {
            color: labelColor,
            fontSize: '12px',
            fontWeight: 600
          }
        },
        labels: {
          style: {
            colors: labelColor
          },
          formatter: function (val) {
            return Math.round(val);
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4
      },
      legend: { show: false },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light',
        y: {
          formatter: function (val) {
            return val + ' dias';
          }
        }
      }
    };

    const timePerStageEl = document.querySelector('#timePerStageChart');
    if (timePerStageEl) {
      timePerStageChart = new ApexCharts(timePerStageEl, timePerStageOptions);
      timePerStageChart.render();
    }

    // Monthly Evolution Chart (Area)
    const monthlyEvolutionOptions = {
      chart: {
        type: 'area',
        height: 300,
        toolbar: { show: false },
        fontFamily: 'Plus Jakarta Sans, sans-serif',
        zoom: { enabled: false }
      },
      series: [
        { name: 'Vendas', data: [] },
        { name: 'Implantados', data: [] }
      ],
      colors: [chartColors.primary, chartColors.success],
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.5,
          opacityTo: 0.1,
          stops: [0, 90, 100]
        }
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      dataLabels: { enabled: false },
      xaxis: {
        categories: [],
        labels: {
          style: {
            colors: labelColor,
            fontSize: '12px'
          }
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: {
            colors: labelColor
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
          colors: labelColor,
          useSeriesColors: false
        },
        markers: {
          width: 10,
          height: 10,
          radius: 3
        }
      },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light',
        shared: true,
        intersect: false
      }
    };

    const monthlyEvolutionEl = document.querySelector('#monthlyEvolutionChart');
    if (monthlyEvolutionEl) {
      monthlyEvolutionChart = new ApexCharts(monthlyEvolutionEl, monthlyEvolutionOptions);
      monthlyEvolutionChart.render();
    }

    // Operator Chart (Bar)
    const operatorChartOptions = {
      chart: {
        type: 'bar',
        height: 300,
        toolbar: { show: false },
        fontFamily: 'Plus Jakarta Sans, sans-serif',
        stacked: false
      },
      series: [
        { name: 'Total', data: [] },
        { name: 'Implantados', data: [] }
      ],
      colors: [chartColors.primary, chartColors.success],
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          borderRadius: 6,
          dataLabels: {
            position: 'top'
          }
        }
      },
      dataLabels: {
        enabled: false
      },
      xaxis: {
        categories: [],
        labels: {
          style: {
            colors: labelColor,
            fontSize: '11px'
          },
          rotate: -45,
          rotateAlways: true
        },
        axisBorder: { show: false },
        axisTicks: { show: false }
      },
      yaxis: {
        labels: {
          style: {
            colors: labelColor
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
          colors: labelColor,
          useSeriesColors: false
        },
        markers: {
          width: 10,
          height: 10,
          radius: 3
        }
      },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light',
        shared: true,
        intersect: false
      }
    };

    const operatorChartEl = document.querySelector('#operatorChart');
    if (operatorChartEl) {
      operatorChart = new ApexCharts(operatorChartEl, operatorChartOptions);
      operatorChart.render();
    }

    // Seller Chart (Bar Horizontal)
    const sellerChartOptions = {
      chart: {
        type: 'bar',
        height: 300,
        toolbar: { show: false },
        fontFamily: 'Plus Jakarta Sans, sans-serif'
      },
      series: [
        { name: 'Total', data: [] },
        { name: 'Implantados', data: [] }
      ],
      colors: [chartColors.primary, chartColors.success],
      plotOptions: {
        bar: {
          horizontal: true,
          barHeight: '60%',
          borderRadius: 4
        }
      },
      dataLabels: {
        enabled: false
      },
      xaxis: {
        categories: [],
        labels: {
          style: {
            colors: labelColor,
            fontSize: '12px'
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: labelColor,
            fontSize: '12px'
          }
        }
      },
      grid: {
        borderColor: borderColor,
        strokeDashArray: 4,
        xaxis: { lines: { show: true } },
        yaxis: { lines: { show: false } }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '13px',
        fontWeight: 500,
        labels: {
          colors: labelColor,
          useSeriesColors: false
        },
        markers: {
          width: 10,
          height: 10,
          radius: 3
        }
      },
      tooltip: {
        theme: isDarkStyle ? 'dark' : 'light',
        shared: true,
        intersect: false
      }
    };

    const sellerChartEl = document.querySelector('#sellerChart');
    if (sellerChartEl) {
      sellerChart = new ApexCharts(sellerChartEl, sellerChartOptions);
      sellerChart.render();
    }
  }

  /**
   * Initialize DataTable for bottlenecks
   */
  function initializeDataTable() {
    const tableEl = document.querySelector('#bottleneckTable');
    if (!tableEl) return;

    bottleneckTable = $(tableEl).DataTable({
      processing: true,
      paging: true,
      pageLength: 10,
      lengthChange: false,
      searching: true,
      ordering: true,
      order: [[3, 'desc']], // Order by days stuck
      language: {
        search: '',
        searchPlaceholder: 'Buscar...',
        emptyTable: 'Nenhum gargalo identificado',
        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
        paginate: {
          first: 'Primeiro',
          last: 'Ultimo',
          next: 'Proximo',
          previous: 'Anterior'
        }
      },
      dom: '<"d-flex justify-content-between align-items-center mb-3"<"search-box"f>>rtip',
      columns: [
        { data: 'numero_proposta', className: 'contract-name' },
        { data: 'nome_contrato' },
        {
          data: 'status_atual',
          className: 'status-cell',
          render: function (data) {
            let badgeClass = 'warning';
            if (data === 'PENDENCIA') badgeClass = 'danger';
            if (data === 'ANALISE OPERADORA') badgeClass = 'info';
            return `<span class="status-badge ${badgeClass}">${data}</span>`;
          }
        },
        {
          data: 'dias_parado',
          className: 'days-stuck',
          render: function (data) {
            const dias = Math.round(data);
            const colorClass = dias > 7 ? 'text-danger' : (dias > 5 ? 'text-warning' : '');
            return `<span class="${colorClass}">${dias} dias</span>`;
          }
        },
        { data: 'vendedor' },
        { data: 'operadora' },
        {
          data: 'valor_contrato',
          className: 'value-cell',
          render: function (data) {
            return formatCurrency(data);
          }
        },
        {
          data: 'id',
          orderable: false,
          render: function (data) {
            return `<a href="/back-office/abrir-contrato/${data}" class="btn-action" title="Abrir contrato">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                <polyline points="15 3 21 3 21 9"/>
                <line x1="10" y1="14" x2="21" y2="3"/>
              </svg>
            </a>`;
          }
        }
      ],
      createdRow: function (row, data) {
        if (Math.round(data.dias_parado) > 7) {
          row.classList.add('critical');
        }
      }
    });
  }

  /**
   * Bind event handlers
   */
  function bindEvents() {
    const btnAplicar = document.querySelector('#btn-aplicar-filtro');
    if (btnAplicar) {
      btnAplicar.addEventListener('click', loadData);
    }
  }

  /**
   * Convert date from dd/mm/yyyy to yyyy-mm-dd
   */
  function converterDataParaBackend(dataBrasileira) {
    if (!dataBrasileira) return '';
    const partes = dataBrasileira.split('/');
    if (partes.length !== 3) return '';
    const dia = partes[0].padStart(2, '0');
    const mes = partes[1].padStart(2, '0');
    const ano = partes[2];
    return `${ano}-${mes}-${dia}`;
  }

  /**
   * Load data from API
   */
  function loadData() {
    const dataInicioEl = document.querySelector('#data-inicio');
    const dataFimEl = document.querySelector('#data-fim');

    const startDate = converterDataParaBackend(dataInicioEl?.value) || '';
    const endDate = converterDataParaBackend(dataFimEl?.value) || '';

    showLoading();

    fetch(`/back-office/relatorio-performance/data?start_date=${startDate}&end_date=${endDate}`)
      .then(response => response.json())
      .then(data => {
        hideLoading();
        updateKPIs(data.metricas);
        updatePipeline(data.distribuicao_status);
        updateCharts(data);
        updateBottleneckTable(data.gargalos);
        updatePendencias(data.pendencias_frequentes);
      })
      .catch(error => {
        hideLoading();
        console.error('Error loading data:', error);
        Swal.fire({
          icon: 'error',
          title: 'Erro ao carregar dados',
          text: 'Nao foi possivel carregar os dados do relatorio.',
          confirmButtonColor: chartColors.primary
        });
      });
  }

  /**
   * Update KPI cards
   */
  function updateKPIs(metricas) {
    if (!metricas) return;

    animateValue('.js-total-vendas', metricas.total_vendas || 0);
    animateValue('.js-implantadas', metricas.implantadas || 0);
    animateValue('.js-em-andamento', metricas.em_andamento || 0);
    animateValue('.js-perdidas', metricas.perdidas || 0);

    const taxaEl = document.querySelector('.js-taxa-conversao');
    if (taxaEl) {
      taxaEl.textContent = `${(metricas.taxa_conversao || 0).toFixed(1)}% taxa de conversao`;
    }

    const tempoEl = document.querySelector('.js-tempo-medio');
    if (tempoEl) {
      const dias = Math.round(metricas.tempo_medio_implantacao || 0);
      tempoEl.textContent = `${dias} dias`;
    }

    const totalValorEl = document.querySelector('.js-total-valor');
    if (totalValorEl) {
      totalValorEl.textContent = formatCurrency(metricas.valor_total || 0);
    }

    const valorPerdidoEl = document.querySelector('.js-valor-perdido');
    if (valorPerdidoEl) {
      valorPerdidoEl.textContent = `${formatCurrency(metricas.valor_perdido || 0)} em contratos`;
    }
  }

  /**
   * Update pipeline visualization
   */
  function updatePipeline(distribuicao) {
    if (!distribuicao || !Array.isArray(distribuicao)) return;

    const statusMap = {
      'VENDA': '.js-pipeline-venda',
      'ANALISE DE DOCUMENTOS': '.js-pipeline-analise-docs',
      'ANALISE OPERADORA': '.js-pipeline-analise-operadora',
      'CONTR. GERADO - AGUARDANDO ASSINATURA': '.js-pipeline-contrato-gerado',
      'BOLETO DISPONIVEL': '.js-pipeline-boleto',
      'IMPLANTADO': '.js-pipeline-implantado',
      'PENDENCIA': '.js-pipeline-pendencia',
      'REGULARIZADO': '.js-pipeline-regularizado',
      'AGUARD. ASSINATURA DA DS': '.js-pipeline-aguard-ds',
      'ESTORNO': '.js-pipeline-estorno',
      'DECLINADO': '.js-pipeline-declinado'
    };

    // Reset all values
    Object.values(statusMap).forEach(selector => {
      const el = document.querySelector(selector);
      if (el) el.textContent = '0';
    });

    // Update with actual values
    distribuicao.forEach(item => {
      const selector = statusMap[item.status];
      if (selector) {
        const el = document.querySelector(selector);
        if (el) {
          animateValue(selector, item.quantidade);
        }
      }
    });
  }

  /**
   * Update all charts
   */
  function updateCharts(data) {
    // Funnel Chart
    if (funnelChart && data.pipeline_funil) {
      const funnelData = data.pipeline_funil;
      funnelChart.updateOptions({
        xaxis: {
          categories: funnelData.map(item => item.etapa)
        }
      });
      funnelChart.updateSeries([{
        data: funnelData.map(item => item.quantidade)
      }]);
    }

    // Status Distribution Chart
    if (statusDistributionChart && data.distribuicao_status) {
      const statusData = data.distribuicao_status.filter(item => item.quantidade > 0);
      statusDistributionChart.updateOptions({
        labels: statusData.map(item => item.status)
      });
      statusDistributionChart.updateSeries(statusData.map(item => item.quantidade));
    }

    // Time Per Stage Chart
    if (timePerStageChart && data.tempo_por_etapa) {
      const tempoData = data.tempo_por_etapa;
      timePerStageChart.updateOptions({
        xaxis: {
          categories: tempoData.map(item => item.etapa)
        }
      });
      timePerStageChart.updateSeries([{
        data: tempoData.map(item => parseFloat(item.media_dias.toFixed(1)))
      }]);
    }

    // Monthly Evolution Chart
    if (monthlyEvolutionChart && data.evolucao_mensal) {
      const evolucaoData = data.evolucao_mensal;
      monthlyEvolutionChart.updateOptions({
        xaxis: {
          categories: evolucaoData.map(item => item.mes)
        }
      });
      monthlyEvolutionChart.updateSeries([
        { name: 'Vendas', data: evolucaoData.map(item => item.vendas) },
        { name: 'Implantados', data: evolucaoData.map(item => item.implantados) }
      ]);
    }

    // Operator Chart
    if (operatorChart && data.por_operadora) {
      const operadoraData = data.por_operadora.slice(0, 8); // Top 8
      operatorChart.updateOptions({
        xaxis: {
          categories: operadoraData.map(item => item.operadora || 'N/A')
        }
      });
      operatorChart.updateSeries([
        { name: 'Total', data: operadoraData.map(item => item.total) },
        { name: 'Implantados', data: operadoraData.map(item => item.implantados) }
      ]);
    }

    // Seller Chart
    if (sellerChart && data.por_vendedor) {
      const vendedorData = data.por_vendedor.slice(0, 10); // Top 10
      sellerChart.updateOptions({
        xaxis: {
          categories: vendedorData.map(item => item.vendedor || 'N/A')
        }
      });
      sellerChart.updateSeries([
        { name: 'Total', data: vendedorData.map(item => item.total) },
        { name: 'Implantados', data: vendedorData.map(item => item.implantados) }
      ]);
    }
  }

  /**
   * Update bottleneck table
   */
  function updateBottleneckTable(gargalos) {
    if (!bottleneckTable) return;

    const totalGargalosEl = document.querySelector('.js-total-gargalos');
    if (totalGargalosEl) {
      totalGargalosEl.textContent = gargalos?.length || 0;
    }

    bottleneckTable.clear();
    if (gargalos && gargalos.length > 0) {
      bottleneckTable.rows.add(gargalos);
    }
    bottleneckTable.draw();
  }

  /**
   * Update pendencias grid
   */
  function updatePendencias(pendencias) {
    const grid = document.querySelector('#pendenciasGrid');
    if (!grid) return;

    if (!pendencias || pendencias.length === 0) {
      grid.innerHTML = `
        <div class="empty-state">
          <svg class="empty-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <path d="M9 12l2 2 4-4"/>
          </svg>
          <h3 class="empty-title">Nenhuma pendencia frequente</h3>
          <p class="empty-text">Otimo! Nao ha motivos de pendencia recorrentes.</p>
        </div>
      `;
      return;
    }

    const maxCount = Math.max(...pendencias.map(p => p.quantidade));

    grid.innerHTML = pendencias.map((item, index) => {
      const percentage = (item.quantidade / maxCount) * 100;
      return `
        <div class="pendencia-card">
          <div class="pendencia-rank">${index + 1}</div>
          <div class="pendencia-content">
            <div class="pendencia-motivo" title="${item.motivo}">${item.motivo}</div>
            <div class="pendencia-count"><span>${item.quantidade}</span> ocorrencias</div>
          </div>
          <div class="pendencia-bar">
            <div class="bar-fill" style="width: ${percentage}%"></div>
          </div>
        </div>
      `;
    }).join('');
  }

  /**
   * Animate number value
   */
  function animateValue(selector, endValue) {
    const el = document.querySelector(selector);
    if (!el) return;

    const startValue = parseInt(el.textContent) || 0;
    const duration = 500;
    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);

      // Easing function
      const easeOutQuart = 1 - Math.pow(1 - progress, 4);
      const currentValue = Math.round(startValue + (endValue - startValue) * easeOutQuart);

      el.textContent = currentValue;

      if (progress < 1) {
        requestAnimationFrame(update);
      }
    }

    requestAnimationFrame(update);
  }

  /**
   * Format currency
   */
  function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(value || 0);
  }

  /**
   * Show loading overlay
   */
  function showLoading() {
    let overlay = document.querySelector('.loading-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.className = 'loading-overlay';
      overlay.innerHTML = '<div class="loading-spinner"></div>';
      document.body.appendChild(overlay);
    }
    overlay.style.display = 'flex';
  }

  /**
   * Hide loading overlay
   */
  function hideLoading() {
    const overlay = document.querySelector('.loading-overlay');
    if (overlay) {
      overlay.style.display = 'none';
    }
  }
})();
