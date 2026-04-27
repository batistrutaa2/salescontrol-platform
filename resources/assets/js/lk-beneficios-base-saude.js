'use strict';

(function () {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    const cfg = window.lkbBaseSaude || {};

    const buildFiltros = () => ({
        busca: $('#lkb-bs-busca').val() || '',
        ocultar_com_lead: $('#lkb-bs-ocultar-leads').is(':checked') ? 1 : 0,
        ocultar_com_contrato: $('#lkb-bs-ocultar-contratos').is(':checked') ? 1 : 0,
    });

    const table = $('#lkb-tabela-base-saude').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'asc']],
        ordering: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
        dom: '<"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => Object.assign(d, buildFiltros()),
        },
        columns: [
            { data: 'cpf_cnpj' },
            { data: 'nome', render: (v) => `<span class="contract-name">${v || '—'}</span>` },
            { data: 'qtd_contratos', className: 'text-center' },
            { data: 'operadoras' },
            { data: 'primeira_implantacao_fmt' },
            { data: 'ultima_implantacao_fmt' },
            {
                data: null,
                render: (row) => {
                    if (row.tem_contrato) return '<span class="contract-value success" style="font-weight:700">Já é cliente</span>';
                    if (row.tem_lead) return '<span class="contract-value angariacao" style="font-weight:700">Lead ativo</span>';
                    return '<span class="contract-value" style="font-weight:700; color: var(--dash-primary);">Disponível</span>';
                },
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: (row) => `<button class="btn btn-sm btn-primary lkb-bs-btn-pegar" data-cpf="${row.cpf_cnpj}" data-nome="${row.nome || ''}" style="border-radius:20px; padding: 0.25rem 0.75rem;">Pegar Lead</button>`,
            },
        ],
    });

    let debounceTimer;
    $('#lkb-bs-busca').on('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => table.ajax.reload(), 350);
    });
    $('#lkb-bs-ocultar-leads, #lkb-bs-ocultar-contratos').on('change', () => table.ajax.reload());

    $(document).on('click', '.lkb-bs-btn-pegar', function () {
        const cpf = $(this).data('cpf');
        const nome = $(this).data('nome');
        $('#lkb-bs-cpf-cnpj').val(cpf);
        $('#lkb-bs-cliente-label').text(`${nome || '—'} · ${cpf}`);
        $('#lkb-bs-produto').val('');
        new bootstrap.Modal(document.getElementById('lkb-modal-pegar')).show();
    });

    $('#lkb-bs-confirmar').on('click', async function () {
        const cpf = $('#lkb-bs-cpf-cnpj').val();
        const produtoId = $('#lkb-bs-produto').val();
        if (!cpf || !produtoId) {
            alert('Selecione o produto de interesse.');
            return;
        }

        this.disabled = true;
        try {
            const resp = await fetch(cfg.pegarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ cpf_cnpj: cpf, produto_id: produtoId }),
            });
            const body = await resp.json();
            if (!resp.ok) {
                alert(body.error || 'Falha ao criar lead.');
                return;
            }
            window.location.href = cfg.kanbanUrl;
        } finally {
            this.disabled = false;
        }
    });
})();
