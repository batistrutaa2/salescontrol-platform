'use strict';

(function () {
    const root = document.querySelector('.dl-page');
    if (!root) return;

    const inicio = document.getElementById('dl-inicio');
    const fim = document.getElementById('dl-fim');
    const loading = document.getElementById('dl-loading');
    const dashboard = document.getElementById('dl-dashboard');
    const errorBox = document.getElementById('dl-error');
    const sellerModalElement = document.getElementById('dl-seller-modal');
    const sellerModal = new bootstrap.Modal(sellerModalElement);
    const hoje = root.dataset.hoje;
    let selectedSeller = null;
    let sellerRequest = null;
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

        const posicoes = [
            ['Total na base', r.total_leads, 'ri-database-2-line', 'purple', 'Leads recebidos no período', '100%'],
            ['Em trabalho com vendedores', r.leads_comercial, 'ri-briefcase-4-line', 'green', 'Ativos em etapa comercial; não inclui remarketing', percentual(r.taxa_comercial)],
            ['Na preditiva', r.leads_preditiva, 'ri-phone-line', 'blue', `${numero(r.tentativas_preditiva)} tentativas realizadas no período`, `${r.tentativas_por_lead}×/lead`],
            ['No remarketing', r.leads_remarketing, 'ri-recycle-line', 'amber', 'Fora da atuação comercial ativa', 'Fila atual'],
            ['Reservatório de leads', r.leads_reservatorio, 'ri-inbox-archive-line', 'amber', 'Leads novos, prontos para envio e ainda não distribuídos', 'Prontos para envio'],
            ['Descartados', r.leads_descartados, 'ri-close-circle-line', 'red', 'Fora do trabalho comercial', percentual(r.taxa_descarte)],
        ];
        const posVenda = [
            ['Viraram venda', r.leads_viraram_venda, 'ri-hand-coin-line', 'purple', 'Venda válida registrada', percentual(r.taxa_venda)],
            ['Fila administrativa', r.leads_fila_implantacao, 'ri-file-list-3-line', 'amber', 'Em processo para implantação', percentual(r.taxa_administrativo)],
            ['Carteira de clientes', r.leads_carteira_clientes, 'ri-shield-check-line', 'green', 'Possuem venda implantada', percentual(r.taxa_implantacao)],
            ['Declinados', r.leads_declinados, 'ri-file-close-line', 'red', 'Encerrados sem implantação', 'Fora da fila'],
            ['Estornados', r.leads_estornados, 'ri-arrow-go-back-line', 'red', 'Venda revertida', 'Fora da fila'],
        ];
        const renderMetricas = itens => itens.map(item => `<article class="dl-kpi ${item[3]}"><header><i class="${item[2]}" aria-hidden="true"></i><em>${item[5]}</em></header><span class="dl-kpi-label">${item[0]}</span><strong>${numero(item[1])}</strong><small>${item[4]}</small></article>`).join('');
        document.getElementById('dl-kpis-position').innerHTML = renderMetricas(posicoes);
        document.getElementById('dl-kpis-sales').innerHTML = renderMetricas(posVenda);

        renderLista('dl-list-comercial', dados.distribuicao_comercial, 'descricao', '#7367f0');
        renderLista('dl-list-administrativo', dados.distribuicao_administrativa, 'descricao', '#ff9f43');
        renderLista('dl-list-descarte', dados.distribuicao_descarte, 'descricao', '#ea5455');
        renderRanking(dados.ranking_vendedores, r.leads_atribuidos_vendedores);
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
        if (!itens?.length) { el.innerHTML = '<tr><td colspan="8" class="dl-empty">Sem vendedores no período</td></tr>'; return; }
        el.innerHTML = itens.map((item, index) => {
            const share = totalDistribuido ? (Number(item.total) / totalDistribuido) * 100 : 0;
            const nome = escapar(item.name);
            return `<tr class="dl-ranking-row" data-seller-id="${Number(item.id)}" data-seller-name="${nome}"><td>${index + 1}</td><td>${nome}</td><td>${numero(item.total)}</td><td>${numero(item.comercial)}</td><td>${numero(item.remarketing)}</td><td>${numero(item.administrativo)}</td><td><div class="dl-share"><i style="--width:${Math.min(100, share)}%"></i><span>${percentual(share)}</span></div></td><td class="dl-row-action"><button type="button" aria-label="Ver fila comercial de ${nome}"><i class="ri-arrow-right-up-line" aria-hidden="true"></i></button></td></tr>`;
        }).join('');
    }

    function periodoSelecionado() {
        if (!inicio.value || !fim.value) return 'Histórico completo';
        const formatar = valor => valor.split('-').reverse().join('/');
        return `${formatar(inicio.value)} — ${formatar(fim.value)}`;
    }

    async function carregarDetalhesVendedor() {
        if (!selectedSeller) return;

        sellerRequest?.abort();
        const request = new AbortController();
        sellerRequest = request;
        const modalLoading = document.getElementById('dl-seller-modal-loading');
        const modalError = document.getElementById('dl-seller-modal-error');
        const modalContent = document.getElementById('dl-seller-modal-content');
        modalLoading.hidden = false;
        modalError.hidden = true;
        modalContent.hidden = true;
        document.getElementById('dl-seller-modal-title').textContent = selectedSeller.name;
        document.getElementById('dl-seller-modal-period').textContent = periodoSelecionado();

        const params = new URLSearchParams();
        if (inicio.value && fim.value) {
            params.set('data_inicial', inicio.value);
            params.set('data_final', fim.value);
        }

        try {
            const response = await fetch(`${root.dataset.vendedorDetalhesUrl}/${selectedSeller.id}/detalhes?${params}`, {
                headers: { Accept: 'application/json' },
                signal: request.signal,
            });
            const dados = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(dados.message || 'Não foi possível carregar os detalhes deste vendedor.');

            document.getElementById('dl-seller-sales-total').textContent = numero(dados.viraram_venda);
            document.getElementById('dl-seller-queue-total').textContent = numero(dados.total_fila_comercial);
            const statuses = document.getElementById('dl-seller-statuses');
            if (!dados.fila_comercial?.length) {
                statuses.innerHTML = '<div class="dl-modal-empty"><i class="ri-inbox-line" aria-hidden="true"></i><p>Nenhum cliente está na fila comercial neste período.</p></div>';
            } else {
                const maximo = Math.max(...dados.fila_comercial.map(item => Number(item.total)));
                statuses.innerHTML = dados.fila_comercial.map(item => `<div class="dl-modal-status"><div><span title="${escapar(item.descricao)}">${escapar(item.descricao)}</span><strong>${numero(item.total)}</strong></div><i style="--width:${maximo ? (Number(item.total) / maximo) * 100 : 0}%"></i></div>`).join('');
            }
            modalContent.hidden = false;
        } catch (error) {
            if (error.name === 'AbortError') return;
            modalError.querySelector('p').textContent = error.message;
            modalError.hidden = false;
        } finally {
            if (sellerRequest === request) modalLoading.hidden = true;
        }
    }

    function abrirDetalhesVendedor(row) {
        selectedSeller = { id: row.dataset.sellerId, name: row.dataset.sellerName };
        sellerModal.show();
        carregarDetalhesVendedor();
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
            series: [dados.resumo.leads_distribuidos, dados.resumo.leads_reservatorio], labels: ['Distribuídos', 'No reservatório'],
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
    document.getElementById('dl-ranking').addEventListener('click', event => { const row = event.target.closest('.dl-ranking-row'); if (row) abrirDetalhesVendedor(row); });
    document.getElementById('dl-seller-modal-retry').addEventListener('click', carregarDetalhesVendedor);
    sellerModalElement.addEventListener('hidden.bs.modal', () => { sellerRequest?.abort(); selectedSeller = null; });
    carregar();
}());
