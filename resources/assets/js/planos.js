'use strict';

$(function () {

    // Inicializa DataTable
    let tabelaPlanos = $('#tabela-planos').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/back-office/getPlans', // Rota para buscar todos os planos
            type: 'GET',
            dataSrc: ''
        },
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'operadora', title: 'Operadora' },
            { data: 'nome', title: 'Nome' },
            {
                data: 'status',
                title: 'STATUS',
                render: data => data === 'Y'
                    ? `<span class="badge bg-label-success">Ativo</span>`
                    : `<span class="badge bg-label-secondary">Inativo</span>`
            },
            { data: 'acomodacao', title: 'Acomodação' },
            { data: 'created_at', title: 'Criado em:' },
            {
                data: null,
                title: 'Ações',
                orderable: false,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-sm btn-warning editar-plano" data-id="${row.id}">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button class="btn btn-sm btn-danger deletar-plano" data-id="${row.id}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    `;
                }
            }
        ]
    });

    // Criar novo plano
    $('#formNovoPlano').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            success: function () {
                $('#novoPlanoModal').modal('hide');
                form[0].reset();
                tabelaPlanos.ajax.reload(null, false);
                toastr.success('Plano cadastrado com sucesso!');
            },
            error: function () {
                toastr.error('Erro ao cadastrar plano.');
            }
        });
    });

    // Abrir modal de edição
    $(document).on('click', '.editar-plano', function () {
        let id = $(this).data('id');

        $.get(route('planos.getById', { id: id }), function (plano) {
            $('#editarPlanoModal #plano_id').val(plano.id);
            $('#editarPlanoModal #empresa_id').val(plano.empresa_id);
            $('#editarPlanoModal #operadora_id').val(plano.operadora_id);
            $('#editarPlanoModal #nome').val(plano.nome);
            $('#editarPlanoModal #status').val(plano.status);
            $('#editarPlanoModal #coparticipacao').val(plano.coparticipacao);
            $('#editarPlanoModal #acomodacao').val(plano.acomodacao);
            $('#editarPlanoModal').modal('show');
        });
    });

    // Salvar edição de plano
    $('#formEditarPlano').on('submit', function (e) {
        e.preventDefault();
        let form = $(this);
        let id = $('#editarPlanoModal #plano_id').val();

        $.ajax({
            url: route('planos.update', { id: id }),
            method: 'PUT',
            data: form.serialize(),
            success: function () {
                $('#editarPlanoModal').modal('hide');
                tabelaPlanos.ajax.reload(null, false);
                toastr.success('Plano atualizado com sucesso!');
            },
            error: function () {
                toastr.error('Erro ao atualizar plano.');
            }
        });
    });

    // Deletar plano
    $(document).on('click', '.deletar-plano', function () {
        if (!confirm('Tem certeza que deseja excluir este plano?')) return;

        let id = $(this).data('id');

        $.ajax({
            url: route('planos.delete', { id: id }),
            method: 'DELETE',
            success: function () {
                tabelaPlanos.ajax.reload(null, false);
                toastr.success('Plano removido com sucesso!');
            },
            error: function () {
                toastr.error('Erro ao remover plano.');
            }
        });
    });

});
