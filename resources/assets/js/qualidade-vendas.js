'use strict';

(function () {
    const root = document.querySelector('.qv-page');
    if (!root) return;

    const ano = Number(root.dataset.ano);
    const hoje = root.dataset.hoje;
    const inicioEl = document.getElementById('qv-inicio');
    const fimEl = document.getElementById('qv-fim');
    const vendedorEl = document.getElementById('qv-vendedor');
    const dashboard = document.getElementById('qv-dashboard');
    const loading = document.getElementById('qv-loading');
    const errorBox = document.getElementById('qv-error');
    const modal = document.getElementById('qv-modal');
    let dados = null;
    let rankingAtual = 'valor_valido';
    let chartStatus = null;
    let chartComposicao = null;
    let modalState = { vendedorId: null, vendedor: 'Toda a empresa', categoria: '', page: 1 };

    const brl = value => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL', maximumFractionDigits: 2 }).format(Number(value || 0));
    const num = value => new Intl.NumberFormat('pt-BR').format(Number(value || 0));
    const pct = value => `${Number(value || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 2 })}%`;
    const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[c]);
    const categoriaLabel = { implantada: 'Implantada', em_processo: 'Em processo', estorno: 'Estorno', declinio: 'Declínio' };

    const kpiDefs = [
        { key: 'valor_bruto', label: 'Vendido bruto', tone: 'purple', icon: 'ri-funds-line', type: 'money', sub: k => `${num(k.total_propostas)} propostas`, categoria: '' },
        { key: 'valor_valido', label: 'Válido para ranking', tone: 'blue', icon: 'ri-trophy-line', type: 'money', sub: () => 'Declínios removidos', categoria: '' },
        { key: 'valor_implantado', label: 'Valor implantado', tone: 'green', icon: 'ri-checkbox-circle-line', type: 'money', sub: k => `${num(k.implantadas)} implantadas`, categoria: 'implantada' },
        { key: 'percentual_implantacao', label: 'Implantação', tone: 'green', icon: 'ri-line-chart-line', type: 'percent', sub: k => `${pct(k.percentual_implantacao_valor)} por valor`, categoria: 'implantada' },
        { key: 'valor_em_processo', label: 'Em implantação', tone: 'amber', icon: 'ri-time-line', type: 'money', sub: k => `${num(k.em_processo)} propostas`, categoria: 'em_processo' },
        { key: 'valor_estornado', label: 'Valor estornado', tone: 'red', icon: 'ri-arrow-go-back-line', type: 'money', sub: k => `${num(k.estornos)} · ${pct(k.percentual_estorno)}`, categoria: 'estorno' },
        { key: 'valor_declinado', label: 'Valor declinado', tone: 'red', icon: 'ri-close-circle-line', type: 'money', sub: k => `${num(k.declinios)} · ${pct(k.percentual_declinio)}`, categoria: 'declinio' },
        { key: 'percentual_perda', label: 'Perda total', tone: 'red', icon: 'ri-alarm-warning-line', type: 'percent', sub: k => `${num(k.perdidas)} propostas perdidas`, categoria: '' },
    ];

    function qs() {
        const params = new URLSearchParams({ data_inicio: inicioEl.value, data_fim: fimEl.value });
        if (vendedorEl.value) params.set('vendedor_id', vendedorEl.value);
        return params;
    }

    async function carregar() {
        loading.hidden = false;
        dashboard.hidden = true;
        errorBox.hidden = true;
        document.getElementById('qv-aplicar').disabled = true;
        try {
            const response = await fetch(`/relatorios/qualidade-vendas/dados?${qs()}`, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || result.error || 'Não foi possível carregar o relatório.');
            dados = result.dados;
            preencherVendedores();
            render();
            dashboard.hidden = false;
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        } finally {
            loading.hidden = true;
            document.getElementById('qv-aplicar').disabled = false;
        }
    }

    function preencherVendedores() {
        const atual = vendedorEl.value;
        vendedorEl.innerHTML = '<option value="">Toda a empresa</option>' + dados.vendedores_filtro.map(v => `<option value="${v.id}">${esc(v.nome)}</option>`).join('');
        vendedorEl.value = atual;
    }

    function variacao(chave) {
        if (!dados.comparacao) return '<span class="qv-delta">Sem comparação</span>';
        const atual = Number(dados.kpis[chave] || 0);
        const anterior = Number(dados.comparacao.kpis[chave] || 0);
        if (anterior === 0) return `<span class="qv-delta ${atual > 0 ? 'up' : ''}">${atual > 0 ? 'Novo' : '0%'}</span>`;
        const delta = ((atual - anterior) / Math.abs(anterior)) * 100;
        return `<span class="qv-delta ${delta > 0 ? 'up' : delta < 0 ? 'down' : ''}">${delta > 0 ? '↑' : delta < 0 ? '↓' : ''} ${Math.abs(delta).toFixed(1)}%</span>`;
    }

    function render() {
        document.getElementById('qv-periodo').textContent = `${dados.periodo.inicio_br} — ${dados.periodo.fim_br}`;
        document.getElementById('qv-comparacao-label').textContent = dados.comparacao
            ? `Comparado a ${dados.comparacao.periodo.inicio_br} — ${dados.comparacao.periodo.fim_br}`
            : 'Sem período anterior disponível dentro do ano';
        renderKpis();
        renderCharts();
        renderRanking();
        renderVendedores();
    }

    function renderKpis() {
        document.getElementById('qv-kpis').innerHTML = kpiDefs.map(def => {
            const value = def.type === 'money' ? brl(dados.kpis[def.key]) : pct(dados.kpis[def.key]);
            return `<article class="qv-kpi ${def.tone}" data-categoria="${def.categoria}">
                <div class="qv-kpi-top"><span class="qv-kpi-icon"><i class="${def.icon}"></i></span>${variacao(def.key)}</div>
                <label>${def.label}</label><strong>${value}</strong><small>${def.sub(dados.kpis)}</small>
            </article>`;
        }).join('');
    }

    function renderCharts() {
        chartStatus?.destroy();
        chartComposicao?.destroy();
        const dark = document.documentElement.classList.contains('dark-style');
        const text = dark ? '#b9bad1' : '#71809b';
        const grid = dark ? '#3c3d52' : '#edf0f5';
        const meses = dados.mensal;
        chartStatus = new ApexCharts(document.getElementById('qv-chart-status'), {
            series: [
                { name: 'Implantadas', data: meses.map(m => m.implantadas) },
                { name: 'Em processo', data: meses.map(m => m.em_processo) },
                { name: 'Estornos', data: meses.map(m => m.estornos) },
                { name: 'Declínios', data: meses.map(m => m.declinios) },
            ],
            chart: { type: 'bar', height: 310, stacked: true, toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans' },
            colors: ['#19b982', '#f3a630', '#ea5a6a', '#7d879b'],
            plotOptions: { bar: { borderRadius: 5, columnWidth: '48%' } },
            dataLabels: { enabled: false }, legend: { show: false },
            xaxis: { categories: meses.map(m => m.label), labels: { style: { colors: text } } },
            yaxis: { labels: { style: { colors: text }, formatter: val => Math.round(val) } },
            grid: { borderColor: grid }, tooltip: { shared: true, intersect: false, theme: dark ? 'dark' : 'light' },
        });
        chartStatus.render();

        chartComposicao = new ApexCharts(document.getElementById('qv-chart-composicao'), {
            series: [dados.kpis.implantadas, dados.kpis.em_processo, dados.kpis.estornos, dados.kpis.declinios],
            labels: ['Implantadas', 'Em processo', 'Estornos', 'Declínios'],
            chart: { type: 'donut', height: 285, fontFamily: 'Plus Jakarta Sans' },
            colors: ['#19b982', '#f3a630', '#ea5a6a', '#7d879b'], stroke: { width: 0 },
            legend: { position: 'bottom', labels: { colors: text } }, dataLabels: { enabled: false }, tooltip: { theme: dark ? 'dark' : 'light' },
            plotOptions: { pie: { donut: { size: '70%', labels: { show: true, total: { show: true, label: 'Propostas', formatter: () => num(dados.kpis.total_propostas) } } } } },
            noData: { text: 'Sem propostas no período' },
        });
        chartComposicao.render();
    }

    const rankingCfg = {
        valor_valido: { main: x => brl(x.valor_valido), meta: x => `${num(x.total_propostas)} propostas`, side: x => `${pct(x.percentual_implantacao)} impl.` },
        percentual_implantacao: { main: x => pct(x.percentual_implantacao), meta: x => `${num(x.implantadas)}/${num(x.elegiveis)} elegíveis`, side: x => brl(x.valor_implantado) },
        valor_implantado: { main: x => brl(x.valor_implantado), meta: x => `${num(x.implantadas)} implantadas`, side: x => `${pct(x.percentual_implantacao)} impl.` },
        perdas: { main: x => pct(x.percentual_perda), meta: x => `${num(x.perdidas)} perdas`, side: x => brl(x.valor_perdido) },
    };

    function renderRanking() {
        const lista = dados.rankings[rankingAtual] || [];
        const cfg = rankingCfg[rankingAtual];
        document.getElementById('qv-podium').innerHTML = lista.slice(0, 3).map(item => `<article class="qv-podium-card" data-vendedor="${item.vendedor_id}" data-nome="${esc(item.vendedor)}">
            <span class="qv-place">${item.posicao}º</span><div><strong>${esc(item.vendedor)}</strong><span>${cfg.main(item)} · ${cfg.meta(item)}</span></div>
        </article>`).join('') || '<div class="qv-empty-cell">Sem vendedores no período.</div>';
        document.getElementById('qv-rank-list').innerHTML = lista.slice(3).map(item => `<div class="qv-rank-row" data-vendedor="${item.vendedor_id}" data-nome="${esc(item.vendedor)}">
            <strong>${item.posicao}º</strong><span class="qv-rank-name">${esc(item.vendedor)}</span><span class="qv-rank-meta">${cfg.meta(item)}</span><span class="qv-rank-meta">${cfg.side(item)}</span><span class="qv-rank-main">${cfg.main(item)}</span>
        </div>`).join('');
    }

    function renderVendedores() {
        const body = document.getElementById('qv-vendedores');
        body.innerHTML = dados.vendedores.map(v => `<tr data-vendedor="${v.vendedor_id}" data-nome="${esc(v.vendedor)}">
            <td>${v.posicao_geral ? `${v.posicao_geral}º` : '—'}</td><td><span class="qv-seller">${esc(v.vendedor)}</span>${v.excluir_ranking ? '<br><small>Fora dos rankings</small>' : ''}</td>
            <td class="qv-money">${brl(v.valor_bruto)}</td><td class="qv-money">${brl(v.valor_valido)}</td>
            <td>${num(v.implantadas)} · ${brl(v.valor_implantado)}</td><td>${num(v.em_processo)} · ${brl(v.valor_em_processo)}</td>
            <td>${num(v.estornos)} · ${brl(v.valor_estornado)}</td><td>${num(v.declinios)} · ${brl(v.valor_declinado)}</td>
            <td><strong>${pct(v.percentual_implantacao)}</strong></td><td><strong>${pct(v.percentual_perda)}</strong></td>
        </tr>`).join('') || '<tr><td colspan="10" class="qv-empty-cell">Nenhuma proposta encontrada no período.</td></tr>';
    }

    function abrirDetalhes(vendedorId = null, vendedor = 'Toda a empresa', categoria = '') {
        modalState = { vendedorId, vendedor, categoria, page: 1 };
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
        document.getElementById('qv-modal-title').textContent = vendedor;
        document.getElementById('qv-modal-subtitle').textContent = `${dados.periodo.inicio_br} a ${dados.periodo.fim_br}`;
        document.querySelectorAll('#qv-modal-categorias button').forEach(btn => btn.classList.toggle('is-active', btn.dataset.categoria === categoria));
        carregarDetalhes();
    }

    async function carregarDetalhes(page = 1) {
        modalState.page = page;
        const body = document.getElementById('qv-propostas');
        body.innerHTML = '<tr><td colspan="8" class="qv-empty-cell">Carregando propostas...</td></tr>';
        const params = new URLSearchParams({ data_inicio: inicioEl.value, data_fim: fimEl.value, page, por_pagina: 20 });
        if (modalState.vendedorId) params.set('vendedor_id', modalState.vendedorId);
        if (modalState.categoria) params.set('categoria', modalState.categoria);
        try {
            const response = await fetch(`/relatorios/qualidade-vendas/propostas?${params}`, { headers: { Accept: 'application/json' } });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Falha ao carregar propostas.');
            body.innerHTML = result.data.map(v => `<tr>
                <td><strong>${esc(v.numero_proposta || `#${v.id}`)}</strong><br><small>${esc(v.cliente || 'Cliente não informado')}</small></td>
                <td>${esc(v.vendedor)}</td><td>${v.data_venda}</td><td>${v.data_implantacao || '—'}</td>
                <td><span class="qv-status-pill ${v.categoria}">${esc(v.status)}</span></td>
                <td>${brl(v.valor_contrato)}</td><td>${brl(v.angariacao)}</td><td class="qv-money">${brl(v.valor_total)}</td>
            </tr>`).join('') || '<tr><td colspan="8" class="qv-empty-cell">Nenhuma proposta nesta categoria.</td></tr>';
            renderPaginacao(result.current_page, result.last_page);
        } catch (error) {
            body.innerHTML = `<tr><td colspan="8" class="qv-empty-cell">${esc(error.message)}</td></tr>`;
        }
    }

    function renderPaginacao(atual, ultima) {
        const paginas = [];
        for (let page = Math.max(1, atual - 2); page <= Math.min(ultima, atual + 2); page++) paginas.push(page);
        document.getElementById('qv-pagination').innerHTML = ultima <= 1 ? '' : `
            <button data-page="${Math.max(1, atual - 1)}" ${atual === 1 ? 'disabled' : ''}>Anterior</button>
            ${paginas.map(p => `<button data-page="${p}" class="${p === atual ? 'is-active' : ''}">${p}</button>`).join('')}
            <button data-page="${Math.min(ultima, atual + 1)}" ${atual === ultima ? 'disabled' : ''}>Próxima</button>`;
    }

    function preset(tipo) {
        const now = new Date(`${hoje}T12:00:00`);
        let start = new Date(ano, 0, 1);
        let end = now;
        if (tipo === 'mes') start = new Date(ano, now.getMonth(), 1);
        if (/^q[1-4]$/.test(tipo)) {
            const q = Number(tipo[1]) - 1;
            start = new Date(ano, q * 3, 1);
            end = new Date(ano, q * 3 + 3, 0);
            if (end > now) end = now;
        }
        const iso = d => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        inicioEl.value = iso(start); fimEl.value = iso(end);
        document.querySelectorAll('#qv-presets button').forEach(btn => btn.classList.toggle('is-active', btn.dataset.preset === tipo));
    }

    document.getElementById('qv-aplicar').addEventListener('click', carregar);
    document.getElementById('qv-exportar').addEventListener('click', () => {
        window.location.assign(`/relatorios/qualidade-vendas/excel?${qs()}`);
    });
    document.getElementById('qv-presets').addEventListener('click', e => { const btn = e.target.closest('[data-preset]'); if (btn) { preset(btn.dataset.preset); carregar(); } });
    document.getElementById('qv-ranking-tabs').addEventListener('click', e => { const btn = e.target.closest('[data-ranking]'); if (!btn) return; rankingAtual = btn.dataset.ranking; document.querySelectorAll('#qv-ranking-tabs button').forEach(b => b.classList.toggle('is-active', b === btn)); renderRanking(); });
    document.getElementById('qv-kpis').addEventListener('click', e => { const card = e.target.closest('.qv-kpi'); if (card) abrirDetalhes(vendedorEl.value || null, vendedorEl.options[vendedorEl.selectedIndex].text, card.dataset.categoria); });
    document.addEventListener('click', e => { const row = e.target.closest('[data-vendedor][data-nome]'); if (row) abrirDetalhes(row.dataset.vendedor, row.dataset.nome); });
    document.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', () => { modal.hidden = true; document.body.style.overflow = ''; }));
    document.getElementById('qv-modal-categorias').addEventListener('click', e => { const btn = e.target.closest('[data-categoria]'); if (!btn) return; modalState.categoria = btn.dataset.categoria; document.querySelectorAll('#qv-modal-categorias button').forEach(b => b.classList.toggle('is-active', b === btn)); carregarDetalhes(); });
    document.getElementById('qv-pagination').addEventListener('click', e => { const btn = e.target.closest('[data-page]'); if (btn && !btn.disabled) carregarDetalhes(Number(btn.dataset.page)); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) { modal.hidden = true; document.body.style.overflow = ''; } });

    carregar();
})();
