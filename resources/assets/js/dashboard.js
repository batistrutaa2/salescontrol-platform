/**
 * Charts ChartsJS
 */
'use strict';

(function () {
  const tableContratosCadastrados = $('#tableContratosCadastrados').DataTable();
  const tableContratosImplantados = $('#tableContratosImplantados').DataTable();

  // Color Variables
  const colors = {
    purple: '#8c57ff',
    yellow: '#ffe800',
    cyan: '#28dac6',
    orange: '#FF8132',
    orangeLight: '#ffcf5c',
    oceanBlue: '#299AFF',
    grey: '#4F5D70',
    greyLight: '#EDF1F4',
    blue: '#2B9AFF',
    blueLight: '#84D0FF'
  };

  let cardColor, headingColor, labelColor, borderColor, legendColor;

  // Set colors based on the style
  if (isDarkStyle) {
    cardColor = config.colors_dark.cardColor;
    headingColor = config.colors_dark.headingColor;
    labelColor = config.colors_dark.textMuted;
    legendColor = config.colors_dark.bodyColor;
    borderColor = config.colors_dark.borderColor;
  } else {
    cardColor = config.colors.cardColor;
    headingColor = config.colors.headingColor;
    labelColor = config.colors.textMuted;
    legendColor = config.colors.bodyColor;
    borderColor = config.colors.borderColor;
  }

  // Set height according to their data-height
  const chartList = document.querySelectorAll('.chartjs');
  chartList.forEach(chartListItem => {
    chartListItem.height = chartListItem.dataset.height;
  });

  // Bar Chart Initialization
  const barChartElement = document.getElementById('barChart');
  let barChartVar;

  const importChartElement = document.getElementById('importChart');
  let importChart;

  if (barChartElement) {
    barChartVar = new Chart(barChartElement, {
      type: 'bar',
      data: {
        labels: [], // Initially empty
        datasets: [
          {
            data: [], // Initially empty
            backgroundColor: colors.oceanBlue,
            borderColor: 'transparent',
            maxBarThickness: 15,
            borderRadius: { topRight: 15, topLeft: 15 }
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 },
        plugins: {
          tooltip: {
            rtl: isRtl,
            backgroundColor: cardColor,
            titleColor: headingColor,
            bodyColor: legendColor,
            borderWidth: 1,
            borderColor: borderColor,
            callbacks: {
              label: tooltipItem => {
                // Format value as currency for tooltip display
                return new Intl.NumberFormat('pt-BR', {
                  style: 'currency',
                  currency: 'BRL'
                }).format(tooltipItem.raw);
              }
            }
          },
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
            ticks: { color: labelColor, font: { size: '13px' } }
          },
          y: {
            min: 0,
            grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
            ticks: {
              stepSize: 5000,
              color: labelColor,
              font: { size: '13px' }
            }
          }
        }
      }
    });

    importChart = new Chart(importChartElement, {
      type: 'bar',
      data: {
        labels: [], // Initially empty
        datasets: [
          {
            data: [], // Initially empty
            backgroundColor: colors.oceanBlue,
            borderColor: 'transparent',
            maxBarThickness: 15,
            borderRadius: { topRight: 15, topLeft: 15 }
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 500 },
        plugins: {
          tooltip: {
            rtl: isRtl,
            backgroundColor: cardColor,
            titleColor: headingColor,
            bodyColor: legendColor,
            borderWidth: 1,
            borderColor: borderColor
          },
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
            ticks: { color: labelColor, font: { size: '13px' } }
          },
          y: {
            min: 0,
            grid: { color: borderColor, drawBorder: false, borderColor: borderColor },
            ticks: {
              stepSize: 5000,
              color: labelColor,
              font: { size: '13px' }
            }
          }
        }
      }
    });

    // Fetch sales metrics function
    async function fetchSalesMetrics(month, year) {
      const url = `/searchMetrics/${month}/${year}`;

      try {
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        const data = await response.json();
        updateChart(data);
      } catch (error) {
        console.error('There was a problem with the fetch operation:', error);
      }
    }

    // Update chart with fetched data
    function updateChart(data) {
      // vendas
      const labels = data.vendasCadastradasPorVendedor.map(item => item.name);
      const values = data.vendasCadastradasPorVendedor.map(item => parseFloat(item.total_vendas));
      // contatos
      const labelsContatos = data.contatosImportadosMesPorVendedor.map(item => item.name);
      const valuesContatos = data.contatosImportadosMesPorVendedor.map(item => parseFloat(item.quantidade));

      const implantadas = data.vendasImplantadasPorVendedor.map(item => parseFloat(item.total_vendas));
      const quantidadeContatosImportados = data.quantidadeContatosImportados;

      const totalVendasCadastradas = values.reduce((acc, val) => acc + val, 0);
      const totalVendasImplantadas = implantadas.reduce((acc, val) => acc + val, 0);

      const totalVendasFormatado = totalVendasCadastradas.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      });

      const totalVendasImplantadasFormatado = totalVendasImplantadas.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
      });

      document.querySelector('.js-valorCadastrado').textContent = totalVendasFormatado;
      document.querySelector('.js-implantado').textContent = totalVendasImplantadasFormatado;
      document.querySelector('.js-quantidadeContatosImportados').textContent = quantidadeContatosImportados;
      document.querySelector('.js-conversao').textContent = `${data.conversaoMensal} %`;

      updateTables(data.contratosCadastrados, data.contratosImplantados);

      barChartVar.data.labels = labels;
      barChartVar.data.datasets[0].data = values;

      importChart.data.labels = labelsContatos;
      importChart.data.datasets[0].data = valuesContatos;

      barChartVar.update();
      importChart.update();
    }

    // Update metrics based on selected month and year
    async function updateMetrics() {
      const month = document.getElementById('select-month').value || new Date().getMonth() + 1;
      const year = document.getElementById('select-year').value || new Date().getFullYear();

      await fetchSalesMetrics(month, year);
    }

    // Add change events to selects
    document.getElementById('select-month').addEventListener('change', updateMetrics);
    document.getElementById('select-year').addEventListener('change', updateMetrics);

    // Set default values for selects on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', () => {
      const monthSelect = document.getElementById('select-month');
      const yearSelect = document.getElementById('select-year');
      monthSelect.value = new Date().getMonth() + 1;
      yearSelect.value = new Date().getFullYear();
    });

    // Update tables function
    function updateTables(dataCadastrados, dataImplantados) {
      console.log(dataCadastrados, dataImplantados);

      // Clear the tables
      tableContratosCadastrados.clear();
      tableContratosImplantados.clear();

      // Add new data to the Cadastrados table
      dataCadastrados.forEach(item => {
        const totalVendasFormatado = parseFloat(item.valor_contrato).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        });

        tableContratosCadastrados.row.add([item.nome_contrato, totalVendasFormatado]);
      });

      // Add new data to the Implantados table
      dataImplantados.forEach(item => {
        const totalVendasFormatado = parseFloat(item.valor_contrato).toLocaleString('pt-BR', {
          style: 'currency',
          currency: 'BRL'
        });

        tableContratosImplantados.row.add([item.nome_contrato, totalVendasFormatado]);
      });

      // Redraw the tables
      tableContratosCadastrados.draw();
      tableContratosImplantados.draw();
    }

    // Fetch initial sales metrics
    fetchSalesMetrics(new Date().getMonth() + 1, new Date().getFullYear());
  }
})();
