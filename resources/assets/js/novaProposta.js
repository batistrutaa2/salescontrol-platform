'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    let planosDaOperadoraAtual = [];
    let titularIndex = 0;
    let isOperadoraAmil = false;
    let currentPropostaType = 'PME';
    let cnpjCleaveInstance = null;

    // ============================================
    // Helpers
    // ============================================
    function escapeHtml(s) {
        return String(s || '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function digitsOnly(v) {
        return String(v || '').replace(/\D/g, '');
    }

    function isValidEmail(v) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || '').trim());
    }

    function isValidDateBr(v) {
        return /^\d{2}\/\d{2}\/\d{4}$/.test(String(v || '').trim());
    }

    function setInvalid(el) {
        el?.classList.add('is-invalid');
    }

    function clearInvalid(scope = document) {
        scope.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    // Campo corrigido deixa de ficar destacado
    document.addEventListener('input', e => e.target.classList?.remove('is-invalid'));
    document.addEventListener('change', e => e.target.classList?.remove('is-invalid'));

    /**
     * Modern Toast Notification
     * @param {string} type - 'success', 'error', 'warning', 'info'
     * @param {string} title - Toast title
     * @param {string} message - Toast message
     */
    function showModernToast(type, title, message) {
        const icons = {
            success: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>`,
            error: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>`,
            warning: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>`,
            info: `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>`
        };

        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'transparent',
            showClass: {
                popup: 'animate__animated animate__slideInRight animate__faster'
            },
            hideClass: {
                popup: 'animate__animated animate__slideOutRight animate__faster'
            },
            html: `
                <div class="custom-toast custom-toast-${type}">
                    <div class="toast-icon">
                        ${icons[type] || icons.info}
                    </div>
                    <div class="toast-content">
                        <div class="toast-title">${escapeHtml(title)}</div>
                        <div class="toast-message">${escapeHtml(message)}</div>
                    </div>
                    <div class="toast-close" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </div>
                </div>
            `,
            customClass: {
                popup: 'custom-toast-popup'
            }
        });
    }

    window.showModernToast = showModernToast;

    // ============================================
    // Coparticipacao Options (dynamic based on operadora)
    // ============================================
    function getCoparticipacaoOptions() {
        if (isOperadoraAmil) {
            return `
                <option value="">Coparticipacao... *</option>
                <option value="PARCIAL">Parcial</option>
                <option value="COMPLETA">Completa</option>
            `;
        }
        return `
            <option value="">Coparticipacao... *</option>
            <option value="Y">Sim</option>
            <option value="N">Nao</option>
        `;
    }

    function updateAllCoparticipacaoSelects() {
        document.querySelectorAll('.select-coparticipacao-titular').forEach(select => {
            const currentValue = select.value;
            select.innerHTML = getCoparticipacaoOptions();

            // Tentar manter valor se compatível, senão resetar
            if (currentValue) {
                const hasOption = Array.from(select.options).some(o => o.value === currentValue);
                if (hasOption) {
                    select.value = currentValue;
                } else {
                    select.value = '';
                }
            }
        });
    }

    // ============================================
    // Date input mask (DD/MM/AAAA) - compatível com Flatpickr
    // ============================================
    function applyDateMask(element) {
        if (!element || element.dataset.dateMaskApplied === '1') return;
        element.dataset.dateMaskApplied = '1';

        element.addEventListener('input', function (e) {
            // Preserva posição do cursor para não pular ao deletar
            const isDeleting = e.inputType === 'deleteContentBackward' || e.inputType === 'deleteContentForward';
            let val = this.value.replace(/\D/g, '').substring(0, 8);
            if (val.length >= 5) {
                val = val.substring(0, 2) + '/' + val.substring(2, 4) + '/' + val.substring(4);
            } else if (val.length >= 3) {
                val = val.substring(0, 2) + '/' + val.substring(2);
            }
            if (!isDeleting || this.value !== val) {
                this.value = val;
            }
        });

        element.addEventListener('keydown', function (e) {
            // Permite: backspace, delete, tab, escape, setas, home, end
            const allowed = [8, 9, 27, 46, 37, 38, 39, 40, 35, 36];
            if (allowed.includes(e.keyCode)) return;
            // Bloqueia tudo que não é número
            if ((e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }

    // ============================================
    // Initialize Flatpickr for date fields
    // ============================================
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr-date', {
            dateFormat: 'd/m/Y',
            allowInput: true
        });
    }
    document.querySelectorAll('.flatpickr-date').forEach(applyDateMask);

    // ============================================
    // CNPJ Mask
    // ============================================
    const inputCnpj = document.getElementById('cpf_cnpj');
    if (inputCnpj && typeof Cleave !== 'undefined') {
        cnpjCleaveInstance = new Cleave(inputCnpj, {
            delimiters: ['.', '.', '/', '-'],
            blocks: [2, 3, 3, 4, 2],
            numericOnly: true
        });
    }

    // ============================================
    // Phone Mask (global)
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
    // CPF Mask (global)
    // ============================================
    function applyCpfMask(element) {
        if (!element || element.dataset.maskApplied === '1') return;
        element.dataset.maskApplied = '1';

        new Cleave(element, {
            delimiters: ['.', '.', '-'],
            blocks: [3, 3, 3, 2],
            numericOnly: true
        });
    }

    document.querySelectorAll('.mask-cpf').forEach(applyCpfMask);

    // ============================================
    // Flatpickr for birth date (global)
    // ============================================
    function applyFlatpickrNascimento(element) {
        if (!element || element.dataset.flatpickrApplied === '1') return;
        element.dataset.flatpickrApplied = '1';

        if (typeof flatpickr !== 'undefined') {
            flatpickr(element, {
                dateFormat: 'd/m/Y',
                allowInput: true
            });
        }
        applyDateMask(element);
    }

    document.querySelectorAll('.flatpickr-nascimento').forEach(applyFlatpickrNascimento);

    // ============================================
    // Monetary Mask (global)
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
    // Update Stats (header counters)
    // ============================================
    function updateStats() {
        const titulares = document.querySelectorAll('#titulares-container .titular-card').length;
        const dependentes = document.querySelectorAll('#titulares-container .dependente-item').length;
        const totalVidas = titulares + dependentes;

        document.getElementById('total-titulares').textContent = titulares;
        document.getElementById('total-dependentes').textContent = dependentes;
        document.getElementById('total-vidas').textContent = totalVidas;
        document.getElementById('vidas').value = totalVidas;

        // Add/remove de titular e dependente tambem persiste o rascunho
        scheduleDraftSave();
    }

    // ============================================
    // Render Plan Options
    // ============================================
    function renderPlanOptions() {
        if (!planosDaOperadoraAtual.length) {
            return '<option value="">Selecione a operadora primeiro *</option>';
        }
        let opts = '<option value="">Selecione o plano... *</option>';
        planosDaOperadoraAtual.forEach(p => {
            const nome = (p.nome || '').toUpperCase();
            const acomodacao = p.acomodacao ? ` - ${p.acomodacao.toUpperCase()}` : '';
            opts += `<option value="${p.id}">${nome}${acomodacao}</option>`;
        });
        return opts;
    }

    // ============================================
    // Add Titular
    // ============================================
    function addTitular() {
        const container = document.getElementById('titulares-container');
        const template = document.getElementById('template-titular');
        if (!container || !template) return;

        const number = titularIndex + 1;

        // Get HTML from template and replace placeholders
        const html = template.content.cloneNode(true).querySelector('.titular-card').outerHTML
            .replace(/__INDEX__/g, titularIndex)
            .replace(/__NUMBER__/g, number);

        container.insertAdjacentHTML('beforeend', html);

        // Get the newly added block
        const newBlock = container.lastElementChild;

        // Update plan select options
        const planSelect = newBlock.querySelector('.select-plano-titular');
        if (planSelect) {
            planSelect.innerHTML = renderPlanOptions();
        }

        // Update coparticipacao select options based on operadora
        const copartSelect = newBlock.querySelector('.select-coparticipacao-titular');
        if (copartSelect) {
            copartSelect.innerHTML = getCoparticipacaoOptions();
        }

        // Apply masks to new fields
        newBlock.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);

        // Se estiver em modo ADESAO, ocultar campo Cargo
        if (currentPropostaType === 'ADESAO') {
            const cargoWrapper = newBlock.querySelector('.field-cargo-wrapper');
            if (cargoWrapper) {
                cargoWrapper.style.display = 'none';
                const cargoSelect = cargoWrapper.querySelector('.select-cargo-titular');
                if (cargoSelect) {
                    cargoSelect.removeAttribute('required');
                    cargoSelect.value = '';
                }
            }
        }
        newBlock.querySelectorAll('.mask-cpf').forEach(applyCpfMask);
        newBlock.querySelectorAll('.flatpickr-nascimento').forEach(applyFlatpickrNascimento);

        titularIndex++;
        updateStats();
        updateTitularNumbers();
    }

    // ============================================
    // Remove Titular
    // ============================================
    function removeTitular(titularCard) {
        if (!titularCard) return;

        const container = document.getElementById('titulares-container');
        const allTitulares = container.querySelectorAll('.titular-card');

        if (allTitulares.length <= 1) {
            showModernToast('warning', 'Atenção', 'O contrato precisa ter pelo menos 1 titular.');
            return;
        }

        titularCard.remove();
        updateStats();
        updateTitularNumbers();
    }

    // ============================================
    // Update Titular Numbers
    // ============================================
    function updateTitularNumbers() {
        const container = document.getElementById('titulares-container');
        const titulares = container.querySelectorAll('.titular-card');

        titulares.forEach((card, index) => {
            const number = index + 1;
            const numberEl = card.querySelector('.badge-number');
            const titleEl = card.querySelector('.badge-text');

            if (numberEl) numberEl.textContent = number;
            if (titleEl) titleEl.textContent = 'Titular';

            // Update data attribute
            card.dataset.titularIndex = index;

            // Update input names
            card.querySelectorAll('[name]').forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.startsWith('titulares[')) {
                    const newName = name.replace(/titulares\[\d+\]/, `titulares[${index}]`);
                    input.setAttribute('name', newName);
                }
            });

            // Update dependentes container data attribute
            const depContainer = card.querySelector('.dependentes-container');
            if (depContainer) {
                depContainer.dataset.titularIndex = index;
            }
        });
    }

    // ============================================
    // Dependentes - hidden inputs + card resumo + modal
    // ============================================
    const DEP_FIELDS = ['nome', 'cpf', 'data_nascimento', 'email', 'telefone1', 'telefone2', 'parentesco', 'plano_anterior', 'operadora_anterior_id'];

    function getDepValues(depItem) {
        const values = {};
        DEP_FIELDS.forEach(field => {
            const input = depItem.querySelector(`input[data-dep-field="${field}"]`);
            values[field] = input ? input.value : '';
        });
        return values;
    }

    function parentescoLabel(value) {
        if (!value) return '';
        const option = document.querySelector(`#dep_parentesco option[value="${CSS.escape(value)}"]`);
        return option ? option.textContent.trim() : value;
    }

    function renderDependenteSummary(depItem) {
        const values = getDepValues(depItem);
        const card = depItem.querySelector('.dep-summary-card');
        if (!card) return;

        const initials = String(values.nome || '?')
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(part => part.charAt(0))
            .join('')
            .toUpperCase() || '?';

        const meta = [values.data_nascimento, values.telefone1].filter(Boolean).join(' · ');
        const planoAnteriorBadge = values.plano_anterior === 'SIM'
            ? '<span class="dep-pill dep-pill-warning">Plano anterior</span>'
            : '';

        card.innerHTML = `
            <span class="dep-avatar">${escapeHtml(initials)}</span>
            <div class="dep-summary-info">
                <div class="dep-summary-top">
                    <span class="dep-summary-nome">${escapeHtml(values.nome || 'Dependente sem nome')}</span>
                    <span class="dep-pill dep-pill-parentesco">${escapeHtml(parentescoLabel(values.parentesco) || 'Sem parentesco')}</span>
                    ${planoAnteriorBadge}
                </div>
                <div class="dep-summary-meta">
                    <span class="dep-chip">Dep.</span>
                    ${escapeHtml(meta || 'Dados incompletos')}
                </div>
            </div>
            <div class="dep-summary-actions">
                <button type="button" class="btn-action btn-edit-dep" title="Editar Dependente">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
                <button type="button" class="btn-action btn-remove-dep" title="Remover Dependente">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
        `;
    }

    function createDependenteElement(titularIdx, depIndex, data = {}) {
        const item = document.createElement('div');
        item.className = 'dependente-item';
        item.dataset.dependenteIndex = depIndex;

        DEP_FIELDS.forEach(field => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `titulares[${titularIdx}][dependentes][${depIndex}][${field}]`;
            input.dataset.depField = field;
            input.value = data[field] || (field === 'plano_anterior' ? 'NAO' : '');
            item.appendChild(input);
        });

        const card = document.createElement('div');
        card.className = 'dep-summary-card';
        item.appendChild(card);

        renderDependenteSummary(item);
        return item;
    }

    // ============================================
    // Remove Dependente
    // ============================================
    function removeDependente(depItem) {
        if (!depItem) return;

        const depContainer = depItem.closest('.dependentes-container');
        depItem.remove();

        updateStats();
        if (depContainer) {
            updateDependenteNumbers(depContainer);
        }
    }

    // ============================================
    // Update Dependente Numbers
    // ============================================
    function updateDependenteNumbers(depContainer) {
        const titularIdx = depContainer.dataset.titularIndex;
        const dependentes = depContainer.querySelectorAll('.dependente-item');

        dependentes.forEach((item, index) => {
            const number = index + 1;
            const chipEl = item.querySelector('.dep-chip');

            if (chipEl) chipEl.textContent = `Dep. ${number}`;

            // Update data attribute
            item.dataset.dependenteIndex = index;

            // Update input names
            item.querySelectorAll('[name]').forEach(input => {
                const name = input.getAttribute('name');
                if (name && name.includes('[dependentes]')) {
                    const newName = name
                        .replace(/titulares\[\d+\]/, `titulares[${titularIdx}]`)
                        .replace(/\[dependentes\]\[\d+\]/, `[dependentes][${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
    }

    // ============================================
    // Modal de Dependente (adicionar/editar)
    // ============================================
    const depModal = {
        mode: 'add',
        titularCard: null,
        depItem: null
    };

    function getDepModalFields() {
        const fields = {};
        DEP_FIELDS.forEach(field => {
            fields[field] = document.getElementById(`dep_${field}`);
        });
        return fields;
    }

    function toggleDepOperadoraAnterior(show) {
        const row = document.getElementById('dep-op-anterior-row');
        if (row) row.style.display = show ? '' : 'none';
    }

    function openDependenteModal(titularCard, depItem = null) {
        const overlay = document.getElementById('dep-modal-overlay');
        if (!overlay || !titularCard) return;

        depModal.mode = depItem ? 'edit' : 'add';
        depModal.titularCard = titularCard;
        depModal.depItem = depItem;

        const fields = getDepModalFields();
        const values = depItem ? getDepValues(depItem) : {};

        DEP_FIELDS.forEach(field => {
            const el = fields[field];
            if (!el) return;
            el.value = values[field] || (field === 'plano_anterior' ? 'NAO' : '');
        });

        toggleDepOperadoraAnterior((values.plano_anterior || 'NAO') === 'SIM');
        clearInvalid(overlay);

        const title = document.getElementById('dep-modal-title');
        if (title) title.textContent = depItem ? 'Editar Dependente' : 'Adicionar Dependente';

        const saveLabel = document.getElementById('dep-modal-save-label');
        if (saveLabel) saveLabel.textContent = depItem ? 'Salvar alterações' : 'Adicionar dependente';

        overlay.classList.add('dep-modal-open');
        document.body.style.overflow = 'hidden';
        fields.nome?.focus();
    }

    function closeDependenteModal() {
        const overlay = document.getElementById('dep-modal-overlay');
        if (!overlay) return;

        overlay.classList.remove('dep-modal-open');
        document.body.style.overflow = '';
        depModal.titularCard = null;
        depModal.depItem = null;
    }

    function validateDependenteModal(fields) {
        const checks = [
            { el: fields.nome, ok: fields.nome?.value.trim() !== '', msg: 'Informe o nome completo do dependente.' },
            { el: fields.cpf, ok: digitsOnly(fields.cpf?.value).length === 11, msg: 'Informe o CPF completo do dependente.' },
            { el: fields.data_nascimento, ok: isValidDateBr(fields.data_nascimento?.value), msg: 'Informe a data de nascimento do dependente (DD/MM/AAAA).' },
            { el: fields.email, ok: isValidEmail(fields.email?.value), msg: 'Informe um e-mail válido para o dependente.' },
            { el: fields.telefone1, ok: digitsOnly(fields.telefone1?.value).length >= 10, msg: 'Informe o telefone do dependente com DDD.' },
            { el: fields.parentesco, ok: fields.parentesco?.value !== '', msg: 'Selecione o parentesco do dependente.' },
            {
                el: fields.operadora_anterior_id,
                ok: fields.plano_anterior?.value !== 'SIM' || fields.operadora_anterior_id?.value !== '',
                msg: 'Informe qual era a operadora do plano anterior do dependente.'
            }
        ];

        let firstError = null;
        checks.forEach(check => {
            if (!check.ok) {
                setInvalid(check.el);
                if (!firstError) firstError = check;
            }
        });

        if (firstError) {
            showModernToast('error', 'Dependente incompleto', firstError.msg);
            firstError.el?.focus();
            return false;
        }
        return true;
    }

    function saveDependenteModal() {
        const fields = getDepModalFields();
        if (!validateDependenteModal(fields)) return;

        const values = {};
        DEP_FIELDS.forEach(field => {
            values[field] = fields[field] ? fields[field].value : '';
        });
        if (values.plano_anterior !== 'SIM') {
            values.operadora_anterior_id = '';
        }

        const nome = values.nome.trim();

        if (depModal.mode === 'edit' && depModal.depItem) {
            DEP_FIELDS.forEach(field => {
                const input = depModal.depItem.querySelector(`input[data-dep-field="${field}"]`);
                if (input) input.value = values[field];
            });
            depModal.depItem.classList.remove('is-invalid');
            renderDependenteSummary(depModal.depItem);
            const depContainer = depModal.depItem.closest('.dependentes-container');
            if (depContainer) updateDependenteNumbers(depContainer);
            showModernToast('success', 'Dependente atualizado', nome);
        } else if (depModal.titularCard) {
            const depContainer = depModal.titularCard.querySelector('.dependentes-container');
            if (!depContainer) return;
            const titularIdx = depContainer.dataset.titularIndex;
            const depIndex = depContainer.querySelectorAll('.dependente-item').length;
            depContainer.appendChild(createDependenteElement(titularIdx, depIndex, values));
            updateDependenteNumbers(depContainer);
            showModernToast('success', 'Dependente adicionado', nome);
        }

        updateStats();
        closeDependenteModal();
    }

    function initDependenteModal() {
        const overlay = document.getElementById('dep-modal-overlay');
        if (!overlay) return;

        applyCpfMask(document.getElementById('dep_cpf'));
        applyPhoneMask(document.getElementById('dep_telefone1'));
        applyPhoneMask(document.getElementById('dep_telefone2'));
        applyFlatpickrNascimento(document.getElementById('dep_data_nascimento'));

        document.getElementById('dep_plano_anterior')?.addEventListener('change', function () {
            toggleDepOperadoraAnterior(this.value === 'SIM');
        });

        document.getElementById('btn-dep-modal-save')?.addEventListener('click', saveDependenteModal);
        document.getElementById('btn-dep-modal-cancel')?.addEventListener('click', closeDependenteModal);
        document.getElementById('btn-dep-modal-close')?.addEventListener('click', closeDependenteModal);

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeDependenteModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('dep-modal-open')) {
                closeDependenteModal();
            }
        });
    }

    // ============================================
    // Update All Plan Selects
    // ============================================
    function updateAllPlanSelects() {
        document.querySelectorAll('.select-plano-titular').forEach(select => {
            const currentValue = select.value;
            select.innerHTML = renderPlanOptions();
            if (currentValue && Array.from(select.options).some(o => o.value === currentValue)) {
                select.value = currentValue;
            }
        });
    }

    // ============================================
    // Event Delegation
    // ============================================

    // Add Titular button (header)
    document.getElementById('btn-add-titular')?.addEventListener('click', addTitular);

    // Delegated events for dynamic elements
    document.addEventListener('click', function (e) {
        // Add Dependente (abre modal vazia)
        if (e.target.closest('.btn-add-dep')) {
            const titularCard = e.target.closest('.titular-card');
            if (titularCard) openDependenteModal(titularCard);
        }

        // Remove Titular
        if (e.target.closest('.btn-remove-titular')) {
            const titularCard = e.target.closest('.titular-card');
            if (titularCard) removeTitular(titularCard);
        }

        // Edit Dependente (abre modal preenchida)
        if (e.target.closest('.btn-edit-dep')) {
            const depItem = e.target.closest('.dependente-item');
            const titularCard = e.target.closest('.titular-card');
            if (depItem && titularCard) openDependenteModal(titularCard, depItem);
        }

        // Remove Dependente
        if (e.target.closest('.btn-remove-dep')) {
            const depItem = e.target.closest('.dependente-item');
            if (depItem) removeDependente(depItem);
        }

        // Remove Portabilidade
        if (e.target.closest('.btn-remove-port')) {
            const portItem = e.target.closest('.port-item');
            if (portItem) {
                portItem.remove();
                updatePortabilidadeNumbers();
            }
        }
    });

    // Quantity of titulares change
    document.getElementById('qtd_titulares')?.addEventListener('change', function () {
        const qtd = Math.max(1, parseInt(this.value, 10) || 1);
        const container = document.getElementById('titulares-container');
        const currentQtd = container.querySelectorAll('.titular-card').length;

        if (qtd > currentQtd) {
            // Add more titulares
            for (let i = currentQtd; i < qtd; i++) {
                addTitular();
            }
        } else if (qtd < currentQtd) {
            // Remove excess titulares (from end)
            const titulares = container.querySelectorAll('.titular-card');
            for (let i = currentQtd - 1; i >= qtd; i--) {
                titulares[i].remove();
            }
            updateStats();
            updateTitularNumbers();
        }
    });

    // Operadora change
    document.getElementById('operadora')?.addEventListener('change', function () {
        const operadoraId = this.value;
        planosDaOperadoraAtual = [];
        updateAllPlanSelects();

        // Detectar se é AMIL
        const selectedOption = this.options[this.selectedIndex];
        const nomeOperadora = (selectedOption?.dataset?.nome || selectedOption?.text || '').toUpperCase().trim();
        isOperadoraAmil = nomeOperadora.startsWith('AMIL');

        // Atualizar selects de coparticipação com as opções corretas
        updateAllCoparticipacaoSelects();

        if (!operadoraId) return;

        // Fetch plans
        fetch(`/comercial/getPlansByOperator/${operadoraId}`)
            .then(res => res.json())
            .then(data => {
                planosDaOperadoraAtual = Array.isArray(data) ? data.map(p => ({
                    id: p.id,
                    nome: p.nome,
                    acomodacao: p.acomodacao
                })) : [];
                updateAllPlanSelects();
            })
            .catch(() => {
                planosDaOperadoraAtual = [];
                updateAllPlanSelects();
                showModernToast('error', 'Erro', 'Erro ao carregar planos da operadora.');
            });
    });

    // Plano anterior toggle (titulares) - o de dependentes vive na modal
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('select-plano-anterior-titular')) {
            const titularCard = e.target.closest('.titular-card');
            const fieldRowOpAnterior = titularCard?.querySelector('.field-row-op-anterior');
            if (fieldRowOpAnterior) {
                fieldRowOpAnterior.style.display = e.target.value === 'SIM' ? 'flex' : 'none';
            }
        }
    });

    // ============================================
    // Angariacao Toggle
    // ============================================
    document.getElementById('angariacao_status')?.addEventListener('change', function () {
        const isSim = this.value === 'SIM';

        const badge = document.getElementById('angariacao-badge');
        const indicator = document.getElementById('angariacao-indicator');

        if (badge) {
            badge.className = `status-badge ${isSim ? 'badge-ativo' : 'badge-inativo'}`;
            badge.textContent = isSim ? 'Ativo' : 'Inativo';
        }

        if (indicator) {
            indicator.className = `status-indicator ${isSim ? 'indicator-ativo' : 'indicator-inativo'}`;
        }
    });

    // ============================================
    // Portabilidade Toggle
    // ============================================
    document.getElementById('portabilidade_status')?.addEventListener('change', function () {
        const isSim = this.value === 'SIM';

        const badge = document.getElementById('portabilidade-badge');
        const qtdInput = document.getElementById('qtd_portabilidade');
        const container = document.getElementById('portabilidade-container');

        if (badge) {
            badge.className = `status-badge ${isSim ? 'badge-ativo' : 'badge-inativo'}`;
            badge.textContent = isSim ? 'Ativo' : 'Inativo';
        }

        if (qtdInput) {
            qtdInput.style.display = isSim ? 'block' : 'none';
            if (!isSim) {
                qtdInput.value = 0;
            }
        }

        if (container) {
            container.style.display = isSim ? 'flex' : 'none';
            if (!isSim) {
                container.innerHTML = '';
            }
        }
    });

    // Quantity of portabilidade change
    document.getElementById('qtd_portabilidade')?.addEventListener('change', function () {
        const qtd = Math.max(0, parseInt(this.value, 10) || 0);
        renderPortabilidadeItems(qtd);
    });

    function renderPortabilidadeItems(qtd) {
        const container = document.getElementById('portabilidade-container');
        const template = document.getElementById('template-portabilidade');
        if (!container || !template) return;

        container.innerHTML = '';

        for (let i = 0; i < qtd; i++) {
            const html = template.content.cloneNode(true).querySelector('.port-item').outerHTML
                .replace(/__PORT_INDEX__/g, i)
                .replace(/__PORT_NUMBER__/g, i + 1);
            container.insertAdjacentHTML('beforeend', html);
        }
    }

    function updatePortabilidadeNumbers() {
        const container = document.getElementById('portabilidade-container');
        const items = container.querySelectorAll('.port-item');
        const qtdInput = document.getElementById('qtd_portabilidade');

        items.forEach((item, index) => {
            const numberEl = item.querySelector('.port-num');
            if (numberEl) numberEl.textContent = index + 1;

            // Update input name
            const input = item.querySelector('input[name]');
            if (input) {
                input.name = `portabilidades[${index}][nome]`;
            }
        });

        if (qtdInput) {
            qtdInput.value = items.length;
        }
    }

    // ============================================
    // Toggle ADESAO/PME
    // ============================================
    function switchToAdesaoMode() {
        currentPropostaType = 'ADESAO';
        const clientData = window.clientData || {};

        // Atualizar hidden input
        const tipoContratoInput = document.getElementById('tipo_contrato');
        if (tipoContratoInput) tipoContratoInput.value = 'ADESAO';

        // Atualizar badge
        const badge = document.getElementById('tipo-proposta-badge');
        if (badge) {
            badge.textContent = 'Adesao';
            badge.classList.remove('badge-ativo');
            badge.classList.add('badge-warning');
        }

        // Atualizar badge do header
        const headerBadge = document.querySelector('.header-badge');
        if (headerBadge) {
            headerBadge.textContent = 'ADESAO';
        }

        // Preencher dados do cliente
        const nomeInput = document.getElementById('nome_contrato');
        const cpfCnpjInput = document.getElementById('cpf_cnpj');

        if (nomeInput && clientData.nome) {
            nomeInput.value = clientData.nome;
        }

        // Trocar mascara CNPJ -> CPF
        if (cpfCnpjInput && typeof Cleave !== 'undefined') {
            if (cnpjCleaveInstance) {
                cnpjCleaveInstance.destroy();
            }
            cpfCnpjInput.value = clientData.cpf || '';
            cnpjCleaveInstance = new Cleave(cpfCnpjInput, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
            cpfCnpjInput.placeholder = '000.000.000-00';
        }

        // Atualizar labels
        const labelCpfCnpj = document.getElementById('label-cpf-cnpj');
        if (labelCpfCnpj) labelCpfCnpj.textContent = 'CPF';

        const labelRazaoSocial = document.getElementById('label-razao-social');
        if (labelRazaoSocial) labelRazaoSocial.textContent = 'Nome do Cliente';

        // Ocultar campos
        const fieldTipoEmpresa = document.getElementById('field-tipo-empresa');
        const fieldDataAbertura = document.getElementById('field-data-abertura');
        if (fieldTipoEmpresa) fieldTipoEmpresa.style.display = 'none';
        if (fieldDataAbertura) fieldDataAbertura.style.display = 'none';

        // Remover required do tipo_empresa
        const tipoEmpresaSelect = document.getElementById('tipo_empresa');
        if (tipoEmpresaSelect) {
            tipoEmpresaSelect.removeAttribute('required');
            tipoEmpresaSelect.value = '';
        }

        // Ocultar campo Cargo nos titulares (nao faz sentido para pessoa fisica)
        document.querySelectorAll('.field-cargo-wrapper').forEach(wrapper => {
            wrapper.style.display = 'none';
            const select = wrapper.querySelector('.select-cargo-titular');
            if (select) {
                select.removeAttribute('required');
                select.value = '';
            }
        });
    }

    function switchToPmeMode() {
        currentPropostaType = 'PME';

        // Atualizar hidden input
        const tipoContratoInput = document.getElementById('tipo_contrato');
        if (tipoContratoInput) tipoContratoInput.value = 'PME';

        // Atualizar badge
        const badge = document.getElementById('tipo-proposta-badge');
        if (badge) {
            badge.textContent = 'PME';
            badge.classList.remove('badge-warning');
            badge.classList.add('badge-ativo');
        }

        // Atualizar badge do header
        const headerBadge = document.querySelector('.header-badge');
        if (headerBadge) {
            headerBadge.textContent = 'PME';
        }

        // Limpar campos
        const nomeInput = document.getElementById('nome_contrato');
        if (nomeInput) nomeInput.value = '';

        // Trocar mascara CPF -> CNPJ
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        if (cpfCnpjInput && typeof Cleave !== 'undefined') {
            if (cnpjCleaveInstance) {
                cnpjCleaveInstance.destroy();
            }
            cpfCnpjInput.value = '';
            cnpjCleaveInstance = new Cleave(cpfCnpjInput, {
                delimiters: ['.', '.', '/', '-'],
                blocks: [2, 3, 3, 4, 2],
                numericOnly: true
            });
            cpfCnpjInput.placeholder = '00.000.000/0000-00';
        }

        // Atualizar labels
        const labelCpfCnpj = document.getElementById('label-cpf-cnpj');
        if (labelCpfCnpj) labelCpfCnpj.textContent = 'CNPJ';

        const labelRazaoSocial = document.getElementById('label-razao-social');
        if (labelRazaoSocial) labelRazaoSocial.textContent = 'Razao Social';

        // Mostrar campos
        const fieldTipoEmpresa = document.getElementById('field-tipo-empresa');
        const fieldDataAbertura = document.getElementById('field-data-abertura');
        if (fieldTipoEmpresa) fieldTipoEmpresa.style.display = '';
        if (fieldDataAbertura) fieldDataAbertura.style.display = '';

        // Restaurar required do tipo_empresa
        const tipoEmpresaSelect = document.getElementById('tipo_empresa');
        if (tipoEmpresaSelect) {
            tipoEmpresaSelect.setAttribute('required', '');
        }

        // Mostrar campo Cargo nos titulares
        document.querySelectorAll('.field-cargo-wrapper').forEach(wrapper => {
            wrapper.style.display = '';
            const select = wrapper.querySelector('.select-cargo-titular');
            if (select) {
                select.setAttribute('required', '');
            }
        });
    }

    // Event listener para toggle de tipo de proposta
    document.getElementById('tipo_proposta_toggle')?.addEventListener('change', function() {
        if (this.value === 'ADESAO') {
            switchToAdesaoMode();
        } else {
            switchToPmeMode();
        }
    });

    // ============================================
    // Form Validation
    // ============================================
    function validateForm() {
        const errors = [];
        let firstInvalid = null;

        const fail = (el, msg) => {
            errors.push(msg);
            setInvalid(el);
            if (!firstInvalid && el) firstInvalid = el;
        };

        clearInvalid();

        // Empresa / contrato
        const nomeContrato = document.getElementById('nome_contrato');
        if (!nomeContrato?.value.trim()) {
            fail(nomeContrato, currentPropostaType === 'PME' ? 'Informe a razão social da empresa.' : 'Informe o nome do cliente.');
        }

        const cpfCnpj = document.getElementById('cpf_cnpj');
        if (!cpfCnpj?.value.trim()) {
            fail(cpfCnpj, currentPropostaType === 'PME' ? 'Informe o CNPJ da empresa.' : 'Informe o CPF do cliente.');
        }

        if (currentPropostaType === 'PME') {
            const tipoEmpresa = document.getElementById('tipo_empresa');
            if (!tipoEmpresa?.value) {
                fail(tipoEmpresa, 'Selecione o tipo da empresa.');
            }
        }

        const operadora = document.getElementById('operadora');
        if (!operadora?.value) {
            fail(operadora, 'Selecione a operadora.');
        }

        // Titulares
        const titulares = document.querySelectorAll('#titulares-container .titular-card');
        if (titulares.length === 0) {
            errors.push('Adicione pelo menos um titular.');
        }

        const emailsVistos = {};
        const telefonesVistos = {};

        titulares.forEach((card, index) => {
            const num = index + 1;
            const body = card.querySelector('.titular-body');
            const field = suffix => body?.querySelector(`[name$="${suffix}"]`);

            const nome = field('[nome]');
            if (!nome?.value.trim()) fail(nome, `Informe o nome completo do Titular ${num}.`);

            const cpf = field('[cpf]');
            if (digitsOnly(cpf?.value).length !== 11) fail(cpf, `Informe o CPF completo do Titular ${num}.`);

            const nascimento = field('[data_nascimento]');
            if (!isValidDateBr(nascimento?.value)) fail(nascimento, `Informe a data de nascimento do Titular ${num} (DD/MM/AAAA).`);

            const email = field('[email]');
            if (!isValidEmail(email?.value)) {
                fail(email, `Informe um e-mail válido para o Titular ${num}.`);
            } else {
                const chave = email.value.trim().toLowerCase();
                if (emailsVistos[chave]) {
                    fail(email, `Titular ${emailsVistos[chave].num} e Titular ${num} não podem ter o mesmo e-mail.`);
                    setInvalid(emailsVistos[chave].el);
                } else {
                    emailsVistos[chave] = { num, el: email };
                }
            }

            const telefone = field('[telefone1]');
            const telefoneDigits = digitsOnly(telefone?.value);
            if (telefoneDigits.length < 10) {
                fail(telefone, `Informe o telefone do Titular ${num} com DDD.`);
            } else if (telefonesVistos[telefoneDigits]) {
                fail(telefone, `Titular ${telefonesVistos[telefoneDigits].num} e Titular ${num} não podem ter o mesmo telefone.`);
                setInvalid(telefonesVistos[telefoneDigits].el);
            } else {
                telefonesVistos[telefoneDigits] = { num, el: telefone };
            }

            if (currentPropostaType === 'PME') {
                const cargo = card.querySelector('.select-cargo-titular');
                if (!cargo?.value) fail(cargo, `Selecione o cargo do Titular ${num}.`);
            }

            const plano = card.querySelector('.select-plano-titular');
            if (!plano?.value) fail(plano, `Selecione o plano do Titular ${num}.`);

            const copart = card.querySelector('.select-coparticipacao-titular');
            if (!copart?.value) fail(copart, `Selecione a coparticipação do Titular ${num}.`);

            const planoAnterior = card.querySelector('.select-plano-anterior-titular');
            if (planoAnterior?.value === 'SIM') {
                const opAnterior = card.querySelector('.field-op-anterior-titular');
                if (!opAnterior?.value) fail(opAnterior, `Informe qual era a operadora do plano anterior do Titular ${num}.`);
            }

            // Dependentes (hidden inputs) - a modal já valida, mas o restore de
            // old input pode trazer dados incompletos
            card.querySelectorAll('.dependente-item').forEach(depItem => {
                const dep = getDepValues(depItem);
                const depOk = dep.nome.trim() !== ''
                    && digitsOnly(dep.cpf).length === 11
                    && isValidDateBr(dep.data_nascimento)
                    && isValidEmail(dep.email)
                    && digitsOnly(dep.telefone1).length >= 10
                    && dep.parentesco !== ''
                    && (dep.plano_anterior !== 'SIM' || dep.operadora_anterior_id !== '');

                if (!depOk) {
                    const summaryCard = depItem.querySelector('.dep-summary-card');
                    fail(summaryCard, `Complete os dados do dependente "${dep.nome.trim() || 'sem nome'}" do Titular ${num} (clique em editar).`);
                }
            });
        });

        return { ok: errors.length === 0, errors, firstInvalid };
    }

    document.getElementById('formNovaProposta')?.addEventListener('submit', function (e) {
        const result = validateForm();
        if (result.ok) {
            // Proposta a caminho do servidor: descarta o rascunho local.
            // Se a validacao server-side falhar, o old() restaura e o
            // rascunho volta a ser salvo na sequencia.
            draftDisabled = true;
            clearTimeout(draftSaveTimer);
            clearDraft();
            return true;
        }

        e.preventDefault();

        const extra = result.errors.length > 1 ? ` (+${result.errors.length - 1} outras pendências)` : '';
        showModernToast('error', 'Proposta incompleta', result.errors[0] + extra);

        if (result.firstInvalid) {
            result.firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof result.firstInvalid.focus === 'function') {
                setTimeout(() => result.firstInvalid.focus({ preventScroll: true }), 300);
            }
        }
        return false;
    });

    // ============================================
    // Restore Old Data (after validation error)
    // ============================================
    function restoreOldTitulares() {
        const oldTitulares = window.oldTitulares || [];
        const oldOperadoraId = window.oldOperadoraId;

        if (!oldTitulares.length) {
            return false;
        }

        // Se há operadora old, disparar evento change para carregar planos
        if (oldOperadoraId) {
            const operadoraSelect = document.getElementById('operadora');
            if (operadoraSelect && operadoraSelect.value) {
                // Detectar se é AMIL antes de restaurar titulares
                const selectedOption = operadoraSelect.options[operadoraSelect.selectedIndex];
                const nomeOperadora = (selectedOption?.dataset?.nome || selectedOption?.text || '').toUpperCase().trim();
                isOperadoraAmil = nomeOperadora.startsWith('AMIL');

                // Fetch plans first, then restore titulares
                fetch(`/comercial/getPlansByOperator/${operadoraSelect.value}`)
                    .then(res => res.json())
                    .then(data => {
                        planosDaOperadoraAtual = Array.isArray(data) ? data.map(p => ({
                            id: p.id,
                            nome: p.nome,
                            acomodacao: p.acomodacao
                        })) : [];

                        // Agora restaurar titulares com planos disponíveis
                        oldTitulares.forEach((titular, index) => {
                            addTitularWithData(titular, index);
                        });

                        updateStats();
                    })
                    .catch(() => {
                        // Mesmo com erro, restaurar titulares
                        oldTitulares.forEach((titular, index) => {
                            addTitularWithData(titular, index);
                        });
                        updateStats();
                    });
            } else {
                oldTitulares.forEach((titular, index) => {
                    addTitularWithData(titular, index);
                });
                updateStats();
            }
        } else {
            oldTitulares.forEach((titular, index) => {
                addTitularWithData(titular, index);
            });
            updateStats();
        }

        return true;
    }

    // ============================================
    // Add Titular with Data (for restoring old)
    // ============================================
    function addTitularWithData(titularData, forceIndex = null) {
        const container = document.getElementById('titulares-container');
        const template = document.getElementById('template-titular');
        if (!container || !template) return;

        const idx = forceIndex !== null ? forceIndex : titularIndex;
        const number = idx + 1;

        const html = template.content.cloneNode(true).querySelector('.titular-card').outerHTML
            .replace(/__INDEX__/g, idx)
            .replace(/__NUMBER__/g, number);

        container.insertAdjacentHTML('beforeend', html);

        const newBlock = container.lastElementChild;

        // Update coparticipacao select options based on operadora before setting value
        const copartSelect = newBlock.querySelector('.select-coparticipacao-titular');
        if (copartSelect) {
            copartSelect.innerHTML = getCoparticipacaoOptions();
        }

        // Fill data
        const setVal = (selector, value) => {
            const el = newBlock.querySelector(selector);
            if (el && value) el.value = value;
        };

        setVal(`[name="titulares[${idx}][nome]"]`, titularData.nome || '');
        setVal(`[name="titulares[${idx}][cpf]"]`, titularData.cpf || '');
        setVal(`[name="titulares[${idx}][data_nascimento]"]`, titularData.data_nascimento || '');
        setVal(`[name="titulares[${idx}][email]"]`, titularData.email || '');
        setVal(`[name="titulares[${idx}][telefone1]"]`, titularData.telefone1 || '');
        setVal(`[name="titulares[${idx}][telefone2]"]`, titularData.telefone2 || '');
        setVal(`[name="titulares[${idx}][cargo]"]`, titularData.cargo || '');
        setVal(`[name="titulares[${idx}][coparticipacao]"]`, titularData.coparticipacao || '');
        setVal(`[name="titulares[${idx}][plano_anterior]"]`, titularData.plano_anterior || 'NAO');
        setVal(`[name="titulares[${idx}][operadora_anterior_id]"]`, titularData.operadora_anterior_id || '');

        // Plan select
        const planSelect = newBlock.querySelector('.select-plano-titular');
        if (planSelect) {
            planSelect.innerHTML = renderPlanOptions();
            if (titularData.plano_id) {
                planSelect.value = titularData.plano_id;
            }
        }

        // Show operadora anterior row if plano_anterior = SIM
        if (titularData.plano_anterior === 'SIM') {
            const opAnteriorRow = newBlock.querySelector('.field-row-op-anterior');
            if (opAnteriorRow) opAnteriorRow.style.display = 'flex';
        }

        // Apply masks
        newBlock.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);
        newBlock.querySelectorAll('.mask-cpf').forEach(applyCpfMask);
        newBlock.querySelectorAll('.flatpickr-nascimento').forEach(applyFlatpickrNascimento);

        // Restore dependentes
        if (titularData.dependentes && Array.isArray(titularData.dependentes)) {
            titularData.dependentes.forEach((depData, depIndex) => {
                addDependenteWithData(newBlock, depData, idx, depIndex);
            });
        }

        // Se estiver em modo ADESAO, ocultar campo Cargo
        if (currentPropostaType === 'ADESAO') {
            const cargoWrapper = newBlock.querySelector('.field-cargo-wrapper');
            if (cargoWrapper) {
                cargoWrapper.style.display = 'none';
                const cargoSelect = cargoWrapper.querySelector('.select-cargo-titular');
                if (cargoSelect) {
                    cargoSelect.removeAttribute('required');
                    cargoSelect.value = '';
                }
            }
        }

        if (forceIndex === null) {
            titularIndex++;
        } else {
            titularIndex = Math.max(titularIndex, idx + 1);
        }
    }

    // ============================================
    // Add Dependente with Data (for restoring old)
    // ============================================
    function addDependenteWithData(titularCard, depData, titularIdx, depIndex) {
        const depContainer = titularCard.querySelector('.dependentes-container');
        if (!depContainer) return;

        depContainer.appendChild(createDependenteElement(titularIdx, depIndex, depData || {}));
        updateDependenteNumbers(depContainer);
    }

    // ============================================
    // Rascunho local - persiste o preenchimento em caso de F5.
    // old() do Laravel so cobre o redirect de erro de validacao;
    // recarregar a pagina e um GET novo, entao o rascunho vive no
    // localStorage (por contato) ate a proposta ser enviada.
    // ============================================
    const DRAFT_KEY = `novaPropostaDraft:${document.getElementById('contato_id')?.value || 'novo'}`;
    const DRAFT_TTL_MS = 24 * 60 * 60 * 1000; // expira em 24h
    const DRAFT_TOP_FIELDS = [
        'nome_contrato', 'cpf_cnpj', 'tipo_empresa', 'data_abertura', 'email',
        'telefone1', 'telefone2', 'operadora', 'qtd_titulares', 'plano_dental',
        'valor_contrato', 'taxa_angariacao', 'angariacao_status',
        'qtd_portabilidade', 'portabilidade_status', 'obs_contrato'
    ];
    let draftSaveTimer = null;
    let draftDisabled = false;

    function serializeTitularCard(card) {
        const body = card.querySelector('.titular-body');
        const field = suffix => body?.querySelector(`[name$="${suffix}"]`)?.value || '';

        return {
            nome: field('[nome]'),
            cpf: field('[cpf]'),
            data_nascimento: field('[data_nascimento]'),
            email: field('[email]'),
            telefone1: field('[telefone1]'),
            telefone2: field('[telefone2]'),
            cargo: field('[cargo]'),
            plano_id: field('[plano_id]'),
            coparticipacao: field('[coparticipacao]'),
            plano_anterior: field('[plano_anterior]') || 'NAO',
            operadora_anterior_id: field('[operadora_anterior_id]'),
            dependentes: Array.from(card.querySelectorAll('.dependente-item')).map(getDepValues)
        };
    }

    function buildDraft() {
        const fields = {};
        DRAFT_TOP_FIELDS.forEach(id => {
            const el = document.getElementById(id);
            if (el) fields[id] = el.value;
        });

        return {
            savedAt: Date.now(),
            tipo_contrato: currentPropostaType,
            fields,
            titulares: Array.from(document.querySelectorAll('#titulares-container .titular-card')).map(serializeTitularCard),
            portabilidades: Array.from(document.querySelectorAll('#portabilidade-container .port-item input[name]')).map(i => i.value)
        };
    }

    function draftTemConteudo(draft) {
        const fields = draft.fields || {};
        const camposDigitados = ['nome_contrato', 'cpf_cnpj', 'email', 'telefone1', 'telefone2', 'obs_contrato', 'operadora']
            .some(id => String(fields[id] || '').trim() !== '');
        const titularDigitado = (draft.titulares || []).some(t =>
            [t.nome, t.cpf, t.email, t.telefone1, t.data_nascimento].some(v => String(v || '').trim() !== '')
            || (t.dependentes || []).length > 0
        );
        return camposDigitados || titularDigitado;
    }

    function saveDraftNow() {
        if (draftDisabled) return;
        try {
            const draft = buildDraft();
            if (draftTemConteudo(draft)) {
                localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            } else {
                localStorage.removeItem(DRAFT_KEY);
            }
        } catch (err) {
            // localStorage indisponivel (modo privado/quota) - segue sem rascunho
        }
    }

    function scheduleDraftSave() {
        if (draftDisabled) return;
        clearTimeout(draftSaveTimer);
        draftSaveTimer = setTimeout(saveDraftNow, 400);
    }

    function clearDraft() {
        try {
            localStorage.removeItem(DRAFT_KEY);
        } catch (err) {
            // ignora
        }
    }

    function readDraft() {
        try {
            const raw = localStorage.getItem(DRAFT_KEY);
            if (!raw) return null;

            const draft = JSON.parse(raw);
            if (!draft || typeof draft !== 'object') return null;

            if (!draft.savedAt || Date.now() - draft.savedAt > DRAFT_TTL_MS) {
                clearDraft();
                return null;
            }
            return draft;
        } catch (err) {
            return null;
        }
    }

    // Restaura o rascunho; retorna true quando restaurou titulares
    // (mesma semantica de restoreOldTitulares para o init).
    function restoreDraft() {
        const draft = readDraft();
        if (!draft || !draftTemConteudo(draft)) return false;

        if (draft.tipo_contrato === 'ADESAO') {
            const toggle = document.getElementById('tipo_proposta_toggle');
            if (toggle) toggle.value = 'ADESAO';
            switchToAdesaoMode();
        }

        Object.entries(draft.fields || {}).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el && value !== undefined && value !== null) el.value = value;
        });

        // Badges e visibilidades que dependem do evento change
        ['plano_dental', 'angariacao_status', 'portabilidade_status'].forEach(id => {
            document.getElementById(id)?.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // Portabilidades (o change acima ja mostrou o container quando SIM)
        const qtdPort = parseInt(draft.fields?.qtd_portabilidade, 10) || 0;
        if (draft.fields?.portabilidade_status === 'SIM' && qtdPort > 0) {
            renderPortabilidadeItems(qtdPort);
            const inputs = document.querySelectorAll('#portabilidade-container .port-item input[name]');
            (draft.portabilidades || []).forEach((nome, i) => {
                if (inputs[i]) inputs[i].value = nome;
            });
        }

        const temTitulares = Array.isArray(draft.titulares) && draft.titulares.length > 0;
        if (temTitulares) {
            // Reusa o fluxo de restore do old(): carrega os planos da
            // operadora e recria titulares + dependentes.
            window.oldTitulares = draft.titulares;
            window.oldOperadoraId = draft.fields?.operadora || null;
            restoreOldTitulares();
        }

        showModernToast('info', 'Rascunho recuperado', 'Restauramos o que voce preencheu antes de atualizar a pagina.');
        return temTitulares;
    }

    // Qualquer digitacao/selecao no formulario agenda o salvamento
    const formNovaProposta = document.getElementById('formNovaProposta');
    formNovaProposta?.addEventListener('input', scheduleDraftSave);
    formNovaProposta?.addEventListener('change', scheduleDraftSave);

    // Garante o ultimo estado mesmo se o F5 vier antes do debounce
    window.addEventListener('beforeunload', saveDraftNow);

    // ============================================
    // Initialize - Add first titular on load or restore old
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('titulares-container');

        initDependenteModal();

        // Prioridade de restore: old() (erro de validacao no servidor)
        // vem antes do rascunho local (F5).
        const hasOldData = restoreOldTitulares();
        const hasDraft = !hasOldData && restoreDraft();

        // Se nao ha dado nenhum para restaurar, adicionar titular vazio
        if (!hasOldData && !hasDraft && container && container.children.length === 0) {
            addTitular();
        }

        // Erros vindos do servidor (flash session + validação)
        const flash = window.pageFlash || {};
        const serverErrors = window.pageErrors || [];

        if (serverErrors.length) {
            const extra = serverErrors.length > 1 ? ` (+${serverErrors.length - 1} outras pendências)` : '';
            showModernToast('error', 'Proposta incompleta', serverErrors[0] + extra);
        } else if (flash.status === 'error' && flash.message) {
            showModernToast('error', 'Erro', flash.message);
        }
    });

})();
