'use strict';

let chartDistribuicaoGeral, chartComercial, chartAdministrativo, chartDescarte, chartMotivosDescarte;

(function () {
    // Configurar cores baseadas no tema
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

    // Paleta de cores moderna
    const chartColors = {
        comercial: {
            primary: '#696cff',
            gradient: '#8592ff'
        },
        administrativo: {
            primary: '#ffab00',
            gradient: '#ffd666'
        },
        descarte: {
            primary: '#ff4c51',
            gradient: '#ff8a8d'
        },
        preditiva: {
            primary: '#28c76f',
            gradient: '#55dd92'
        },
        motivos: {
            primary: '#7367f0',
            gradient: '#9e95f5'
        }
    };

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
    }

    function renderizarGraficos(data) {
        renderizarGraficoDistribuicaoGeral(data.resumo);
        renderizarGraficoComercial(data.distribuicao_comercial);
        renderizarGraficoAdministrativo(data.distribuicao_administrativa);
        renderizarGraficoDescarte(data.distribuicao_descarte);
        renderizarGraficoMotivosDescarte(data.motivos_descarte);
    }

    function renderizarGraficoDistribuicaoGeral(resumo) {
        const total = resumo.leads_comercial + resumo.leads_administrativo + resumo.leads_preditiva + resumo.leads_descartados;

        const options = {
            series: [
                resumo.leads_comercial,
                resumo.leads_administrativo,
                resumo.leads_preditiva,
                resumo.leads_descartados
            ],
            chart: {
                type: 'donut',
                height: 380,
                width: '100%',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true
                    }
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 4,
                    opacity: 0.12
                }
            },
            labels: ['Com Vendedores', 'Administrativo', 'Na Preditiva', 'Descartados'],
            colors: [chartColors.comercial.primary, chartColors.administrativo.primary, chartColors.preditiva.primary, chartColors.descarte.primary],
            stroke: {
                width: 0
            },
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
                                color: headingColor,
                                offsetY: -10
                            },
                            value: {
                                show: true,
                                fontSize: '26px',
                                fontWeight: 700,
                                color: headingColor,
                                offsetY: 5,
                                formatter: function (val) {
                                    return formatarNumero(parseInt(val));
                                }
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '14px',
                                fontWeight: 500,
                                color: labelColor,
                                formatter: function (w) {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatarNumero(total);
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                show: true,
                position: 'bottom',
                fontSize: '13px',
                fontFamily: 'inherit',
                fontWeight: 500,
                labels: {
                    colors: labelColor,
                    useSeriesColors: false
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12,
                    offsetX: -3
                },
                itemMargin: {
                    horizontal: 12,
                    vertical: 8
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return val.toFixed(1) + '%';
                },
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                dropShadow: {
                    enabled: true,
                    top: 1,
                    left: 1,
                    blur: 2,
                    opacity: 0.3
                }
            },
            tooltip: {
                enabled: true,
                fillSeriesColor: false,
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function(value) {
                        const percentual = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                        return formatarNumero(value) + ' leads (' + percentual + '%)';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.9
                    }
                },
                active: {
                    filter: {
                        type: 'darken',
                        value: 0.85
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        height: 320
                    },
                    legend: {
                        position: 'bottom',
                        fontSize: '11px'
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
        if (!distribuicao || distribuicao.length === 0) {
            document.querySelector("#chart-comercial").innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><span>Sem dados para exibir</span></div>';
            return;
        }

        const categorias = distribuicao.map(item => item.descricao);
        const valores = distribuicao.map(item => item.total);
        const maxValue = Math.max(...valores);

        const options = {
            series: [{
                name: 'Leads',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 380,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 4,
                    opacity: 0.1
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false,
                    columnWidth: categorias.length <= 3 ? '40%' : '65%',
                    distributed: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: gerarGradienteCores(chartColors.comercial.primary, chartColors.comercial.gradient, categorias.length),
            dataLabels: {
                enabled: true,
                offsetY: -22,
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: [labelColor]
                },
                formatter: function(val) {
                    return formatarNumero(val);
                }
            },
            legend: {
                show: false
            },
            xaxis: {
                categories: categorias,
                labels: {
                    rotate: categorias.length > 4 ? -45 : 0,
                    rotateAlways: categorias.length > 4,
                    trim: true,
                    maxHeight: 80,
                    style: {
                        fontSize: '11px',
                        fontWeight: 500,
                        colors: labelColor
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
                        fontSize: '11px',
                        colors: labelColor
                    },
                    formatter: function(val) {
                        return formatarNumero(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 10,
                    right: 10
                }
            },
            tooltip: {
                enabled: true,
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function(val) {
                        return formatarNumero(val) + ' leads';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.88
                    }
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
        if (!distribuicao || distribuicao.length === 0) {
            document.querySelector("#chart-administrativo").innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><span>Sem dados para exibir</span></div>';
            return;
        }

        const categorias = distribuicao.map(item => item.descricao);
        const valores = distribuicao.map(item => item.total);

        const options = {
            series: [{
                name: 'Leads',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 380,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 4,
                    opacity: 0.1
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false,
                    columnWidth: categorias.length <= 3 ? '40%' : '65%',
                    distributed: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: gerarGradienteCores(chartColors.administrativo.primary, chartColors.administrativo.gradient, categorias.length),
            dataLabels: {
                enabled: true,
                offsetY: -22,
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: [labelColor]
                },
                formatter: function(val) {
                    return formatarNumero(val);
                }
            },
            legend: {
                show: false
            },
            xaxis: {
                categories: categorias,
                labels: {
                    rotate: categorias.length > 4 ? -45 : 0,
                    rotateAlways: categorias.length > 4,
                    trim: true,
                    maxHeight: 80,
                    style: {
                        fontSize: '11px',
                        fontWeight: 500,
                        colors: labelColor
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
                        fontSize: '11px',
                        colors: labelColor
                    },
                    formatter: function(val) {
                        return formatarNumero(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 10,
                    right: 10
                }
            },
            tooltip: {
                enabled: true,
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function(val) {
                        return formatarNumero(val) + ' leads';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.88
                    }
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

    function renderizarGraficoDescarte(distribuicao) {
        if (!distribuicao || distribuicao.length === 0) {
            document.querySelector("#chart-descarte").innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><span>Sem dados para exibir</span></div>';
            return;
        }

        const categorias = distribuicao.map(item => item.descricao);
        const valores = distribuicao.map(item => item.total);

        const options = {
            series: [{
                name: 'Leads',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 380,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 4,
                    opacity: 0.1
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: false,
                    columnWidth: categorias.length <= 3 ? '40%' : '65%',
                    distributed: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: gerarGradienteCores(chartColors.descarte.primary, chartColors.descarte.gradient, categorias.length),
            dataLabels: {
                enabled: true,
                offsetY: -22,
                style: {
                    fontSize: '12px',
                    fontWeight: 600,
                    colors: [labelColor]
                },
                formatter: function(val) {
                    return formatarNumero(val);
                }
            },
            legend: {
                show: false
            },
            xaxis: {
                categories: categorias,
                labels: {
                    rotate: categorias.length > 4 ? -45 : 0,
                    rotateAlways: categorias.length > 4,
                    trim: true,
                    maxHeight: 80,
                    style: {
                        fontSize: '11px',
                        fontWeight: 500,
                        colors: labelColor
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
                        fontSize: '11px',
                        colors: labelColor
                    },
                    formatter: function(val) {
                        return formatarNumero(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: 0,
                    bottom: 0,
                    left: 10,
                    right: 10
                }
            },
            tooltip: {
                enabled: true,
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function(val) {
                        return formatarNumero(val) + ' leads';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.88
                    }
                }
            }
        };

        if (chartDescarte) {
            chartDescarte.destroy();
        }

        chartDescarte = new ApexCharts(
            document.querySelector("#chart-descarte"),
            options
        );
        chartDescarte.render();
    }

    function renderizarGraficoMotivosDescarte(motivos) {
        if (!motivos || motivos.length === 0) {
            document.querySelector("#chart-motivos-descarte").innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted"><span>Sem dados para exibir</span></div>';
            return;
        }

        const categorias = motivos.map(item => item.motivo || 'Sem motivo');
        const valores = motivos.map(item => item.total);
        const total = valores.reduce((a, b) => a + b, 0);

        const options = {
            series: [{
                name: 'Leads Descartados',
                data: valores
            }],
            chart: {
                type: 'bar',
                height: 380,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                },
                dropShadow: {
                    enabled: true,
                    top: 2,
                    left: 0,
                    blur: 4,
                    opacity: 0.1
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    horizontal: true,
                    barHeight: categorias.length <= 3 ? '35%' : '60%',
                    distributed: true,
                    dataLabels: {
                        position: 'center'
                    }
                }
            },
            colors: gerarGradienteCores(chartColors.motivos.primary, chartColors.motivos.gradient, categorias.length),
            dataLabels: {
                enabled: true,
                textAnchor: 'middle',
                style: {
                    fontSize: '11px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                formatter: function(val, opts) {
                    const percent = total > 0 ? ((val / total) * 100).toFixed(0) : 0;
                    return formatarNumero(val) + ' (' + percent + '%)';
                },
                dropShadow: {
                    enabled: true,
                    top: 1,
                    left: 1,
                    blur: 2,
                    opacity: 0.3
                }
            },
            legend: {
                show: false
            },
            xaxis: {
                categories: categorias,
                labels: {
                    style: {
                        fontSize: '11px',
                        colors: labelColor
                    },
                    formatter: function(val) {
                        return formatarNumero(val);
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
                        fontSize: '11px',
                        fontWeight: 500,
                        colors: labelColor
                    },
                    maxWidth: 150,
                    formatter: function(val) {
                        // Truncar textos longos
                        if (val && val.length > 20) {
                            return val.substring(0, 18) + '...';
                        }
                        return val;
                    }
                }
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
                    top: 0,
                    bottom: 0,
                    left: 10,
                    right: 10
                }
            },
            tooltip: {
                enabled: true,
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function(val) {
                        const percent = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                        return formatarNumero(val) + ' leads (' + percent + '%)';
                    }
                }
            },
            states: {
                hover: {
                    filter: {
                        type: 'darken',
                        value: 0.88
                    }
                }
            }
        };

        if (chartMotivosDescarte) {
            chartMotivosDescarte.destroy();
        }

        chartMotivosDescarte = new ApexCharts(
            document.querySelector("#chart-motivos-descarte"),
            options
        );
        chartMotivosDescarte.render();
    }

    // Função auxiliar para gerar cores em gradiente
    function gerarGradienteCores(corInicial, corFinal, quantidade) {
        if (quantidade <= 1) return [corInicial];
        if (quantidade === 2) return [corInicial, corFinal];

        const cores = [];
        const hexToRgb = (hex) => {
            const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
            return result ? {
                r: parseInt(result[1], 16),
                g: parseInt(result[2], 16),
                b: parseInt(result[3], 16)
            } : null;
        };

        const rgbToHex = (r, g, b) => {
            return '#' + [r, g, b].map(x => {
                const hex = Math.round(x).toString(16);
                return hex.length === 1 ? '0' + hex : hex;
            }).join('');
        };

        const inicio = hexToRgb(corInicial);
        const fim = hexToRgb(corFinal);

        for (let i = 0; i < quantidade; i++) {
            const ratio = i / (quantidade - 1);
            const r = inicio.r + (fim.r - inicio.r) * ratio;
            const g = inicio.g + (fim.g - inicio.g) * ratio;
            const b = inicio.b + (fim.b - inicio.b) * ratio;
            cores.push(rgbToHex(r, g, b));
        }

        return cores;
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
