'use strict';

$(function () {
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

  const barChartElement = document.getElementById('barChart');
  let barChartVar;

  const importChartElement = document.getElementById('queueChart');
  let importChart;

  if (barChartElement) {
    barChartVar = new Chart(barChartElement, {
      type: 'bar',
      data: {
        labels: [], // Inicialmente vazio
        datasets: [
          {
            data: [], // Inicialmente vazio
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

    importChart = new Chart(importChartElement, {
      type: 'bar',
      data: {
        labels: [], // Initially empty
        datasets: [
          {
            data: [], // Initially empty
            backgroundColor: colors.cyan,
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

    async function fetchSalesMetrics(user, initialDate, finalDate) {
      const url = `/relatorios/getList/${user}/${initialDate}/${finalDate}`;

      try {
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        if (!response.ok) {
          throw new Error('Erro na resposta da rede');
        }

        const data = await response.json();
        updateChart(data);
      } catch (error) {
        console.error('Houve um problema na operação fetch:', error);
      }
    }

    function updateChart(data) {
      const labels = data.ligacoes.map(item => item.status);
      const values = data.ligacoes.map(item => item.total_ligacoes);

      const labelsQueue = data.fila.map(item => item.status);
      const valuesQueue = data.fila.map(item => item.total_tabulacoes);

      barChartVar.data.labels = labels;
      barChartVar.data.datasets[0].data = values;

      importChart.data.labels = labelsQueue;
      importChart.data.datasets[0].data = valuesQueue;

      barChartVar.update();
      importChart.update();
    }

    document.getElementById('filter_button').addEventListener('click', () => {
      const startDate = document.getElementById('start_date').value;
      const endDate = document.getElementById('end_date').value;
      const vendedor = document.getElementById('label').value;

      if (!startDate || !endDate || !vendedor) {
        alert('Por favor, preencha todos os campos do filtro.');
        return;
      }

      fetchSalesMetrics(vendedor, startDate, endDate);
      $('.js-container-grafico').show();
    });

    // Evento para limpar os filtros
    document.getElementById('clear_filter').addEventListener('click', () => {
      document.getElementById('start_date').value = '';
      document.getElementById('end_date').value = '';
      document.getElementById('label').value = '';

      $('.js-container-grafico').hide();
    });
  }
});
