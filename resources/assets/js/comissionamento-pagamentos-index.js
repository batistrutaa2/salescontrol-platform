/* global $, toastr, moment */
(function () {
    'use strict';

    const $root = $('#pgmts-root');
    if (!$root.length) return;

    const URL_DATA = $root.data('url');
    const PDF_BASE = String($root.data('pdf-base') || '');       // .../pagamento/PAYMENT_ID/pdf
    const URL_ESTORNO_BASE = String($root.data('estornar-url') || ''); // .../pagamentos/PAYMENT_ID/estornar
    const USER_ROLE = $root.data('role');


    const fmtBRL = (v) => (Number(v) || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const fmtPct = (v) => v == null ? '—' : `${Number(v).toFixed(2)}%`;

    const $mes = $('#filtro-mes');
    const $vend = $('#filtro-vendedor');
    const $creator = $('#filtro-criado-por');
    const $apply = $('#btn-aplicar');

    $vend.select2({ width: '100%', placeholder: 'Todos' });
    $creator.select2({ width: '100%', placeholder: 'Todos' });

    const table = $('#tabela-pagamentos').DataTable({
        processing: true,
        serverSide: false,          // carregaremos tudo e filtraremos via request
        searching: false,
        paging: true,
        info: true,
        responsive: true,
        order: [[3, 'desc']],       // por data_pagamento
        ajax: {
            url: URL_DATA,
            data: function (d) {
                d.mes = $mes.val();
                d.vendedor_id = $vend.val() || '';
                d.created_by = $creator.val() || '';
            }
        },
        columns: [
            { data: 'id' },
            { data: 'vendedor' },
            { data: 'mes' },
            { data: 'data_pagamento', render: d => d ? moment(d).format('DD/MM/YYYY') : '-' },
            { data: 'percentual_comissao', className: 'text-end', render: fmtPct },
            { data: 'percentual_imposto', className: 'text-end', render: fmtPct },
            { data: 'total_bruto', className: 'text-end', render: fmtBRL },
            { data: 'total_imposto', className: 'text-end', render: fmtBRL },
            { data: 'total_liquido', className: 'text-end', render: fmtBRL },
            { data: 'salario', className: 'text-end', render: fmtBRL },
            { data: 'total_receber', className: 'text-end', render: fmtBRL },
            { data: 'criado_por' },
            {
                data: null,
                orderable: false,
                render: function (row) {
                    const pdfUrl = PDF_BASE.replace('PAYMENT_ID', row.id);
                    const estornoUrl = URL_ESTORNO_BASE ? URL_ESTORNO_BASE.replace('PAYMENT_ID', row.id) : null;

                    let buttons = `
                    <div class="btn-group btn-group-sm" role="group">
                        <a class="btn btn-outline-primary" href="${pdfUrl}" target="_blank" title="Abrir PDF">
                        <i class="ri-file-pdf-line"></i>
                        </a>
                    `;

                    // só exibe estorno se não for vendedor
                    if (estornoUrl && USER_ROLE !== 1) {
                        buttons += `
                        <button class="btn btn-outline-danger js-estornar" data-url="${estornoUrl}" title="Estornar">
                        <i class="ri-arrow-go-back-line"></i>
                        </button>
                    `;
                    }

                    buttons += `</div>`;
                    return buttons;
                }
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        },
        drawCallback: function () {
            // somatórios no footer
            const data = table.rows({ search: 'applied' }).data().toArray();
            const sum = (key) => data.reduce((acc, r) => acc + (Number(r[key]) || 0), 0);

            $('#ft-bruto').text(fmtBRL(sum('total_bruto')));
            $('#ft-imp').text(fmtBRL(sum('total_imposto')));
            $('#ft-liq').text(fmtBRL(sum('total_liquido')));
            $('#ft-sal').text(fmtBRL(sum('salario')));
            $('#ft-total').text(fmtBRL(sum('total_receber')));

            // bind dos botões de estorno (se houver)
            $('.js-estornar').off('click').on('click', async function () {
                const url = $(this).data('url');
                if (!url) return;
                if (!confirm('Confirma estornar este pagamento? As vendas vinculadas voltarão a ficar pendentes.')) return;

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    if (!res.ok) {
                        const err = await res.json().catch(() => ({}));
                        throw new Error(err.message || 'Falha ao estornar.');
                    }
                    const json = await res.json();
                    toastr.success(json.message || 'Pagamento estornado.');
                    table.ajax.reload(null, false);
                } catch (e) {
                    console.error(e);
                    toastr.error(e.message || 'Erro ao estornar.');
                }
            });
        }
    });

    $apply.on('click', () => table.ajax.reload());

})();
