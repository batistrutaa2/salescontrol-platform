'use strict';

let chartDistribuicaoGeral, chartComercial, chartAdministrativo;

(function () {
    $(function () {
        // Inicializar flatpickr com modo range
        if (document.querySelector('.flatpickr-range')) {
            flatpickr('.flatpickr-range', {
                mode: 'range',
                dateFormat: 'd/m/Y',
                locale: 'pt',
                altInput: true,
                altFormat: 'd/m/Y'
            });
        }

        // Carregar dados inicialmente
        carregarDados();

        // Event listener para o botão filtrar
        $('#btn_filtrar').on('click', function() {
            carregarDados();
        });
    });

    function carregarDados() {
        const periodo = $('#filtro_periodo').val();
        let dataInicial = '';
        let dataFinal = '';

        // Extrair data inicial e final do range
        if (periodo) {
            const datas = periodo.split(' to ');
            if (datas[0]) {
                dataInicial = converterDataParaBackend(datas[0].trim());
            }
            if (datas[1]) {
                dataFinal = converterDataParaBackend(datas[1].trim());
            } else if (datas[0]) {
                dataFinal = dataInicial;
            }
        }

        $.ajax({
            url: '/relatorios/distribuicao-leads/dados',
            method: 'GET',
            data: {
                data_inicial: dataInicial,
                data_final: dataFinal
            },
            beforeSend: function() {
                // Mostrar loading nos cards
                $('.metric-value').html('<span class="spinner-border spinner-border-sm"></span>');
            },
            success: function(response) {
                atualizarCards(response.resumo);
                renderizarGraficos(response);
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados:', error);
                toastr.error('Erro ao carregar dados do relatório');
            }
        });
    }

    function atualizarCards(resumo) {
        $('#total-leads').text(formatarNumero(resumo.total_leads));
        $('#leads-distribuidos').text(formatarNumero(resumo.leads_distribuidos));
        $('#leads-comercial').text(formatarNumero(resumo.leads_comercial));
        $('#leads-administrativo').text(formatarNumero(resumo.leads_administrativo));
        $('#leads-preditiva').text(formatarNumero(resumo.leads_preditiva));
        $('#leads-descartados').text(formatarNumero(resumo.leads_descartados));
        // Mantendo tentativas_preditiva no response para análise futura
    }

    function renderizarGraficos(data) {
        renderizarGraficoComercial(data.distribuicao_comercial);
        renderizarGraficoAdministrativo(data.distribuicao_administrativa);
        renderizarGraficoDistribuicaoGeral(data.resumo);
    }

    function renderizarGraficoDistribuicaoGeral(resumo) {
        const options = {
            series: [
                resumo.leads_comercial,
                resumo.leads_administrativo,
                resumo.leads_preditiva,
                resumo.leads_descartados
            ],
            chart: {
                type: 'pie',
                height: 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                }
            },
            labels: ['Com Vendedores', 'Administrativo', 'Na Preditiva', 'Descartados'],
            colors: ['#17a2b8', '#ffc107', '#28a745', '#dc3545'],
            legend: {
                position: 'bottom',
                fontSize: '14px',
                fontFamily: 'inherit'
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        const total = resumo.total_leads;
                        const percentual = ((value / total) * 100).toFixed(1);
                        return value + ' (' + percentual + '%)';
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 300
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        if (chartDistribuicaoGeral) {
            chartDistribuicaoGeral.destroy();
        }

        chartDistribuicaoGeral = new ApexCharts(
            document.querySelector("#chart-distribuicao-geral"),
            options
        );
        chartDistribuicaoGeral.render();
    }

    function renderizarGraficoComercial(distribuicao) {
        const categorias = distribuicao.map(item => item.descricao);
        const valores = distribuicao.map(item => item.total);

        const options = {
            series: [{
                name: 'Leads',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: false,
                    columnWidth: '60%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            xaxis: {
                categories: categorias,
                labels: {
                    rotate: -45,
                    rotateAlways: true,
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Quantidade de Leads'
                }
            },
            colors: ['#17a2b8'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#20c997'],
                    inverseColors: false,
                    opacityFrom: 0.85,
                    opacityTo: 0.85
                }
            }
        };

        if (chartComercial) {
            chartComercial.destroy();
        }

        chartComercial = new ApexCharts(
            document.querySelector("#chart-comercial"),
            options
        );
        chartComercial.render();
    }

    function renderizarGraficoAdministrativo(distribuicao) {
        const categorias = distribuicao.map(item => item.descricao);
        const valores = distribuicao.map(item => item.total);

        const options = {
            series: [{
                name: 'Leads',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: true
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    horizontal: false,
                    columnWidth: '60%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            xaxis: {
                categories: categorias,
                labels: {
                    rotate: -45,
                    rotateAlways: true,
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Quantidade de Leads'
                }
            },
            colors: ['#ffc107'],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.5,
                    gradientToColors: ['#fd7e14'],
                    inverseColors: false,
                    opacityFrom: 0.85,
                    opacityTo: 0.85
                }
            }
        };

        if (chartAdministrativo) {
            chartAdministrativo.destroy();
        }

        chartAdministrativo = new ApexCharts(
            document.querySelector("#chart-administrativo"),
            options
        );
        chartAdministrativo.render();
    }

    function formatarNumero(numero) {
        return new Intl.NumberFormat('pt-BR').format(numero);
    }

    function converterDataParaBackend(dataBrasileira) {
        // Converte dd/mm/yyyy para yyyy-mm-dd
        if (!dataBrasileira) return '';

        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';

        const dia = partes[0].padStart(2, '0');
        const mes = partes[1].padStart(2, '0');
        const ano = partes[2];

        return `${ano}-${mes}-${dia}`;
    }

})();
