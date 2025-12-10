'use strict';

// Variáveis globais dos gráficos
let chartEvolucao, chartStatus, chartOperadora, chartVendedores;

(function () {
    // ========================================
    // Design System Colors
    // ========================================
    const designColors = {
        primary: '#7C3AED',
        primaryLight: '#A78BFA',
        success: '#10B981',
        successLight: '#34D399',
        info: '#06B6D4',
        infoLight: '#22D3EE',
        warning: '#F59E0B',
        warningLight: '#FBBF24',
        danger: '#EF4444',
        dangerLight: '#F87171'
    };

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
                    className: 'text-end value-cell primary',
                    render: val => formatCurrency(val)
                },
                {
                    data: 'recebido',
                    className: 'text-end value-cell success',
                    render: val => formatCurrency(val)
                },
                {
                    data: 'aberto',
                    className: 'text-end value-cell warning',
                    render: val => formatCurrency(val)
                },
                {
                    data: 'cancelado',
                    className: 'text-end value-cell muted',
                    render: val => formatCurrency(val)
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function(data) {
                        const taxa = data.previsto > 0 ? (data.recebido / data.previsto * 100) : 0;
                        const badgeClass = taxa >= 80 ? 'success' : taxa >= 50 ? 'warning' : 'danger';
                        return `<span class="badge-taxa ${badgeClass}">${taxa.toFixed(1)}%</span>`;
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
                height: 320,
                fontFamily: 'Plus Jakarta Sans, sans-serif',
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
            colors: [designColors.primary, designColors.success, designColors.warning],
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
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
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
                        fontSize: '12px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    },
                    formatter: function (val) {
                        return formatCurrencyShort(val);
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: -10,
                    bottom: 0,
                    left: 10,
                    right: 10
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                style: {
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
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
                height: 320,
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            colors: [designColors.success, designColors.warning, designColors.danger],
            plotOptions: {
                pie: {
                    donut: {
                        size: '72%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                fontSize: '14px',
                                fontFamily: 'Plus Jakarta Sans, sans-serif',
                                fontWeight: 600,
                                color: headingColor
                            },
                            value: {
                                show: true,
                                fontSize: '20px',
                                fontFamily: 'JetBrains Mono, monospace',
                                fontWeight: 600,
                                color: headingColor,
                                formatter: function (val) {
                                    return formatCurrency(val);
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '13px',
                                fontFamily: 'Plus Jakarta Sans, sans-serif',
                                fontWeight: 500,
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
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                fontSize: '13px',
                fontWeight: 500,
                labels: {
                    colors: legendColor
                },
                markers: {
                    width: 10,
                    height: 10,
                    radius: 10
                },
                itemMargin: {
                    horizontal: 12,
                    vertical: 8
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + '%';
                },
                style: {
                    fontSize: '12px',
                    fontFamily: 'JetBrains Mono, monospace',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                dropShadow: {
                    enabled: false
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                style: {
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                y: {
                    formatter: function (val) {
                        return formatCurrency(val);
                    }
                }
            },
            stroke: {
                width: 4,
                colors: [cardColor]
            }
        };
        chartStatus = new ApexCharts(document.querySelector('#chartStatusDistribuicao'), statusOptions);
        chartStatus.render();

        // 3. Gráfico por Operadora (Barras Horizontais)
        const operadoraOptions = {
            series: [],
            chart: {
                type: 'bar',
                height: 320,
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '65%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: [designColors.primary, designColors.success, designColors.warning],
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    },
                    formatter: function (val) {
                        return formatCurrencyShort(val);
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
                        fontSize: '12px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    }
                }
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                xaxis: {
                    lines: {
                        show: true
                    }
                },
                yaxis: {
                    lines: {
                        show: false
                    }
                },
                padding: {
                    top: -10,
                    bottom: 0
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                style: {
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
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
                height: 320,
                fontFamily: 'Plus Jakarta Sans, sans-serif',
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
                    columnWidth: '55%'
                }
            },
            colors: [designColors.info],
            dataLabels: {
                enabled: true,
                offsetY: -24,
                style: {
                    fontSize: '11px',
                    fontFamily: 'JetBrains Mono, monospace',
                    fontWeight: 600,
                    colors: [headingColor]
                },
                formatter: function (val) {
                    return formatCurrencyShort(val);
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '11px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    },
                    rotate: -45,
                    rotateAlways: true,
                    trim: true,
                    maxHeight: 80
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
                        fontSize: '12px',
                        fontFamily: 'Plus Jakarta Sans, sans-serif'
                    },
                    formatter: function (val) {
                        return formatCurrencyShort(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: -10,
                    bottom: 0
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                style: {
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
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
                $('.kpi-value').addClass('loading-state');
            },
            success: function (response) {
                atualizarKPIs(response.resumo);
                atualizarGraficoEvolucao(response.evolucaoMensal);
                atualizarGraficoStatus(response.statusDistribuicao);
                atualizarGraficoOperadora(response.porOperadora);
                atualizarGraficoVendedores(response.topVendedores);
                atualizarTabela(response.porOperadora);

                // Remover loading state
                $('.kpi-value').removeClass('loading-state');
            },
            error: function(xhr, status, error) {
                console.error('Erro ao carregar dados:', error);
                $('.kpi-value').removeClass('loading-state');
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

        // Atualizar taxa de recebimento com ícones SVG
        const taxa = resumo.taxa_recebimento || 0;
        let trendClass = 'trend-up';
        let trendIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
            <polyline points="17 6 23 6 23 12"></polyline>
        </svg>`;

        if (taxa < 50) {
            trendClass = 'trend-down';
            trendIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
                <polyline points="17 18 23 18 23 12"></polyline>
            </svg>`;
        } else if (taxa < 80) {
            trendClass = 'trend-neutral';
            trendIcon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>`;
        }

        $('#taxaRecebimento')
            .removeClass('trend-up trend-down trend-neutral')
            .addClass(trendClass)
            .html(`${trendIcon}<span>${taxa}%</span>`);
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

    function formatCurrencyShort(value) {
        if (value >= 1000000) {
            return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
        } else if (value >= 1000) {
            return 'R$ ' + (value / 1000).toFixed(1) + 'K';
        }
        return 'R$ ' + Number(value).toFixed(0);
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
