'use strict';

// Variáveis globais dos gráficos
let chartEvolucao, chartStatus, chartOperadora, chartVendedores;

(function () {
    // Configuração de cores baseada no tema
    let cardColor, labelColor, headingColor, borderColor, legendColor;

    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        labelColor = config.colors_dark.textMuted;
        headingColor = config.colors_dark.headingColor;
        borderColor = config.colors_dark.borderColor;
        legendColor = config.colors_dark.bodyColor;
    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        headingColor = config.colors.headingColor;
        borderColor = config.colors.borderColor;
        legendColor = config.colors.bodyColor;
    }

    // Cores personalizadas
    const chartColors = {
        primary: '#696cff',
        success: '#71dd37',
        warning: '#ffab00',
        danger: '#ff3e1d',
        info: '#03c3ec',
        secondary: '#8592a3'
    };

    // ========================================
    // Inicialização
    // ========================================
    $(function () {
        // Configurar Flatpickr
        $('.flatpickr-date').flatpickr({
            dateFormat: 'd/m/Y',
            locale: 'pt'
        });

        // Inicializar DataTable
        const table = $('#relatorioTable').DataTable({
            paging: true,
            pageLength: 10,
            searching: false,
            info: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            columns: [
                { data: 'operadora' },
                {
                    data: 'previsto',
                    className: 'text-end fw-semibold',
                    render: val => formatCurrency(val)
                },
                {
                    data: 'recebido',
                    className: 'text-end',
                    render: val => `<span class="text-success">${formatCurrency(val)}</span>`
                },
                {
                    data: 'aberto',
                    className: 'text-end',
                    render: val => `<span class="text-warning">${formatCurrency(val)}</span>`
                },
                {
                    data: 'cancelado',
                    className: 'text-end',
                    render: val => `<span class="text-muted">${formatCurrency(val)}</span>`
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function(data) {
                        const taxa = data.previsto > 0 ? (data.recebido / data.previsto * 100) : 0;
                        const badgeClass = taxa >= 80 ? 'success' : taxa >= 50 ? 'warning' : 'danger';
                        return `<span class="badge bg-${badgeClass}">${taxa.toFixed(1)}%</span>`;
                    }
                }
            ]
        });

        // Inicializar gráficos
        initCharts();

        // Carregar dados iniciais
        carregarDados();

        // Event listeners
        $('#btnFiltrar').on('click', carregarDados);
        $('#btnExportar').on('click', exportarRelatorio);
    });

    // ========================================
    // Inicializar Gráficos
    // ========================================
    function initCharts() {
        // 1. Gráfico de Evolução Mensal (Área)
        const evolucaoOptions = {
            series: [],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: false,
                        reset: true
                    }
                },
                background: 'transparent'
            },
            colors: [chartColors.primary, chartColors.success, chartColors.warning],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    },
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'left',
                labels: {
                    colors: legendColor
                },
                markers: {
                    width: 10,
                    height: 10
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 7,
                padding: {
                    top: -20,
                    bottom: -8,
                    left: 20,
                    right: 20
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            }
        };
        chartEvolucao = new ApexCharts(document.querySelector('#chartEvolucaoMensal'), evolucaoOptions);
        chartEvolucao.render();

        // 2. Gráfico de Status (Donut)
        const statusOptions = {
            series: [],
            labels: [],
            chart: {
                type: 'donut',
                height: 350
            },
            colors: [chartColors.success, chartColors.warning, chartColors.secondary],
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px',
                                color: headingColor
                            },
                            value: {
                                show: true,
                                fontSize: '24px',
                                fontWeight: 600,
                                color: headingColor,
                                formatter: function (val) {
                                    return formatCurrency(val);
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '14px',
                                color: labelColor,
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatCurrency(total);
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                labels: {
                    colors: legendColor
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + '%';
                },
                style: {
                    colors: ['#fff']
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            }
        };
        chartStatus = new ApexCharts(document.querySelector('#chartStatusDistribuicao'), statusOptions);
        chartStatus.render();

        // 3. Gráfico por Operadora (Barras Horizontais)
        const operadoraOptions = {
            series: [],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 8,
                    barHeight: '70%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: [chartColors.primary, chartColors.success, chartColors.warning],
            dataLabels: {
                enabled: true,
                offsetX: 30,
                style: {
                    fontSize: '12px',
                    colors: ['#fff']
                },
                formatter: function (val) {
                    return formatCurrency(val);
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    },
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'left',
                labels: {
                    colors: legendColor
                }
            },
            grid: {
                borderColor: borderColor,
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: false
                    }
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            }
        };
        chartOperadora = new ApexCharts(document.querySelector('#chartPorOperadora'), operadoraOptions);
        chartOperadora.render();

        // 4. Gráfico Top Vendedores (Barras)
        const vendedoresOptions = {
            series: [{
                name: 'Recebido',
                data: []
            }],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    dataLabels: {
                        position: 'top'
                    },
                    columnWidth: '60%'
                }
            },
            colors: [chartColors.info],
            dataLabels: {
                enabled: true,
                offsetY: -25,
                style: {
                    fontSize: '11px',
                    colors: [headingColor]
                },
                formatter: function (val) {
                    return formatCurrency(val);
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    },
                    rotate: -45,
                    rotateAlways: true
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    },
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 7
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            }
        };
        chartVendedores = new ApexCharts(document.querySelector('#chartTopVendedores'), vendedoresOptions);
        chartVendedores.render();
    }

    // ========================================
    // Carregar Dados
    // ========================================
    function carregarDados() {
        // Converter datas brasileiras para formato ISO
        const dataInicial = converterDataParaBackend($('#data_inicial').val());
        const dataFinal = converterDataParaBackend($('#data_final').val());

        $.ajax({
            url: '/financeiro/recebiveis/relatorio-financeiro/fetch',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                data_inicial: dataInicial,
                data_final: dataFinal,
                operadora_id: $('#operadora_id').val()
            },
            beforeSend: function() {
                // Adicionar loading state
                $('.metric-value').addClass('loading-state');
            },
            success: function (response) {
                atualizarKPIs(response.resumo);
                atualizarGraficoEvolucao(response.evolucaoMensal);
                atualizarGraficoStatus(response.statusDistribuicao);
                atualizarGraficoOperadora(response.porOperadora);
                atualizarGraficoVendedores(response.topVendedores);
                atualizarTabela(response.porOperadora);

                // Remover loading state
                $('.metric-value').removeClass('loading-state');
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados:', error);
                $('.metric-value').removeClass('loading-state');
            }
        });
    }

    // ========================================
    // Atualizar KPIs
    // ========================================
    function atualizarKPIs(resumo) {
        $('#totalPrevisto').text(formatCurrency(resumo.total_previsto));
        $('#totalRecebido').text(formatCurrency(resumo.total_recebido));
        $('#totalAberto').text(formatCurrency(resumo.total_aberto));
        $('#totalAtraso').text(formatCurrency(resumo.em_atraso));

        // Atualizar taxa de recebimento
        const icon = resumo.taxa_recebimento >= 80 ? 'ri-arrow-up-line' :
                     resumo.taxa_recebimento >= 50 ? 'ri-arrow-right-line' :
                     'ri-arrow-down-line';
        $('#taxaRecebimento').html(`<i class="${icon}"></i>${resumo.taxa_recebimento}%`);
    }

    // ========================================
    // Atualizar Gráficos
    // ========================================
    function atualizarGraficoEvolucao(dados) {
        if (!dados || dados.length === 0) {
            chartEvolucao.updateOptions({
                series: [],
                xaxis: { categories: [] }
            });
            return;
        }

        const meses = dados.map(d => d.mes);
        const previsto = dados.map(d => parseFloat(d.previsto));
        const recebido = dados.map(d => parseFloat(d.recebido));
        const aberto = dados.map(d => parseFloat(d.aberto));

        chartEvolucao.updateOptions({
            series: [
                { name: 'Previsto', data: previsto },
                { name: 'Recebido', data: recebido },
                { name: 'Em Aberto', data: aberto }
            ],
            xaxis: { categories: meses }
        });
    }

    function atualizarGraficoStatus(dados) {
        if (!dados || dados.length === 0) {
            chartStatus.updateOptions({
                series: [],
                labels: []
            });
            return;
        }

        const valores = dados.map(d => parseFloat(d.valor));
        const labels = dados.map(d => d.status);

        chartStatus.updateOptions({
            series: valores,
            labels: labels
        });
    }

    function atualizarGraficoOperadora(dados) {
        if (!dados || dados.length === 0) {
            chartOperadora.updateOptions({
                series: [],
                xaxis: { categories: [] }
            });
            return;
        }

        // Pegar apenas os top 10
        const top10 = dados.slice(0, 10);
        const operadoras = top10.map(d => d.operadora);
        const previsto = top10.map(d => parseFloat(d.previsto));
        const recebido = top10.map(d => parseFloat(d.recebido));
        const aberto = top10.map(d => parseFloat(d.aberto));

        chartOperadora.updateOptions({
            series: [
                { name: 'Previsto', data: previsto },
                { name: 'Recebido', data: recebido },
                { name: 'Em Aberto', data: aberto }
            ],
            xaxis: { categories: operadoras }
        });
    }

    function atualizarGraficoVendedores(dados) {
        if (!dados || dados.length === 0) {
            chartVendedores.updateOptions({
                series: [{ data: [] }],
                xaxis: { categories: [] }
            });
            return;
        }

        const vendedores = dados.map(d => d.vendedor);
        const valores = dados.map(d => parseFloat(d.valor));

        chartVendedores.updateOptions({
            series: [{ name: 'Recebido', data: valores }],
            xaxis: { categories: vendedores }
        });
    }

    function atualizarTabela(dados) {
        const table = $('#relatorioTable').DataTable();
        table.clear().rows.add(dados).draw();
    }

    // ========================================
    // Funções Utilitárias
    // ========================================
    function formatCurrency(value) {
        return 'R$ ' + Number(value).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function converterDataParaBackend(dataBrasileira) {
        if (!dataBrasileira) return '';

        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';

        const dia = partes[0].padStart(2, '0');
        const mes = partes[1].padStart(2, '0');
        const ano = partes[2];

        return `${ano}-${mes}-${dia}`;
    }

    function exportarRelatorio() {
        // TODO: Implementar exportação para Excel/PDF
        alert('Funcionalidade de exportação em desenvolvimento');
    }

})();
