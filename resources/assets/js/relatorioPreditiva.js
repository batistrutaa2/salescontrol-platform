/**
 * Relatorio de Atividade Preditiva
 * Design System: Glass Morphism + Gradient
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    // ============================================
    // Theme Detection & Colors
    // ============================================
    const isDarkStyle = document.documentElement.classList.contains('dark-style');

    // Theme-aware colors
    const colors = {
        primary: '#7C3AED',
        primaryLight: '#A78BFA',
        success: '#10B981',
        successLight: '#34D399',
        danger: '#EF4444',
        dangerLight: '#F87171',
        info: '#06B6D4',
        infoLight: '#22D3EE',
        warning: '#F59E0B',
        warningLight: '#FBBF24'
    };

    // Theme-aware chart colors
    let cardColor, labelColor, headingColor, borderColor, legendColor;

    if (isDarkStyle) {
        cardColor = 'rgba(30, 32, 47, 0.95)';
        labelColor = '#9CA3AF';
        headingColor = '#F9FAFB';
        borderColor = 'rgba(255, 255, 255, 0.08)';
        legendColor = '#D1D5DB';
    } else {
        cardColor = 'rgba(255, 255, 255, 0.95)';
        labelColor = '#9CA3AF';
        headingColor = '#1F2937';
        borderColor = 'rgba(255, 255, 255, 0.2)';
        legendColor = '#6B7280';
    }

    // ============================================
    // Initialize Components
    // ============================================

    // Initialize Flatpickr
    const flatpickrConfig = {
        dateFormat: 'd/m/Y',
        locale: {
            firstDayOfWeek: 0,
            weekdays: {
                shorthand: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
                longhand: ['Domingo', 'Segunda', 'Terca', 'Quarta', 'Quinta', 'Sexta', 'Sabado']
            },
            months: {
                shorthand: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                longhand: ['Janeiro', 'Fevereiro', 'Marco', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro']
            }
        }
    };

    // Initialize date pickers with default values
    const dataInicioPicker = flatpickr('#data_inicio', {
        ...flatpickrConfig,
        defaultDate: new Date(new Date().setDate(new Date().getDate() - 30))
    });

    const dataFimPicker = flatpickr('#data_fim', {
        ...flatpickrConfig,
        defaultDate: new Date()
    });

    // Initialize Select2
    const select2 = $('.select2');
    if (select2.length) {
        select2.each(function () {
            var $this = $(this);
            $this.wrap('<div class="position-relative"></div>').select2({
                placeholder: 'Selecione uma opcao',
                allowClear: true,
                dropdownParent: $this.parent()
            });
        });
    }

    // Initialize DataTable
    const tabelaAtividades = $('#tabela-atividades');
    let dt = null;

    if (tabelaAtividades.length) {
        dt = tabelaAtividades.DataTable({
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6 d-flex justify-content-center justify-content-md-end"f>>t<"row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
            language: {
                processing: "Processando...",
                search: "Pesquisar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 a 0 de 0 registros",
                infoFiltered: "(filtrado de _MAX_ registros no total)",
                loadingRecords: "Carregando...",
                zeroRecords: "Nenhum registro encontrado",
                emptyTable: "Nenhum dado disponivel na tabela",
                paginate: {
                    first: "Primeiro",
                    previous: "Anterior",
                    next: "Proximo",
                    last: "Ultimo"
                },
                aria: {
                    sortAscending: ": ativar para ordenar coluna de forma crescente",
                    sortDescending: ": ativar para ordenar coluna de forma decrescente"
                }
            },
            responsive: true,
            processing: true,
            serverSide: false,
            columns: [
                { data: 'data', name: 'data' },
                { data: 'usuario', name: 'usuario' },
                { data: 'cliente', name: 'cliente' },
                { data: 'telefone', name: 'telefone' },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data) {
                        const statusClass = data === 'CONVERSAO' ? 'status-conversao' : (data === 'DESCARTE' ? 'status-descarte' : 'status-default');
                        const statusLabel = data === 'CONVERSAO' ? 'Convertido' : (data === 'DESCARTE' ? 'Descartado' : data);
                        return `<span class="status-badge ${statusClass}">${statusLabel}</span>`;
                    }
                },
                { data: 'tabulacao', name: 'tabulacao' }
            ],
            order: [[0, 'desc']]
        });
    }

    // ============================================
    // Filter Toggle
    // ============================================
    const toggleBtn = document.getElementById('toggleFilters');
    const filterBody = document.getElementById('filterBody');

    if (toggleBtn && filterBody) {
        toggleBtn.addEventListener('click', function () {
            filterBody.style.display = filterBody.style.display === 'none' ? 'block' : 'none';
            toggleBtn.querySelector('svg').style.transform = filterBody.style.display === 'none' ? 'rotate(180deg)' : 'rotate(0deg)';
        });
    }

    // ============================================
    // Form Submission
    // ============================================
    const form = document.getElementById('form-filtro-preditiva');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            buscarDados();
        });
    }

    // ============================================
    // Data Fetching
    // ============================================
    function buscarDados() {
        showLoading(true);

        const formData = new FormData(form);

        // Convert dates to backend format
        const dataInicio = document.getElementById('data_inicio').value;
        const dataFim = document.getElementById('data_fim').value;

        formData.set('data_inicio', converterDataParaBackend(dataInicio));
        formData.set('data_fim', converterDataParaBackend(dataFim));

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {
                showLoading(false);

                if (response.success) {
                    // Update KPI cards
                    atualizarKPIs(response.resumo);

                    // Update table
                    if (dt) {
                        dt.clear().rows.add(response.atividades).draw();
                    }

                    // Update charts
                    atualizarGraficoAtividadeDiaria(response.grafico_diario);
                    atualizarGraficoVendedores(response.grafico_vendedores);

                    if (response.grafico_tabulacao) {
                        atualizarGraficoTabulacao(response.grafico_tabulacao);
                    }
                } else {
                    console.error('Erro ao buscar dados:', response.message);
                }
            })
            .catch(error => {
                showLoading(false);
                console.error('Erro na requisicao:', error);
            });
    }

    // ============================================
    // KPI Updates
    // ============================================
    function atualizarKPIs(resumo) {
        animateValue('total-contatos', resumo.total || 0);
        animateValue('total-convertidos', resumo.convertidos || 0);
        animateValue('total-descartados', resumo.descartados || 0);
        document.getElementById('taxa-conversao').textContent = resumo.taxa_conversao || '0%';
    }

    function animateValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const start = parseInt(element.textContent) || 0;
        const end = parseInt(value) || 0;
        const duration = 500;
        const startTime = performance.now();

        function update(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (end - start) * easeProgress);
            element.textContent = current.toLocaleString('pt-BR');

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }

        requestAnimationFrame(update);
    }

    // ============================================
    // Charts
    // ============================================
    let chartAtividadeDiaria, chartVendedores, chartTabulacao;

    function atualizarGraficoAtividadeDiaria(dados) {
        const el = document.getElementById('grafico-atividade-diaria');
        if (!el) return;

        // Destroy existing chart first
        if (chartAtividadeDiaria) {
            chartAtividadeDiaria.destroy();
            chartAtividadeDiaria = null;
        }

        // Clear any empty state
        el.innerHTML = '';

        if (!dados || !dados.datas || dados.datas.length === 0) {
            el.innerHTML = createEmptyChartState('Sem dados de atividade diaria');
            return;
        }

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
                type: 'area',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.primary, colors.success, colors.danger],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: { top: 10 }
            },
            xaxis: {
                categories: dados.datas || [],
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '11px'
                    }
                }
            },
            yaxis: {
                tickAmount: 4,
                labels: {
                    style: { colors: labelColor }
                }
            },
            tooltip: {
                shared: true,
                theme: isDarkStyle ? 'dark' : 'light'
            },
            legend: {
                show: false
            },
            markers: {
                size: 4,
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: { size: 6 }
            }
        };

        chartAtividadeDiaria = new ApexCharts(el, options);
        chartAtividadeDiaria.render();
    }

    function atualizarGraficoVendedores(dados) {
        const el = document.getElementById('grafico-vendedores');
        if (!el) return;

        if (chartVendedores) {
            chartVendedores.destroy();
            chartVendedores = null;
        }

        el.innerHTML = '';

        if (!dados || !dados.nomes || dados.nomes.length === 0) {
            el.innerHTML = createEmptyChartState('Sem dados de vendedores');
            document.getElementById('vendedor-highlights').style.display = 'none';
            return;
        }

        // Update highlight strip
        atualizarHighlights(dados);

        const options = {
            series: [
                {
                    name: 'Convertidos',
                    type: 'bar',
                    data: dados.convertidos || []
                },
                {
                    name: 'Descartados',
                    type: 'bar',
                    data: dados.descartados || []
                },
                {
                    name: 'Taxa de Conversao',
                    type: 'line',
                    data: dados.taxa || []
                }
            ],
            chart: {
                height: 380,
                type: 'line',
                stacked: false,
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    borderRadius: 8,
                    borderRadiusApplication: 'end',
                    columnWidth: '50%'
                }
            },
            colors: [colors.success, colors.danger, colors.warning],
            fill: {
                opacity: [1, 1, 1]
            },
            stroke: {
                width: [0, 0, 3],
                curve: 'smooth'
            },
            markers: {
                size: [0, 0, 5],
                strokeColors: isDarkStyle ? '#1e202f' : '#fff',
                strokeWidth: 2,
                hover: { size: 7 }
            },
            dataLabels: {
                enabled: true,
                enabledOnSeries: [2],
                formatter: function (val) {
                    return val + '%';
                },
                style: {
                    fontSize: '11px',
                    fontWeight: 800,
                    fontFamily: 'JetBrains Mono, monospace'
                },
                background: {
                    enabled: true,
                    foreColor: colors.warning,
                    borderRadius: 6,
                    padding: 5,
                    opacity: 0.9,
                    borderWidth: 0,
                    borderColor: 'transparent',
                    dropShadow: { enabled: false }
                },
                offsetY: -8
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: { top: 10, bottom: 0 }
            },
            xaxis: {
                categories: dados.nomes,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '11px',
                        fontWeight: 600
                    },
                    rotate: dados.nomes.length > 5 ? -45 : 0,
                    rotateAlways: dados.nomes.length > 5
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    title: {
                        text: 'Quantidade',
                        style: {
                            color: labelColor,
                            fontWeight: 600,
                            fontSize: '12px'
                        }
                    },
                    labels: {
                        style: { colors: labelColor },
                        formatter: function (val) { return Math.round(val); }
                    },
                    min: 0
                },
                {
                    show: false
                },
                {
                    opposite: true,
                    title: {
                        text: 'Taxa %',
                        style: {
                            color: colors.warning,
                            fontWeight: 600,
                            fontSize: '12px'
                        }
                    },
                    labels: {
                        style: { colors: colors.warning },
                        formatter: function (val) { return Math.round(val) + '%'; }
                    },
                    min: 0,
                    max: 100
                }
            ],
            legend: {
                show: false
            },
            tooltip: {
                shared: true,
                intersect: false,
                theme: isDarkStyle ? 'dark' : 'light',
                custom: function ({ series, seriesIndex, dataPointIndex, w }) {
                    const nome = dados.nomes[dataPointIndex];
                    const conv = dados.convertidos[dataPointIndex];
                    const desc = dados.descartados[dataPointIndex];
                    const total = dados.total[dataPointIndex];
                    const taxa = dados.taxa[dataPointIndex];
                    const taxaColor = taxa >= 50 ? colors.success : (taxa >= 25 ? colors.warning : colors.danger);
                    const tooltipBg = isDarkStyle ? 'rgba(30, 32, 47, 0.98)' : 'rgba(255, 255, 255, 0.98)';
                    const tooltipBorder = isDarkStyle ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';

                    return `<div style="padding: 14px 18px; font-family: Plus Jakarta Sans, sans-serif; background: ${tooltipBg}; border: 1px solid ${tooltipBorder}; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.15);">
                        <div style="font-weight: 800; font-size: 13px; margin-bottom: 10px; color: ${headingColor}; letter-spacing: -0.01em;">${nome}</div>
                        <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${labelColor}">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: ${colors.primary}; display: inline-block;"></span>
                                    Total
                                </span>
                                <span style="font-weight: 700; font-family: JetBrains Mono, monospace; color: ${headingColor}">${total}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${colors.success}">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: ${colors.success}; display: inline-block;"></span>
                                    Convertidos
                                </span>
                                <span style="font-weight: 700; font-family: JetBrains Mono, monospace; color: ${colors.success}">${conv}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                                <span style="display: flex; align-items: center; gap: 6px; color: ${colors.danger}">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: ${colors.danger}; display: inline-block;"></span>
                                    Descartados
                                </span>
                                <span style="font-weight: 700; font-family: JetBrains Mono, monospace; color: ${colors.danger}">${desc}</span>
                            </div>
                            <div style="border-top: 1px solid ${tooltipBorder}; margin-top: 4px; padding-top: 8px; display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                                <span style="color: ${labelColor}; font-weight: 600;">Taxa de Conversao</span>
                                <span style="font-weight: 800; font-size: 14px; font-family: JetBrains Mono, monospace; color: ${taxaColor}">${taxa}%</span>
                            </div>
                        </div>
                    </div>`;
                }
            }
        };

        chartVendedores = new ApexCharts(el, options);
        chartVendedores.render();
    }

    function atualizarHighlights(dados) {
        const highlightsEl = document.getElementById('vendedor-highlights');
        if (!highlightsEl || !dados || !dados.nomes || dados.nomes.length === 0) return;

        highlightsEl.style.display = '';

        // Best conversion rate (with at least 1 contact)
        let bestIdx = 0;
        let bestTaxa = -1;
        for (let i = 0; i < dados.taxa.length; i++) {
            if (dados.total[i] > 0 && dados.taxa[i] > bestTaxa) {
                bestTaxa = dados.taxa[i];
                bestIdx = i;
            }
        }

        // Highest volume
        let volIdx = 0;
        let maxVol = 0;
        for (let i = 0; i < dados.total.length; i++) {
            if (dados.total[i] > maxVol) {
                maxVol = dados.total[i];
                volIdx = i;
            }
        }

        document.getElementById('best-seller-name').textContent = dados.nomes[bestIdx];
        document.getElementById('best-seller-taxa').textContent = dados.taxa[bestIdx] + '%';
        document.getElementById('volume-seller-name').textContent = dados.nomes[volIdx];
        document.getElementById('volume-seller-total').textContent = dados.total[volIdx] + ' contatos';
    }

    function atualizarGraficoTabulacao(dados) {
        const el = document.getElementById('grafico-tabulacao');
        if (!el) return;

        // Destroy existing chart first
        if (chartTabulacao) {
            chartTabulacao.destroy();
            chartTabulacao = null;
        }

        // Clear any empty state
        el.innerHTML = '';

        if (!dados || !dados.valores || dados.valores.length === 0) {
            el.innerHTML = createEmptyChartState('Sem dados de tabulacao');
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
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            plotOptions: {
                bar: {
                    distributed: true,
                    borderRadius: 8,
                    horizontal: false,
                    columnWidth: '60%',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            colors: [colors.primary, colors.success, colors.info, colors.warning, colors.danger, colors.primaryLight],
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val;
                },
                style: {
                    fontSize: '11px',
                    fontWeight: 600,
                    colors: ['#fff']
                },
                offsetY: -20
            },
            legend: {
                show: false
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4
            },
            xaxis: {
                categories: dados.labels,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '11px'
                    },
                    rotate: -45,
                    rotateAlways: dados.labels.length > 4
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                title: {
                    text: 'Quantidade',
                    style: { color: labelColor, fontWeight: 500 }
                },
                labels: {
                    style: { colors: labelColor }
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (val) {
                        return val + ' contatos';
                    }
                }
            }
        };

        chartTabulacao = new ApexCharts(el, options);
        chartTabulacao.render();
    }

    function createEmptyChartState(message) {
        return `
            <div class="empty-state" style="min-height: 260px;">
                <div class="empty-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </div>
                <h5 class="empty-title">${message}</h5>
                <p class="empty-description">Aplique os filtros para visualizar os dados.</p>
            </div>
        `;
    }

    // ============================================
    // Utility Functions
    // ============================================
    function converterDataParaBackend(dataBrasileira) {
        if (!dataBrasileira) return '';

        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';

        const dia = partes[0].padStart(2, '0');
        const mes = partes[1].padStart(2, '0');
        const ano = partes[2];

        return `${ano}-${mes}-${dia}`;
    }

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('active', show);
        }
    }

    // ============================================
    // Initial Load
    // ============================================
    buscarDados();
});
