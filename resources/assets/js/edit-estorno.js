// Camada extra para a tela de estorno: reaproveita 100% o JS de novaProposta
// e adiciona apenas (a) restauração das portabilidades antigas e (b) confirmação
// SweetAlert antes de devolver a venda ao backoffice.

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        restoreOldPortabilidades();
        wireConfirmReenvio();
    });

    // ---------------------------------------------------------------
    // Portabilidades — reconstrói os items com os dados antigos.
    // O JS de novaProposta só monta items vazios via change handler,
    // então fazemos isso explicitamente quando há dados pré-existentes.
    // ---------------------------------------------------------------
    function restoreOldPortabilidades() {
        const olds = Array.isArray(window.oldPortabilidades) ? window.oldPortabilidades : [];
        if (!olds.length) return;

        const container = document.getElementById('portabilidade-container');
        const template = document.getElementById('template-portabilidade');
        const qtdInput = document.getElementById('qtd_portabilidade');
        const statusSelect = document.getElementById('portabilidade_status');
        const badge = document.getElementById('portabilidade-badge');
        if (!container || !template) return;

        container.style.display = 'flex';
        container.innerHTML = '';

        olds.forEach(function (port, index) {
            const html = template.content.cloneNode(true).querySelector('.port-item').outerHTML
                .replace(/__PORT_INDEX__/g, index)
                .replace(/__PORT_NUMBER__/g, index + 1);
            container.insertAdjacentHTML('beforeend', html);

            const last = container.lastElementChild;
            const input = last.querySelector('input[name]');
            if (input) input.value = port.nome || '';
        });

        if (qtdInput) {
            qtdInput.value = olds.length;
            qtdInput.style.display = 'block';
        }
        if (statusSelect) statusSelect.value = 'SIM';
        if (badge) {
            badge.classList.remove('badge-inativo');
            badge.classList.add('badge-ativo');
            badge.textContent = 'Ativo';
        }
    }

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
