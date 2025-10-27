'use strict';

$(function () {

    // ==============================
    // CONFIG / ENDPOINTS
    // ==============================
    const ENDPOINT_VENDAS_MES = '/vendas/valores-mensais';

    // ==============================
    // HELPERS
    // ==============================
    const currencyBRL = (n) =>
        (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(n || 0)));

    // Gera iniciais do nome
    function getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    // Cores para avatares
    function getAvatarColor(index) {
        const colors = ['primary', 'success', 'danger', 'warning', 'info', 'secondary'];
        return colors[index % colors.length];
    }

    // Determina cor baseada no progresso
    function getProgressColor(percent) {
        if (percent >= 100) return 'success';
        if (percent >= 70) return 'warning';
        return 'danger';
    }

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

        // Configurar cores baseadas no tema
        let cardColor, labelColor, headingColor, borderColor;
        if (typeof isDarkStyle !== 'undefined' && isDarkStyle) {
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

        const options = {
            chart: {
                type: 'bar',
                height: 380,
                toolbar: { show: false },
                animations: { enabled: true },
                background: cardColor
            },
            colors: ['#7367F0'], // Cor primária
            series: [{ name: 'Vendido (R$)', data: series }],
            plotOptions: {
                bar: {
                    horizontal: true,
                    barHeight: '65%',
                    borderRadius: 8,
                    distributed: false
                }
            },
            xaxis: {
                categories,
                labels: {
                    formatter: (val) => currencyBRL(val),
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    maxWidth: 240,
                    style: {
                        colors: labelColor,
                        fontSize: '13px'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: (val) => currencyBRL(val),
                style: {
                    colors: ['#fff'],
                    fontSize: '11px'
                },
                offsetX: 10
            },
            tooltip: {
                enabled: true,
                theme: isDarkStyle ? 'dark' : 'light',
                x: { show: true },
                y: { formatter: (val) => currencyBRL(val) }
            },
            grid: {
                borderColor: borderColor,
                strokeDashArray: 4,
                padding: {
                    top: -20,
                    bottom: -10,
                    left: 10,
                    right: 10
                }
            },
            legend: {
                labels: {
                    colors: labelColor
                }
            },
            noData: {
                text: 'Sem dados para exibir',
                align: 'center',
                verticalAlign: 'middle',
                style: {
                    color: labelColor,
                    fontSize: '14px'
                }
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
    // RANKING
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
                    podio.html('<div class="col-12 text-center"><p class="text-muted">Nenhuma venda encontrada para a meta atual.</p></div>');
                    return;
                }

                if (meta.descricao) {
                    $('.js-meta-nome').text(`🏆 Ranking de Vendas - ${meta.descricao}`);
                }

                vendedores.sort((a, b) => b.total - a.total);

                // Calcular totais
                const totalVendido = vendedores
                    .filter(v => v.nome !== 'KAIQUE ALBERTIN' && v.nome !== 'LEANDRO ALVES')
                    .reduce((acc, v) => acc + parseFloat(v.total), 0);

                const metaValor = meta.tipo_calculo === 'VALOR'
                    ? parseFloat(meta.valor_meta)
                    : parseFloat(meta.quantidade_meta);

                const falta = Math.max(metaValor - totalVendido, 0);
                const progresso = metaValor > 0 ? Math.min((totalVendido / metaValor) * 100, 100) : 0;

                // Atualizar KPIs
                $('.js-total-vendido').text(
                    meta.tipo_calculo === 'VALOR'
                        ? currencyBRL(totalVendido)
                        : `${Math.floor(totalVendido)} unidades`
                );

                $('.js-meta-total').text(
                    meta.tipo_calculo === 'VALOR'
                        ? currencyBRL(metaValor)
                        : `${Math.floor(metaValor)} unidades`
                );

                $('.js-meta-restante').text(
                    meta.tipo_calculo === 'VALOR'
                        ? currencyBRL(falta)
                        : `${Math.floor(falta)} unidades`
                );

                // Cor dinâmica para "Falta Para Atingir"
                const progressColor = getProgressColor(progresso);
                $('.js-meta-restante-color').removeClass('text-success text-warning text-danger')
                    .addClass(`text-${progressColor}`);

                // Atualizar progresso
                $('.js-progresso-percent').text(`${progresso.toFixed(1)}%`);
                $('.js-progresso-bar')
                    .css('width', `${progresso}%`)
                    .attr('aria-valuenow', progresso)
                    .removeClass('bg-success bg-warning bg-danger')
                    .addClass(`bg-${progressColor}`);

                // Renderizar pódio melhorado
                const medalhas = ['🥇', '🥈', '🥉'];
                const coresPodio = ['warning', 'secondary', 'info'];
                const cardSizes = ['col-lg-4', 'col-lg-3', 'col-lg-3']; // 1º lugar maior

                vendedores.slice(0, 3).forEach((v, i) => {
                    const iniciais = getInitials(v.nome);
                    const percentMeta = metaValor > 0 ? ((parseFloat(v.total) / metaValor) * 100).toFixed(1) : 0;

                    const podioCard = `
                        <div class="${cardSizes[i]} col-md-6">
                            <div class="card text-center h-100 border-${coresPodio[i]} animate__animated animate__zoomIn">
                                <div class="card-body">
                                    <div class="mb-3 display-3">${medalhas[i]}</div>
                                    <div class="avatar avatar-lg mx-auto mb-3">
                                        <span class="avatar-initial rounded-circle bg-label-${coresPodio[i]} fs-4">
                                            ${iniciais}
                                        </span>
                                    </div>
                                    <h5 class="mb-1">${v.nome}</h5>
                                    <p class="text-muted mb-2">${i + 1}º Lugar</p>
                                    <h4 class="text-${coresPodio[i]} mb-2">
                                        ${meta.tipo_calculo === 'VALOR' ? currencyBRL(v.total) : Math.floor(v.total) + ' un.'}
                                    </h4>
                                    <small class="text-muted">${percentMeta}% da meta</small>
                                </div>
                            </div>
                        </div>
                    `;
                    podio.append(podioCard);
                });

                // Renderizar tabela melhorada
                vendedores.slice(3).forEach((v, index) => {
                    const posicao = index + 4;
                    const iniciais = getInitials(v.nome);
                    const colorClass = getAvatarColor(index);
                    const percentMeta = metaValor > 0 ? ((parseFloat(v.total) / metaValor) * 100).toFixed(1) : 0;
                    const progressColor = getProgressColor(percentMeta);

                    const linha = `
                        <tr class="animate__animated animate__fadeIn">
                            <td>
                                <span class="badge bg-label-secondary rounded-pill">${posicao}º</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        <span class="avatar-initial rounded-circle bg-label-${colorClass}">
                                            ${iniciais}
                                        </span>
                                    </div>
                                    <span class="fw-medium">${v.nome}</span>
                                </div>
                            </td>
                            <td class="text-end fw-semibold">
                                ${meta.tipo_calculo === 'VALOR' ? currencyBRL(v.total) : Math.floor(v.total) + ' un.'}
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-3" style="height: 8px;">
                                        <div class="progress-bar bg-${progressColor}"
                                             role="progressbar"
                                             style="width: ${Math.min(percentMeta, 100)}%"
                                             aria-valuenow="${percentMeta}"
                                             aria-valuemin="0"
                                             aria-valuemax="100">
                                        </div>
                                    </div>
                                    <small class="text-muted" style="min-width: 45px;">${percentMeta}%</small>
                                </div>
                            </td>
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
