'use strict';

(function () {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    const cfg = window.lkBeneficios || {};

    const buildExtraFilters = () => ({
        busca: $('#lkb-filtro-busca').val() || '',
        status: $('#lkb-filtro-status').val() || '',
        tipo: $('#lkb-filtro-tipo').val() || '',
    });

    const statusVariantClass = {
        EM_IMPLANTACAO: 'angariacao',
        ATIVO: 'success',
        SUSPENSO: 'muted',
        CANCELADO: 'muted',
    };

    const table = $('#lkb-tabela-contratos').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'desc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
        dom: '<"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => Object.assign(d, buildExtraFilters()),
        },
        columns: [
            {
                data: 'id',
                render: (id) => `<span class="contract-value muted">#${id}</span>`,
            },
            {
                data: 'nome_cliente',
                render: (nome) => `<span class="contract-name">${nome || '—'}</span>`,
            },
            { data: 'cpf_cnpj' },
            { data: 'produto_nome' },
            {
                data: 'produto_tipo_label',
                render: (d) => `<span class="contract-value muted">${d}</span>`,
            },
            {
                data: null,
                render: (row) => {
                    const variant = statusVariantClass[row.status] || 'muted';
                    return `<span class="contract-value ${variant}" style="font-weight:700;">${row.status_label}</span>`;
                },
            },
            {
                data: 'valor_mensal_fmt',
                className: 'text-end',
                render: (v) => `<span class="contract-value">${v}</span>`,
            },
            { data: 'data_vigencia_fmt' },
            { data: 'corretor_nome' },
            {
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: (id) => {
                    const url = (cfg.showUrlTemplate || '').replace('__ID__', id);
                    return `<a href="${url}" class="btn btn-sm btn-outline-primary" style="border-radius:20px; padding: 0.25rem 0.75rem;"><i class="ri-eye-line"></i></a>`;
                },
            },
        ],
    });

    let debounceTimer;
    $('#lkb-filtro-busca').on('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => table.ajax.reload(), 350);
    });
    $('#lkb-filtro-status, #lkb-filtro-tipo').on('change', () => table.ajax.reload());
})();
