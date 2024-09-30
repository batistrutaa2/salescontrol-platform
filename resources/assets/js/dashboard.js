/**
 * Charts ChartsJS
 */
'use strict';

(function () {
  // Color Variables
  const purpleColor = '#8c57ff',
    yellowColor = '#ffe800',
    cyanColor = '#28dac6',
    orangeColor = '#FF8132',
    orangeLightColor = '#ffcf5c',
    oceanBlueColor = '#299AFF',
    greyColor = '#4F5D70',
    greyLightColor = '#EDF1F4',
    blueColor = '#2B9AFF',
    blueLightColor = '#84D0FF';

  let cardColor, headingColor, labelColor, borderColor, legendColor;

  // Definindo as cores com base no estilo
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
  // --------------------------------------------------------------------
  const chartList = document.querySelectorAll('.chartjs');
  chartList.forEach(function (chartListItem) {
    chartListItem.height = chartListItem.dataset.height;
  });

  // Bar Chart
  // --------------------------------------------------------------------
  const barChartElement = document.getElementById('barChart');
  let barChartVar; // Variável para armazenar a instância do gráfico

  if (barChartElement) {
    barChartVar = new Chart(barChartElement, {
      type: 'bar',
      data: {
        labels: [], // Inicialmente vazio
        datasets: [
          {
            data: [], // Inicialmente vazio
            backgroundColor: oceanBlueColor,
            borderColor: 'transparent',
            maxBarThickness: 15,
            borderRadius: {
              topRight: 15,
              topLeft: 15
            }
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 500
        },
        plugins: {
          tooltip: {
            rtl: isRtl,
            backgroundColor: cardColor,
            titleColor: headingColor,
            bodyColor: legendColor,
            borderWidth: 1,
            borderColor: borderColor,
            callbacks: {
              label: function (tooltipItem) {
                // Formata o valor como moeda ao exibir no tooltip
                return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(tooltipItem.raw);
              }
            }
          },
          legend: {
            display: false
          }
        },

        scales: {
          x: {
            grid: {
              color: borderColor,
              drawBorder: false,
              borderColor: borderColor
            },
            ticks: {
              color: labelColor,
              font: {
                size: '13px'
              }
            }
          },
          y: {
            min: 0,
            grid: {
              color: borderColor,
              drawBorder: false,
              borderColor: borderColor
            },
            ticks: {
              stepSize: 5000,
              color: labelColor,
              font: {
                size: '13px'
              }
            }
          }
        }
      }
    });

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

    function updateChart(data) {
      // Processa os dados
      const labels = data.vendasCadastradasPorVendedor.map(item => item.name);
      const values = data.vendasCadastradasPorVendedor.map(item => parseFloat(item.total_vendas));
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

      barChartVar.data.labels = labels;
      barChartVar.data.datasets[0].data = values;

      barChartVar.update();
    }

    // Função para atualizar as métricas com base no mês e ano selecionados
    async function updateMetrics() {
      // Obtém o mês e o ano selecionados
      const month = document.getElementById('select-month').value;
      const year = document.getElementById('select-year').value;

      // Obtém a data atual
      const currentDate = new Date();
      const currentMonth = currentDate.getMonth() + 1; // getMonth() retorna 0-11, então adicionamos 1
      const currentYear = currentDate.getFullYear();

      // Se mês ou ano não forem selecionados, usa o mês e ano atuais
      const finalMonth = month ? month : currentMonth;
      const finalYear = year ? year : currentYear;

      // Chama a função para buscar os dados com o mês e ano definidos
      await fetchSalesMetrics(finalMonth, finalYear);
    }

    // Adiciona eventos de mudança aos selects
    document.getElementById('select-month').addEventListener('change', updateMetrics);
    document.getElementById('select-year').addEventListener('change', updateMetrics);

    const currentDate = new Date();
    const currentMonth = currentDate.getMonth() + 1;
    const currentYear = currentDate.getFullYear();

    document.addEventListener('DOMContentLoaded', () => {
      const monthSelect = document.getElementById('select-month');
      const yearSelect = document.getElementById('select-year');
      monthSelect.value = currentMonth;
      yearSelect.value = currentYear;
    });

    fetchSalesMetrics(currentDate.getMonth() + 1, currentDate.getFullYear());
  }
})();
