'use strict';

$(function () {
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

                // Atualiza título com nome/descrição da meta
                if (meta.descricao) {
                    $('.js-meta-nome').text(`🏆 Ranking de Vendas - ${meta.descricao}`);
                }

                // Ordenar vendedores por total vendido
                vendedores.sort((a, b) => b.total - a.total);

                const medalhas = ['🥇', '🥈', '🥉'];
                const cores = ['warning', 'secondary', 'info'];

                // Top 3 (pódio)
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

                // Total vendido por todos os vendedores
                const totalVendido = vendedores
                    .filter(v => v.nome !== 'Kaique Albertin' && v.nome !== 'Leandro Alves')
                    .reduce((acc, v) => acc + parseFloat(v.total), 0);

                // Valor da meta (dependendo do tipo de cálculo)
                const metaValor = meta.tipo_calculo === 'VALOR'
                    ? parseFloat(meta.valor_meta)
                    : parseFloat(meta.quantidade_meta);

                const falta = Math.max(metaValor - totalVendido, 0);

                // Atualizar cards de meta
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

                // Demais vendedores (a partir do 4º)
                vendedores.slice(3).forEach((v, index) => {
                    const progresso = Math.round((v.total / metaValor) * 100);
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

    // Primeira execução
    carregarRanking();

    // Atualização a cada 5 segundos
    setInterval(carregarRanking, 5000);
});
