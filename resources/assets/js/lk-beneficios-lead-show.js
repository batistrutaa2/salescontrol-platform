'use strict';

(function () {
    const cfg = window.lkbLeadShow || {};
    if (!cfg.urls) return;

    const escapar = (s) => {
        const div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    };

    const buildUrl = (template, id) => template.replace('__CID__', id);

    // -------------------------------------------------------------------------
    // Toast
    // -------------------------------------------------------------------------
    const SVG = {
        success: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        error: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        info: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        close: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    };

    const toast = (type, title, message) => {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2800,
            timerProgressBar: true,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            html: `
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-icon">${SVG[type] || SVG.info}</div>
                    <div class="toast-content">
                        <div class="toast-title">${escapar(title)}</div>
                        <div class="toast-message">${escapar(message || '')}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">${SVG.close}</div>
                </div>
            `,
            customClass: { popup: 'custom-toast-popup' },
        });
    };

    // -------------------------------------------------------------------------
    // Fetch helper
    // -------------------------------------------------------------------------
    const reqJson = async (url, method, body = null) => {
        const opts = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': cfg.csrf,
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        };
        if (body) opts.body = JSON.stringify(body);

        const resp = await fetch(url, opts);
        let data = {};
        try { data = await resp.json(); } catch (_) {}
        return { ok: resp.ok, status: resp.status, data };
    };

    // =========================================================================
    // Comentários
    // =========================================================================
    const $form = document.getElementById('lkb-comment-form');
    const $input = document.getElementById('lkb-comment-input');
    const $list = document.getElementById('lkb-comment-list');
    const $count = document.getElementById('lkb-comments-count');
    const $len = document.getElementById('lkb-comment-len');
    const $submit = document.getElementById('lkb-comment-submit');

    const updateLen = () => { if ($len && $input) $len.textContent = ($input.value || '').length; };

    if ($input) $input.addEventListener('input', updateLen);
    updateLen();

    const removeEmpty = () => {
        const empty = document.getElementById('lkb-comment-empty');
        if (empty) empty.remove();
    };

    const formatDate = (iso) => {
        try {
            const d = new Date(iso);
            const pad = (n) => String(n).padStart(2, '0');
            return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
        } catch (_) { return iso; }
    };

    const renderComment = (c) => {
        const initial = (c.user?.name || '?').charAt(0).toUpperCase();
        const isOwner = c.user?.id === cfg.usuarioAtual;
        const li = document.createElement('li');
        li.className = 'lkb-comment-item is-new';
        li.dataset.comentarioId = c.id;
        li.innerHTML = `
            <div class="lkb-comment-avatar">${escapar(initial)}</div>
            <div class="lkb-comment-content">
                <header class="lkb-comment-meta">
                    <span class="lkb-comment-author">${escapar(c.user?.name || 'Sistema')}</span>
                    <span class="lkb-comment-date">${escapar(formatDate(c.created_at))}</span>
                </header>
                <p class="lkb-comment-text">${escapar(c.anotacao)}</p>
            </div>
            ${isOwner ? `
                <button type="button" class="lkb-comment-delete" data-action="delete" data-id="${c.id}" title="Excluir comentário">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    </svg>
                </button>
            ` : ''}
        `;
        return li;
    };

    const updateCount = (delta) => {
        if (!$count) return;
        const cur = parseInt($count.textContent, 10) || 0;
        $count.textContent = Math.max(0, cur + delta);
    };

    if ($form) {
        $form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = ($input.value || '').trim();
            if (!text) {
                $input.classList.add('is-invalid');
                $input.focus();
                return;
            }
            $input.classList.remove('is-invalid');
            $submit.disabled = true;

            const { ok, status, data } = await reqJson(cfg.urls.comentarioStore, 'POST', { anotacao: text });

            $submit.disabled = false;

            if (!ok) {
                if (status === 422) {
                    $input.classList.add('is-invalid');
                }
                toast('error', 'Não foi possível comentar', data.message || 'Tente novamente.');
                return;
            }

            removeEmpty();
            const li = renderComment(data.comentario);
            $list.insertBefore(li, $list.firstChild);
            updateCount(1);

            $input.value = '';
            updateLen();
            toast('success', 'Comentário adicionado', '');
        });
    }

    // Delete (delegado)
    if ($list) {
        $list.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action="delete"]');
            if (!btn) return;
            const id = btn.dataset.id;
            if (!id) return;

            const result = typeof Swal !== 'undefined'
                ? await Swal.fire({
                    title: 'Excluir comentário?',
                    text: 'Esta ação é permanente.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Excluir',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary',
                    },
                    buttonsStyling: false,
                })
                : { isConfirmed: confirm('Excluir comentário?') };

            if (!result.isConfirmed) return;

            const url = buildUrl(cfg.urls.comentarioDeleteTemplate, id);
            const { ok, data } = await reqJson(url, 'DELETE');

            if (!ok) {
                toast('error', 'Falha ao excluir', data.message || 'Tente novamente.');
                return;
            }

            const li = $list.querySelector(`[data-comentario-id="${id}"]`);
            if (li) li.remove();
            updateCount(-1);

            // Re-adiciona empty se ficou vazio
            if (!$list.querySelector('.lkb-comment-item')) {
                const empty = document.createElement('li');
                empty.className = 'lkb-comment-empty';
                empty.id = 'lkb-comment-empty';
                empty.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p>Nenhum comentário ainda. Seja o primeiro a deixar uma anotação.</p>
                `;
                $list.appendChild(empty);
            }

            toast('success', 'Comentário removido', '');
        });
    }

    // =========================================================================
    // Informação fixada
    // =========================================================================
    const $modalEl = document.getElementById('lkb-pinned-modal');
    const bsModal = $modalEl && typeof bootstrap !== 'undefined' ? new bootstrap.Modal($modalEl) : null;
    const $card = document.getElementById('lkb-pinned-card');
    const $cardText = document.getElementById('lkb-pinned-text');
    const $btnEdit = document.getElementById('lkb-btn-edit-pinned');
    const $btnClear = document.getElementById('lkb-btn-clear-pinned');
    const $pinnedInput = document.getElementById('lkb-pinned-input');
    const $pinnedLen = document.getElementById('lkb-pinned-len');
    const $pinnedSave = document.getElementById('lkb-pinned-save');

    const updatePinnedLen = () => {
        if ($pinnedLen && $pinnedInput) $pinnedLen.textContent = ($pinnedInput.value || '').length;
    };
    if ($pinnedInput) $pinnedInput.addEventListener('input', updatePinnedLen);

    const renderPinned = (texto) => {
        const filled = !!(texto && texto.trim());
        if (!$card || !$cardText) return;
        $card.classList.toggle('is-filled', filled);
        $card.classList.toggle('is-empty', !filled);
        if (filled) {
            $cardText.textContent = texto;
        } else {
            $cardText.innerHTML = '<span class="lkb-pinned-empty">Nenhuma informação fixada. Use este espaço para destacar dados críticos do contato — preferências, restrições, contexto sensível.</span>';
        }
        if ($btnClear) $btnClear.hidden = !filled;
        if ($btnEdit) {
            $btnEdit.querySelector('span').textContent = filled ? 'Editar' : 'Adicionar';
        }
    };

    if ($btnEdit) {
        $btnEdit.addEventListener('click', () => {
            updatePinnedLen();
            bsModal?.show();
        });
    }

    if ($btnClear) {
        $btnClear.addEventListener('click', async () => {
            const result = typeof Swal !== 'undefined'
                ? await Swal.fire({
                    title: 'Remover informação fixada?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Remover',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-danger me-2',
                        cancelButton: 'btn btn-label-secondary',
                    },
                    buttonsStyling: false,
                })
                : { isConfirmed: confirm('Remover informação fixada?') };
            if (!result.isConfirmed) return;

            const { ok, data } = await reqJson(cfg.urls.informacaoFixada, 'PUT', { informacao_fixada: null });
            if (!ok) {
                toast('error', 'Falha ao remover', data.message || 'Tente novamente.');
                return;
            }
            renderPinned(null);
            if ($pinnedInput) $pinnedInput.value = '';
            updatePinnedLen();
            toast('success', 'Informação removida', '');
        });
    }

    if ($pinnedSave) {
        $pinnedSave.addEventListener('click', async () => {
            const texto = ($pinnedInput.value || '').trim();
            $pinnedSave.disabled = true;

            const { ok, data } = await reqJson(cfg.urls.informacaoFixada, 'PUT', {
                informacao_fixada: texto || null,
            });

            $pinnedSave.disabled = false;

            if (!ok) {
                toast('error', 'Falha ao salvar', data.message || 'Tente novamente.');
                return;
            }

            renderPinned(data.informacao_fixada);
            bsModal?.hide();
            toast('success', data.informacao_fixada ? 'Informação fixada' : 'Informação removida', '');
        });
    }
})();
