'use strict';

$(function () {
    const tabela = $('#tabela-estudos').DataTable({
        processing: true,
        serverSide: true,
        ajax: '/estudo/getListStudies',
        columns: [
            { data: 'id' }, // ID
            { data: 'titulo' }, // Estudo
            { data: 'user.name' }, // Usuário
            { data: 'created_at' }, // Data criação
            {   // Ações
                data: 'id',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    const url = `${window.location.origin}/visualizar-estudo/${row.link_unico}`;
                    return `
                        <a href="/visualizar-estudo/${row.link_unico}" class="btn btn-sm btn-primary">Ver</a>
                        <button class="btn btn-sm btn-info copy-link" data-url="${url}">Copiar Link</button>
                        <button class="btn btn-sm btn-danger delete-estudo" data-id="${row.id}">Excluir</button>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        pageLength: 10
    });

    // Copiar link
    $(document).on('click', '.copy-link', function () {
        const url = $(this).data('url');
        navigator.clipboard.writeText(url).then(() => {
            toastr.info('Link copiado para a área de transferência!');
        }).catch(err => {
            toastr.error('Erro ao copiar link');
        });
    });

    // Excluir estudo
    $(document).on('click', '.delete-estudo', function () {
        const id = $(this).data('id');

        if (!confirm('Tem certeza que deseja excluir este estudo?')) return;

        $.ajax({
            url: `/estudo-delete/${id}`,
            type: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                toastr.success('Estudo excluído com sucesso!');
                tabela.ajax.reload(null, false); // recarrega sem resetar a paginação
            },
            error: function (err) {
                toastr.error('Erro ao excluir estudo.');
                console.error(err);
            }
        });
    });
});
