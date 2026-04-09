'use strict';

$(function () {

    function toggleCamposMeta(tipoCalculo) {
        if (tipoCalculo === 'VALOR') {
            $('[name="valor_meta"]').closest('.col-md-6').show();
            $('[name="quantidade_meta"]').closest('.col-md-6').hide();
        } else if (tipoCalculo === 'QUANTIDADE') {
            $('[name="quantidade_meta"]').closest('.col-md-6').show();
            $('[name="valor_meta"]').closest('.col-md-6').hide();
        } else {
            $('[name="valor_meta"], [name="quantidade_meta"]').closest('.col-md-6').hide();
        }
    }

    const selectTipo = $('[name="tipo_calculo"]');
    toggleCamposMeta(selectTipo.val()); // inicial

    selectTipo.on('change', function () {
        toggleCamposMeta($(this).val());
    });


    const tabelaMetas = $('#metas-table');

    if (tabelaMetas.length) {
        tabelaMetas.DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: true,
            ordering: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            columnDefs: [
                {
                    targets: -1, // Ações
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ]
        });
    }
});
