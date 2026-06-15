/**
 * Escola LK Brokers — Liberar acesso de usuários à área do aluno.
 */
'use strict';

(function () {
    const page = document.querySelector('.esc-acessos-page');
    if (!page) return;

    const toggleBase = page.dataset.toggleBase; // .../escola/gestao/acessos
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const toast = window.escolaToast;

    page.querySelectorAll('.esc-toggle-acesso').forEach(input => {
        input.addEventListener('change', () => {
            const id = input.dataset.id;
            const habilitada = input.checked;
            input.disabled = true;

            fetch(`${toggleBase}/${id}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ habilitada: habilitada ? 1 : 0 })
            }).then(async r => {
                const d = await r.json().catch(() => ({}));
                if (r.ok && d.success) {
                    toast('success', habilitada ? 'Acesso liberado' : 'Acesso removido',
                        habilitada ? 'O usuário já pode acessar a Escola.' : 'O usuário não verá mais a Escola.');
                } else {
                    input.checked = !habilitada; // reverte
                    toast('error', 'Ops!', d.message || 'Não foi possível atualizar o acesso.');
                }
            }).catch(() => {
                input.checked = !habilitada;
                toast('error', 'Ops!', 'Não foi possível atualizar o acesso.');
            }).finally(() => {
                input.disabled = false;
            });
        });
    });
})();
