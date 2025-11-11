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
        <button class="btn btn-sm btn-primary btn-edit-comissao me-1" data-id="${row.id}" title="Editar">
          <i class="ri-edit-line"></i>
        </button>
        <button class="btn btn-sm btn-danger btn-delete-comissao" data-id="${row.id}" title="Excluir">
          <i class="ri-delete-bin-line"></i>
        </button>
      `
            }
        ]
    });


    $('#formComissao').on('submit', function (e) {
        e.preventDefault();

        const form = $(this);
        const isEditing = form.data('editing') === true;
        let formData = form.serialize();

        // Se for edição, adicionar _method=PUT para method spoofing do Laravel
        if (isEditing) {
            formData += '&_method=PUT';
        }

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            success: function (res) {
                $('#modalCreateComissao').modal('hide');
                form[0].reset();
                $('.select2').val(null).trigger('change');

                // Limpar flags de edição
                form.removeData('editing');
                form.removeData('edit-id');

                const message = isEditing
                    ? 'Comissão atualizada com sucesso!'
                    : 'Comissão cadastrada com sucesso!';
                toastr.success(message);
                dataTable.ajax.reload();
            },
            error: function (xhr) {
                if (xhr.responseJSON?.errors) {
                    Object.values(xhr.responseJSON.errors).forEach((error) => {
                        toastr.error(error);
                    });
                } else {
                    const message = isEditing
                        ? 'Erro ao atualizar a comissão.'
                        : 'Erro ao cadastrar a comissão.';
                    toastr.error(message);
                }
            }
        });
    });

    // Editar configuração
    $(document).on('click', '.btn-edit-comissao', function () {
        const id = $(this).data('id');
        const row = dataTable.row($(this).closest('tr')).data();

        if (!row) {
            toastr.error('Erro ao carregar dados da configuração.');
            return;
        }

        // Preencher o modal com os dados
        $('#formComissao').attr('action', `/comissionamento/${id}`);
        $('#formComissao').data('editing', true);
        $('#formComissao').data('edit-id', id);

        // Atualizar título do modal
        $('#modalCreateComissaoLabel').text('Editar Configuração de Comissão');

        // Preencher campos
        $('#user_id').val(row.user?.id || '').trigger('change');
        $('#percentual').val(num(row.percentual).toFixed(2));
        $('#imposto').val(num(row.imposto).toFixed(2));
        $('#grade').val((row.grade || '').toUpperCase());

        // Formatar salário para o formato brasileiro
        const salarioFormatado = num(row.salario).toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        $('#salario').val(salarioFormatado);

        $('#periodicidade').val(row.periodicidade || 'mensal');

        // Abrir modal
        $('#modalCreateComissao').modal('show');
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

    // Máscara monetária para o campo salário
    if (typeof Cleave !== 'undefined') {
        new Cleave('#salario', {
            numeral: true,
            numeralDecimalMark: ',',
            delimiter: '.',
            numeralDecimalScale: 2,
            numeralPositiveOnly: true,
            prefix: '',
            rawValueTrimPrefix: true
        });
    }

    // Resetar modal ao abrir para criar nova configuração
    $('[data-bs-target="#modalCreateComissao"]').on('click', function () {
        const form = $('#formComissao');

        // Resetar form
        form[0].reset();
        form.attr('action', '/comissionamento');
        form.removeData('editing');
        form.removeData('edit-id');

        // Resetar título
        $('#modalCreateComissaoLabel').text('Nova Configuração de Comissão');

        // Resetar select2
        $('#user_id').val('').trigger('change');
    });

    // Confirmação de exclusão (opcional)
    $(document).on('submit', '.delete-form', function (e) {
        e.preventDefault();
        if (confirm('Deseja realmente excluir essa configuração?')) {
            this.submit();
        }
    });
})();
