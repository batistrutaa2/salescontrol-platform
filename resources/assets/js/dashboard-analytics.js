/**
 * Dashboard Analytics - SalesControl
 * Modern ApexCharts implementation with dark/light theme support
 */
'use strict';

(function () {
    // ============================================
    // Theme Configuration
    // ============================================
    let cardColor, headingColor, labelColor, borderColor, legendColor;

    const colors = {
        primary: '#7C3AED',
        primaryLight: '#A78BFA',
        success: '#10B981',
        successLight: '#34D399',
        info: '#06B6D4',
        infoLight: '#22D3EE',
        warning: '#F59E0B',
        warningLight: '#FBBF24',
        danger: '#EF4444',
        gradient: {
            primary: ['#7C3AED', '#A78BFA'],
            success: ['#10B981', '#34D399'],
            info: ['#06B6D4', '#22D3EE'],
            warning: ['#F59E0B', '#FBBF24']
        }
    };

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

    // ============================================
    // DataTables Initialization
    // ============================================
    const tableContratosCadastrados = $('#tableContratosCadastrados').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        dom: '<"table-top"f>rt<"table-bottom"lip>',
        ordering: true,
        order: [[1, 'desc']]
    });

    const tableContratosImplantados = $('#tableContratosImplantados').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
        },
        pageLength: 5,
        lengthMenu: [5, 10, 25],
        dom: '<"table-top"f>rt<"table-bottom"lip>',
        ordering: true,
        order: [[1, 'desc']]
    });

    // ============================================
    // Chart Instances
    // ============================================
    let salesPerformanceChart = null;
    let conversionFunnelChart = null;
    let leadsImportChart = null;
    let leadsTransferChart = null;

    // ============================================
    // Chart Configurations
    // ============================================
    const commonChartConfig = {
        chart: {
            fontFamily: 'inherit',
            toolbar: { show: false },
            zoom: { enabled: false },
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
            }
        },
        grid: {
            borderColor: borderColor,
            strokeDashArray: 4,
            padding: { top: 0, right: 0, bottom: 0, left: 10 }
        },
        states: {
            hover: { filter: { type: 'lighten', value: 0.1 } },
            active: { filter: { type: 'darken', value: 0.1 } }
        }
    };

    // Sales Performance Chart Config
    function getSalesPerformanceConfig(categories = [], dataCadastradas = [], dataImplantadas = []) {
        return {
            ...commonChartConfig,
            chart: {
                ...commonChartConfig.chart,
                type: 'bar',
                height: 350,
                stacked: false
            },
            series: [
                {
                    name: 'Cadastradas',
                    data: dataCadastradas
                },
                {
                    name: 'Implantadas',
                    data: dataImplantadas
                }
            ],
            colors: [colors.primary, colors.success],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 8,
                    borderRadiusApplication: 'end'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    },
                    rotate: -45,
                    rotateAlways: categories.length > 5
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    },
                    formatter: function (value) {
                        return formatCurrency(value, true);
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'vertical',
                    shadeIntensity: 0.3,
                    gradientToColors: [colors.primaryLight, colors.successLight],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.85,
                    stops: [0, 100]
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (value) {
                        return formatCurrency(value);
                    }
                }
            },
            legend: {
                show: false
            }
        };
    }

    // Conversion Funnel Chart Config
    function getConversionFunnelConfig(totalContatos = 0, totalCadastrados = 0, totalImplantados = 0) {
        return {
            ...commonChartConfig,
            chart: {
                ...commonChartConfig.chart,
                type: 'radialBar',
                height: 350,
                sparkline: { enabled: false }
            },
            series: [
                Math.round((totalImplantados / Math.max(totalCadastrados, 1)) * 100),
                Math.round((totalCadastrados / Math.max(totalContatos, 1)) * 100),
                100
            ],
            colors: [colors.success, colors.primary, colors.info],
            plotOptions: {
                radialBar: {
                    startAngle: -135,
                    endAngle: 135,
                    hollow: {
                        margin: 0,
                        size: '35%',
                        background: 'transparent'
                    },
                    track: {
                        background: borderColor,
                        strokeWidth: '100%',
                        margin: 8,
                        dropShadow: {
                            enabled: true,
                            top: 0,
                            left: 0,
                            blur: 3,
                            opacity: 0.1
                        }
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 500,
                            color: labelColor,
                            offsetY: -10
                        },
                        value: {
                            show: true,
                            fontSize: '22px',
                            fontWeight: 700,
                            color: headingColor,
                            offsetY: 5,
                            formatter: function (val) {
                                return val + '%';
                            }
                        },
                        total: {
                            show: true,
                            label: 'Taxa Geral',
                            fontSize: '12px',
                            fontWeight: 400,
                            color: labelColor,
                            formatter: function (w) {
                                const sum = w.globals.spikeData ?
                                    w.globals.spikeData.reduce((a, b) => a + b, 0) : 0;
                                return Math.round(sum / 3) + '%';
                            }
                        }
                    }
                }
            },
            stroke: {
                lineCap: 'round'
            },
            labels: ['Implantacao', 'Conversao', 'Leads'],
            legend: {
                show: true,
                position: 'bottom',
                labels: {
                    colors: labelColor
                },
                markers: {
                    width: 12,
                    height: 12,
                    radius: 12
                }
            }
        };
    }

    // Leads Import Chart Config
    function getLeadsImportConfig(categories = [], data = []) {
        return {
            ...commonChartConfig,
            chart: {
                ...commonChartConfig.chart,
                type: 'bar',
                height: 300
            },
            series: [{
                name: 'Leads Importados',
                data: data
            }],
            colors: [colors.info],
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '60%',
                    borderRadius: 6,
                    distributed: false,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                style: {
                    colors: ['#fff'],
                    fontSize: '12px',
                    fontWeight: 600
                },
                formatter: function (val) {
                    return val;
                },
                offsetX: 5
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.25,
                    gradientToColors: [colors.infoLight],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.85,
                    stops: [0, 100]
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (value) {
                        return value + ' leads';
                    }
                }
            }
        };
    }

    // Leads Transfer Chart Config
    function getLeadsTransferConfig(categories = [], data = []) {
        return {
            ...commonChartConfig,
            chart: {
                ...commonChartConfig.chart,
                type: 'bar',
                height: 300
            },
            series: [{
                name: 'Transferencias',
                data: data
            }],
            colors: [colors.warning],
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '60%',
                    borderRadius: 6,
                    distributed: false,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                style: {
                    colors: ['#fff'],
                    fontSize: '12px',
                    fontWeight: 600
                },
                formatter: function (val) {
                    return val;
                },
                offsetX: 5
            },
            xaxis: {
                categories: categories,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'dark',
                    type: 'horizontal',
                    shadeIntensity: 0.25,
                    gradientToColors: [colors.warningLight],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 0.85,
                    stops: [0, 100]
                }
            },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                y: {
                    formatter: function (value) {
                        return value + ' transferencias';
                    }
                }
            }
        };
    }

    // ============================================
    // Initialize Charts
    // ============================================
    function initializeCharts() {
        // Sales Performance Chart
        const salesEl = document.querySelector('#salesPerformanceChart');
        if (salesEl) {
            salesPerformanceChart = new ApexCharts(salesEl, getSalesPerformanceConfig());
            salesPerformanceChart.render();
        }

        // Conversion Funnel Chart
        const funnelEl = document.querySelector('#conversionFunnelChart');
        if (funnelEl) {
            conversionFunnelChart = new ApexCharts(funnelEl, getConversionFunnelConfig());
            conversionFunnelChart.render();
        }

        // Leads Import Chart
        const importEl = document.querySelector('#leadsImportChart');
        if (importEl) {
            leadsImportChart = new ApexCharts(importEl, getLeadsImportConfig());
            leadsImportChart.render();
        }

        // Leads Transfer Chart
        const transferEl = document.querySelector('#leadsTransferChart');
        if (transferEl) {
            leadsTransferChart = new ApexCharts(transferEl, getLeadsTransferConfig());
            leadsTransferChart.render();
        }
    }

    // ============================================
    // Utility Functions
    // ============================================
    function formatCurrency(value, short = false) {
        if (short && value >= 1000) {
            if (value >= 1000000) {
                return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
            }
            return 'R$ ' + (value / 1000).toFixed(1) + 'K';
        }
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value);
    }

    function animateValue(element, start, end, duration = 1000) {
        const startTimestamp = performance.now();
        const step = (timestamp) => {
            const progress = Math.min((timestamp - startTimestamp) / duration, 1);
            const easeOutQuart = 1 - Math.pow(1 - progress, 4);
            const current = Math.floor(start + (end - start) * easeOutQuart);

            if (element.classList.contains('js-conversao')) {
                element.textContent = current;
            } else if (element.classList.contains('js-quantidadeContatosImportados')) {
                element.textContent = current.toLocaleString('pt-BR');
            } else {
                element.textContent = formatCurrency(current);
            }

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        };
        requestAnimationFrame(step);
    }

    // ============================================
    // Fetch & Update Data
    // ============================================
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
            updateDashboard(data);
        } catch (error) {
            console.error('Error fetching metrics:', error);
            toastr.error('Erro ao carregar metricas do dashboard');
        }
    }

    function updateDashboard(data) {
        // Extract data
        const vendasCadastradas = data.vendasCadastradasPorVendedor || [];
        const vendasImplantadas = data.vendasImplantadasPorVendedor || [];
        const contatosImportados = data.contatosImportadosMesPorVendedor || [];
        const contatosTransferidos = data.quantidadeContatosTransferidos || [];

        // Calculate totals
        const totalCadastradas = vendasCadastradas.reduce((acc, item) => acc + parseFloat(item.total_vendas || 0), 0);
        const totalImplantadas = vendasImplantadas.reduce((acc, item) => acc + parseFloat(item.total_vendas || 0), 0);
        const totalContatosImportados = data.quantidadeContatosImportados || 0;
        const conversaoMensal = parseFloat(data.conversaoMensal) || 0;

        // Calculate angariação totals
        const totalAngariacaoCadastradas = vendasCadastradas.reduce((acc, item) => acc + parseFloat(item.total_angariacao || 0), 0);
        const totalAngariacaoImplantadas = vendasImplantadas.reduce((acc, item) => acc + parseFloat(item.total_angariacao || 0), 0);

        // Animate KPI values
        const valorCadastradoEl = document.querySelector('.js-valorCadastrado');
        const angariacaoEl = document.querySelector('.js-angariacao');
        const implantadoEl = document.querySelector('.js-implantado');
        const contatosEl = document.querySelector('.js-quantidadeContatosImportados');

        if (valorCadastradoEl) animateValue(valorCadastradoEl, 0, totalCadastradas);
        if (angariacaoEl) animateValue(angariacaoEl, 0, totalAngariacaoCadastradas);
        if (implantadoEl) animateValue(implantadoEl, 0, totalImplantadas);
        if (contatosEl) animateValue(contatosEl, 0, totalContatosImportados);

        // Update trends
        const trendCadastradoEl = document.querySelector('.js-trend-cadastrado');
        const trendAngariacaoEl = document.querySelector('.js-trend-angariacao');
        const trendImplantadoEl = document.querySelector('.js-trend-implantado');

        if (trendCadastradoEl) {
            const qtdCadastradas = vendasCadastradas.reduce((acc, item) => acc + 1, 0);
            trendCadastradoEl.textContent = qtdCadastradas + ' vendedor(es)';
        }

        if (trendAngariacaoEl) {
            if (totalAngariacaoImplantadas > 0) {
                trendAngariacaoEl.textContent = formatCurrency(totalAngariacaoImplantadas) + ' implantada';
            } else {
                trendAngariacaoEl.textContent = 'Total cadastrado';
            }
        }

        if (trendImplantadoEl) {
            if (totalAngariacaoImplantadas > 0) {
                trendImplantadoEl.textContent = formatCurrency(totalAngariacaoImplantadas) + ' angariacao';
            } else {
                trendImplantadoEl.textContent = 'Sem angariacao';
            }
        }

        // Update Sales Performance Chart
        if (salesPerformanceChart) {
            const categories = vendasCadastradas.map(item => item.name || 'N/A');
            const dataCadastradas = vendasCadastradas.map(item => parseFloat(item.total_vendas || 0));

            // Map implanted sales to same sellers
            const dataImplantadas = categories.map(name => {
                const found = vendasImplantadas.find(item => item.name === name);
                return found ? parseFloat(found.total_vendas || 0) : 0;
            });

            salesPerformanceChart.updateOptions({
                xaxis: { categories: categories }
            }, false, false);

            salesPerformanceChart.updateSeries([
                { name: 'Cadastradas', data: dataCadastradas },
                { name: 'Implantadas', data: dataImplantadas }
            ]);
        }

        // Update Conversion Funnel Chart
        if (conversionFunnelChart) {
            const implantacaoRate = totalCadastradas > 0
                ? Math.round((totalImplantadas / totalCadastradas) * 100)
                : 0;
            const conversaoRate = Math.round(conversaoMensal);

            conversionFunnelChart.updateSeries([implantacaoRate, conversaoRate, 100]);
        }

        // Update Leads Import Chart
        if (leadsImportChart) {
            const categories = contatosImportados.map(item => item.name || 'N/A');
            const dataLeads = contatosImportados.map(item => parseInt(item.quantidade || 0));

            leadsImportChart.updateOptions({
                xaxis: { categories: categories }
            }, false, false);

            leadsImportChart.updateSeries([{ name: 'Leads Importados', data: dataLeads }]);
        }

        // Update Leads Transfer Chart
        if (leadsTransferChart) {
            const categories = contatosTransferidos.map(item => item.name || 'N/A');
            const dataTransfer = contatosTransferidos.map(item => parseInt(item.quantidade || 0));

            leadsTransferChart.updateOptions({
                xaxis: { categories: categories }
            }, false, false);

            leadsTransferChart.updateSeries([{ name: 'Transferencias', data: dataTransfer }]);
        }

        // Update Tables
        updateTables(data.contratosCadastrados || [], data.contratosImplantados || []);
    }

    function formatAngariacao(item) {
        if (item.angariacao_status === 'SIM' && parseFloat(item.angariacao_valor || 0) > 0) {
            const valor = formatCurrency(parseFloat(item.angariacao_valor));
            return `<span class="contract-value angariacao">${valor}</span>`;
        }
        return '<span class="contract-value muted">--</span>';
    }

    function updateTables(dataCadastrados, dataImplantados) {
        // Update counts
        const countCadastradosEl = document.querySelector('.js-count-cadastrados');
        const countImplantadosEl = document.querySelector('.js-count-implantados');

        if (countCadastradosEl) countCadastradosEl.textContent = dataCadastrados.length;
        if (countImplantadosEl) countImplantadosEl.textContent = dataImplantados.length;

        // Clear and repopulate Cadastrados table
        tableContratosCadastrados.clear();
        dataCadastrados.forEach(item => {
            const valorFormatado = formatCurrency(parseFloat(item.valor_contrato || 0));
            tableContratosCadastrados.row.add([
                `<span class="contract-name">${item.nome_contrato || 'N/A'}</span>`,
                `<span class="contract-value">${valorFormatado}</span>`,
                formatAngariacao(item)
            ]);
        });
        tableContratosCadastrados.draw();

        // Clear and repopulate Implantados table
        tableContratosImplantados.clear();
        dataImplantados.forEach(item => {
            const valorFormatado = formatCurrency(parseFloat(item.valor_contrato || 0));
            tableContratosImplantados.row.add([
                `<span class="contract-name">${item.nome_contrato || 'N/A'}</span>`,
                `<span class="contract-value success">${valorFormatado}</span>`,
                formatAngariacao(item)
            ]);
        });
        tableContratosImplantados.draw();
    }

    // ============================================
    // Event Listeners
    // ============================================
    function setupEventListeners() {
        const monthSelect = document.getElementById('select-month');
        const yearSelect = document.getElementById('select-year');

        if (monthSelect) {
            monthSelect.addEventListener('change', updateMetrics);
        }

        if (yearSelect) {
            yearSelect.addEventListener('change', updateMetrics);
        }
    }

    async function updateMetrics() {
        const month = document.getElementById('select-month').value;
        const year = document.getElementById('select-year').value;

        if (month && year) {
            await fetchSalesMetrics(month, year);
        }
    }

    // ============================================
    // Initialize Dashboard
    // ============================================
    function initializeDashboard() {
        // Set default values
        const monthSelect = document.getElementById('select-month');
        const yearSelect = document.getElementById('select-year');
        const currentMonth = new Date().getMonth() + 1;
        const currentYear = new Date().getFullYear();

        if (monthSelect) monthSelect.value = currentMonth;
        if (yearSelect) yearSelect.value = currentYear;

        // Initialize charts
        initializeCharts();

        // Setup event listeners
        setupEventListeners();

        // Initial data fetch
        fetchSalesMetrics(currentMonth, currentYear);

        // Add entrance animations
        const cards = document.querySelectorAll('[data-aos]');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeDashboard);
    } else {
        initializeDashboard();
    }
})();
