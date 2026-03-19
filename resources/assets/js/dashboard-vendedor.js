/**
 * Dashboard Vendedor JS
 * ApexCharts + Glass Morphism Design System
 */
'use strict';

(function () {
    // Theme configuration
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

    // Design system colors
    const dsColors = {
        primary: '#7C3AED',
        primaryLight: '#A78BFA',
        success: '#10B981',
        successLight: '#34D399',
        info: '#06B6D4',
        infoLight: '#22D3EE',
        warning: '#F59E0B',
        warningLight: '#FBBF24'
    };

    // Format currency helper
    function formatCurrency(value) {
        return parseFloat(value || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    // Chart instances
    let monthlyChart = null;

    // ========================================
    // Fetch Metrics
    // ========================================
    async function fetchMetrics(month, year) {
        // Show loading
        document.querySelectorAll('.dv-kpi-value').forEach(el => {
            el.style.opacity = '0.4';
        });

        try {
            const response = await fetch(`/dashboard-vendedor/metrics?month=${month}&year=${year}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');

            const data = await response.json();

            updateKPIs(data);
            updateSecondaryCards(data);
            updateCharts(data);
            updateRecentSales(data);
        } catch (error) {
            console.error('Erro ao buscar métricas:', error);
        } finally {
            document.querySelectorAll('.dv-kpi-value').forEach(el => {
                el.style.opacity = '1';
            });
        }
    }

    // ========================================
    // Update KPI Cards
    // ========================================
    function updateKPIs(data) {
        document.getElementById('dv-sales-registered').textContent = formatCurrency(data.sales_registered);
        document.getElementById('dv-sales-implanted').textContent = formatCurrency(data.sales_implanted);
        document.getElementById('dv-ticket-medio').textContent = formatCurrency(data.ticket_medio);
    }

    // ========================================
    // Update Secondary Cards
    // ========================================
    function updateSecondaryCards(data) {
        // Ranking mensal
        const rankingMes = data.ranking_mensal;
        if (rankingMes) {
            const mesNomes = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            if (rankingMes.posicao) {
                document.getElementById('dv-ranking-mes-pos').textContent = rankingMes.posicao;
                document.getElementById('dv-ranking-mes-suffix').textContent = 'º';
                document.getElementById('dv-ranking-mes-detail').textContent = `de ${rankingMes.total_vendedores} vendedores`;
            } else {
                document.getElementById('dv-ranking-mes-pos').textContent = '—';
                document.getElementById('dv-ranking-mes-suffix').textContent = '';
                document.getElementById('dv-ranking-mes-detail').textContent = 'Sem vendas no mês';
            }
            document.getElementById('dv-ranking-mes-label').textContent = `Sua posição em ${mesNomes[rankingMes.mes] || ''}`;
        }

        // Ranking trimestral
        const rankingTri = data.ranking_trimestral;
        if (rankingTri) {
            if (rankingTri.posicao) {
                document.getElementById('dv-ranking-tri-pos').textContent = rankingTri.posicao;
                document.getElementById('dv-ranking-tri-suffix').textContent = 'º';
                document.getElementById('dv-ranking-tri-detail').textContent = `de ${rankingTri.total_vendedores} vendedores`;
            } else {
                document.getElementById('dv-ranking-tri-pos').textContent = '—';
                document.getElementById('dv-ranking-tri-suffix').textContent = '';
                document.getElementById('dv-ranking-tri-detail').textContent = 'Sem vendas no trimestre';
            }
            document.getElementById('dv-ranking-tri-label').textContent = `Sua posição no ${rankingTri.trimestre}º trimestre`;
        }

        // Operadora mais vendida
        const operadora = data.operadora_mais_vendida;
        if (operadora) {
            document.getElementById('dv-operadora-nome').textContent = operadora.operadora;
            document.getElementById('dv-operadora-detail').textContent = `${operadora.total} vendas realizadas`;
            // Progress bar - max 100%
            const progressPercent = Math.min(operadora.total * 2, 100);
            document.getElementById('dv-operadora-progress').style.width = `${progressPercent}%`;
        } else {
            document.getElementById('dv-operadora-nome').textContent = '—';
            document.getElementById('dv-operadora-detail').textContent = 'Nenhuma venda registrada';
            document.getElementById('dv-operadora-progress').style.width = '0%';
        }

        // Taxa de conversão
        const conversao = data.taxa_conversao;
        if (conversao) {
            document.getElementById('dv-taxa-conversao').textContent = `${conversao.percentual}%`;
            document.getElementById('dv-leads-trabalhados').textContent = conversao.leads_trabalhados;
            document.getElementById('dv-vendas-realizadas').textContent = conversao.vendas;
        }
    }

    // ========================================
    // Update Charts
    // ========================================
    function updateCharts(data) {
        // Destroy existing chart
        if (monthlyChart) {
            monthlyChart.destroy();
            monthlyChart = null;
        }

        // Monthly Overview - Area Chart
        const monthLabels = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const monthlyData = new Array(12).fill(0);

        if (data.monthly_overview) {
            data.monthly_overview.forEach(item => {
                if (item.month >= 1 && item.month <= 12) {
                    monthlyData[item.month - 1] = parseFloat(item.total);
                }
            });
        }

        const monthlyChartEl = document.querySelector('#dvMonthlyChart');
        if (monthlyChartEl) {
            monthlyChart = new ApexCharts(monthlyChartEl, {
                chart: {
                    type: 'area',
                    height: 300,
                    toolbar: { show: false },
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                series: [{
                    name: 'Vendas (R$)',
                    data: monthlyData
                }],
                colors: [dsColors.primary],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    width: 3,
                    curve: 'smooth'
                },
                xaxis: {
                    categories: monthLabels,
                    labels: {
                        style: { colors: labelColor }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        style: { colors: labelColor },
                        formatter: function (val) {
                            if (val >= 1000) {
                                return 'R$ ' + (val / 1000).toFixed(0) + 'k';
                            }
                            return 'R$ ' + val.toFixed(0);
                        }
                    }
                },
                grid: {
                    borderColor: borderColor,
                    strokeDashArray: 4
                },
                dataLabels: { enabled: false },
                tooltip: {
                    theme: isDarkStyle ? 'dark' : 'light',
                    y: {
                        formatter: function (val) {
                            return formatCurrency(val);
                        }
                    }
                },
                markers: {
                    size: 4,
                    colors: [dsColors.primary],
                    strokeColors: cardColor,
                    strokeWidth: 2,
                    hover: { size: 6 }
                }
            });
            monthlyChart.render();
        }
    }

    // ========================================
    // Update Recent Sales Table
    // ========================================
    function updateRecentSales(data) {
        const tbody = document.getElementById('dv-recent-sales-body');
        const vendas = data.vendas_recentes || [];

        // Update count badge
        const countEl = document.getElementById('dv-table-count');
        if (countEl) {
            countEl.innerHTML = `<span>${vendas.length}</span> vendas`;
        }

        if (vendas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="dv-empty-state">Nenhuma venda encontrada</td></tr>';
            return;
        }

        tbody.innerHTML = vendas.map(venda => {
            const statusClass = getStatusClass(venda.status);
            return `<tr>
                <td>${venda.created_at || '—'}</td>
                <td class="dv-contract-name">${venda.nome_contrato || '—'}</td>
                <td>${venda.operadora || '—'}</td>
                <td>${venda.nome_plano || '—'}</td>
                <td class="dv-contract-value">${formatCurrency(venda.valor_total)}</td>
                <td><span class="dv-status-badge ${statusClass}">${venda.status || '—'}</span></td>
            </tr>`;
        }).join('');
    }

    function getStatusClass(status) {
        if (!status) return 'default';
        const s = status.toLowerCase();
        if (s.includes('implantado')) return 'implantado';
        if (s.includes('venda')) return 'venda';
        if (s.includes('pend') || s.includes('boleto') || s.includes('regularizado')) return 'pendencia';
        if (s.includes('analis') || s.includes('aguard') || s.includes('assinatura')) return 'analise';
        return 'default';
    }

    // ========================================
    // Event Listeners
    // ========================================
    const monthSelect = document.getElementById('dv-select-month');
    const yearSelect = document.getElementById('dv-select-year');

    if (monthSelect) {
        monthSelect.addEventListener('change', () => {
            fetchMetrics(monthSelect.value, yearSelect.value);
        });
    }

    if (yearSelect) {
        yearSelect.addEventListener('change', () => {
            fetchMetrics(monthSelect.value, yearSelect.value);
        });
    }

    // ========================================
    // Init
    // ========================================
    document.addEventListener('DOMContentLoaded', () => {
        const now = new Date();
        if (monthSelect) monthSelect.value = now.getMonth() + 1;
        if (yearSelect) yearSelect.value = now.getFullYear();

        fetchMetrics(monthSelect.value, yearSelect.value);
    });

})();
