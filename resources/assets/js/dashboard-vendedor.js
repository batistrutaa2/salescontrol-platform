/**
 * Dashboard comercial do vendedor — períodos atuais.
 */
'use strict';

(function () {
    const root = document.getElementById('dv-dashboard');
    if (!root) return;

    const monthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
    const monthShort = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const periodButtons = document.querySelectorAll('[data-period]');
    const retryButton = document.getElementById('dv-retry');
    let selectedPeriod = 'year';
    let monthlyChart = null;
    let currentRequest = null;
    let movementTimer = null;

    const currency = value => Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });

    const compactCurrency = value => {
        const number = Number(value || 0);
        if (number >= 1000000) return `R$ ${(number / 1000000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mi`;
        if (number >= 1000) return `R$ ${(number / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })} mil`;
        return currency(number);
    };

    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const setText = (id, value) => {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    };

    async function fetchMetrics() {
        if (currentRequest) currentRequest.abort();
        currentRequest = new AbortController();

        root.setAttribute('aria-busy', 'true');
        root.classList.add('is-loading');
        document.getElementById('dv-error').hidden = true;

        try {
            const params = new URLSearchParams({
                period: selectedPeriod
            });
            const response = await fetch(`/dashboard-vendedor/metrics?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                signal: currentRequest.signal
            });

            if (!response.ok) throw new Error(`Dashboard request failed with ${response.status}`);

            const data = await response.json();
            setText('dv-live-region', `Relatório do ${data.period_label} atualizado.`);
            renderDashboard(data);
        } catch (error) {
            if (error.name === 'AbortError') return;
            console.error('Erro ao carregar o dashboard do vendedor:', error);
            document.getElementById('dv-error').hidden = false;
        } finally {
            root.setAttribute('aria-busy', 'false');
            root.classList.remove('is-loading');
            currentRequest = null;
        }
    }

    function renderDashboard(data) {
        renderAnnual(data.annual);
        renderRanking(data.ranking, data.period_label, data.period_key);
        renderLargestSale(data.largest_sale);
        renderMonths(data.monthly);
        renderChart(data.monthly);
        renderInsights(data);
        renderSales(data.detail_sales, data.detail_total, data.period_label);
    }

    function renderAnnual(annual) {
        setText('dv-valid-value', currency(annual.valid_value));
        setText('dv-sales-count', annual.sales_count);
        setText('dv-average-ticket', currency(annual.average_ticket));
        setText('dv-contract-value', currency(annual.contract_value));
        setText('dv-fundraising-value', currency(annual.fundraising_value));
        setText('dv-implanted-count', annual.implanted_count);
        setText('dv-implanted-value', currency(annual.implanted_value));
        setText('dv-implantation-rate', `${Number(annual.implantation_rate).toLocaleString('pt-BR')}%`);
    }

    function renderRanking(ranking, periodLabel, periodKey) {
        setText('dv-ranking-period', periodLabel);
        const suffix = document.getElementById('dv-ranking-suffix');
        const gap = document.getElementById('dv-ranking-gap');

        if (ranking.excluded) {
            setText('dv-ranking-position', '—');
            suffix.hidden = true;
            setText('dv-ranking-context', 'Seu perfil está fora do ranking por configuração da gestão.');
            gap.hidden = true;
        } else if (!ranking.position) {
            setText('dv-ranking-position', '—');
            suffix.hidden = true;
            setText('dv-ranking-context', 'Sua primeira venda válida colocará você no ranking.');
            gap.hidden = true;
        } else {
            setText('dv-ranking-position', ranking.position);
            suffix.hidden = false;
            setText('dv-ranking-context', `entre ${ranking.total_sellers} vendedores com vendas válidas`);
            gap.hidden = !ranking.previous_position;
            if (ranking.previous_position) setText('dv-ranking-distance', currency(ranking.distance_to_previous));
        }

        const leaders = document.getElementById('dv-leaders');
        if (!ranking.leaders.length) {
            leaders.innerHTML = '<li class="dv-empty-inline">Ainda não há vendedores classificados neste período.</li>';
            return;
        }

        leaders.innerHTML = ranking.leaders.map(leader => `
            <li class="${leader.is_current_user ? 'is-current' : ''}">
                <span class="dv-leader-position" aria-label="${leader.position}ª posição">${leader.position}º</span>
                <span class="dv-leader-name">${escapeHtml(leader.seller)}${leader.is_current_user ? '<small>Você</small>' : ''}</span>
            </li>
        `).join('');

        animateRankingMovement(ranking, periodLabel, periodKey);
    }

    function animateRankingMovement(ranking, periodLabel, periodKey) {
        const card = document.querySelector('.dv-score-ranking');
        const movement = document.getElementById('dv-ranking-movement');
        const celebration = document.getElementById('dv-rank-celebration');

        window.clearTimeout(movementTimer);
        movement.hidden = true;
        celebration.hidden = true;
        card.classList.remove('is-gain', 'is-loss');
        celebration.classList.remove('is-active');

        if (!ranking.position || ranking.excluded) return;

        const storageKey = `salescontrol:dashboard-ranking:v1:${ranking.viewer_id}:${periodKey}`;
        let previousPosition = null;

        try {
            const storedPosition = window.localStorage.getItem(storageKey);
            previousPosition = storedPosition === null ? null : Number(storedPosition);
            window.localStorage.setItem(storageKey, String(ranking.position));
        } catch (_) {
            return;
        }

        if (!previousPosition || previousPosition === Number(ranking.position)) return;

        const difference = Math.abs(previousPosition - Number(ranking.position));
        const gained = Number(ranking.position) < previousPosition;
        const plural = difference === 1 ? 'posição' : 'posições';
        void card.offsetWidth;

        card.classList.add(gained ? 'is-gain' : 'is-loss');
        document.getElementById('dv-ranking-movement-icon').className = gained ? 'ri-arrow-up-line' : 'ri-arrow-down-line';
        setText('dv-ranking-movement-title', gained
            ? `Você subiu ${difference} ${plural}!`
            : `Você caiu ${difference} ${plural}`);
        setText('dv-ranking-movement-copy', `Agora está em ${ranking.position}º no ${periodLabel}.`);
        movement.hidden = false;

        if (gained) {
            celebration.hidden = false;
            celebration.classList.add('is-active');
        }

        setText('dv-live-region', `${gained ? 'Você subiu' : 'Você caiu'} ${difference} ${plural}. Agora está em ${ranking.position}º no ${periodLabel}.`);

        movementTimer = window.setTimeout(() => {
            movement.hidden = true;
            card.classList.remove('is-gain', 'is-loss');
            celebration.classList.remove('is-active');
            celebration.hidden = true;
        }, 4800);
    }

    function renderLargestSale(sale) {
        const split = document.getElementById('dv-largest-split');
        if (!sale) {
            setText('dv-largest-total', currency(0));
            setText('dv-largest-client', 'Nenhuma venda no período');
            setText('dv-largest-product', 'O primeiro grande contrato aparecerá aqui.');
            split.hidden = true;
            return;
        }

        setText('dv-largest-total', currency(sale.total));
        setText('dv-largest-client', sale.client);
        setText('dv-largest-product', `${sale.product} · ${sale.operator} · ${sale.date}`);
        setText('dv-largest-contract', currency(sale.contract_value));
        setText('dv-largest-fundraising', currency(sale.fundraising));
        split.hidden = false;
    }

    function renderMonths(monthly) {
        const container = document.getElementById('dv-months');
        const maxValue = Math.max(...monthly.map(month => Number(month.total)), 1);
        container.style.setProperty('--month-count', monthly.length);

        container.innerHTML = monthly.map(month => {
            const intensity = Math.max(8, Math.round(Number(month.total) / maxValue * 100));
            return `
                <div class="dv-month" role="listitem">
                    <span>${monthShort[month.month - 1]}</span>
                    <strong>${escapeHtml(compactCurrency(month.total))}</strong>
                    <small>${month.count} ${month.count === 1 ? 'venda' : 'vendas'}</small>
                    <i style="--month-strength: ${intensity}%" aria-hidden="true"></i>
                </div>
            `;
        }).join('');
    }

    function renderChart(monthly) {
        if (monthlyChart) monthlyChart.destroy();
        const dark = document.documentElement.classList.contains('dark-style');
        const mobile = window.matchMedia('(max-width: 767px)').matches;
        monthlyChart = new ApexCharts(document.getElementById('dvMonthlyChart'), {
            chart: {
                type: 'bar',
                height: mobile ? 180 : 220,
                toolbar: { show: false },
                animations: { enabled: true, easing: 'easeout', speed: 450 },
            },
            series: [{ name: 'Valor válido', data: monthly.map(month => Number(month.total)) }],
            colors: ['#6D4CE8'],
            plotOptions: { bar: { borderRadius: 5, columnWidth: '48%', distributed: false } },
            dataLabels: { enabled: false },
            grid: { borderColor: dark ? '#35304A' : '#E4E0EC', strokeDashArray: 4 },
            xaxis: {
                categories: monthly.map(month => monthShort[month.month - 1]),
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: {
                    rotate: 0,
                    trim: false,
                    style: { colors: dark ? '#BDB6D0' : '#696276', fontWeight: 600, fontSize: mobile ? '10px' : '12px' }
                }
            },
            yaxis: { labels: { show: !mobile, formatter: compactCurrency, style: { colors: dark ? '#BDB6D0' : '#696276' } } },
            tooltip: { theme: dark ? 'dark' : 'light', y: { formatter: currency } },
            states: { hover: { filter: { type: 'darken', value: 0.08 } }, active: { filter: { type: 'none' } } }
        });
        monthlyChart.render();
    }

    function renderInsights(data) {
        const product = data.top_product;
        if (product) {
            setText('dv-top-product', product.name);
            setText('dv-top-product-operator', product.operator || 'Operadora não informada');
            setText('dv-top-product-count', product.count);
            setText('dv-top-product-total', currency(product.total));
        } else {
            setText('dv-top-product', 'Ainda sem produto líder');
            setText('dv-top-product-operator', 'As vendas válidas do período definirão o destaque.');
            setText('dv-top-product-count', '0');
            setText('dv-top-product-total', currency(0));
        }

        if (data.best_month) {
            setText('dv-best-month', monthNames[data.best_month.month - 1]);
            setText('dv-best-month-value', `${currency(data.best_month.total)} em ${data.best_month.count} ${data.best_month.count === 1 ? 'venda' : 'vendas'}`);
        } else {
            setText('dv-best-month', '—');
            setText('dv-best-month-value', 'Sem vendas');
        }

        setText('dv-pending-reversals', data.pending_reversals);
        document.getElementById('dv-reversal-link').classList.toggle('has-pending', Number(data.pending_reversals) > 0);
    }

    function renderSales(sales, total, periodLabel) {
        const body = document.getElementById('dv-sales-body');
        setText('dv-detail-count', `${total} ${total === 1 ? 'venda' : 'vendas'}`);
        setText('dv-ledger-subtitle', `Últimas vendas válidas do ${periodLabel}.`);

        if (!sales.length) {
            body.innerHTML = `<tr><td colspan="6" class="dv-table-state">Nenhuma venda válida no ${escapeHtml(periodLabel)}.</td></tr>`;
            return;
        }

        body.innerHTML = sales.map(sale => `
            <tr>
                <td data-label="Data">${escapeHtml(sale.date)}</td>
                <td data-label="Cliente e produto"><strong>${escapeHtml(sale.client)}</strong><span>${escapeHtml(sale.product)} · ${escapeHtml(sale.operator)}</span></td>
                <td data-label="Contrato">${escapeHtml(currency(sale.contract_value))}</td>
                <td data-label="Angariação">${escapeHtml(currency(sale.fundraising))}</td>
                <td data-label="Total válido"><b>${escapeHtml(currency(sale.total))}</b></td>
                <td data-label="Status"><span class="dv-status ${statusClass(sale.status)}">${escapeHtml(sale.status)}</span></td>
            </tr>
        `).join('');
    }

    function statusClass(status) {
        const normalized = String(status || '').toLocaleLowerCase('pt-BR');
        if (normalized.includes('estorno')) return 'is-reversal';
        if (normalized.includes('pend') || normalized.includes('anális') || normalized.includes('aguard')) return 'is-pending';
        return 'is-positive';
    }

    retryButton.addEventListener('click', fetchMetrics);
    periodButtons.forEach(button => {
        button.addEventListener('click', () => {
            selectedPeriod = button.dataset.period;
            periodButtons.forEach(option => {
                const active = option === button;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-pressed', String(active));
            });
            fetchMetrics();
        });
    });

    document.addEventListener('DOMContentLoaded', fetchMetrics);
})();
