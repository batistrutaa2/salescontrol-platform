/**
 * Relatório de Implantações - Dashboard
 * ApexCharts implementation with theme support
 */

'use strict';

(function () {
    // ============================================
    // Theme Configuration
    // ============================================
    let cardColor, labelColor, headingColor, borderColor, legendColor;

    const isDarkStyle = document.documentElement.classList.contains('dark-style');

    if (isDarkStyle) {
        cardColor = '#2D3748';
        labelColor = '#A0AEC0';
        headingColor = '#F7FAFC';
        borderColor = '#4A5568';
        legendColor = '#E2E8F0';
    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        headingColor = config.colors.headingColor;
        borderColor = config.colors.borderColor;
        legendColor = config.colors.bodyColor;
    }

    // Custom color palette - cores vibrantes para dark mode
    const colors = isDarkStyle ? {
        primary: '#A78BFA',
        success: '#34D399',
        successLight: '#6EE7B7',
        info: '#38BDF8',
        warning: '#FBBF24',
        danger: '#FB7185',
        slate: '#718096'
    } : {
        primary: '#7C3AED',
        success: '#10B981',
        successLight: '#34D399',
        info: '#06B6D4',
        warning: '#F59E0B',
        danger: '#EF4444',
        slate: '#64748B'
    };

    // ============================================
    // State Management
    // ============================================
    let chartInstances = {};
    let currentFilters = {};
    let flatpickrInstances = {};

    // ============================================
    // Initialization
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        init();
    });

    function init() {
        initFlatpickr();
        initEventListeners();
        carregarDadosIniciais();
    }

    // ============================================
    // Flatpickr Initialization
    // ============================================
    function initFlatpickr() {
        const flatpickrConfig = {
            dateFormat: 'd/m/Y',
            locale: 'pt',
            allowInput: true
        };

        const dataInicio = document.getElementById('dataInicio');
        const dataFim = document.getElementById('dataFim');

        if (dataInicio) {
            flatpickrInstances.dataInicio = flatpickr(dataInicio, flatpickrConfig);
        }
        if (dataFim) {
            flatpickrInstances.dataFim = flatpickr(dataFim, flatpickrConfig);
        }
    }

    // ============================================
    // Event Listeners
    // ============================================
    function initEventListeners() {
        // Form submit
        const form = document.getElementById('filtrosForm');
        if (form) {
            form.addEventListener('submit', handleFiltroSubmit);
        }

        // Clear filters
        const limparBtn = document.getElementById('limparFiltros');
        if (limparBtn) {
            limparBtn.addEventListener('click', limparFiltros);
        }

        // Refresh
        const atualizarBtn = document.getElementById('atualizarBtn');
        if (atualizarBtn) {
            atualizarBtn.addEventListener('click', function() {
                carregarDadosIniciais();
            });
        }

        // Toggle filters
        const toggleBtn = document.getElementById('toggleFilters');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleFilters);
        }
    }

    function toggleFilters() {
        const filterBody = document.getElementById('filterBody');
        const toggleBtn = document.getElementById('toggleFilters');

        if (filterBody) {
            const isHidden = filterBody.style.display === 'none';
            filterBody.style.display = isHidden ? 'block' : 'none';

            if (toggleBtn) {
                toggleBtn.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        }
    }

    // ============================================
    // Data Loading
    // ============================================
    function carregarDadosIniciais() {
        showLoading();

        fetch('/relatorios/implantacoes/dados', {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                popularFiltros(response.data);
                atualizarDashboard(response.data);
                carregarImplantacoes();
            } else {
                showToast('Erro ao carregar dados', 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao carregar dados:', error);
            showToast('Erro ao carregar dados', 'error');
        })
        .finally(() => {
            hideLoading();
        });
    }

    // ============================================
    // Filters
    // ============================================
    function popularFiltros(data) {
        // Anos
        const filtroAno = document.getElementById('filtroAno');
        if (filtroAno && data.anos_disponiveis) {
            filtroAno.innerHTML = '<option value="">Todos os anos</option>';
            data.anos_disponiveis.forEach(ano => {
                filtroAno.innerHTML += `<option value="${ano}">${ano}</option>`;
            });
        }

        // Vendedores
        const filtroVendedor = document.getElementById('filtroVendedor');
        if (filtroVendedor && data.vendedores) {
            filtroVendedor.innerHTML = '<option value="">Todos os vendedores</option>';
            data.vendedores.forEach(vendedor => {
                filtroVendedor.innerHTML += `<option value="${vendedor.id}">${vendedor.name}</option>`;
            });
        }

        // Operadoras
        const filtroOperadora = document.getElementById('filtroOperadora');
        if (filtroOperadora && data.operadoras) {
            filtroOperadora.innerHTML = '<option value="">Todas as operadoras</option>';
            data.operadoras.forEach(operadora => {
                filtroOperadora.innerHTML += `<option value="${operadora}">${operadora}</option>`;
            });
        }
    }

    function handleFiltroSubmit(e) {
        e.preventDefault();
        const formData = new FormData(e.target);

        // Convert dates from BR to backend format
        const dataInicio = formData.get('data_inicio');
        const dataFim = formData.get('data_fim');

        if (dataInicio) {
            formData.set('data_inicio', converterDataParaBackend(dataInicio));
        }
        if (dataFim) {
            formData.set('data_fim', converterDataParaBackend(dataFim));
        }

        currentFilters = new URLSearchParams(formData).toString();
        aplicarFiltros();
    }

    function converterDataParaBackend(dataBrasileira) {
        if (!dataBrasileira) return '';
        const partes = dataBrasileira.split('/');
        if (partes.length !== 3) return '';
        return `${partes[2]}-${partes[1].padStart(2, '0')}-${partes[0].padStart(2, '0')}`;
    }

    function aplicarFiltros() {
        showLoading();

        fetch(`/relatorios/implantacoes/dados?${currentFilters}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                atualizarDashboard(response.data);
                carregarImplantacoes();
            } else {
                showToast('Erro ao aplicar filtros', 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao aplicar filtros:', error);
            showToast('Erro ao aplicar filtros', 'error');
        })
        .finally(() => {
            hideLoading();
        });
    }

    function limparFiltros() {
        const form = document.getElementById('filtrosForm');
        if (form) {
            form.reset();
        }

        // Clear flatpickr
        Object.values(flatpickrInstances).forEach(instance => {
            if (instance) instance.clear();
        });

        currentFilters = {};
        carregarDadosIniciais();
    }

    // ============================================
    // Dashboard Update
    // ============================================
    function atualizarDashboard(data) {
        // Update KPIs
        if (data.resumo_geral) {
            updateElement('totalContratos', formatNumber(data.resumo_geral.total_contratos || 0));
            updateElement('valorTotal', formatCurrency(data.resumo_geral.valor_total || 0));
            updateElement('totalVidas', formatNumber(data.resumo_geral.total_vidas || 0));
            updateElement('ticketMedio', formatCurrency(data.resumo_geral.ticket_medio || 0));
        }

        // Update charts
        atualizarGraficos(data);
    }

    function updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    // ============================================
    // Charts - ApexCharts with Theme
    // ============================================
    function atualizarGraficos(data) {
        // Destroy existing charts
        Object.values(chartInstances).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        chartInstances = {};

        // Create new charts
        criarGraficoImplantacoesPorMes(data.vendas_por_mes || []);
        criarGraficoImplantacoesPorVendedor(data.vendas_por_vendedor || []);
        criarGraficoImplantacoesPorOperadora(data.vendas_por_operadora || []);
        criarGraficoImplantacoesPorPlano(data.vendas_por_plano || []);
    }

    function criarGraficoImplantacoesPorMes(dados) {
        const el = document.getElementById('implantacoesPorMesChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de implantacoes por mes');
            return;
        }

        const sortedData = [...dados].sort((a, b) => {
            if (a.ano !== b.ano) return a.ano - b.ano;
            return a.mes - b.mes;
        });

        const labels = sortedData.map(item => {
            const mesNome = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'][item.mes - 1];
            return `${mesNome}/${item.ano}`;
        });
        const valores = sortedData.map(item => parseFloat(item.valor_total) || 0);
        const quantidades = sortedData.map(item => parseInt(item.total_vendas) || 0);

        const options = {
            series: [
                {
                    name: 'Valor Total',
                    type: 'area',
                    data: valores
                },
                {
                    name: 'Quantidade',
                    type: 'line',
                    data: quantidades
                }
            ],
            chart: {
                height: 300,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.success, colors.info],
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '12px'
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    title: {
                        text: 'Valor (R$)',
                        style: { color: labelColor, fontWeight: 500 }
                    },
                    labels: {
                        style: { colors: labelColor },
                        formatter: val => formatCurrencyShort(val)
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Quantidade',
                        style: { color: labelColor, fontWeight: 500 }
                    },
                    labels: {
                        style: { colors: labelColor }
                    }
                }
            ],
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: legendColor }
            },
            tooltip: {
                shared: true,
                y: {
                    formatter: function (val, opts) {
                        if (opts.seriesIndex === 0) {
                            return formatCurrency(val);
                        }
                        return val + ' contratos';
                    }
                }
            },
            dataLabels: { enabled: false }
        };

        chartInstances.implantacoesPorMes = new ApexCharts(el, options);
        chartInstances.implantacoesPorMes.render();
    }

    function criarGraficoImplantacoesPorVendedor(dados) {
        const el = document.getElementById('implantacoesPorVendedorChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de implantacoes por vendedor');
            return;
        }

        const topVendedores = dados.slice(0, 8);
        const labels = topVendedores.map(item => item.vendedor || 'N/A');
        const valores = topVendedores.map(item => parseFloat(item.valor_total) || 0);

        const options = {
            series: [{
                name: 'Valor Total',
                data: valores
            }],
            chart: {
                height: 300,
                type: 'bar',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.success],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '60%',
                    distributed: false,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                offsetX: 5,
                formatter: val => formatCurrencyShort(val),
                style: {
                    colors: ['#fff'],
                    fontSize: '11px',
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: { colors: labelColor },
                    formatter: val => formatCurrencyShort(val)
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor, fontSize: '12px' }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } }
            },
            tooltip: {
                y: {
                    formatter: val => formatCurrency(val)
                }
            }
        };

        chartInstances.implantacoesPorVendedor = new ApexCharts(el, options);
        chartInstances.implantacoesPorVendedor.render();
    }

    function criarGraficoImplantacoesPorOperadora(dados) {
        const el = document.getElementById('implantacoesPorOperadoraChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de implantacoes por operadora');
            return;
        }

        const labels = dados.map(item => item.operadora || 'N/A');
        const valores = dados.map(item => parseFloat(item.valor_total) || 0);

        const chartColors = [
            colors.success,
            colors.primary,
            colors.info,
            colors.warning,
            colors.danger,
            colors.slate,
            '#8B5CF6',
            '#EC4899'
        ];

        const options = {
            series: valores,
            chart: {
                height: 300,
                type: 'donut',
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            colors: chartColors.slice(0, dados.length),
            labels: labels,
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            name: {
                                fontSize: '14px',
                                color: headingColor
                            },
                            value: {
                                fontSize: '20px',
                                fontFamily: 'JetBrains Mono, monospace',
                                fontWeight: 600,
                                color: headingColor,
                                formatter: val => formatCurrencyShort(val)
                            },
                            total: {
                                show: true,
                                label: 'Total',
                                fontSize: '12px',
                                color: labelColor,
                                formatter: w => {
                                    const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    return formatCurrencyShort(total);
                                }
                            }
                        }
                    }
                }
            },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                labels: { colors: legendColor },
                markers: {
                    width: 10,
                    height: 10,
                    radius: 10
                }
            },
            stroke: {
                width: 2,
                colors: [cardColor]
            },
            tooltip: {
                y: {
                    formatter: val => formatCurrency(val)
                }
            }
        };

        chartInstances.implantacoesPorOperadora = new ApexCharts(el, options);
        chartInstances.implantacoesPorOperadora.render();
    }

    function criarGraficoImplantacoesPorPlano(dados) {
        const el = document.getElementById('implantacoesPorPlanoChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de planos implantados');
            return;
        }

        const topPlanos = dados.slice(0, 10);
        const labels = topPlanos.map(item => truncateText(item.nome_plano || 'N/A', 25));
        const quantidades = topPlanos.map(item => parseInt(item.total_vendas) || 0);

        const options = {
            series: [{
                name: 'Quantidade',
                data: quantidades
            }],
            chart: {
                height: 300,
                type: 'bar',
                toolbar: { show: false },
                fontFamily: 'Plus Jakarta Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.info],
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 6,
                    barHeight: '60%',
                    distributed: false
                }
            },
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                offsetX: 5,
                style: {
                    colors: ['#fff'],
                    fontSize: '11px',
                    fontWeight: 600
                }
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: { colors: labelColor }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: labelColor, fontSize: '11px' }
                }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                xaxis: { lines: { show: true } },
                yaxis: { lines: { show: false } }
            },
            tooltip: {
                y: {
                    formatter: val => val + ' contratos'
                }
            }
        };

        chartInstances.implantacoesPorPlano = new ApexCharts(el, options);
        chartInstances.implantacoesPorPlano.render();
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
                <p class="empty-description">Ajuste os filtros para visualizar os dados.</p>
            </div>
        `;
    }

    // ============================================
    // Table - DataTables
    // ============================================
    function carregarImplantacoes() {
        const params = currentFilters ? `${currentFilters}&per_page=1000` : 'per_page=1000';

        fetch(`/relatorios/implantacoes/listar?${params}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                renderizarTabelaImplantacoes(response.data);
                updateElement('tableCount', response.data.length);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar implantacoes:', error);
            showToast('Erro ao carregar lista de implantacoes', 'error');
        });
    }

    function renderizarTabelaImplantacoes(implantacoes) {
        const table = document.getElementById('implantacoesTable');
        if (!table) return;

        // Check if DataTable already initialized
        if ($.fn.DataTable.isDataTable(table)) {
            $(table).DataTable().clear().rows.add(implantacoes).draw();
            return;
        }

        // Initialize DataTable
        $(table).DataTable({
            data: implantacoes,
            columns: [
                {
                    data: 'data_implantacao',
                    render: data => formatDate(data)
                },
                {
                    data: 'nome_contrato',
                    defaultContent: '-'
                },
                {
                    data: 'cpf_cnpj',
                    defaultContent: '-'
                },
                {
                    data: 'user.name',
                    defaultContent: '-'
                },
                {
                    data: 'operadora',
                    defaultContent: '-'
                },
                {
                    data: 'nome_plano',
                    render: data => truncateText(data || '-', 30)
                },
                {
                    data: 'valor_contrato',
                    render: data => `<span class="contract-value">${formatCurrency(data)}</span>`
                },
                {
                    data: 'vidas',
                    render: data => `<span class="vidas-badge">${data || 0}</span>`
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>t<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
        });
    }

    // ============================================
    // Utilities
    // ============================================
    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    function formatCurrencyShort(value) {
        if (value >= 1000000) {
            return 'R$ ' + (value / 1000000).toFixed(1) + 'M';
        }
        if (value >= 1000) {
            return 'R$ ' + (value / 1000).toFixed(1) + 'K';
        }
        return formatCurrency(value);
    }

    function formatNumber(value) {
        return new Intl.NumberFormat('pt-BR').format(value || 0);
    }

    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    function truncateText(text, maxLength) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    function showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.add('active');
        }
    }

    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.remove('active');
        }
    }

    function showToast(message, type = 'info') {
        console.log(`${type.toUpperCase()}: ${message}`);
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info',
                title: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
})();
