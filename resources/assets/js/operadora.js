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
                title: 'Status Atual',
                render: function (data) {
                    if (data === 'Y') {
                        return `<span class="badge bg-label-success">Ativo</span>`;
                    } else {
                        return `<span class="badge bg-label-secondary">Inativo</span>`;
                    }
                }
            },
            { data: 'created_at' },
            {
                data: null,
                title: 'Ações',
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-warning me-1 editar-operadora" data-id="${row.id}">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deletar-operadora" data-id="${row.id}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    `;
                }
            }
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
