/**
 * Relatório de Atividade Preditiva
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Inicializar Select2
  const select2 = $('.select2');
  if (select2.length) {
    select2.each(function () {
      var $this = $(this);
      $this.wrap('<div class="position-relative"></div>').select2({
        placeholder: 'Selecione uma opção',
        dropdownParent: $this.parent()
      });
    });
  }

  // Inicializar DataTable
  const tabelaAtividades = $('#tabela-atividades');
  if (tabelaAtividades.length) {
    const dt = tabelaAtividades.DataTable({
      dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.1/i18n/pt-BR.json'
      },
      responsive: true,
      processing: true,
      serverSide: false,
      columns: [
        { data: 'data', name: 'data' },
        { data: 'usuario', name: 'usuario' },
        { data: 'cliente', name: 'cliente' },
        { data: 'telefone', name: 'telefone' },
        { data: 'status', name: 'status' },
        { data: 'tabulacao', name: 'tabulacao' },
        { data: 'observacao', name: 'observacao' }
      ],
      order: [[0, 'desc']]
    });

    // Exportar para Excel
    $('#btn-exportar-excel').on('click', function () {
      const dataInicio = $('#data_inicio').val();
      const dataFim = $('#data_fim').val();
      const usuarioId = $('#usuario_id').val();

      window.location.href = `/relatorio/preditiva/exportar?data_inicio=${dataInicio}&data_fim=${dataFim}&usuario_id=${usuarioId}`;
    });

    // Buscar dados ao enviar o formulário
    $('#form-filtro-preditiva').on('submit', function (e) {
      e.preventDefault();
      buscarDados();
    });

    // Função para buscar dados
    function buscarDados() {
      const form = $('#form-filtro-preditiva');
      const url = form.attr('action');
      const formData = form.serialize();

      // Mostrar loading
      const cardBody = form.closest('.card-body');
      cardBody.append(
        '<div class="overlay-loading"><div class="spinner-border text-primary" role="status"></div></div>'
      );

      $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function (response) {
          // Remover loading
          cardBody.find('.overlay-loading').remove();

          if (response.success) {
            // Atualizar cards de resumo
            $('#total-contatos').text(response.resumo.total || 0);
            $('#total-convertidos').text(response.resumo.convertidos || 0);
            $('#total-descartados').text(response.resumo.descartados || 0);
            $('#taxa-conversao').text(response.resumo.taxa_conversao || '0%');

            // Atualizar tabela
            dt.clear().rows.add(response.atividades).draw();

            // Atualizar gráficos
            atualizarGraficoAtividadeDiaria(response.grafico_diario);
            atualizarGraficoStatus(response.grafico_status);

            // Adicionar chamada para o gráfico de tabulação
            if (response.grafico_tabulacao) {
              atualizarGraficoTabulacao(response.grafico_tabulacao);
            }
          } else {
            toastr.error(response.message || 'Erro ao buscar dados', 'Erro');
          }
        },
        error: function (xhr, status, error) {
          // Remover loading
          cardBody.find('.overlay-loading').remove();
          toastr.error('Erro ao processar a requisição', 'Erro');
          console.error(error);
        }
      });
    }

    // Inicializar gráficos
    let chartAtividadeDiaria, chartStatus, chartTabulacao;

    function atualizarGraficoAtividadeDiaria(dados) {
      const options = {
        series: [
          {
            name: 'Contatos',
            data: dados.contatos || []
          },
          {
            name: 'Convertidos',
            data: dados.convertidos || []
          },
          {
            name: 'Descartados',
            data: dados.descartados || []
          }
        ],
        chart: {
          height: 300,
          type: 'line',
          toolbar: {
            show: false
          }
        },
        colors: ['#696cff', '#71dd37', '#ff3e1d'],
        dataLabels: {
          enabled: false
        },
        stroke: {
          curve: 'smooth',
          width: 3
        },
        grid: {
          borderColor: '#f1f1f1',
          padding: {
            top: 10
          }
        },
        xaxis: {
          categories: dados.datas || [],
          axisBorder: {
            show: false
          }
        },
        yaxis: {
          tickAmount: 4
        },
        tooltip: {
          shared: true
        },
        legend: {
          position: 'top'
        }
      };

      if (chartAtividadeDiaria) {
        chartAtividadeDiaria.updateOptions(options);
      } else {
        chartAtividadeDiaria = new ApexCharts(document.querySelector('#grafico-atividade-diaria'), options);
        chartAtividadeDiaria.render();
      }
    }

    function atualizarGraficoStatus(dados) {
      const options = {
        series: dados.valores || [],
        chart: {
          height: 300,
          type: 'donut'
        },
        labels: dados.labels || [],
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00'],
        dataLabels: {
          enabled: true,
          formatter: function (val) {
            return val.toFixed(1) + '%';
          }
        },
        plotOptions: {
          pie: {
            donut: {
              size: '70%',
              labels: {
                show: true,
                name: {
                  show: true
                },
                value: {
                  show: true,
                  formatter: function (val) {
                    return val;
                  }
                },
                total: {
                  show: true,
                  formatter: function (w) {
                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                  }
                }
              }
            }
          }
        },
        legend: {
          position: 'bottom'
        }
      };

      if (chartStatus) {
        chartStatus.updateOptions(options);
      } else {
        chartStatus = new ApexCharts(document.querySelector('#grafico-status'), options);
        chartStatus.render();
      }
    }

    // Nova função para o gráfico de tabulação
    function atualizarGraficoTabulacao(dados) {
      // Verificar se os dados existem
      if (!dados || !dados.valores || !dados.labels) {
        console.error('Dados inválidos para o gráfico de tabulação');
        return;
      }

      const options = {
        series: [{
          name: 'Quantidade',
          data: dados.valores
        }],
        chart: {
          height: 300,
          type: 'bar',
          toolbar: {
            show: false
          }
        },
        plotOptions: {
          bar: {
            distributed: true, // Cores diferentes para cada barra
            borderRadius: 4,
            horizontal: false,
            columnWidth: '55%'
          }
        },
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00', '#8592a3'],
        dataLabels: {
          enabled: true,
          formatter: function(val) {
            return val;
          },
          style: {
            fontSize: '12px'
          }
        },
        legend: {
          show: false
        },
        xaxis: {
          categories: dados.labels,
          labels: {
            style: {
              fontSize: '12px'
            }
          }
        },
        yaxis: {
          title: {
            text: 'Quantidade'
          }
        },
        tooltip: {
          y: {
            formatter: function(val) {
              return val + " contatos";
            }
          }
        }
      };

      // Se o gráfico já existe, destrua-o antes de criar um novo
      if (chartTabulacao) {
        chartTabulacao.destroy();
      }

      // Criar novo gráfico
      chartTabulacao = new ApexCharts(document.querySelector('#grafico-tabulacao'), options);
      chartTabulacao.render();
    }

    // Carregar dados iniciais
    buscarDados();
  }

  // Adicionar estilos CSS para o overlay de loading
  const style = document.createElement('style');
  style.textContent = `
    .overlay-loading {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(255, 255, 255, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
  `;
  document.head.appendChild(style);
});
