/**
 * Performance do Vendedor
 * Relatório de evolução de carreira (ano a ano) — ApexCharts + Glass Morphism
 */
'use strict';

(function () {
    // ============ Tema ============
    let labelColor, borderColor;

    if (isDarkStyle) {
        labelColor = config.colors_dark.textMuted;
        borderColor = config.colors_dark.borderColor;
    } else {
        labelColor = config.colors.textMuted;
        borderColor = config.colors.borderColor;
    }

    const ds = {
        primary: '#7C3AED',
        primaryLight: '#A78BFA',
        success: '#10B981',
        info: '#06B6D4',
        warning: '#F59E0B',
        danger: '#EF4444',
        muted: '#94A3B8'
    };

    const MESES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // ============ Helpers de formatação ============
    function formatCurrency(value) {
        return parseFloat(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    function formatCurrencyShort(value) {
        const v = parseFloat(value || 0);
        if (Math.abs(v) >= 1000000) return 'R$ ' + (v / 1000000).toFixed(1).replace('.', ',') + 'M';
        if (Math.abs(v) >= 1000) return 'R$ ' + (v / 1000).toFixed(1).replace('.', ',') + 'k';
        return formatCurrency(v);
    }
    function formatNumber(value) {
        return parseInt(value || 0, 10).toLocaleString('pt-BR');
    }
    function formatPercent(value) {
        return parseFloat(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) + '%';
    }

    // ============ Elementos de filtro ============
    const selVendedor = document.getElementById('pv-select-vendedor');
    const selAno = document.getElementById('pv-select-ano');

    let curvaChart = null;
    let mensalChart = null;
    let periodoParcial = false;
    let periodoLabel = '';

    // ============ Inicializa select de ano (range padrão) ============
    function popularAnos(primeiroAno, ultimoAno, selecionado) {
        const atual = new Date().getFullYear();
        const fim = ultimoAno || atual;
        const inicio = primeiroAno || (fim - 5);
        const sel = selecionado || fim;

        let html = '';
        for (let ano = fim; ano >= inicio; ano--) {
            html += `<option value="${ano}" ${ano === sel ? 'selected' : ''}>${ano}</option>`;
        }
        selAno.innerHTML = html;
    }

    // ============ Fetch ============
    async function carregar() {
        const vendedorId = selVendedor.value;
        const ano = selAno.value || new Date().getFullYear();
        if (!vendedorId) return;

        document.querySelectorAll('.pv-kpi-value').forEach(el => (el.style.opacity = '0.4'));

        try {
            const resp = await fetch(`/relatorios/atividade/dados?vendedor_id=${vendedorId}&ano=${ano}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error('Falha na requisição');

            const json = await resp.json();
            if (!json.success) throw new Error(json.message || 'Erro ao carregar dados');

            render(json.data);
        } catch (e) {
            console.error('Erro ao carregar performance:', e);
        } finally {
            document.querySelectorAll('.pv-kpi-value').forEach(el => (el.style.opacity = '1'));
        }
    }

    // ============ Render geral ============
    function render(data) {
        periodoParcial = !!(data.periodo && data.periodo.parcial);
        periodoLabel = (data.periodo && data.periodo.label) || '';

        atualizarTempoDeCasa(data.vendedor);
        atualizarNivel(data.diagnostico);
        atualizarPeriodo();
        atualizarKpis(data.kpis);
        renderCurvaEvolucao(data.evolucao_anual);
        renderComparativoMensal(data.comparativo_mensal);
        renderDiagnostico(data.diagnostico, data.ano_foco);
        renderTabela(data.evolucao_anual);

        // Reajusta o range de anos ao tempo de casa do vendedor mantendo a seleção
        const anoSelecionado = parseInt(selAno.value, 10);
        popularAnos(data.vendedor.primeiro_ano, data.vendedor.ultimo_ano, anoSelecionado);
    }

    // ============ Tempo de casa ============
    function atualizarTempoDeCasa(v) {
        const anos = v.anos_de_trabalho;
        document.getElementById('pv-tenure-anos').textContent = anos + (anos === 1 ? ' ano de trabalho' : ' anos de trabalho');
        document.getElementById('pv-tenure-desde').textContent = `desde ${v.primeiro_ano}`;
    }

    function atualizarPeriodo() {
        const chip = document.getElementById('pv-periodo-chip');
        const subtitle = document.getElementById('pv-curva-subtitle');
        if (periodoParcial) {
            if (chip) {
                chip.hidden = false;
                chip.textContent = `Mesmo período em cada ano · ${periodoLabel}`;
            }
            if (subtitle) subtitle.textContent = `Comparação no mesmo período de cada ano (${periodoLabel})`;
        } else {
            if (chip) chip.hidden = true;
            if (subtitle) subtitle.textContent = 'Valor vendido e taxa de conversão ao longo da carreira';
        }
    }

    function atualizarNivel(diag) {
        const badge = document.getElementById('pv-nivel-badge');
        document.getElementById('pv-nivel-text').textContent = diag.nivel || '—';
        badge.classList.remove('nivel-up', 'nivel-down', 'nivel-flat');
        if (diag.tendencia === 'crescimento') badge.classList.add('nivel-up');
        else if (diag.tendencia === 'queda') badge.classList.add('nivel-down');
        else badge.classList.add('nivel-flat');
    }

    // ============ KPIs + badges YoY ============
    const KPI_MAP = {
        valor_vendido: { id: 'valor-vendido', fmt: formatCurrency },
        total_vendas: { id: 'total-vendas', fmt: formatNumber },
        leads_trabalhados: { id: 'leads', fmt: formatNumber },
        taxa_conversao: { id: 'conversao', fmt: formatPercent },
        vidas: { id: 'vidas', fmt: formatNumber },
        ticket_medio: { id: 'ticket', fmt: formatCurrency }
    };

    function atualizarKpis(kpis) {
        Object.keys(KPI_MAP).forEach(key => {
            const cfg = KPI_MAP[key];
            const kpi = kpis[key] || {};
            const valEl = document.getElementById(`pv-kpi-${cfg.id}`);
            if (valEl) valEl.textContent = cfg.fmt(kpi.atual);
            renderTrend(cfg.id, kpi);
        });
    }

    function renderTrend(id, kpi) {
        const el = document.getElementById(`pv-trend-${id}`);
        if (!el) return;
        el.classList.remove('trend-up', 'trend-down', 'trend-flat');

        if (kpi.variacao_pct === null || kpi.variacao_pct === undefined) {
            el.classList.add('trend-flat');
            el.innerHTML = '<span class="pv-trend-text">sem base anterior</span>';
            return;
        }

        const dir = kpi.direcao;
        const arrowUp = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>';
        const arrowDown = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>';
        const arrowFlat = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>';

        let arrow = arrowFlat;
        if (dir === 'up') { el.classList.add('trend-up'); arrow = arrowUp; }
        else if (dir === 'down') { el.classList.add('trend-down'); arrow = arrowDown; }
        else { el.classList.add('trend-flat'); }

        const ctx = periodoParcial ? 'vs mesmo período' : 'vs ano anterior';
        el.innerHTML = `${arrow}<span>${Math.abs(kpi.variacao_pct).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%</span><span class="pv-trend-text">${ctx}</span>`;
    }

    // ============ Gráfico: Curva de Evolução (combo) ============
    function renderCurvaEvolucao(serie) {
        const anos = serie.map(s => s.ano);
        const valores = serie.map(s => Number(s.valor_vendido));
        const conversao = serie.map(s => Number(s.taxa_conversao));

        const options = {
            chart: { height: 340, type: 'line', toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans, sans-serif', stacked: false },
            series: [
                { name: 'Valor vendido', type: 'column', data: valores },
                { name: 'Conversão', type: 'line', data: conversao }
            ],
            colors: [ds.primary, ds.warning],
            stroke: { width: [0, 3], curve: 'smooth' },
            plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } },
            dataLabels: { enabled: false },
            fill: { opacity: [0.9, 1] },
            markers: { size: [0, 5], colors: [ds.warning], strokeColors: '#fff', strokeWidth: 2 },
            xaxis: { categories: anos, labels: { style: { colors: labelColor, fontSize: '13px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: [
                { seriesName: 'Valor vendido', labels: { style: { colors: labelColor }, formatter: v => formatCurrencyShort(v) } },
                { opposite: true, seriesName: 'Conversão', labels: { style: { colors: labelColor }, formatter: v => v.toFixed(0) + '%' } }
            ],
            grid: { borderColor: borderColor, strokeDashArray: 4, padding: { top: 0 } },
            legend: { show: false },
            tooltip: {
                theme: isDarkStyle ? 'dark' : 'light',
                shared: true,
                intersect: false,
                y: {
                    formatter: (val, opts) => {
                        return opts.seriesIndex === 1 ? formatPercent(val) : formatCurrency(val);
                    }
                }
            }
        };

        if (curvaChart) {
            curvaChart.updateOptions({ xaxis: { categories: anos } }, false, false);
            curvaChart.updateSeries(options.series, true);
        } else {
            curvaChart = new ApexCharts(document.querySelector('#pvCurvaEvolucao'), options);
            curvaChart.render();
        }
    }

    // ============ Gráfico: Comparativo Mensal ============
    function renderComparativoMensal(comp) {
        const meses = comp.meses || [];
        const focoData = meses.map(m => (m.valor_ano_foco === null ? null : Number(m.valor_ano_foco)));
        const anteriorData = meses.map(m => Number(m.valor_ano_anterior));

        document.getElementById('pv-legend-ano-foco').textContent = comp.ano_foco;
        document.getElementById('pv-legend-ano-anterior').textContent = comp.ano_anterior;
        document.getElementById('pv-comparativo-subtitle').textContent = `Valor vendido mês a mês — ${comp.ano_foco} vs ${comp.ano_anterior}`;

        const options = {
            chart: { height: 340, type: 'area', toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
            series: [
                { name: String(comp.ano_foco), data: focoData },
                { name: String(comp.ano_anterior), data: anteriorData }
            ],
            colors: [ds.primary, ds.muted],
            stroke: { width: [3, 2], curve: 'smooth', dashArray: [0, 5] },
            fill: { type: 'gradient', gradient: { shadeIntensity: 0.6, opacityFrom: [0.25, 0.05], opacityTo: [0.02, 0], stops: [0, 90, 100] } },
            dataLabels: { enabled: false },
            markers: { size: 0, hover: { size: 5 } },
            xaxis: { categories: MESES, labels: { style: { colors: labelColor, fontSize: '12px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
            yaxis: { labels: { style: { colors: labelColor }, formatter: v => formatCurrencyShort(v) } },
            grid: { borderColor: borderColor, strokeDashArray: 4 },
            legend: { show: false },
            tooltip: { theme: isDarkStyle ? 'dark' : 'light', y: { formatter: v => formatCurrency(v) } }
        };

        if (mensalChart) {
            mensalChart.updateSeries(options.series, true);
        } else {
            mensalChart = new ApexCharts(document.querySelector('#pvComparativoMensal'), options);
            mensalChart.render();
        }
    }

    // ============ Diagnóstico ============
    function renderDiagnostico(diag, anoFoco) {
        const tendEl = document.getElementById('pv-diag-tendencia');
        const tendText = document.getElementById('pv-diag-tendencia-text');
        const labelTend = { crescimento: 'Em crescimento', queda: 'Em queda', estavel: 'Estável' };
        tendText.textContent = labelTend[diag.tendencia] || '—';
        tendEl.classList.remove('tend-up', 'tend-down', 'tend-flat');
        if (diag.tendencia === 'crescimento') tendEl.classList.add('tend-up');
        else if (diag.tendencia === 'queda') tendEl.classList.add('tend-down');
        else tendEl.classList.add('tend-flat');

        document.getElementById('pv-diag-melhor-ano').textContent = diag.melhor_ano || '—';
        document.getElementById('pv-diag-melhor-ano-valor').textContent = diag.melhor_ano_valor > 0 ? formatCurrency(diag.melhor_ano_valor) : '';

        const ul = document.getElementById('pv-diag-resumo');
        ul.innerHTML = (diag.resumo || []).map(txt => `<li>${txt}</li>`).join('');
    }

    // ============ Tabela YoY ============
    function renderTabela(serie) {
        const tbody = document.getElementById('pv-table-tbody');
        if (!serie || !serie.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="pv-table-empty">Sem histórico para este vendedor.</td></tr>';
            return;
        }

        // Mais recente primeiro
        const linhas = [...serie].reverse();

        tbody.innerHTML = linhas.map(l => {
            let cresc = '<span class="pv-chip pv-chip-flat">—</span>';
            if (l.crescimento_valor_pct !== null && l.crescimento_valor_pct !== undefined) {
                const up = l.crescimento_valor_pct >= 0;
                const sinal = up ? '+' : '−';
                cresc = `<span class="pv-chip ${up ? 'pv-chip-up' : 'pv-chip-down'}">${sinal}${Math.abs(l.crescimento_valor_pct).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%</span>`;
            }
            return `<tr>
                <td class="pv-td-ano">${l.ano}</td>
                <td class="pv-num pv-mono">${formatCurrency(l.valor_vendido)}</td>
                <td class="pv-num">${cresc}</td>
                <td class="pv-num">${formatNumber(l.total_vendas)}</td>
                <td class="pv-num">${formatNumber(l.leads_trabalhados)}</td>
                <td class="pv-num">${formatPercent(l.taxa_conversao)}</td>
                <td class="pv-num">${formatNumber(l.vidas)}</td>
                <td class="pv-num pv-mono">${formatCurrency(l.ticket_medio)}</td>
            </tr>`;
        }).join('');
    }

    // ============ Eventos ============
    if (selVendedor && selAno) {
        popularAnos(null, null, new Date().getFullYear());
        selVendedor.addEventListener('change', carregar);
        selAno.addEventListener('change', carregar);
        carregar();
    }
})();
