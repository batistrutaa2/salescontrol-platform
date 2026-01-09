/**
 * Vendas Analítico - Sales Intelligence Dashboard
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

    // Custom color palette - cores mais vibrantes para dark mode
    const colors = isDarkStyle ? {
        indigo: '#818CF8',
        indigoLight: '#A5B4FC',
        emerald: '#34D399',
        amber: '#FBBF24',
        sky: '#38BDF8',
        rose: '#FB7185',
        slate: '#718096'
    } : {
        indigo: '#4F46E5',
        indigoLight: '#6366F1',
        emerald: '#10B981',
        amber: '#F59E0B',
        sky: '#0EA5E9',
        rose: '#F43F5E',
        slate: '#64748B'
    };

    // ============================================
    // State Management
    // ============================================
    let chartInstances = {};
    let currentPage = 1;
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

        // Export
        const exportarBtn = document.getElementById('exportarBtn');
        if (exportarBtn) {
            exportarBtn.addEventListener('click', exportarRelatorio);
        }

        // Status filter
        const statusTabela = document.getElementById('filtroStatusTabela');
        if (statusTabela) {
            statusTabela.addEventListener('change', handleFiltroStatusTabela);
        }

        // Toggle filters
        const toggleBtn = document.getElementById('toggleFilters');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', toggleFilters);
        }

        // Pagination (delegated)
        const paginacao = document.getElementById('paginacao');
        if (paginacao) {
            paginacao.addEventListener('click', handlePaginationClick);
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

        fetch('/vendas/dados', {
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
                carregarVendas();
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

        // Status
        const filtroStatusTabela = document.getElementById('filtroStatusTabela');
        if (filtroStatusTabela && data.status_disponiveis) {
            filtroStatusTabela.innerHTML = '<option value="">Todos os status</option>';
            data.status_disponiveis.forEach(status => {
                filtroStatusTabela.innerHTML += `<option value="${status.id}">${status.descricao}</option>`;
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
        currentPage = 1;
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

        fetch(`/vendas/dados?${currentFilters}`, {
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
                carregarVendas();
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
        currentPage = 1;
        carregarDadosIniciais();
    }

    function handleFiltroStatusTabela() {
        currentPage = 1;
        carregarVendas();
    }

    // ============================================
    // Dashboard Update
    // ============================================
    function atualizarDashboard(data) {
        // Update KPIs
        if (data.resumo_geral) {
            updateElement('totalContratos', formatNumber(data.resumo_geral.total_contratos || 0));
            updateElement('valorTotal', formatCurrency(data.resumo_geral.valor_total || 0));
            updateElement('valorImplantado', formatCurrency(data.resumo_geral.valor_implantado || 0));
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
        criarGraficoVendasPorMes(data.vendas_por_mes || []);
        criarGraficoVendasPorVendedor(data.vendas_por_vendedor || []);
        criarGraficoVendasPorOperadora(data.vendas_por_operadora || []);
        criarGraficoVendasPorPlano(data.vendas_por_plano || []);
        criarGraficoVendasPorEntidade(data.vendas_por_entidade || []);
    }

    function criarGraficoVendasPorMes(dados) {
        const el = document.getElementById('vendasPorMesChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de vendas por mês');
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
                fontFamily: 'DM Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.indigo, colors.emerald],
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
                        return val + ' vendas';
                    }
                }
            },
            dataLabels: { enabled: false }
        };

        chartInstances.vendasPorMes = new ApexCharts(el, options);
        chartInstances.vendasPorMes.render();
    }

    function criarGraficoVendasPorVendedor(dados) {
        const el = document.getElementById('vendasPorVendedorChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de vendas por vendedor');
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
                fontFamily: 'DM Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.indigo],
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

        chartInstances.vendasPorVendedor = new ApexCharts(el, options);
        chartInstances.vendasPorVendedor.render();
    }

    function criarGraficoVendasPorOperadora(dados) {
        const el = document.getElementById('vendasPorOperadoraChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de vendas por operadora');
            return;
        }

        const labels = dados.map(item => item.operadora || 'N/A');
        const valores = dados.map(item => parseFloat(item.valor_total) || 0);

        const chartColors = [
            colors.indigo,
            colors.emerald,
            colors.amber,
            colors.sky,
            colors.rose,
            colors.slate,
            '#8B5CF6',
            '#EC4899'
        ];

        const options = {
            series: valores,
            chart: {
                height: 300,
                type: 'donut',
                fontFamily: 'DM Sans, sans-serif',
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
                                fontFamily: 'IBM Plex Mono, monospace',
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

        chartInstances.vendasPorOperadora = new ApexCharts(el, options);
        chartInstances.vendasPorOperadora.render();
    }

    function criarGraficoVendasPorPlano(dados) {
        const el = document.getElementById('vendasPorPlanoChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de planos vendidos');
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
                fontFamily: 'DM Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.emerald],
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
                    formatter: val => val + ' vendas'
                }
            }
        };

        chartInstances.vendasPorPlano = new ApexCharts(el, options);
        chartInstances.vendasPorPlano.render();
    }

    function criarGraficoVendasPorEntidade(dados) {
        const el = document.getElementById('vendasPorEntidadeChart');
        if (!el || dados.length === 0) {
            if (el) el.innerHTML = createEmptyChartState('Sem dados de vendas por entidade de classe');
            return;
        }

        const labels = dados.map(item => item.entidade || 'NÃO INFORMADO');
        const quantidades = dados.map(item => parseInt(item.total_vendas) || 0);
        const valores = dados.map(item => parseFloat(item.valor_total) || 0);

        const options = {
            series: [
                {
                    name: 'Qtd. Vendas',
                    type: 'column',
                    data: quantidades
                },
                {
                    name: 'Valor Total',
                    type: 'line',
                    data: valores
                }
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: false },
                fontFamily: 'DM Sans, sans-serif',
                background: 'transparent'
            },
            colors: [colors.indigo, colors.emerald],
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '50%'
                }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            markers: {
                size: [0, 5],
                colors: [colors.indigo, colors.emerald],
                strokeColors: '#fff',
                strokeWidth: 2
            },
            xaxis: {
                categories: labels,
                labels: {
                    style: {
                        colors: labelColor,
                        fontSize: '11px'
                    },
                    rotate: -45,
                    rotateAlways: labels.length > 5
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: [
                {
                    title: {
                        text: 'Quantidade',
                        style: { color: labelColor, fontWeight: 500 }
                    },
                    labels: {
                        style: { colors: labelColor }
                    }
                },
                {
                    opposite: true,
                    title: {
                        text: 'Valor (R$)',
                        style: { color: labelColor, fontWeight: 500 }
                    },
                    labels: {
                        style: { colors: labelColor },
                        formatter: val => formatCurrencyShort(val)
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
            dataLabels: {
                enabled: true,
                enabledOnSeries: [0],
                style: {
                    colors: ['#fff'],
                    fontSize: '11px',
                    fontWeight: 600
                }
            },
            tooltip: {
                shared: true,
                y: {
                    formatter: function (val, opts) {
                        if (opts.seriesIndex === 0) {
                            return val + ' vendas';
                        }
                        return formatCurrency(val);
                    }
                }
            }
        };

        chartInstances.vendasPorEntidade = new ApexCharts(el, options);
        chartInstances.vendasPorEntidade.render();
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
    // Table
    // ============================================
    function carregarVendas() {
        const statusTabela = document.getElementById('filtroStatusTabela')?.value || '';
        let params = currentFilters ? `${currentFilters}&page=${currentPage}` : `page=${currentPage}`;

        if (statusTabela) {
            params += `&status=${statusTabela}`;
        }

        fetch(`/vendas/listar?${params}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                renderizarTabelaVendas(response.data);
                renderizarPaginacao(response.pagination);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar vendas:', error);
            showToast('Erro ao carregar lista de vendas', 'error');
        });
    }

    function renderizarTabelaVendas(vendas) {
        const tbody = document.getElementById('vendasTableBody');
        if (!tbody) return;

        if (!vendas || vendas.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <line x1="9" y1="15" x2="15" y2="15"/>
                                </svg>
                            </div>
                            <h5 class="empty-title">Nenhuma venda encontrada</h5>
                            <p class="empty-description">Tente ajustar os filtros de busca.</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = vendas.map(venda => {
            const status = venda.contato_corretor?.tabulacao?.descricao || 'N/A';
            const statusClass = getStatusClass(status);

            return `
                <tr>
                    <td>${formatDate(venda.data_vigencia)}</td>
                    <td>${venda.nome_contrato || '-'}</td>
                    <td>${venda.cpf_cnpj || '-'}</td>
                    <td>${venda.user?.name || '-'}</td>
                    <td>${venda.operadora || '-'}</td>
                    <td>${truncateText(venda.nome_plano || '-', 30)}</td>
                    <td class="contract-value">${formatCurrency(venda.valor_contrato)}</td>
                    <td><span class="status-badge ${statusClass}">${status}</span></td>
                </tr>
            `;
        }).join('');
    }

    function getStatusClass(status) {
        const statusLower = status.toLowerCase();
        if (statusLower.includes('implantado')) return 'status-implantado';
        if (statusLower.includes('pendencia') || statusLower.includes('pendência')) return 'status-pendencia';
        if (statusLower.includes('venda')) return 'status-venda';
        if (statusLower.includes('analise') || statusLower.includes('análise')) return 'status-analise';
        return 'status-default';
    }

    function renderizarPaginacao(pagination) {
        const paginacaoEl = document.getElementById('paginacao');
        if (!paginacaoEl) return;

        if (!pagination || pagination.last_page <= 1) {
            paginacaoEl.innerHTML = '';
            return;
        }

        let html = '';

        // Previous
        if (pagination.current_page > 1) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page - 1}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </a>
                </li>
            `;
        }

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

        for (let i = startPage; i <= endPage; i++) {
            const active = i === pagination.current_page ? 'active' : '';
            html += `
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }

        // Next
        if (pagination.current_page < pagination.last_page) {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.current_page + 1}">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                </li>
            `;
        }

        paginacaoEl.innerHTML = html;
    }

    function handlePaginationClick(e) {
        e.preventDefault();
        const target = e.target.closest('[data-page]');
        if (target) {
            const page = parseInt(target.dataset.page);
            if (page && page !== currentPage) {
                currentPage = page;
                carregarVendas();
            }
        }
    }

    // ============================================
    // Export
    // ============================================
    function exportarRelatorio() {
        const params = currentFilters || '';
        window.open(`/vendas/exportar?${params}`, '_blank');
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
