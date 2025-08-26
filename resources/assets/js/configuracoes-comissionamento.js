'use strict';

(function () {
    const table = $('#comissionamento-table');

    const dataTable = table.DataTable({
        ajax: '/comissionamento/getCommissioning',
        processing: true,
        serverSide: false,
        responsive: true,
        columns: [
            { data: 'id' },
            { data: 'user.name', defaultContent: '—' },
            {
                data: 'percentual',
                render: (data) => parseFloat(data).toFixed(2) + ' %'
            },
            {
                data: 'periodicidade',
                render: (data) => data.charAt(0).toUpperCase() + data.slice(1)
            },
            {
                data: null,
                orderable: false,
                render: (data, type, row) => `
        <button class="btn btn-sm btn-danger btn-delete-comissao" data-id="${row.id}">
            <i class="ri-delete-bin-line"></i>
        </button>
    `
            }

        ]
    });

    $('#formComissao').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const formData = form.serialize();

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            success: function (res) {
                $('#modalCreateComissao').modal('hide');
                form[0].reset();
                $('.select2').val(null).trigger('change');

                toastr.success('Comissão cadastrada com sucesso!');
                dataTable.ajax.reload();
            },
            error: function (xhr) {
                if (xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach((error) => {
                        toastr.error(error);
                    });
                } else {
                    toastr.error('Erro ao cadastrar a comissão.');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-comissao', function () {
        const id = $(this).data('id');
        const url = `/comissionamento/${id}`;
        const token = $('meta[name="csrf-token"]').attr('content');

        if (confirm('Deseja realmente excluir esta configuração?')) {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: token,
                    _method: 'DELETE'
                },
                success: function (res) {
                    toastr.success(res.message || 'Configuração excluída com sucesso!');
                    $('#comissionamento-table').DataTable().ajax.reload();
                },
                error: function () {
                    toastr.error('Erro ao excluir a configuração.');
                }
            });
        }
    });



    // Select2 reinicializado dentro do modal
    $('.select2').select2({
        dropdownParent: $('#modalCreateComissao'),
        width: '100%'
    });

    // Confirmação de exclusão (opcional)
    $(document).on('submit', '.delete-form', function (e) {
        e.preventDefault();
        if (confirm('Deseja realmente excluir essa configuração?')) {
            this.submit();
        }
    });
})();
