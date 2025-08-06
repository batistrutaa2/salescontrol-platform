'use strict';

(function () {
    const tabela = $('#comissao-faturamento-table');
    let dataTable;
    let dadosAtuais = [];

    function atualizarTotais(dados) {
        const totalVendido = dados.reduce((acc, item) => acc + parseFloat(item.total_implantado), 0);
        const totalComissionado = dados.reduce((acc, item) => acc + parseFloat(item.comissao), 0);

        $('#card-total-vendido').text(`R$ ${totalVendido.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`);
        $('#card-total-comissionado').text(`R$ ${totalComissionado.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`);
    }

    function calcularComissao(valor, percentual) {
        return (valor * percentual / 100).toFixed(2);
    }

    function loadFaturamentoData(periodo) {
        $.ajax({
            url: '/comissionamento/faturamento',
            type: 'GET',
            data: { periodo: periodo },
            success: function (response) {
                dadosAtuais = response.data;
                atualizarTotais(dadosAtuais);

                if (dataTable) {
                    dataTable.clear().rows.add(dadosAtuais).draw();
                } else {
                    dataTable = tabela.DataTable({
                        data: dadosAtuais,
                        columns: [
                            { data: 'vendedor' },
                            {
                                data: 'total_implantado',
                                render: data => `R$ ${parseFloat(data).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                            },
                            {
                                data: 'percentual',
                                render: data => `${parseFloat(data).toFixed(2)} %`
                            },
                            {
                                data: 'comissao',
                                render: data => `R$ ${parseFloat(data).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`
                            },
                            {
                                data: null,
                                orderable: false,
                                render: row => `
                                    <button class="btn btn-sm btn-success btn-faturar" data-id="${row.user_id}">
                                        <i class="ri-check-double-line"></i> Faturar
                                    </button>
                                `
                            }
                        ]
                    });
                }
            },
            error: function () {
                toastr.error('Erro ao carregar dados de faturamento.');
            }
        });
    }

    // Carrega ao abrir a tela
    const periodoAtual = $('#filtroPeriodo').val();
    loadFaturamentoData(periodoAtual);

    // Atualiza ao mudar o filtro
    $('#filtroPeriodo').on('change', function () {
        const periodo = $(this).val();
        loadFaturamentoData(periodo);
    });

    // Evento de clique em "Faturar"
    $(document).on('click', '.btn-faturar', function () {
        const id = $(this).data('id');
        const vendedor = dadosAtuais.find(v => v.user_id == id);
        if (!vendedor) return;

        const valorComissao = parseFloat(vendedor.comissao).toFixed(2);
        const mesRef = $('#filtroPeriodo').val(); // yyyy-mm
        const [ano, mes] = mesRef.split('-');
        const dataFormatada = `${mes}/${ano}`;

        $('#faturar-vendedor').text(vendedor.vendedor);
        $('#faturar-periodo').text(dataFormatada);
        $('#faturar-vendido').text(`R$ ${parseFloat(vendedor.total_implantado).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`);
        $('#faturar-percentual').text(`${parseFloat(vendedor.percentual).toFixed(2)} %`);
        $('#faturar-comissao').text(`R$ ${valorComissao.replace('.', ',')}`);

        $('#faturar-user-id').val(vendedor.user_id);
        $('#faturar-valor').val(valorComissao);
        $('#modalFaturar').modal('show');
    });

    // Envio (mock) do faturamento
    $('#formFaturarComissao').on('submit', function (e) {
        e.preventDefault();

        const userId = $('#faturar-user-id').val();
        const valor = $('#faturar-valor').val();
        const periodo = $('#filtroPeriodo').val();

        // TODO: Enviar para backend futuramente
        toastr.success(`Comissão de R$ ${valor.replace('.', ',')} faturada com sucesso para o usuário ${userId} (${periodo})`);
        $('#modalFaturar').modal('hide');
    });
})();
