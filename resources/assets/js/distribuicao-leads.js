'use strict';

(function () {
    const root = document.querySelector('.dl-page');
    if (!root) return;

    const inicio = document.getElementById('dl-inicio');
    const fim = document.getElementById('dl-fim');
    const loading = document.getElementById('dl-loading');
    const dashboard = document.getElementById('dl-dashboard');
    const errorBox = document.getElementById('dl-error');
    const hoje = root.dataset.hoje;
    let chartEvolucao = null;
    let chartCobertura = null;
    let chartMotivos = null;

    const numero = valor => new Intl.NumberFormat('pt-BR').format(Number(valor || 0));
    const percentual = valor => `${Number(valor || 0).toLocaleString('pt-BR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
    const escapar = valor => String(valor ?? '').replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' })[c]);
    const dark = () => document.documentElement.classList.contains('dark-style');
    const dataIso = data => `${data.getFullYear()}-${String(data.getMonth() + 1).padStart(2, '0')}-${String(data.getDate()).padStart(2, '0')}`;

    async function carregar() {
        loading.hidden = false;
        dashboard.hidden = true;
        errorBox.hidden = true;
        document.getElementById('dl-aplicar').disabled = true;
        const params = new URLSearchParams();
        if (inicio.value && fim.value) {
            params.set('data_inicial', inicio.value);
            params.set('data_final', fim.value);
        }

        try {
            const response = await fetch(`/relatorios/distribuicao-leads/dados?${params}`, { headers: { Accept: 'application/json' } });
            const dados = await response.json();
            if (!response.ok) {
                const mensagens = dados.errors ? Object.values(dados.errors).flat().join(' ') : dados.message;
                throw new Error(mensagens || 'Não foi possível carregar o relatório.');
            }
            renderizar(dados);
            dashboard.hidden = false;
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
        } finally {
            loading.hidden = true;
            document.getElementById('dl-aplicar').disabled = false;
        }
    }

    function renderizar(dados) {
        const r = dados.resumo;
        document.getElementById('dl-period-label').textContent = dados.periodo.inicio
            ? `${dados.periodo.inicio} — ${dados.periodo.fim}`
            : 'Histórico completo';
        document.getElementById('dl-group-label').textContent = dados.periodo.agrupamento === 'mensal' ? 'Agrupado por mês' : 'Agrupado por dia';

        const kpis = [
            ['Total recebido', r.total_leads, 'ri-database-2-line', 'purple', `${numero(r.leads_nao_distribuidos)} aguardando distribuição`, ''],
            ['Cobertura da base', percentual(r.cobertura_distribuicao), 'ri-user-shared-line', 'green', `${numero(r.leads_distribuidos)} leads distribuídos`, percentual(r.cobertura_distribuicao)],
            ['Sob custódia comercial', r.leads_comercial, 'ri-briefcase-4-line', 'purple', `${percentual(r.taxa_comercial)} dos distribuídos`, percentual(r.taxa_comercial)],
            ['Administrativo', r.leads_administrativo, 'ri-file-list-3-line', 'amber', `${percentual(r.taxa_administrativo)} dos distribuídos`, percentual(r.taxa_administrativo)],
            ['Na preditiva', r.leads_preditiva, 'ri-phone-line', 'green', `${numero(r.tentativas_preditiva)} tentativas`, `${r.tentativas_por_lead}×`],
            ['Descartados', r.leads_descartados, 'ri-close-circle-line', 'red', `${percentual(r.taxa_descarte)} da entrada`, percentual(r.taxa_descarte)],
            ['Tentativas preditiva', r.tentativas_preditiva, 'ri-phone-find-line', 'purple', 'Volume de chamadas no período', `${r.tentativas_por_lead}×/lead`],
            ['Sem distribuição', r.leads_nao_distribuidos, 'ri-inbox-unarchive-line', 'amber', 'Leads sem vendedor', percentual(100 - r.cobertura_distribuicao)],
        ];
        document.getElementById('dl-kpis').innerHTML = kpis.map(k => `<article class="dl-kpi ${k[3]}"><header><i class="${k[2]}"></i><em>${k[5]}</em></header><label>${k[0]}</label><strong>${typeof k[1] === 'number' ? numero(k[1]) : k[1]}</strong><small>${k[4]}</small></article>`).join('');

        const fluxo = [
            ['Recebidos', r.total_leads, '100% da entrada'],
            ['Distribuídos', r.leads_distribuidos, percentual(r.cobertura_distribuicao)],
            ['Comercial', r.leads_comercial, percentual(r.taxa_comercial)],
            ['Administrativo', r.leads_administrativo, percentual(r.taxa_administrativo)],
            ['Descartados', r.leads_descartados, percentual(r.taxa_descarte)],
        ];
        document.getElementById('dl-flow').innerHTML = fluxo.map(item => `<div class="dl-flow-item"><span>${item[0]}</span><strong>${numero(item[1])}</strong><small>${item[2]}</small></div>`).join('');

        renderLista('dl-list-comercial', dados.distribuicao_comercial, 'descricao', '#7367f0');
        renderLista('dl-list-administrativo', dados.distribuicao_administrativa, 'descricao', '#ff9f43');
        renderLista('dl-list-descarte', dados.distribuicao_descarte, 'descricao', '#ea5455');
        renderRanking(dados.ranking_vendedores, r.leads_distribuidos);
        renderGraficos(dados);
    }

    function renderLista(id, itens, campo, cor) {
        const el = document.getElementById(id);
        if (!itens?.length) { el.innerHTML = '<div class="dl-empty">Sem dados neste período</div>'; return; }
        const maximo = Math.max(...itens.map(i => Number(i.total)));
        el.innerHTML = itens.slice(0, 8).map(item => `<div class="dl-status-row"><span title="${escapar(item[campo])}">${escapar(item[campo])}</span><i style="--width:${maximo ? (item.total / maximo) * 100 : 0}%;--color:${cor}"></i><strong>${numero(item.total)}</strong></div>`).join('');
    }

    function renderRanking(itens, totalDistribuido) {
        const el = document.getElementById('dl-ranking');
        if (!itens?.length) { el.innerHTML = '<tr><td colspan="7" class="dl-empty">Sem vendedores no período</td></tr>'; return; }
        el.innerHTML = itens.map((item, index) => {
            const share = totalDistribuido ? (Number(item.total) / totalDistribuido) * 100 : 0;
            return `<tr><td>${index + 1}</td><td>${escapar(item.name)}</td><td>${numero(item.total)}</td><td>${numero(item.comercial)}</td><td>${numero(item.administrativo)}</td><td>${numero(item.descarte)}</td><td><div class="dl-share"><i style="--width:${Math.min(100, share)}%"></i><span>${percentual(share)}</span></div></td></tr>`;
        }).join('');
    }

    function renderGraficos(dados) {
        chartEvolucao?.destroy(); chartCobertura?.destroy(); chartMotivos?.destroy();
        const texto = dark() ? '#b9bad1' : '#74809a';
        const grade = dark() ? '#3c3d52' : '#edf0f5';
        const tooltip = dark() ? 'dark' : 'light';
        const labels = dados.evolucao.map(i => formatarPeriodo(i.periodo));

        chartEvolucao = new ApexCharts(document.getElementById('dl-chart-evolucao'), {
            series: [{ name: 'Recebidos', data: dados.evolucao.map(i => i.total) }, { name: 'Distribuídos', data: dados.evolucao.map(i => i.distribuidos) }],
            chart: { type: 'area', height: 310, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: ['#7367f0', '#28c76f'], stroke: { curve: 'smooth', width: 3 }, fill: { type: 'gradient', gradient: { opacityFrom: .28, opacityTo: .03 } },
            dataLabels: { enabled: false }, xaxis: { categories: labels, labels: { style: { colors: texto }, rotate: -35 } }, yaxis: { labels: { style: { colors: texto }, formatter: numero } },
            grid: { borderColor: grade, strokeDashArray: 4 }, legend: { labels: { colors: texto } }, tooltip: { theme: tooltip, shared: true }, noData: { text: 'Sem dados no período' }
        });
        chartEvolucao.render();

        chartCobertura = new ApexCharts(document.getElementById('dl-chart-cobertura'), {
            series: [dados.resumo.leads_distribuidos, dados.resumo.leads_nao_distribuidos], labels: ['Distribuídos', 'Aguardando'],
            chart: { type: 'donut', height: 275, fontFamily: 'inherit' }, colors: ['#28c76f', '#ff9f43'], stroke: { width: 0 }, dataLabels: { enabled: false },
            legend: { position: 'bottom', labels: { colors: texto } }, tooltip: { theme: tooltip },
            plotOptions: { pie: { donut: { size: '72%', labels: { show: true, total: { show: true, label: 'Cobertura', formatter: () => percentual(dados.resumo.cobertura_distribuicao) } } } } }
        });
        chartCobertura.render();

        const motivos = dados.motivos_descarte.slice(0, 8);
        chartMotivos = new ApexCharts(document.getElementById('dl-chart-motivos'), {
            series: [{ name: 'Leads', data: motivos.map(i => i.total) }], chart: { type: 'bar', height: 310, toolbar: { show: false }, fontFamily: 'inherit' },
            colors: ['#ea5455'], plotOptions: { bar: { horizontal: true, borderRadius: 5, barHeight: '58%' } }, dataLabels: { enabled: true, formatter: numero },
            xaxis: { categories: motivos.map(i => i.motivo || 'Sem motivo'), labels: { style: { colors: texto }, formatter: numero } }, yaxis: { labels: { style: { colors: texto }, maxWidth: 145 } },
            grid: { borderColor: grade, strokeDashArray: 4 }, tooltip: { theme: tooltip }, noData: { text: 'Sem descartes no período' }
        });
        chartMotivos.render();
    }

    function formatarPeriodo(valor) {
        if (!valor) return '';
        const partes = valor.split('-');
        return partes.length === 2 ? `${partes[1]}/${partes[0]}` : `${partes[2]}/${partes[1]}`;
    }

    function aplicarPreset(botao) {
        const agora = new Date(`${hoje}T12:00:00`);
        let comeco = null;
        if (botao.dataset.days) {
            comeco = new Date(agora); comeco.setDate(comeco.getDate() - Number(botao.dataset.days) + 1);
        } else if (botao.dataset.preset === 'mes') comeco = new Date(agora.getFullYear(), agora.getMonth(), 1);
        else if (botao.dataset.preset === 'ano') comeco = new Date(agora.getFullYear(), 0, 1);
        inicio.value = comeco ? dataIso(comeco) : '';
        fim.value = comeco ? hoje : '';
        document.querySelectorAll('#dl-presets button').forEach(item => item.classList.toggle('is-active', item === botao));
        carregar();
    }

    document.getElementById('dl-aplicar').addEventListener('click', () => { document.querySelectorAll('#dl-presets button').forEach(item => item.classList.remove('is-active')); carregar(); });
    document.getElementById('dl-presets').addEventListener('click', event => { const botao = event.target.closest('button'); if (botao) aplicarPreset(botao); });
    carregar();
}());
