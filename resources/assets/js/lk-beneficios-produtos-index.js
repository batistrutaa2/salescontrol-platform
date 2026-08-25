'use strict';

(function () {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    const cfg = window.lkbProdutos || {};

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const buildUrl = (template, id) => template.replace('__ID__', id);

    const tipoMap = (cfg.tipos || []).reduce((acc, t) => {
        acc[t.value] = t;
        return acc;
    }, {});

    const tipoSlug = (tipo) => String(tipo || '').toLowerCase();

    const coberturasPadrao = Array.isArray(cfg.coberturasVida) ? cfg.coberturasVida : [];
    const coberturasSelecionadas = new Set();
    const normalizarCobertura = (valor) => String(valor || '').trim().replace(/\s+/g, ' ');
    const chaveCobertura = (valor) => normalizarCobertura(valor).toLocaleLowerCase('pt-BR');
    const encontrarCobertura = (valor) => Array.from(coberturasSelecionadas)
        .find(item => chaveCobertura(item) === chaveCobertura(valor));

    // -------------------------------------------------------------------------
    // Toast (réplica do helper canônico do módulo)
    // -------------------------------------------------------------------------
    const SVG = {
        toastSuccess: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        toastError: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
        toastWarning: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        toastInfo: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        toastClose: '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    };

    const showModernToast = (type, title, message) => {
        if (typeof Swal === 'undefined') {
            console.warn('SweetAlert2 não disponível.', { type, title, message });
            return;
        }
        const iconKey = {
            success: SVG.toastSuccess,
            error: SVG.toastError,
            warning: SVG.toastWarning,
            info: SVG.toastInfo,
        };
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'transparent',
            showClass: { popup: 'animate__animated animate__slideInRight animate__faster' },
            hideClass: { popup: 'animate__animated animate__slideOutRight animate__faster' },
            html: `
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-icon">${iconKey[type] || SVG.toastInfo}</div>
                    <div class="toast-content">
                        <div class="toast-title">${escapar(title)}</div>
                        <div class="toast-message">${escapar(message)}</div>
                    </div>
                    <button type="button" class="toast-close" aria-label="Fechar notificação" onclick="Swal.close()">${SVG.toastClose}</button>
                </div>
            `,
            customClass: { popup: 'custom-toast-popup' },
        });
    };

    // =========================================================================
    // Fetch helpers
    // =========================================================================
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
    // DataTable
    // =========================================================================
    const buildFiltros = () => ({
        tipo: $('#lkb-filter-tipo').val() || '',
        status: $('#lkb-filter-status').val() || '',
        busca: $('#lkb-filter-search').val() || '',
    });

    const renderTipoBadge = (row) => {
        const slug = tipoSlug(row.tipo);
        const label = escapar(row.tipo_label || row.tipo);
        const icon = escapar(row.tipo_icon || 'ri-shield-line');
        return `<span class="lkb-tipo-badge is-${slug}"><i class="${icon}"></i>${label}</span>`;
    };

    const renderStatusSwitch = (row) => {
        const checked = row.ativo ? 'checked' : '';
        return `
            <label class="lkb-status-switch" title="${row.ativo ? 'Ativo' : 'Inativo'}">
                <input type="checkbox" data-action="toggle" data-id="${row.id}" ${checked}>
                <span class="lkb-switch-track"></span>
                <span class="lkb-status-text">${row.ativo ? 'Ativo' : 'Inativo'}</span>
            </label>
        `;
    };

    const renderCoberturas = (row) => {
        const coberturas = Array.isArray(row.coberturas) ? row.coberturas : [];
        if (!coberturas.length) return '<span class="lkb-cell-muted">Não informadas</span>';

        const resumo = coberturas.slice(0, 3).join(', ');
        const sufixo = coberturas.length > 3 ? ` e mais ${coberturas.length - 3}` : '';
        return `<span class="lkb-coverage-count" title="${escapar(resumo + sufixo)}"><strong>${coberturas.length}</strong> ${coberturas.length === 1 ? 'cobertura' : 'coberturas'}</span>`;
    };

    const renderActions = (row) => `
        <div class="lkb-row-actions">
            <button type="button" class="lkb-btn-icon" data-action="edit" data-id="${row.id}" title="Editar">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </button>
            <button type="button" class="lkb-btn-icon is-danger" data-action="delete" data-id="${row.id}" title="Excluir">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                    <path d="M10 11v6"/><path d="M14 11v6"/>
                    <path d="M9 6V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                </svg>
            </button>
        </div>
    `;

    const table = $('#lkb-tabela-produtos').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        scrollX: true,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
        dom: '<"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => Object.assign(d, buildFiltros()),
        },
        columns: [
            { data: 'id', render: (id) => `<span class="lkb-cell-id">#${id}</span>`, width: '60px' },
            { data: 'nome', render: (v) => `<span class="lkb-cell-nome">${escapar(v)}</span>` },
            { data: null, orderable: false, render: (row) => renderTipoBadge(row) },
            { data: null, render: (row) => {
                const valor = row.modalidade_label || row.subtipo;
                return valor ? escapar(valor) : '<span class="lkb-cell-muted">—</span>';
            } },
            { data: 'operadora_nome', orderable: false, render: (v) => v ? escapar(v) : '<span class="lkb-cell-muted">—</span>' },
            { data: null, orderable: false, render: (row) => renderStatusSwitch(row) },
            { data: null, orderable: false, render: (row) => renderCoberturas(row), width: '130px' },
            { data: null, orderable: false, className: 'text-end', render: (row) => renderActions(row), width: '120px' },
        ],
    });

    // -------------------------------------------------------------------------
    // Filtros — listeners
    // -------------------------------------------------------------------------
    $('#lkb-filter-tipo, #lkb-filter-status').on('change', () => table.ajax.reload());

    let searchTimeout = null;
    $('#lkb-filter-search').on('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => table.ajax.reload(), 350);
    });

    // =========================================================================
    // Modal CRUD
    // =========================================================================
    const $modalEl = document.getElementById('lkb-modal-produto');
    const bsModal = $modalEl ? new bootstrap.Modal($modalEl) : null;
    const $form = document.getElementById('lkb-form-produto');
    const $title = document.getElementById('lkb-modal-produto-title');
    const $idField = document.getElementById('lkb-produto-id');
    const $btnSalvar = document.getElementById('lkb-btn-salvar-produto');
    const $tipoField = document.getElementById('lkb-produto-tipo');
    const $modalidadeGroup = document.getElementById('lkb-modalidade-group');
    const $modalidadeField = document.getElementById('lkb-produto-modalidade');
    const $standardCoverages = document.getElementById('lkb-standard-coverages');
    const $selectedCoverages = document.getElementById('lkb-selected-coverages');
    const $coverageCounter = document.getElementById('lkb-coverage-counter');
    const $customCoverageInput = document.getElementById('lkb-custom-coverage-input');

    const isVida = () => $tipoField?.value === 'VIDA';

    const syncCoverageUI = () => {
        document.querySelectorAll('[data-coverage-standard]').forEach(input => {
            input.checked = !!encontrarCobertura(input.value);
        });

        if ($standardCoverages) $standardCoverages.hidden = !isVida();
        if ($modalidadeGroup) $modalidadeGroup.hidden = !isVida();

        const customizadas = Array.from(coberturasSelecionadas).filter(cobertura => (
            !isVida() || !coberturasPadrao.some(padrao => chaveCobertura(padrao) === chaveCobertura(cobertura))
        ));

        if ($selectedCoverages) {
            $selectedCoverages.innerHTML = customizadas.length
                ? customizadas.map(cobertura => `
                    <span class="lkb-selected-coverage">
                        <span>${escapar(cobertura)}</span>
                        <button type="button" data-remove-coverage="${escapar(cobertura)}" aria-label="Remover ${escapar(cobertura)}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </button>
                    </span>
                `).join('')
                : '<span class="lkb-coverage-empty">Nenhuma cobertura adicional.</span>';
        }

        if ($coverageCounter) {
            const quantidade = coberturasSelecionadas.size;
            $coverageCounter.textContent = `${quantidade} ${quantidade === 1 ? 'selecionada' : 'selecionadas'}`;
        }
    };

    const adicionarCobertura = (valor) => {
        const cobertura = normalizarCobertura(valor);
        if (!cobertura || encontrarCobertura(cobertura)) return false;
        const padrao = coberturasPadrao.find(item => chaveCobertura(item) === chaveCobertura(cobertura));
        coberturasSelecionadas.add(padrao || cobertura);
        syncCoverageUI();
        return true;
    };

    const removerCobertura = (valor) => {
        const encontrada = encontrarCobertura(valor);
        if (encontrada) coberturasSelecionadas.delete(encontrada);
        syncCoverageUI();
    };

    const setFormState = (state) => {
        if ($btnSalvar) $btnSalvar.dataset.state = state;
    };

    const clearFieldErrors = () => {
        $form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        $form.querySelectorAll('[data-error-for]').forEach(el => el.textContent = '');
    };

    const showFieldErrors = (errors) => {
        clearFieldErrors();
        Object.entries(errors || {}).forEach(([field, msgs]) => {
            const rootField = field.split('.')[0];
            const input = $form.querySelector(`[name="${field}"]`) || $form.querySelector(`[name="${rootField}"]`);
            if (input) input.classList.add('is-invalid');
            const errEl = $form.querySelector(`[data-error-for="${field}"]`) || $form.querySelector(`[data-error-for="${rootField}"]`);
            if (errEl) errEl.textContent = Array.isArray(msgs) ? msgs[0] : String(msgs);
        });
    };

    const resetForm = () => {
        $form.reset();
        $idField.value = '';
        clearFieldErrors();
        document.getElementById('lkb-produto-ativo').checked = true;
        coberturasSelecionadas.clear();
        if ($modalidadeField) $modalidadeField.value = '';
        if ($customCoverageInput) $customCoverageInput.value = '';
        syncCoverageUI();
        setFormState('idle');
    };

    const fillForm = (row) => {
        $idField.value = row.id;
        document.getElementById('lkb-produto-nome').value = row.nome || '';
        document.getElementById('lkb-produto-tipo').value = row.tipo || '';
        document.getElementById('lkb-produto-subtipo').value = row.subtipo || '';
        $modalidadeField.value = row.modalidade || '';
        document.getElementById('lkb-produto-operadora').value = row.operadora_id || '';
        document.getElementById('lkb-produto-descricao').value = row.descricao || '';
        document.getElementById('lkb-produto-ativo').checked = !!row.ativo;
        coberturasSelecionadas.clear();
        (Array.isArray(row.coberturas) ? row.coberturas : []).forEach(adicionarCobertura);
        syncCoverageUI();
        clearFieldErrors();
        setFormState('idle');
    };

    // Botão Novo Produto
    document.getElementById('lkb-btn-novo-produto')?.addEventListener('click', () => {
        resetForm();
        $title.textContent = 'Novo plano';
        bsModal?.show();
    });

    // Reset quando fechar
    $modalEl?.addEventListener('hidden.bs.modal', () => {
        resetForm();
    });

    // Eventos delegados na tabela
    $('#lkb-tabela-produtos tbody').on('click', '[data-action]', function (e) {
        const action = this.dataset.action;
        const id = parseInt(this.dataset.id, 10);
        const tr = $(this).closest('tr');
        const row = table.row(tr).data();
        if (!row) return;

        if (action === 'edit') {
            fillForm(row);
            $title.textContent = 'Editar plano';
            bsModal?.show();
        } else if (action === 'delete') {
            confirmDelete(row);
        } else if (action === 'toggle') {
            // o checkbox já mudou de estado visualmente; manda o novo valor
            e.stopPropagation();
            handleToggle(row, this.checked);
        }
    });

    // Submit do form
    $form.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearFieldErrors();
        setFormState('loading');

        const id = $idField.value;
        const ativoChecked = document.getElementById('lkb-produto-ativo').checked;

        const payload = {
            nome: document.getElementById('lkb-produto-nome').value.trim(),
            tipo: document.getElementById('lkb-produto-tipo').value,
            subtipo: document.getElementById('lkb-produto-subtipo').value.trim() || null,
            modalidade: isVida() ? ($modalidadeField.value || null) : null,
            operadora_id: document.getElementById('lkb-produto-operadora').value || null,
            descricao: document.getElementById('lkb-produto-descricao').value.trim() || null,
            coberturas: Array.from(coberturasSelecionadas),
            ativo: ativoChecked,
        };

        const url = id ? buildUrl(cfg.updateUrlTemplate, id) : cfg.storeUrl;
        const method = id ? 'PUT' : 'POST';

        const { ok, status, data } = await reqJson(url, method, payload);

        if (!ok) {
            setFormState('idle');
            if (status === 422 && data.errors) {
                showFieldErrors(data.errors);
            }
            showModernToast('error', 'Não foi possível salvar', data.message || 'Verifique os campos e tente novamente.');
            return;
        }

        setFormState('success');
        showModernToast('success', id ? 'Plano atualizado' : 'Plano criado', data.message || (id ? 'Alterações salvas.' : 'Novo plano adicionado ao catálogo.'));

        setTimeout(() => {
            bsModal?.hide();
            table.ajax.reload(null, false);
        }, 700);
    });

    $tipoField?.addEventListener('change', syncCoverageUI);

    document.querySelectorAll('[data-coverage-standard]').forEach(input => {
        input.addEventListener('change', () => {
            if (input.checked) adicionarCobertura(input.value);
            else removerCobertura(input.value);
        });
    });

    const handleAddCustomCoverage = () => {
        if (!$customCoverageInput) return;
        if (adicionarCobertura($customCoverageInput.value)) $customCoverageInput.value = '';
        $customCoverageInput.focus();
    };

    document.getElementById('lkb-add-coverage')?.addEventListener('click', handleAddCustomCoverage);
    $customCoverageInput?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        handleAddCustomCoverage();
    });

    $selectedCoverages?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-coverage]');
        if (button) removerCobertura(button.dataset.removeCoverage);
    });

    // =========================================================================
    // Toggle ativo
    // =========================================================================
    const handleToggle = async (row, novoAtivo) => {
        const url = buildUrl(cfg.toggleUrlTemplate, row.id);
        const { ok, data } = await reqJson(url, 'PATCH', { ativo: !!novoAtivo });

        if (!ok) {
            showModernToast('error', 'Falha ao alterar status', data.message || 'Tente novamente.');
            table.ajax.reload(null, false);
            return;
        }
        showModernToast('success', data.ativo ? 'Produto ativado' : 'Produto desativado', `“${row.nome}” agora está ${data.ativo ? 'visível' : 'oculto'} nos selects.`);
        table.ajax.reload(null, false);
    };

    // =========================================================================
    // Delete
    // =========================================================================
    const confirmDelete = (row) => {
        if (typeof Swal === 'undefined') return;
        Swal.fire({
            title: 'Excluir plano?',
            html: `Você está prestes a remover <strong>${escapar(row.nome)}</strong>. Esta ação é permanente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn btn-danger me-2',
                cancelButton: 'btn btn-label-secondary',
            },
            buttonsStyling: false,
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            const url = buildUrl(cfg.deleteUrlTemplate, row.id);
            const { ok, status, data } = await reqJson(url, 'DELETE');

            if (!ok) {
                if (status === 422) {
                    showModernToast('warning', 'Não foi possível excluir', data.message || 'Existem registros vinculados.');
                } else {
                    showModernToast('error', 'Falha ao excluir', data.message || 'Tente novamente.');
                }
                return;
            }
            showModernToast('success', 'Plano removido', data.message || `“${row.nome}” foi removido do catálogo.`);
            table.ajax.reload(null, false);
        });
    };
})();
