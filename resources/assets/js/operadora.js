'use strict';

$(function () {
    // Inicializa DataTable
    let tabelaOperadoras = $('#tabela-operadoras').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/back-office/getOperators',
            type: 'GET',
            dataSrc: ''
        },
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'nome', title: 'Nome da Operadora' },
            {
                data: 'status',
                render: function (data) {
                    if (data === 'Y') {
                        return `<span class="badge bg-label-success">ATIVO</span>`;
                    } else {
                        return `<span class="badge bg-label-secondary">INATIVO</span>`;
                    }
                }
            },
            { data: 'created_at' }
        ]
    });

    // Recarrega tabela após criar nova operadora
    $('#formNovaOperadora').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function () {
                $('#novaOperadoraModal').modal('hide');
                form[0].reset();
                tabelaOperadoras.ajax.reload(null, false); // recarrega sem perder paginação
                toastr.success('Operadora cadastrada com sucesso!');
            },
            error: function () {
                toastr.error('Erro ao cadastrar operadora.');
            }
        });
    });
});
