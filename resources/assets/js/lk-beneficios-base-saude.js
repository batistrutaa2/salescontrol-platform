'use strict';

(function () {
    if (typeof $ === 'undefined' || !$.fn.DataTable) return;
    const cfg = window.lkbBaseSaude || {};

    // =========================================================================
    // Helpers
    // =========================================================================
    const escapar = (txt) => {
        const div = document.createElement('div');
        div.textContent = txt == null ? '' : String(txt);
        return div.innerHTML;
    };

    const formatarCpfCnpj = (valor) => {
        const v = String(valor || '').replace(/\D/g, '');
        if (v.length === 11) {
            return v.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }
        if (v.length === 14) {
            return v.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
        }
        return valor || '—';
    };

    const iniciaisDoNome = (nome) => {
        const partes = String(nome || '').trim().split(/\s+/).filter(Boolean);
        if (!partes.length) return '?';
        if (partes.length === 1) return partes[0].slice(0, 2).toUpperCase();
        return (partes[0][0] + partes[partes.length - 1][0]).toUpperCase();
    };

    // -------------------------------------------------------------------------
    // Toast (réplica do helper canônico do módulo — ver lk-beneficios-kanban.js)
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
    // DataTable
    // =========================================================================
    const buildFiltros = () => ({
        busca: $('#lkb-bs-busca').val() || '',
        ocultar_com_lead: $('#lkb-bs-ocultar-leads').is(':checked') ? 1 : 0,
        ocultar_com_contrato: $('#lkb-bs-ocultar-contratos').is(':checked') ? 1 : 0,
    });

    const renderBotaoPegar = (row) => {
        const cpf = escapar(row.cpf_cnpj);
        const nome = escapar(row.nome || '');
        const operadoras = escapar(row.operadoras || '');
        const qtd = Number(row.qtd_contratos || 0);
        const arrowSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
        return `<button class="btn btn-sm lkb-bs-btn-pegar"
                    data-cpf="${cpf}"
                    data-nome="${nome}"
                    data-qtd="${qtd}"
                    data-operadoras="${operadoras}">
                ${arrowSvg} Pegar Lead
            </button>`;
    };

    const table = $('#lkb-tabela-base-saude').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[4, 'asc']],
        ordering: false,
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json' },
        dom: '<"row"<"col-sm-12"tr>><"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        ajax: {
            url: cfg.datatableUrl,
            data: (d) => Object.assign(d, buildFiltros()),
        },
        columns: [
            { data: 'cpf_cnpj', render: (v) => formatarCpfCnpj(v) },
            { data: 'nome', render: (v) => `<span class="contract-name">${escapar(v || '—')}</span>` },
            { data: 'qtd_contratos', className: 'text-center' },
            { data: 'operadoras' },
            { data: 'primeira_implantacao_fmt' },
            { data: 'ultima_implantacao_fmt' },
            {
                data: null,
                render: (row) => {
                    if (row.tem_contrato) return '<span class="contract-value success" style="font-weight:700">Já é cliente</span>';
                    if (row.tem_lead) return '<span class="contract-value angariacao" style="font-weight:700">Lead ativo</span>';
                    return '<span class="contract-value" style="font-weight:700; color: var(--dash-primary);">Disponível</span>';
                },
            },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: renderBotaoPegar,
            },
        ],
    });

    let debounceTimer;
    $('#lkb-bs-busca').on('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => table.ajax.reload(), 350);
    });
    $('#lkb-bs-ocultar-leads, #lkb-bs-ocultar-contratos').on('change', () => table.ajax.reload());

    // =========================================================================
    // Modal — abrir / popular cliente / produtos / submit
    // =========================================================================
    const $modalEl = document.getElementById('lkb-modal-pegar');
    const $btnConfirmar = document.getElementById('lkb-bs-confirmar');
    const $cpfInput = document.getElementById('lkb-bs-cpf-cnpj');
    const $produtoInput = document.getElementById('lkb-bs-produto-id');
    const bsModal = $modalEl ? new bootstrap.Modal($modalEl) : null;

    const limparSelecaoProduto = () => {
        document.querySelectorAll('#lkb-modal-pegar .lkb-produto-card').forEach((card) => {
            card.classList.remove('is-selected');
            card.setAttribute('aria-checked', 'false');
            const radio = card.querySelector('input[type="radio"]');
            if (radio) radio.checked = false;
        });
        $produtoInput.value = '';
    };

    const selecionarProduto = (card) => {
        document.querySelectorAll('#lkb-modal-pegar .lkb-produto-card').forEach((c) => {
            c.classList.remove('is-selected');
            c.setAttribute('aria-checked', 'false');
        });
        card.classList.add('is-selected');
        card.setAttribute('aria-checked', 'true');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        $produtoInput.value = card.dataset.id || (radio ? radio.value : '');
    };

    // Click + keyboard (radio cards acessíveis)
    document.querySelectorAll('#lkb-modal-pegar .lkb-produto-card').forEach((card) => {
        card.addEventListener('click', () => selecionarProduto(card));
        card.addEventListener('keydown', (e) => {
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                selecionarProduto(card);
            }
        });
    });

    // Abrir modal a partir da tabela
    $(document).on('click', '.lkb-bs-btn-pegar', function () {
        const cpf = String($(this).data('cpf') || '');
        const nome = $(this).data('nome') || '';
        const qtd = Number($(this).data('qtd') || 0);
        const operadoras = $(this).data('operadoras') || '';

        $cpfInput.value = cpf;
        document.getElementById('lkb-bs-cliente-avatar').textContent = iniciaisDoNome(nome);
        document.getElementById('lkb-bs-cliente-nome').textContent = nome || 'Sem nome';
        document.getElementById('lkb-bs-cliente-doc').textContent = formatarCpfCnpj(cpf);

        // Tags do cliente: contratos + operadoras (até 2)
        const tagsContainer = document.getElementById('lkb-bs-cliente-tags');
        tagsContainer.innerHTML = '';
        if (qtd > 0) {
            tagsContainer.insertAdjacentHTML('beforeend', `
                <span class="lkb-cliente-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    ${qtd} contrato${qtd !== 1 ? 's' : ''}
                </span>
            `);
        }
        if (operadoras) {
            const lista = String(operadoras).split(',').map((s) => s.trim()).filter(Boolean).slice(0, 2);
            lista.forEach((op) => {
                tagsContainer.insertAdjacentHTML('beforeend', `
                    <span class="lkb-cliente-tag">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                        ${escapar(op)}
                    </span>
                `);
            });
        }

        limparSelecaoProduto();
        $btnConfirmar.dataset.state = 'idle';
        $btnConfirmar.disabled = !document.querySelector('#lkb-modal-pegar .lkb-produto-card');

        if (bsModal) bsModal.show();
    });

    // =========================================================================
    // Submit
    // =========================================================================
    $btnConfirmar?.addEventListener('click', async () => {
        const cpf = $cpfInput.value;
        const produtoId = $produtoInput.value;

        if (!cpf) {
            showModernToast('warning', 'Cliente não selecionado', 'Tente clicar em Pegar Lead novamente.');
            return;
        }
        if (!produtoId) {
            showModernToast('warning', 'Selecione um produto', 'Escolha um produto de interesse para criar o lead.');
            return;
        }

        $btnConfirmar.dataset.state = 'loading';
        $btnConfirmar.disabled = true;

        try {
            const resp = await fetch(cfg.pegarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ cpf_cnpj: cpf, produto_id: produtoId }),
            });
            const body = await resp.json().catch(() => ({}));

            if (!resp.ok) {
                $btnConfirmar.dataset.state = 'idle';
                $btnConfirmar.disabled = false;
                showModernToast('error', 'Não foi possível criar o lead', body.error || 'Tente novamente em instantes.');
                return;
            }

            $btnConfirmar.dataset.state = 'success';
            showModernToast('success', 'Lead criado!', 'Card adicionado em NOVO CLIENTE no pipeline.');

            // Pequena pausa para o usuário ver o estado de sucesso antes do redirect
            setTimeout(() => {
                window.location.href = cfg.kanbanUrl;
            }, 850);
        } catch (err) {
            $btnConfirmar.dataset.state = 'idle';
            $btnConfirmar.disabled = false;
            showModernToast('error', 'Erro de rede', 'Verifique sua conexão e tente novamente.');
        }
    });

    // Reset ao fechar (caso usuário cancele)
    $modalEl?.addEventListener('hidden.bs.modal', () => {
        limparSelecaoProduto();
        $btnConfirmar.dataset.state = 'idle';
        $btnConfirmar.disabled = !document.querySelector('#lkb-modal-pegar .lkb-produto-card');
    });
})();
