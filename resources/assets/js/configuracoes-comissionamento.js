'use strict';

(function () {
    const table = $('#comissionamento-table');

    // helper numérico seguro
    const num = (x) => {
        const n = parseFloat(x);
        return Number.isFinite(n) ? n : 0;
    };

    // Grade badge mapping
    const gradeBadgeClass = (grade) => {
        const g = (grade || '').toUpperCase();
        switch (g) {
            case 'JUNIOR': return 'grade-junior';
            case 'SENIOR': return 'grade-senior';
            case 'ADMIN': return 'grade-admin';
            case 'COMERCIAL': return 'grade-comercial';
            default: return 'grade-junior';
        }
    };

    const dataTable = table.DataTable({
        ajax: {
            url: '/comissionamento/getCommissioning',
            dataSrc: 'data'
        },
        processing: true,
        serverSide: false,
        responsive: true,
        columns: [
            { data: 'id', defaultContent: '—' },

            { data: 'user.name', defaultContent: '—' },

            {
                data: 'percentual',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    if (type === 'display' || type === 'filter') {
                        return `<span class="cell-percent">${value.toFixed(2)} %</span>`;
                    }
                    return value;
                }
            },
            {
                data: 'percentual_angariacao',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    if (type === 'display' || type === 'filter') {
                        return `<span class="cell-percent">${value.toFixed(2)} %</span>`;
                    }
                    return value;
                }
            },
            {
                data: 'imposto',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    if (type === 'display' || type === 'filter') {
                        return `<span class="cell-percent">${value.toFixed(2)} %</span>`;
                    }
                    return value;
                }
            },

            {
                data: 'grade',
                render: (d) => {
                    const s = (d || '').toString().toUpperCase();
                    const label = s ? s.charAt(0) + s.slice(1).toLowerCase() : '—';
                    return s ? `<span class="grade-badge ${gradeBadgeClass(s)}">${label}</span>` : '—';
                }
            },

            {
                data: 'salario',
                className: 'text-end',
                render: (data, type) => {
                    const value = num(data);
                    if (type === 'display' || type === 'filter') {
                        return `<span class="cell-value">${value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</span>`;
                    }
                    return value;
                }
            },
            {
                data: 'periodicidade',
                render: (d) => {
                    const s = (d || '').toString();
                    return s ? `<span class="periodicidade-badge">${s.charAt(0).toUpperCase() + s.slice(1)}</span>` : '—';
                }
            },

            {
                data: null,
                className: 'text-end',
                orderable: false,
                searchable: false,
                render: (_data, _type, row) => `
        <div class="actions-cell">
            <button class="btn-dash btn-primary btn-sm btn-edit-comissao" data-id="${row.id}" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button class="btn-dash btn-danger btn-sm btn-delete-comissao" data-id="${row.id}" title="Excluir">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                    <line x1="10" y1="11" x2="10" y2="17"/>
                    <line x1="14" y1="11" x2="14" y2="17"/>
                </svg>
            </button>
        </div>
      `
            }
        ],
        drawCallback: function () {
            const count = this.api().rows({ search: 'applied' }).count();
            $('#badge-total-count').text(count);
        }
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
        $('#modalCreateComissaoLabel').contents().filter(function() {
            return this.nodeType === 3;
        }).last().replaceWith(' Editar Configuração de Comissão');

        // Preencher campos
        $('#user_id').val(row.user?.id || '').trigger('change');
        $('#percentual').val(num(row.percentual).toFixed(2));
        $('#percentual_angariacao').val(num(row.percentual_angariacao).toFixed(2));
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
        $('#modalCreateComissaoLabel').contents().filter(function() {
            return this.nodeType === 3;
        }).last().replaceWith(' Nova Configuração de Comissão');

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
