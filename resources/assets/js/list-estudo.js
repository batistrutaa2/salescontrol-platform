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
                    const editUrl = `${window.location.origin}/editar-estudo/${row.id}`;
                    return `
                        <div class="es-actions">
                            <a href="/visualizar-estudo/${row.link_unico}" class="es-btn-action es-btn-view"><i class="ri-eye-line"></i> Ver</a>
                            <a href="${editUrl}" class="es-btn-action es-btn-edit"><i class="ri-pencil-line"></i> Editar</a>
                            <button class="es-btn-action es-btn-copy copy-link" data-url="${url}"><i class="ri-link"></i> Copiar</button>
                            <button class="es-btn-action es-btn-delete delete-estudo" data-id="${row.id}"><i class="ri-delete-bin-line"></i> Excluir</button>
                        </div>
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
