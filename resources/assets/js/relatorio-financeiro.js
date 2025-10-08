'use strict';

$(function () {
    // 📅 Datepicker no padrão BR
    $('.datepicker').flatpickr({ dateFormat: "d/m/Y" });

    let table = $('#relatorioTable').DataTable({
        paging: true,
        searching: false,
        info: false,
        columns: [
            { data: 'operadora' },
            {
                data: 'previsto',
                render: val => 'R$ ' + Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            },
            {
                data: 'recebido',
                render: val => 'R$ ' + Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            },
            {
                data: 'aberto',
                render: val => 'R$ ' + Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })
            }
        ]
    });

    function carregarDados() {
        $.ajax({
            url: '/financeiro/recebiveis/relatorio-financeiro/fetch',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                data_inicial: $('#data_inicial').val(),
                data_final: $('#data_final').val(),
                operadora_id: $('#operadora_id').val()
            },
            success: function (response) {
                $('#valorPrevisto').text('R$ ' + Number(response.resumo.previsto).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                $('#valorRecebido').text('R$ ' + Number(response.resumo.recebido).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));
                $('#valorAberto').text('R$ ' + Number(response.resumo.aberto).toLocaleString('pt-BR', { minimumFractionDigits: 2 }));

                table.clear().rows.add(response.porOperadora).draw();
            }
        });
    }

    $('#btnFiltrar').on('click', carregarDados);

    // Carregar inicial
    carregarDados();
});
