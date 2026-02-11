'use strict';

// ════════════════════════════════════════════════
// Chart instances (global para permitir update)
// ════════════════════════════════════════════════
let chartEvolucao, chartGauge, chartOperadora, chartYoY, chartPrevisao;

(function () {
    // ════════════════════════════════════════════
    // Design System Colors
    // ════════════════════════════════════════════
    const dc = {
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

    // Theme-aware colors
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

    const fontUI = 'Plus Jakarta Sans, sans-serif';
    const fontMono = 'JetBrains Mono, monospace';
    const tooltipTheme = isDarkStyle ? 'dark' : 'light';

    // DataTable instances
    let dtOperadoras, dtCohort;

    // Flatpickr instance
    let fpRange;

    // ════════════════════════════════════════════
    // DOM Ready
    // ════════════════════════════════════════════
    $(function () {
        initFlatpickr();
        initPillToggle();
        initDataTables();
        initCharts();
        carregarDados();

        $('#btnFiltrar').on('click', carregarDados);
        $('#btnLimpar').on('click', limparFiltros);
        $('#btnExportar').on('click', exportarRelatorio);
    });

    // ════════════════════════════════════════════
    // Flatpickr — Range mode
    // ════════════════════════════════════════════
    function initFlatpickr() {
        const hoje = new Date();
        const inicioAno = new Date(hoje.getFullYear(), 0, 1);

        fpRange = flatpickr('#periodo_range', {
            mode: 'range',
            dateFormat: 'd/m/Y',
            locale: 'pt',
            defaultDate: [inicioAno, hoje],
            allowInput: false,
            disableMobile: true
        });
    }

    // ════════════════════════════════════════════
    // Pill Toggle — Tipo Receita
    // ════════════════════════════════════════════
    function initPillToggle() {
        $(document).on('click', '.pill-option', function () {
            $('.pill-option').removeClass('active');
            $(this).addClass('active');
            $('#tipo_receita').val($(this).data('value'));
        });
    }

    // ════════════════════════════════════════════
    // DataTables — 3 tabelas
    // ════════════════════════════════════════════
    function initDataTables() {
        const dtLang = { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json' };
        const dtBase = { paging: true, pageLength: 10, searching: false, info: true, language: dtLang };

        // 1. Operadoras
        dtOperadoras = $('#tabelaOperadoras').DataTable({
            ...dtBase,
            columns: [
                { data: 'operadora' },
                { data: 'previsto', className: 'text-end value-cell primary', render: v => formatCurrency(v) },
                { data: 'recebido', className: 'text-end value-cell success', render: v => formatCurrency(v) },
                { data: 'pendente', className: 'text-end value-cell warning', render: v => formatCurrency(v) },
                { data: 'cancelado', className: 'text-end value-cell danger', render: v => formatCurrency(v) },
                {
                    data: 'taxa_recebimento', className: 'text-center',
                    render: function (val) {
                        const cls = val >= 80 ? 'success' : val >= 50 ? 'warning' : 'danger';
                        return '<span class="badge-taxa ' + cls + '">' + val.toFixed(1) + '%</span>';
                    }
                },
                { data: 'ticket_medio', className: 'text-end value-cell primary', render: v => formatCurrency(v) }
            ]
        });

        // 2. Cohort
        dtCohort = $('#tabelaCohort').DataTable({
            ...dtBase,
            pageLength: 20,
            order: [[0, 'desc']],
            columns: [
                { data: 'ano_implantacao' },
                { data: 'qtd_contratos', className: 'text-center' },
                { data: 'valor_total_contratos', className: 'text-end value-cell primary', render: v => formatCurrency(v) },
                { data: 'receita_fixa', className: 'text-end value-cell primary', render: v => formatCurrency(v) },
                { data: 'receita_vitalicio', className: 'text-end value-cell success', render: v => formatCurrency(v) },
                { data: 'receita_total', className: 'text-end value-cell success', render: v => formatCurrency(v) },
                {
                    data: 'roi', className: 'text-center',
                    render: function (val) {
                        const pct = Math.min(val, 100);
                        const cls = val >= 100 ? 'success' : val >= 50 ? 'warning' : 'danger';
                        return '<div class="roi-bar-wrapper">' +
                            '<div class="roi-bar ' + cls + '" style="width:' + pct + '%"></div>' +
                            '<span class="roi-label">' + val.toFixed(1) + '%</span>' +
                            '</div>';
                    }
                }
            ]
        });
    }

    // ════════════════════════════════════════════
    // Charts — 7 ApexCharts
    // ════════════════════════════════════════════
    function initCharts() {
        const gridBase = {
            borderColor: borderColor,
            strokeDashArray: 4,
            padding: { top: -10, bottom: 0, left: 10, right: 10 }
        };

        // 1. Evolução Mensal (Area stacked)
        chartEvolucao = new ApexCharts(document.querySelector('#chartEvolucaoMensal'), {
            series: [],
            chart: {
                type: 'area',
                height: 320,
                stacked: true,
                fontFamily: fontUI,
                toolbar: { show: true, tools: { download: true, zoom: true, zoomin: true, zoomout: true, pan: false, reset: true } },
                background: 'transparent'
            },
            colors: [dc.primary, dc.success],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2.5 },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.05, stops: [0, 90, 100] }
            },
            xaxis: {
                categories: [],
                labels: { style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI },
                    formatter: v => formatCurrencyShort(v)
                }
            },
            legend: { show: false },
            grid: gridBase,
            tooltip: {
                theme: tooltipTheme,
                style: { fontFamily: fontUI },
                y: { formatter: v => formatCurrency(v) }
            }
        });
        chartEvolucao.render();

        // 2. Gauge — Radial Bar
        chartGauge = new ApexCharts(document.querySelector('#chartTaxaRecebimento'), {
            series: [0],
            chart: { type: 'radialBar', height: 260, fontFamily: fontUI },
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: { size: '68%', background: 'transparent' },
                    track: {
                        background: isDarkStyle ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)',
                        strokeWidth: '100%',
                        margin: 0
                    },
                    dataLabels: {
                        name: { offsetY: -12, color: labelColor, fontSize: '13px', fontFamily: fontUI, fontWeight: 500 },
                        value: {
                            offsetY: 8,
                            color: headingColor,
                            fontSize: '28px',
                            fontFamily: fontMono,
                            fontWeight: 700,
                            formatter: v => v.toFixed(1) + '%'
                        }
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.5,
                    gradientToColors: [dc.success],
                    stops: [0, 100]
                }
            },
            colors: [dc.primary],
            labels: ['Recebimento'],
            stroke: { lineCap: 'round' }
        });
        chartGauge.render();

        // 3. Operadora — Horizontal bar grouped
        chartOperadora = new ApexCharts(document.querySelector('#chartPorOperadora'), {
            series: [],
            chart: { type: 'bar', height: 340, fontFamily: fontUI, toolbar: { show: false } },
            plotOptions: {
                bar: { horizontal: true, borderRadius: 5, barHeight: '60%' }
            },
            colors: [dc.success, dc.warning, dc.danger],
            dataLabels: { enabled: false },
            xaxis: {
                categories: [],
                labels: {
                    style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI },
                    formatter: v => formatCurrencyShort(v)
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: { style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI } }
            },
            legend: { show: false },
            grid: { ...gridBase, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
            tooltip: {
                theme: tooltipTheme,
                style: { fontFamily: fontUI },
                y: { formatter: v => formatCurrency(v) }
            }
        });
        chartOperadora.render();

        // 4. YoY — Grouped bar
        chartYoY = new ApexCharts(document.querySelector('#chartComparativoAnual'), {
            series: [],
            chart: { type: 'bar', height: 320, fontFamily: fontUI, toolbar: { show: false } },
            plotOptions: { bar: { borderRadius: 5, columnWidth: '65%' } },
            colors: [dc.primary, dc.info, dc.primaryLight],
            dataLabels: { enabled: false },
            xaxis: {
                categories: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                labels: { style: { colors: labelColor, fontSize: '11px', fontFamily: fontUI } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI },
                    formatter: v => formatCurrencyShort(v)
                }
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'right',
                fontFamily: fontUI,
                fontSize: '12px',
                labels: { colors: legendColor }
            },
            grid: gridBase,
            tooltip: {
                theme: tooltipTheme,
                style: { fontFamily: fontUI },
                y: { formatter: v => formatCurrency(v) }
            }
        });
        chartYoY.render();

        // 6. Previsão — Line + Area
        chartPrevisao = new ApexCharts(document.querySelector('#chartPrevisaoReceita'), {
            series: [],
            chart: {
                type: 'area',
                height: 300,
                fontFamily: fontUI,
                toolbar: { show: true, tools: { download: true, zoom: true, zoomin: true, zoomout: true, pan: false, reset: true } },
                background: 'transparent'
            },
            colors: [dc.success, dc.primary],
            stroke: {
                curve: 'smooth',
                width: [3, 2],
                dashArray: [0, 6]
            },
            fill: {
                type: ['gradient', 'gradient'],
                gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90, 100] }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: [],
                labels: { style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor, fontSize: '12px', fontFamily: fontUI },
                    formatter: v => formatCurrencyShort(v)
                }
            },
            legend: { show: false },
            grid: gridBase,
            tooltip: {
                theme: tooltipTheme,
                style: { fontFamily: fontUI },
                y: { formatter: v => formatCurrency(v) }
            }
        });
        chartPrevisao.render();
    }

    // ════════════════════════════════════════════
    // Carregar Dados (AJAX)
    // ════════════════════════════════════════════
    function carregarDados() {
        // Parse range dates
        let dataInicial = '';
        let dataFinal = '';
        if (fpRange && fpRange.selectedDates.length >= 1) {
            dataInicial = converterDataParaBackend(flatpickr.formatDate(fpRange.selectedDates[0], 'd/m/Y'));
        }
        if (fpRange && fpRange.selectedDates.length >= 2) {
            dataFinal = converterDataParaBackend(flatpickr.formatDate(fpRange.selectedDates[1], 'd/m/Y'));
        }

        $.ajax({
            url: '/financeiro/recebiveis/relatorio-financeiro/fetch',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                data_inicial: dataInicial,
                data_final: dataFinal,
                operadora_id: $('#operadora_id').val(),
                vendedor_id: $('#vendedor_id').val(),
                tipo_receita: $('#tipo_receita').val()
            },
            beforeSend: function () {
                $('.kpi-value').addClass('skeleton');
                $('.chart-body').addClass('loading-overlay');
            },
            success: function (data) {
                atualizarKPIs(data.kpis);
                atualizarChartEvolucao(data.evolucaoMensal);
                atualizarChartGauge(data.taxaRecebimentoGauge);
                atualizarChartOperadora(data.porOperadora);
                atualizarChartYoY(data.comparativoAnual);
                atualizarChartPrevisao(data.previsaoReceita);
                atualizarTabelaOperadoras(data.porOperadora);
                atualizarTabelaCohort(data.cohortAnalise);

                $('.kpi-value').removeClass('skeleton');
                $('.chart-body').removeClass('loading-overlay');
            },
            error: function (xhr, status, error) {
                console.error('Erro ao carregar dados:', error);
                $('.kpi-value').removeClass('skeleton');
                $('.chart-body').removeClass('loading-overlay');
            }
        });
    }

    // ════════════════════════════════════════════
    // Limpar Filtros
    // ════════════════════════════════════════════
    function limparFiltros() {
        const hoje = new Date();
        const inicioAno = new Date(hoje.getFullYear(), 0, 1);
        if (fpRange) fpRange.setDate([inicioAno, hoje]);
        $('#operadora_id').val('');
        $('#vendedor_id').val('');
        $('.pill-option').removeClass('active').first().addClass('active');
        $('#tipo_receita').val('todas');
        carregarDados();
    }

    // ════════════════════════════════════════════
    // Atualizar KPIs
    // ════════════════════════════════════════════
    function atualizarKPIs(kpis) {
        animateValue('#kpiReceitaTotal', kpis.receita_total, true);
        animateValue('#kpiReceitaRecebida', kpis.receita_recebida, true);
        animateValue('#kpiMrr', kpis.mrr, true);
        animateValue('#kpiInadimplencia', kpis.inadimplencia, true);
        animateValue('#kpiReceitaFixa', kpis.receita_fixa, true);
        animateValue('#kpiReceitaVitalicio', kpis.receita_vitalicio, true);
        $('#kpiContratosAtivos').text(kpis.contratos_ativos);
        animateValue('#kpiReceitaCancelada', kpis.receita_cancelada, true);

        // Trends
        updateTrend('#trendVariacaoAnual', kpis.variacao_anual, '%', ' vs ano anterior');
        updateTrend('#trendTaxaRecebimento', kpis.taxa_recebimento, '%', '', 'Taxa: ');
        updateTrend('#trendMrrVariacao', kpis.mrr_variacao, '%');

        $('#trendInadimplenciaQtd span').text(kpis.inadimplencia_qtd + ' parcelas');

        $('#trendVitalicioPerc span').text(kpis.receita_vitalicio_percentual + '% do total');
        $('#trendValorCarteira span').text('Carteira: ' + formatCurrencyShort(kpis.valor_carteira));
        $('#trendTaxaCancelamento span').text(kpis.taxa_cancelamento + '% cancelamento');

        // Color coding for cancelamento trend
        const $cancelTrend = $('#trendTaxaCancelamento');
        $cancelTrend.removeClass('trend-up trend-down trend-neutral');
        $cancelTrend.addClass(kpis.taxa_cancelamento > 10 ? 'trend-down' : kpis.taxa_cancelamento > 5 ? 'trend-neutral' : 'trend-up');
    }

    function updateTrend(selector, value, suffix, append, prepend) {
        const $el = $(selector);
        $el.removeClass('trend-up trend-down trend-neutral');
        if (value > 0) {
            $el.addClass('trend-up');
        } else if (value < 0) {
            $el.addClass('trend-down');
        } else {
            $el.addClass('trend-neutral');
        }
        const prefix = value > 0 ? '+' : '';
        const pre = prepend || '';
        const app = append || '';
        $el.find('span').text(pre + prefix + value + suffix + app);
    }

    // ════════════════════════════════════════════
    // Chart Update Functions
    // ════════════════════════════════════════════

    function atualizarChartEvolucao(dados) {
        if (!dados || dados.length === 0) {
            chartEvolucao.updateOptions({ series: [], xaxis: { categories: [] } });
            return;
        }
        chartEvolucao.updateOptions({
            series: [
                { name: 'Receita Fixa', data: dados.map(d => d.receita_fixa) },
                { name: 'Receita Vitalício', data: dados.map(d => d.receita_vitalicio) }
            ],
            xaxis: { categories: dados.map(d => d.mes) }
        });
    }

    function atualizarChartGauge(gauge) {
        const pct = gauge.percentual || 0;
        // Dynamic color based on percentage
        let gaugeColor = dc.danger;
        let gradientEnd = dc.dangerLight;
        if (pct >= 80) {
            gaugeColor = dc.success;
            gradientEnd = dc.successLight;
        } else if (pct >= 50) {
            gaugeColor = dc.warning;
            gradientEnd = dc.warningLight;
        }

        chartGauge.updateOptions({
            series: [pct],
            colors: [gaugeColor],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.5,
                    gradientToColors: [gradientEnd],
                    stops: [0, 100]
                }
            }
        });

        // Mini stats
        $('#gaugePrevisto').text(formatCurrencyShort(gauge.total_previsto));
        $('#gaugeRecebido').text(formatCurrencyShort(gauge.total_recebido));
        $('#gaugeAberto').text(formatCurrencyShort(gauge.total_aberto));
    }

    function atualizarChartOperadora(dados) {
        if (!dados || dados.length === 0) {
            chartOperadora.updateOptions({ series: [], xaxis: { categories: [] } });
            return;
        }
        chartOperadora.updateOptions({
            series: [
                { name: 'Recebido', data: dados.map(d => d.recebido) },
                { name: 'Pendente', data: dados.map(d => d.pendente) },
                { name: 'Cancelado', data: dados.map(d => d.cancelado) }
            ],
            xaxis: { categories: dados.map(d => d.operadora) }
        });
    }

    function atualizarChartYoY(data) {
        if (!data || !data.anos || data.anos.length === 0) {
            chartYoY.updateOptions({ series: [] });
            return;
        }
        const series = data.anos.map(ano => ({
            name: String(ano),
            data: data.series[ano] || Array(12).fill(0)
        }));
        chartYoY.updateOptions({ series: series });
    }

    function atualizarChartPrevisao(data) {
        if (!data) return;

        const historico = data.historico || [];
        const previsao = data.previsao || [];

        // Combine all months
        const allMeses = [];
        const historicoVals = [];
        const previsaoVals = [];

        historico.forEach(h => {
            allMeses.push(h.mes);
            historicoVals.push(h.valor);
            previsaoVals.push(null);
        });

        // Bridge: last historico value connects to first previsao
        if (historico.length > 0 && previsao.length > 0) {
            previsaoVals[previsaoVals.length - 1] = historico[historico.length - 1].valor;
        }

        previsao.forEach(p => {
            allMeses.push(p.mes);
            historicoVals.push(null);
            previsaoVals.push(p.valor);
        });

        chartPrevisao.updateOptions({
            series: [
                { name: 'Realizado', data: historicoVals },
                { name: 'Previsão', data: previsaoVals }
            ],
            xaxis: { categories: allMeses }
        });
    }

    // ════════════════════════════════════════════
    // Table Update Functions
    // ════════════════════════════════════════════

    function atualizarTabelaOperadoras(dados) {
        dtOperadoras.clear().rows.add(dados || []).draw();
    }

    function atualizarTabelaCohort(dados) {
        dtCohort.clear().rows.add(dados || []).draw();
    }

    // ════════════════════════════════════════════
    // Utilitários
    // ════════════════════════════════════════════

    function formatCurrency(value) {
        return 'R$ ' + Number(value || 0).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatCurrencyShort(value) {
        const v = Number(value || 0);
        if (v >= 1000000) return 'R$ ' + (v / 1000000).toFixed(1) + 'M';
        if (v >= 1000) return 'R$ ' + (v / 1000).toFixed(1) + 'K';
        return 'R$ ' + v.toFixed(0);
    }

    function formatPercent(value) {
        return Number(value || 0).toFixed(1) + '%';
    }

    function converterDataParaBackend(dataBrasileira) {
        if (!dataBrasileira) return '';
        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';
        return partes[2] + '-' + partes[1].padStart(2, '0') + '-' + partes[0].padStart(2, '0');
    }

    function animateValue(selector, endValue, isCurrency) {
        const $el = $(selector);
        const start = 0;
        const end = Number(endValue || 0);
        const duration = 600;
        const startTime = performance.now();

        function step(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            // ease-out quad
            const eased = 1 - (1 - progress) * (1 - progress);
            const current = start + (end - start) * eased;

            if (isCurrency) {
                $el.text(formatCurrency(current));
            } else {
                $el.text(Math.round(current));
            }

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }
        requestAnimationFrame(step);
    }

    function exportarRelatorio() {
        window.print();
    }

})();
