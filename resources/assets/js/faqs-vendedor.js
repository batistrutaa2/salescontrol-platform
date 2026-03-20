'use strict';

$(function () {
    // Filtro por operadora
    $('#filtro-operadora').on('change', function () {
        let operadoraId = $(this).val();
        if (operadoraId) {
            $('.faq-operadora-group').hide();
            $('.faq-operadora-group[data-operadora="' + operadoraId + '"]').show();
        } else {
            $('.faq-operadora-group').show();
        }
    });

    // Busca por texto
    $('#busca-faq').on('keyup', function () {
        let termo = $(this).val().toLowerCase();
        if (termo.length === 0) {
            $('.faq-item').show();
            $('.faq-operadora-group').show();
            return;
        }

        $('.faq-item').each(function () {
            let titulo = $(this).find('.faq-titulo').text().toLowerCase();
            let resposta = $(this).find('.faq-resposta').text().toLowerCase();
            if (titulo.includes(termo) || resposta.includes(termo)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Esconde grupos sem itens visíveis
        $('.faq-operadora-group').each(function () {
            let visiveis = $(this).find('.faq-item:visible').length;
            if (visiveis === 0) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    });
});
