'use strict';

(function () {
    // State
    let dados = null;
    let charts = {};

    // DOM Elements
    const filtroAno = document.getElementById('filtro-ano');
    const filtroMesInicio = document.getElementById('filtro-mes-inicio');
    const filtroMesFim = document.getElementById('filtro-mes-fim');
    const btnCarregar = document.getElementById('btn-carregar');
    const btnAnalisar = document.getElementById('btn-analisar');

    const kpisContainer = document.getElementById('kpis-container');
    const chartsContainer = document.getElementById('charts-container');
    const emptyState = document.getElementById('empty-state');
    const iaContainer = document.getElementById('ia-analysis-container');

    // IA Elements
    const iaLoading = document.getElementById('ia-loading');
    const iaResult = document.getElementById('ia-result');
    const iaResultText = document.getElementById('ia-result-text');
    const iaError = document.getElementById('ia-error');
    const iaErrorText = document.getElementById('ia-error-text');
    const iaEmpty = document.getElementById('ia-empty');

    // Chart colors based on theme
    let cardColor, labelColor, headingColor, borderColor;

    function initColors() {
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
    }

    // Format currency
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    // Format number
    function formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(value);
    }

    // Load data from API
    async function loadData() {
        const ano = filtroAno.value;
        const mesInicio = filtroMesInicio.value;
        const mesFim = filtroMesFim.value;

        btnCarregar.disabled = true;
        btnCarregar.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Carregando...
        `;

        try {
            const response = await fetch(`/relatorios/aproveitamento/dados?ano=${ano}&mes_inicio=${mesInicio}&mes_fim=${mesFim}`);
            const result = await response.json();

            if (result.success) {
                dados = result.dados;
                renderData();
                emptyState.style.display = 'none';
                kpisContainer.style.display = 'grid';
                chartsContainer.style.display = 'grid';
                if (iaContainer) {
                    iaContainer.style.display = 'block';
                }
                if (btnAnalisar) {
                    btnAnalisar.disabled = false;
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: result.error || 'Erro ao carregar dados'
                });
            }
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: 'Erro de conexão ao carregar dados'
            });
        } finally {
            btnCarregar.disabled = false;
            btnCarregar.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"/>
                    <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                </svg>
                Carregar Dados
            `;
        }
    }

    // Render all data
    function renderData() {
        initColors();
        renderKPIs();
        renderChartLeadsMes();
        renderChartVendasMes();
        renderChartFunil();
        renderChartTemperatura();
        renderChartStatus();
        renderChartFontes();
    }

    // Render KPIs
    function renderKPIs() {
        const kpis = dados.kpis;
        document.getElementById('kpi-total-leads').textContent = formatNumber(kpis.total_leads);
        document.getElementById('kpi-vendas-cadastradas').textContent = formatNumber(kpis.vendas_cadastradas);
        document.getElementById('kpi-vendas-implantadas').textContent = formatNumber(kpis.vendas_implantadas);
        document.getElementById('kpi-taxa-conversao').textContent = kpis.taxa_conversao + '%';
        document.getElementById('kpi-valor-cadastradas').textContent = formatCurrency(kpis.valor_cadastradas);
        document.getElementById('kpi-valor-implantadas').textContent = formatCurrency(kpis.valor_implantadas);
    }

    // Chart: Leads por Mês
    function renderChartLeadsMes() {
        const container = document.getElementById('chart-leads-mes');
        if (charts.leadsMes) {
            charts.leadsMes.destroy();
        }

        const leadsPorMes = dados.leads_por_mes;

        const options = {
            series: [
                {
                    name: 'Total',
                    data: leadsPorMes.map(m => m.total)
                },
                {
                    name: 'ADS',
                    data: leadsPorMes.map(m => m.ads)
                },
                {
                    name: 'Base',
                    data: leadsPorMes.map(m => m.base)
                }
            ],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 6
                }
            },
            colors: ['#7C3AED', '#06B6D4', '#F59E0B'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: leadsPorMes.map(m => m.nome),
                labels: {
                    style: { colors: labelColor }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor },
                    formatter: function (val) {
                        return formatNumber(val);
                    }
                }
            },
            fill: {
                opacity: 1
            },
            legend: {
                show: false
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatNumber(val) + ' leads';
                    }
                }
            },
            grid: {
                borderColor: borderColor
            }
        };

        charts.leadsMes = new ApexCharts(container, options);
        charts.leadsMes.render();
    }

    // Chart: Vendas por Mês (Cadastradas vs Implantadas)
    function renderChartVendasMes() {
        const container = document.getElementById('chart-vendas-mes');
        if (charts.vendasMes) {
            charts.vendasMes.destroy();
        }

        const vendasPorMes = dados.vendas_por_mes;

        const options = {
            series: [
                {
                    name: 'Cadastradas',
                    type: 'column',
                    data: vendasPorMes.map(m => m.cadastradas)
                },
                {
                    name: 'Implantadas',
                    type: 'column',
                    data: vendasPorMes.map(m => m.implantadas)
                },
                {
                    name: 'Valor Implantado',
                    type: 'line',
                    data: vendasPorMes.map(m => m.implantadas_valor)
                }
            ],
            chart: {
                height: 300,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            plotOptions: {
                bar: {
                    columnWidth: '60%',
                    borderRadius: 4
                }
            },
            colors: ['#06B6D4', '#10B981', '#7C3AED'],
            fill: {
                opacity: [1, 1, 1]
            },
            labels: vendasPorMes.map(m => m.nome),
            xaxis: {
                labels: {
                    style: { colors: labelColor }
                }
            },
            yaxis: [
                {
                    title: {
                        text: 'Quantidade',
                        style: { color: labelColor }
                    },
                    labels: {
                        style: { colors: labelColor }
                    }
                },
                {
                    show: false
                },
                {
                    opposite: true,
                    title: {
                        text: 'Valor (R$)',
                        style: { color: labelColor }
                    },
                    labels: {
                        style: { colors: labelColor },
                        formatter: function (val) {
                            return formatCurrency(val);
                        }
                    }
                }
            ],
            legend: {
                show: false
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val, { seriesIndex }) {
                        if (seriesIndex === 0 || seriesIndex === 1) {
                            return formatNumber(val) + ' vendas';
                        }
                        return formatCurrency(val);
                    }
                }
            },
            grid: {
                borderColor: borderColor
            }
        };

        charts.vendasMes = new ApexCharts(container, options);
        charts.vendasMes.render();
    }

    // Chart: Funil de Conversão
    function renderChartFunil() {
        const container = document.getElementById('chart-funil');
        if (charts.funil) {
            charts.funil.destroy();
        }

        const funil = dados.funil_conversao;

        const options = {
            series: [funil.importados, funil.atribuidos, funil.em_negociacao, funil.convertidos],
            chart: {
                type: 'donut',
                height: 300,
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            labels: ['Importados', 'Atribuídos', 'Em Negociação', 'Convertidos'],
            colors: ['#7C3AED', '#06B6D4', '#F59E0B', '#10B981'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            name: {
                                show: true,
                                color: headingColor
                            },
                            value: {
                                show: true,
                                color: headingColor,
                                formatter: function (val) {
                                    return formatNumber(val);
                                }
                            },
                            total: {
                                show: true,
                                label: 'Taxa Conversão',
                                color: labelColor,
                                formatter: function () {
                                    return funil.taxa_conversao + '%';
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                position: 'bottom',
                labels: { colors: labelColor }
            },
            dataLabels: {
                enabled: false
            }
        };

        charts.funil = new ApexCharts(container, options);
        charts.funil.render();
    }

    // Chart: Temperatura
    function renderChartTemperatura() {
        const container = document.getElementById('chart-temperatura');
        if (charts.temperatura) {
            charts.temperatura.destroy();
        }

        const temp = dados.distribuicao_temperatura;
        const total = temp.quente + temp.morno + temp.frio;

        const options = {
            series: [temp.quente, temp.morno, temp.frio],
            chart: {
                type: 'pie',
                height: 300,
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            labels: ['Quente', 'Morno', 'Frio'],
            colors: ['#EF4444', '#F59E0B', '#06B6D4'],
            legend: {
                position: 'bottom',
                labels: { colors: labelColor }
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
                y: {
                    formatter: function (val) {
                        return formatNumber(val) + ' leads';
                    }
                }
            }
        };

        charts.temperatura = new ApexCharts(container, options);
        charts.temperatura.render();
    }

    // Chart: Status
    function renderChartStatus() {
        const container = document.getElementById('chart-status');
        if (charts.status) {
            charts.status.destroy();
        }

        const statusDist = dados.distribuicao_status.slice(0, 10);

        const options = {
            series: [{
                name: 'Leads',
                data: statusDist.map(s => s.total)
            }],
            chart: {
                type: 'bar',
                height: 300,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    barHeight: '70%'
                }
            },
            colors: ['#7C3AED'],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return formatNumber(val);
                },
                style: {
                    colors: ['#fff']
                }
            },
            xaxis: {
                categories: statusDist.map(s => s.status),
                labels: {
                    style: { colors: labelColor }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor }
                }
            },
            grid: {
                borderColor: borderColor
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatNumber(val) + ' leads';
                    }
                }
            }
        };

        charts.status = new ApexCharts(container, options);
        charts.status.render();
    }

    // Chart: Fontes
    function renderChartFontes() {
        const container = document.getElementById('chart-fontes');
        if (charts.fontes) {
            charts.fontes.destroy();
        }

        const fontes = dados.fontes_importacao;

        const options = {
            series: fontes.map(f => f.total),
            chart: {
                type: 'donut',
                height: 300,
                fontFamily: 'Plus Jakarta Sans, sans-serif'
            },
            labels: fontes.map(f => f.fonte),
            colors: ['#7C3AED', '#06B6D4', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#14B8A6', '#F97316', '#EC4899', '#6366F1'],
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            },
            legend: {
                position: 'bottom',
                labels: { colors: labelColor }
            },
            dataLabels: {
                enabled: false
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatNumber(val) + ' leads';
                    }
                }
            }
        };

        charts.fontes = new ApexCharts(container, options);
        charts.fontes.render();
    }

    // Generate AI Analysis
    async function generateAnalysis() {
        if (!dados) {
            Swal.fire({
                icon: 'warning',
                title: 'Atenção',
                text: 'Carregue os dados primeiro'
            });
            return;
        }

        const ano = filtroAno.value;
        const mesInicio = filtroMesInicio.value;
        const mesFim = filtroMesFim.value;

        btnAnalisar.disabled = true;
        btnAnalisar.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Analisando...
        `;

        // Show loading
        iaLoading.style.display = 'flex';
        iaResult.style.display = 'none';
        iaError.style.display = 'none';
        iaEmpty.style.display = 'none';

        try {
            const response = await fetch('/relatorios/aproveitamento/analise', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    ano: ano,
                    mes_inicio: mesInicio,
                    mes_fim: mesFim
                })
            });

            const result = await response.json();

            iaLoading.style.display = 'none';

            if (result.success) {
                iaResult.style.display = 'block';
                iaResultText.innerHTML = formatIAResponse(result.analise);
            } else {
                iaError.style.display = 'flex';
                iaErrorText.textContent = result.error || 'Erro desconhecido';
            }
        } catch (error) {
            console.error('Erro na análise IA:', error);
            iaLoading.style.display = 'none';
            iaError.style.display = 'flex';
            iaErrorText.textContent = 'Erro de conexão. Verifique sua internet e tente novamente.';
        } finally {
            btnAnalisar.disabled = false;
            btnAnalisar.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/>
                    <circle cx="7.5" cy="14.5" r="1.5"/>
                    <circle cx="16.5" cy="14.5" r="1.5"/>
                </svg>
                Gerar Análise IA
            `;
        }
    }

    // Format IA response (markdown to HTML)
    function formatIAResponse(text) {
        // Headers
        text = text.replace(/### (.+)/g, '<h4 class="ia-section-title">$1</h4>');
        text = text.replace(/## (.+)/g, '<h3 class="ia-section-title">$1</h3>');

        // Bold
        text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

        // Lists
        text = text.replace(/^- (.+)$/gm, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul class="ia-list">$&</ul>');

        // Numbered lists
        text = text.replace(/^\d+\. (.+)$/gm, '<li>$1</li>');

        // Line breaks
        text = text.replace(/\n\n/g, '</p><p>');
        text = text.replace(/\n/g, '<br>');

        // Wrap in paragraph
        if (!text.startsWith('<')) {
            text = '<p>' + text + '</p>';
        }

        return text;
    }

    // Event Listeners
    btnCarregar.addEventListener('click', loadData);

    if (btnAnalisar) {
        btnAnalisar.addEventListener('click', generateAnalysis);
    }

    // Validate mes_fim >= mes_inicio
    filtroMesInicio.addEventListener('change', function () {
        if (parseInt(filtroMesFim.value) < parseInt(this.value)) {
            filtroMesFim.value = this.value;
        }
    });

    filtroMesFim.addEventListener('change', function () {
        if (parseInt(this.value) < parseInt(filtroMesInicio.value)) {
            this.value = filtroMesInicio.value;
        }
    });

    // Initialize colors
    initColors();
})();
