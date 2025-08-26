'use strict';

(function () {
    const table = $('#comissionamento-table');

    // helper numérico seguro
    const num = (x) => {
        const n = parseFloat(x);
        return Number.isFinite(n) ? n : 0;
    };

    const dataTable = table.DataTable({
        ajax: {
            url: '/comissionamento/getCommissioning',
            // Se o seu controller retorna { data: [...] }, deixe 'data'.
            // Se retorna um array puro [...], troque para dataSrc: ''.
            dataSrc: 'data'
        },
        processing: true,
        serverSide: false,
        responsive: true,
        columns: [
            { data: 'id', defaultContent: '—' },

            // Suporta objeto aninhado: { user: { name: '...' } }
            { data: 'user.name', defaultContent: '—' },

            {
                data: 'percentual',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    return (type === 'display' || type === 'filter')
                        ? `${value.toFixed(2)} %`
                        : value; // para sort
                }
            },
            {
                data: 'imposto',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    return (type === 'display' || type === 'filter')
                        ? `${value.toFixed(2)} %`
                        : value;
                }
            },

            // grade: ex. 'junior' | 'pleno' | 'senior'
            {
                data: 'grade',
                render: (d) => {
                    const s = (d || '').toString();
                    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '—';
                }
            },

            // salário em BRL
            {
                data: 'salario',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    return (type === 'display' || type === 'filter')
                        ? value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
                        : value;
                }
            },
            { data: 'periodicidade' },

            {
                data: null,
                orderable: false,
                searchable: false,
                render: (_data, _type, row) => `
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
