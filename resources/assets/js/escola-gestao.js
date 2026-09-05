/**
 * Academia Comercial — Gestão de módulos.
 */
'use strict';

(function () {
    const page = document.querySelector('.esc-gestao-page');
    if (!page) return;

    const storeUrl = page.dataset.storeUrl;
    const updateBase = page.dataset.updateUrl; // .../escola/gestao/modulos
    const settingsUrl = page.dataset.settingsUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const modalEl = document.getElementById('modal-modulo');
    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('form-modulo');
    const titleEl = modalEl.querySelector('.esc-modal-title');

    const toast = window.escolaToast;
    const settingsForm = document.getElementById('form-escola-configuracoes');

    settingsForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const button = settingsForm.querySelector('button[type="submit"]');
        const status = document.getElementById('escola-configuracoes-status');
        const input = document.getElementById('escola-percentual-conclusao');
        button.disabled = true;
        status.textContent = 'Salvando…';

        try {
            const response = await fetch(settingsUrl, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ escola_percentual_conclusao: Number(input.value) })
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Revise o percentual informado.');
            }

            input.value = data.escola_percentual_conclusao;
            status.textContent = 'Critério salvo para esta empresa.';
            toast('success', 'Critério atualizado', 'As próximas conclusões usarão este percentual.');
        } catch (error) {
            status.textContent = error.message || 'Não foi possível salvar. Tente novamente.';
            toast('error', 'Não foi possível salvar', status.textContent);
        } finally {
            button.disabled = false;
        }
    });

    function abrirNovo() {
        form.reset();
        document.getElementById('modulo-id').value = '';
        document.getElementById('modulo-ativo').checked = true;
        titleEl.textContent = 'Novo módulo';
        modal.show();
    }

    function abrirEdicao(tr) {
        form.reset();
        document.getElementById('modulo-id').value = tr.dataset.id;
        document.getElementById('modulo-titulo').value = tr.dataset.titulo || '';
        document.getElementById('modulo-descricao').value = tr.dataset.descricao || '';
        document.getElementById('modulo-ordem').value = tr.dataset.ordem || 0;
        document.getElementById('modulo-ativo').checked = tr.dataset.ativo === '1';
        titleEl.textContent = 'Editar módulo';
        modal.show();
    }

    document.getElementById('btn-novo-modulo')?.addEventListener('click', abrirNovo);

    page.querySelectorAll('.btn-editar-modulo').forEach(btn => {
        btn.addEventListener('click', () => abrirEdicao(btn.closest('tr')));
    });

    page.querySelectorAll('.btn-excluir-modulo').forEach(btn => {
        btn.addEventListener('click', () => {
            const tr = btn.closest('tr');
            Swal.fire({
                title: 'Excluir módulo?',
                text: 'Todas as aulas, vídeos e materiais deste módulo serão removidos.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' },
                buttonsStyling: false
            }).then(res => {
                if (!res.isConfirmed) return;
                fetch(`${updateBase}/${tr.dataset.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                }).then(r => r.json()).then(d => {
                    if (d.success) { tr.remove(); toast('success', 'Pronto!', 'Módulo excluído com sucesso.'); }
                    else toast('error', 'Ops!', 'Não foi possível excluir o módulo.');
                }).catch(() => toast('error', 'Ops!', 'Não foi possível excluir o módulo.'));
            });
        });
    });

    form.addEventListener('submit', e => {
        e.preventDefault();
        const id = document.getElementById('modulo-id').value;
        const fd = new FormData(form);
        fd.set('ativo', document.getElementById('modulo-ativo').checked ? 1 : 0);

        let url = storeUrl;
        if (id) {
            url = `${updateBase}/${id}`;
            fd.append('_method', 'PUT'); // method spoofing p/ upload de capa
        }

        fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: fd
        }).then(async r => {
            const d = await r.json().catch(() => ({}));
            if (r.ok && d.success) {
                toast('success', 'Pronto!', 'Módulo salvo com sucesso.');
                setTimeout(() => window.location.reload(), 800);
            } else {
                toast('error', 'Verifique os dados', d.message || 'Revise os campos e tente novamente.');
            }
        }).catch(() => toast('error', 'Ops!', 'Erro ao salvar o módulo.'));
    });
})();
