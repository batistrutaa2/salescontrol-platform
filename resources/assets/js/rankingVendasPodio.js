'use strict';

$(function () {

    // ==============================
    // CONFIG / ENDPOINTS
    // ==============================
    // Agora este endpoint retorna totais do mês agrupados por vendedor (ver controller abaixo)
    const ENDPOINT_VENDAS_MES = '/vendas/valores-mensais'; // ?empresa_id=... (opcional)

    // ==============================
    // HELPERS (GRÁFICO)
    // ==============================
    const currencyBRL = (n) =>
        (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(n || 0)));

    // Normaliza resposta para { categories: ['Vendedor A', 'Vendedor B', ...], series: [1234.56, 987.00, ...] }
    function normalizeVendedoresData(apiData) {
        const items = Array.isArray(apiData) ? apiData : [];
        const getNome = (item) => item.vendedor || item.nome || item.name || '—';
        const getTotal = (item) => Number(item.total || item.valor || 0);

        items.sort((a, b) => getTotal(b) - getTotal(a));

        return {
            categories: items.map(getNome),
            series: items.map(getTotal)
        };
    }


    // ==============================
    // APEXCHARTS
    // ==============================
    let chartInstance = null;

    function renderChart({ categories, series }) {
        const el = document.querySelector('#chart-vendas-mes');
        if (!el || typeof ApexCharts === 'undefined') return;

        if (chartInstance) {
            try { chartInstance.destroy(); } catch (e) { }
            chartInstance = null;
        }

        const options = {
            chart: {
                type: 'bar',
                height: 360,
                toolbar: { show: false },
                animations: { enabled: true }
            },
            series: [{ name: 'Vendido (R$)', data: series }],
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '70%',
                    borderRadius: 4
                }
            },
            // ✅ categorias no X (mesmo com barra horizontal)
            xaxis: {
                categories, // ['Jaqueline Gois', 'Matheus Dile', ...]
                labels: {
                    formatter: (val) => currencyBRL(val)
                }
            },
            // ❌ não defina yaxis.categories
            yaxis: {
                labels: { maxWidth: 240 }
            },
            dataLabels: { enabled: false },
            tooltip: {
                x: { show: true }, // mostra o nome do vendedor
                y: { formatter: (val) => currencyBRL(val) }
            },
            grid: { strokeDashArray: 4 },
            noData: {
                text: 'Sem dados para o mês',
                align: 'center',
                verticalAlign: 'middle'
            }
        };

        chartInstance = new ApexCharts(el, options);
        chartInstance.render();
    }


    function fetchVendasMes() {
        $.ajax({
            url: ENDPOINT_VENDAS_MES,
            method: 'GET',
            dataType: 'json',
            cache: false
        })
            .done(function (res) {
                const payload = Array.isArray(res) ? res : (res?.data || []);
                const normalized = normalizeVendedoresData(payload);
                renderChart(normalized);
            })
            .fail(function (err) {
                console.warn('Erro ao buscar vendas por vendedor no mês:', err);
                renderChart({ categories: [], series: [] });
            });
    }

    // ==============================
    // RANKING (CÓDIGO EXISTENTE)
    // ==============================
    function carregarRanking() {
        $.ajax({
            url: '/rankingVendasData',
            method: 'GET',
            success: function (response) {
                const meta = response.meta;
                const vendedores = response.vendedores;

                const podio = $('#ranking-podio');
                const tbody = $('#ranking-tabela tbody');

                podio.empty();
                tbody.empty();

                if (!meta || !Array.isArray(vendedores) || vendedores.length === 0) {
                    podio.html('<p class="text-muted">Nenhuma venda encontrada para a meta atual.</p>');
                    return;
                }

                if (meta.descricao) {
                    $('.js-meta-nome').text(`🏆 Ranking de Vendas - ${meta.descricao}`);
                }

                vendedores.sort((a, b) => b.total - a.total);

                const medalhas = ['🥇', '🥈', '🥉'];
                const cores = ['warning', 'secondary', 'info'];

                vendedores.slice(0, 3).forEach((v, i) => {
                    const animClass = `animate__animated animate__fadeInUp animate__faster`;
                    const destaque = `
            <div class="col-md-3 d-flex flex-column align-items-center ${animClass}">
              <div class="display-3 mb-2">${medalhas[i]}</div>
              <div class="fw-bold fs-5 text-${cores[i]}">${v.nome}</div>
              <div class="text-muted">R$ ${parseFloat(v.total).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</div>
              <span class="badge bg-label-${cores[i]} mt-2">${i + 1}º Lugar</span>
            </div>
          `;
                    podio.append(destaque);
                });

                const totalVendido = vendedores
                    .filter(v => v.nome !== 'KAIQUE ALBERTIN' && v.nome !== 'LEANDRO ALVES')
                    .reduce((acc, v) => acc + parseFloat(v.total), 0);

                const metaValor = meta.tipo_calculo === 'VALOR'
                    ? parseFloat(meta.valor_meta)
                    : parseFloat(meta.quantidade_meta);

                const falta = Math.max(metaValor - totalVendido, 0);

                $('.js-total-vendido').text(
                    meta.tipo_calculo === 'VALOR'
                        ? `R$ ${totalVendido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                        : `${totalVendido} unidades`
                );

                $('.js-meta-total').text(
                    meta.tipo_calculo === 'VALOR'
                        ? `R$ ${metaValor.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                        : `${metaValor} unidades`
                );

                $('.js-meta-restante').text(
                    meta.tipo_calculo === 'VALOR'
                        ? `R$ ${falta.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                        : `${falta} unidades`
                );

                vendedores.slice(3).forEach((v, index) => {
                    const linha = `
            <tr class="animate__animated animate__fadeInUp animate__faster">
              <td>${index + 4}</td>
              <td>${v.nome}</td>
              <td>R$ ${parseFloat(v.total).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}</td>
            </tr>
          `;
                    tbody.append(linha);
                });
            },
            error: function () {
                toastr.error('Erro ao carregar o ranking de vendas.');
            }
        });
    }

    // ==============================
    // INIT
    // ==============================
    carregarRanking();
    setInterval(carregarRanking, 5000);

    fetchVendasMes();
    setInterval(fetchVendasMes, 60000);
});
