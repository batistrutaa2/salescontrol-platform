'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    const vendaId = document.querySelector('input[name="venda_id"]')?.value ||
                    document.querySelector('input[name="id"]')?.value;

    // ============================================
    // Initialize Flatpickr
    // ============================================
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr-date', {
            dateFormat: 'd/m/Y',
            locale: 'pt'
        });
    }

    // ============================================
    // CNPJ Mask
    // ============================================
    const inputCnpj = document.querySelector('.mask-cnpj');
    if (inputCnpj && typeof Cleave !== 'undefined') {
        new Cleave(inputCnpj, {
            delimiters: ['.', '.', '/', '-'],
            blocks: [2, 3, 3, 4, 2],
            numericOnly: true
        });
    }

    // ============================================
    // CPF Mask
    // ============================================
    document.querySelectorAll('.mask-cpf').forEach(function (el) {
        if (typeof Cleave !== 'undefined') {
            new Cleave(el, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
        }
    });

    // ============================================
    // Phone Mask
    // ============================================
    function applyPhoneMask(element) {
        if (!element || element.dataset.maskApplied === '1') return;
        element.dataset.maskApplied = '1';

        new Cleave(element, {
            delimiters: ['(', ') ', '-'],
            blocks: [0, 2, 5, 4],
            numericOnly: true
        });
    }

    document.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);

    // ============================================
    // Monetary Mask
    // ============================================
    function applyMonetaryMask(element) {
        if (!element || element.dataset.maskApplied === '1') return;
        element.dataset.maskApplied = '1';

        let v = String(element.value || '');
        v = v.replace(/\s+/g, '').replace(/^R\$\s?/, '').replace(/\./g, '').replace(',', '.');
        const num = parseFloat(v);
        element.value = isNaN(num) ? '' : num.toFixed(2).replace('.', ',');

        new Cleave(element, {
            numeral: true,
            numeralThousandsGroupStyle: 'thousand',
            numeralDecimalMark: ',',
            delimiter: '.',
            prefix: 'R$ ',
            numeralDecimalScale: 2
        });
    }

    document.querySelectorAll('.monetary-field').forEach(applyMonetaryMask);

    // ============================================
    // Operadora Change - Load Plans
    // ============================================
    const operadoraSelect = document.getElementById('operadoraSelect');
    const planoSelect = document.getElementById('planoSelect');

    if (operadoraSelect && planoSelect) {
        operadoraSelect.addEventListener('change', function () {
            const operadoraId = this.value;

            if (!operadoraId) {
                planoSelect.innerHTML = '<option value="">Selecione a operadora primeiro</option>';
                return;
            }

            planoSelect.innerHTML = '<option value="">Carregando...</option>';

            fetch(`/comercial/getPlansByOperator/${operadoraId}`)
                .then(res => res.json())
                .then(data => {
                    let options = '<option value="">Selecione...</option>';
                    if (Array.isArray(data)) {
                        data.forEach(p => {
                            const nome = (p.nome || '').toUpperCase();
                            const acomodacao = p.acomodacao ? ` - ${p.acomodacao.toUpperCase()}` : '';
                            options += `<option value="${p.id}" data-acomodacao="${p.acomodacao || ''}">${nome}${acomodacao}</option>`;
                        });
                    }
                    planoSelect.innerHTML = options;
                })
                .catch(() => {
                    planoSelect.innerHTML = '<option value="">Erro ao carregar</option>';
                });
        });
    }

    // ============================================
    // Angariacao Toggle
    // ============================================
    const angariacaoStatus = document.getElementById('angariacao_status');
    const angariacaoBadge = document.getElementById('angariacao-badge');

    if (angariacaoStatus && angariacaoBadge) {
        angariacaoStatus.addEventListener('change', function () {
            const isSim = this.value === 'SIM';
            angariacaoBadge.className = `status-badge ${isSim ? 'badge-ativo' : 'badge-inativo'}`;
            angariacaoBadge.textContent = isSim ? 'Ativo' : 'Inativo';
        });
    }

    // ============================================
    // Acessos Management
    // ============================================
    const acessosGrid = document.getElementById('acessos-grid');
    const acessosEmpty = document.getElementById('acessos-empty');
    const acessosLoading = document.getElementById('acessos-loading');
    const acessosCount = document.getElementById('acessos-count');

    function loadAcessos() {
        if (!vendaId || !acessosGrid) return;

        acessosLoading.style.display = 'flex';
        acessosEmpty.style.display = 'none';
        acessosGrid.innerHTML = '';

        fetch(`/back-office/acessos-empresa/${vendaId}`)
            .then(res => res.json())
            .then(data => {
                acessosLoading.style.display = 'none';

                if (!data.acessos || data.acessos.length === 0) {
                    acessosEmpty.style.display = 'block';
                    acessosCount.textContent = '0';
                    return;
                }

                acessosCount.textContent = data.acessos.length;

                data.acessos.forEach(acesso => {
                    const card = document.createElement('div');
                    card.className = 'acesso-card';
                    card.innerHTML = `
                        <div class="acesso-email">${acesso.email}</div>
                        <div class="acesso-senha">${acesso.senha}</div>
                        ${acesso.cpf ? `<div class="acesso-cpf">CPF: ${acesso.cpf}</div>` : ''}
                        <div class="acesso-actions">
                            <button type="button" class="btn-edit" data-id="${acesso.id}" data-email="${acesso.email}" data-senha="${acesso.senha}" data-cpf="${acesso.cpf || ''}">Editar</button>
                            <button type="button" class="btn-delete" data-id="${acesso.id}">Remover</button>
                        </div>
                    `;
                    acessosGrid.appendChild(card);
                });

                // Attach events
                attachAcessoEvents();
            })
            .catch(() => {
                acessosLoading.style.display = 'none';
                acessosEmpty.style.display = 'block';
                acessosEmpty.innerHTML = '<p>Erro ao carregar acessos</p>';
            });
    }

    function attachAcessoEvents() {
        // Edit buttons
        document.querySelectorAll('.acesso-card .btn-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const email = this.dataset.email;
                const senha = this.dataset.senha;
                const cpf = this.dataset.cpf;

                document.getElementById('acesso_id').value = id;
                document.getElementById('acesso_email').value = email;
                document.getElementById('acesso_senha').value = senha;
                document.getElementById('acesso_cpf').value = cpf;

                const modal = new bootstrap.Modal(document.getElementById('modalAddAcesso'));
                modal.show();
            });
        });

        // Delete buttons
        document.querySelectorAll('.acesso-card .btn-delete').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('delete_acesso_id').value = this.dataset.id;
                const modal = new bootstrap.Modal(document.getElementById('modalDeleteAcesso'));
                modal.show();
            });
        });
    }

    // Form submit for acesso
    const formAcesso = document.getElementById('form-acesso');
    if (formAcesso) {
        formAcesso.addEventListener('submit', function (e) {
            e.preventDefault();

            const acessoId = document.getElementById('acesso_id').value;
            const email = document.getElementById('acesso_email').value;
            const senha = document.getElementById('acesso_senha').value;
            const cpf = document.getElementById('acesso_cpf').value;

            const url = acessoId
                ? `/back-office/acessos-empresa/${acessoId}`
                : '/back-office/acessos-empresa';

            const method = acessoId ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    venda_id: vendaId,
                    email: email,
                    senha: senha,
                    cpf: cpf
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalAddAcesso'))?.hide();
                        formAcesso.reset();
                        document.getElementById('acesso_id').value = '';
                        loadAcessos();
                    } else {
                        alert(data.message || 'Erro ao salvar acesso');
                    }
                })
                .catch(() => {
                    alert('Erro ao salvar acesso');
                });
        });
    }

    // Confirm delete
    const btnConfirmDelete = document.getElementById('btn-confirm-delete');
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener('click', function () {
            const acessoId = document.getElementById('delete_acesso_id').value;

            fetch(`/back-office/acessos-empresa/${acessoId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            })
                .then(res => res.json())
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('modalDeleteAcesso'))?.hide();
                    loadAcessos();
                })
                .catch(() => {
                    alert('Erro ao remover acesso');
                });
        });
    }

    // Reset modal on close
    const modalAddAcesso = document.getElementById('modalAddAcesso');
    if (modalAddAcesso) {
        modalAddAcesso.addEventListener('hidden.bs.modal', function () {
            formAcesso?.reset();
            document.getElementById('acesso_id').value = '';
        });
    }

    // ============================================
    // Initialize
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        loadAcessos();
    });

})();
