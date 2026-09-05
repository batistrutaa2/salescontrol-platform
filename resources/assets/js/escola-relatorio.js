/**
 * Academia Comercial — Relatório de progresso.
 */
'use strict';

(function () {
    const page = document.querySelector('.esc-relatorio-page');
    if (!page || typeof $ === 'undefined') return;

    const url = page.dataset.url;

    $('.esc-select2').select2({ allowClear: false });

    const tabela = $('#tabela-relatorio').DataTable({
        processing: true,
        ajax: {
            url: url,
            data: function (d) {
                d.modulo_id = $('#filtro-modulo').val();
                d.user_id = $('#filtro-vendedor').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'nome' },
            { data: 'concluidas' },
            { data: 'iniciadas' },
            { data: 'total_aulas' },
            {
                data: 'percentual',
                render: function (v) {
                    return `<div class="esc-progress esc-progress-inline">
                        <div class="esc-progress-bar" style="width:${v}%"></div>
                    </div><span class="small ms-2">${v}%</span>`;
                }
            },
            { data: 'ultima_atividade' }
        ],
        order: [[4, 'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        }
    });

    $('#filtro-modulo, #filtro-vendedor').on('change', () => tabela.ajax.reload());
})();
