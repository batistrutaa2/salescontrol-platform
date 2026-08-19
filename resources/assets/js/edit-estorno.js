// Camada extra para a tela de estorno: reaproveita 100% o JS de novaProposta
// e adiciona a confirmação SweetAlert antes de devolver a venda ao backoffice.

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        wireConfirmReenvio();
    });

    // ---------------------------------------------------------------
    // Submit: confirma com o vendedor antes de devolver ao backoffice.
    // O listener original de novaProposta já valida operadora/titulares;
    // este aqui roda DEPOIS apenas para perguntar "Confirma reenvio?".
    // ---------------------------------------------------------------
    function wireConfirmReenvio() {
        const form = document.getElementById('formNovaProposta');
        const btn = document.getElementById('btn-reenviar');
        if (!form || form.dataset.modo !== 'estorno') return;

        let confirmed = false;

        form.addEventListener('submit', function (event) {
            // novaProposta.js pode ter chamado preventDefault em validação prévia.
            if (event.defaultPrevented) return;
            if (confirmed) return;

            event.preventDefault();
            if (typeof window.Swal === 'undefined') {
                confirmed = true;
                form.submit();
                return;
            }

            window.Swal.fire({
                icon: 'question',
                title: 'Reenviar para o backoffice?',
                html: 'A venda voltará para a fila do backoffice em status <strong>VENDA</strong> com todas as suas correções.',
                showCancelButton: true,
                confirmButtonText: 'Sim, reenviar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#7C3AED',
                reverseButtons: true,
            }).then(function (result) {
                if (result.isConfirmed) {
                    confirmed = true;
                    if (btn) {
                        btn.disabled = true;
                        const span = btn.querySelector('span');
                        if (span) span.textContent = 'Reenviando...';
                    }
                    form.submit();
                }
            });
        });
    }
})();
