'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    let planosDaOperadoraAtual = [];
    let titularIndex = 0;

    // ============================================
    // Initialize Flatpickr for date fields
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
    const inputCnpj = document.getElementById('cpf_cnpj');
    if (inputCnpj && typeof Cleave !== 'undefined') {
        new Cleave(inputCnpj, {
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
                locale: 'pt',
                allowInput: true
            });
        }
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
        const dependentes = document.querySelectorAll('#titulares-container .dependente-card').length;
        const totalVidas = titulares + dependentes;

        document.getElementById('total-titulares').textContent = titulares;
        document.getElementById('total-dependentes').textContent = dependentes;
        document.getElementById('total-vidas').textContent = totalVidas;
        document.getElementById('vidas').value = totalVidas;
    }

    // ============================================
    // Render Plan Options
    // ============================================
    function renderPlanOptions() {
        if (!planosDaOperadoraAtual.length) {
            return '<option value="">Selecione a operadora primeiro</option>';
        }
        let opts = '<option value="">Selecione o plano...</option>';
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

        // Apply masks to new fields
        newBlock.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);
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
            if (typeof toastr !== 'undefined') {
                toastr.warning('O contrato precisa ter pelo menos 1 titular.');
            }
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
    // Add Dependente
    // ============================================
    function addDependente(titularCard) {
        const depContainer = titularCard.querySelector('.dependentes-container');
        const template = document.getElementById('template-dependente');
        if (!depContainer || !template) return;

        const titularIdx = depContainer.dataset.titularIndex;
        const depIndex = depContainer.querySelectorAll('.dependente-card').length;
        const depNumber = depIndex + 1;

        const html = template.content.cloneNode(true).querySelector('.dependente-card').outerHTML
            .replace(/__INDEX__/g, titularIdx)
            .replace(/__DEP_INDEX__/g, depIndex)
            .replace(/__DEP_NUMBER__/g, depNumber);

        depContainer.insertAdjacentHTML('beforeend', html);

        // Get the newly added block
        const newBlock = depContainer.lastElementChild;

        // Apply masks
        newBlock.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);
        newBlock.querySelectorAll('.mask-cpf').forEach(applyCpfMask);
        newBlock.querySelectorAll('.flatpickr-nascimento').forEach(applyFlatpickrNascimento);

        updateStats();
        updateDependenteNumbers(depContainer);
    }

    // ============================================
    // Remove Dependente
    // ============================================
    function removeDependente(dependenteCard) {
        if (!dependenteCard) return;

        const depContainer = dependenteCard.closest('.dependentes-container');
        dependenteCard.remove();

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
        const dependentes = depContainer.querySelectorAll('.dependente-card');

        dependentes.forEach((card, index) => {
            const number = index + 1;
            const titleEl = card.querySelector('.dep-badge');

            if (titleEl) titleEl.textContent = `Dep. ${number}`;

            // Update data attribute
            card.dataset.dependenteIndex = index;

            // Update input names
            card.querySelectorAll('[name]').forEach(input => {
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
        // Add Dependente
        if (e.target.closest('.btn-add-dep')) {
            const titularCard = e.target.closest('.titular-card');
            if (titularCard) addDependente(titularCard);
        }

        // Remove Titular
        if (e.target.closest('.btn-remove-titular')) {
            const titularCard = e.target.closest('.titular-card');
            if (titularCard) removeTitular(titularCard);
        }

        // Remove Dependente
        if (e.target.closest('.btn-remove-dep')) {
            const dependenteCard = e.target.closest('.dependente-card');
            if (dependenteCard) removeDependente(dependenteCard);
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
                if (typeof toastr !== 'undefined') {
                    toastr.error('Erro ao carregar planos da operadora.');
                }
            });
    });

    // Plano anterior toggle (dependentes)
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('select-plano-anterior')) {
            const fieldOperadora = e.target.closest('.field-row')?.querySelector('.field-op-anterior');
            if (fieldOperadora) {
                fieldOperadora.style.display = e.target.value === 'SIM' ? 'block' : 'none';
            }
        }

        // Plano anterior toggle (titulares)
        if (e.target.classList.contains('select-plano-anterior-titular')) {
            const fieldOperadora = e.target.closest('.field-row')?.querySelector('.field-op-anterior-titular');
            if (fieldOperadora) {
                fieldOperadora.style.display = e.target.value === 'SIM' ? 'block' : 'none';
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
    // Form Validation
    // ============================================
    document.getElementById('formNovaProposta')?.addEventListener('submit', function (e) {
        // Check operadora
        const operadoraId = document.getElementById('operadora')?.value;
        if (!operadoraId) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') toastr.error('Selecione a operadora.');
            return false;
        }

        // Check if at least one titular exists
        const titulares = document.querySelectorAll('#titulares-container .titular-card');
        if (titulares.length === 0) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') toastr.error('Adicione pelo menos um titular.');
            return false;
        }

        // Check all titulares have plans selected
        const planSelects = document.querySelectorAll('#titulares-container .select-plano-titular');
        for (const select of planSelects) {
            if (!select.value) {
                e.preventDefault();
                if (typeof toastr !== 'undefined') toastr.error('Selecione o plano para todos os titulares.');
                return false;
            }
        }

        return true;
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

        // Show operadora anterior if plano_anterior = SIM
        if (titularData.plano_anterior === 'SIM') {
            const opAnteriorField = newBlock.querySelector('.field-op-anterior-titular');
            if (opAnteriorField) opAnteriorField.style.display = 'block';
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
        const template = document.getElementById('template-dependente');
        if (!depContainer || !template) return;

        const depNumber = depIndex + 1;

        const html = template.content.cloneNode(true).querySelector('.dependente-card').outerHTML
            .replace(/__INDEX__/g, titularIdx)
            .replace(/__DEP_INDEX__/g, depIndex)
            .replace(/__DEP_NUMBER__/g, depNumber);

        depContainer.insertAdjacentHTML('beforeend', html);

        const newBlock = depContainer.lastElementChild;

        // Fill data
        const setVal = (selector, value) => {
            const el = newBlock.querySelector(selector);
            if (el && value) el.value = value;
        };

        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][nome]"]`, depData.nome || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][cpf]"]`, depData.cpf || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][data_nascimento]"]`, depData.data_nascimento || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][email]"]`, depData.email || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][telefone1]"]`, depData.telefone1 || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][telefone2]"]`, depData.telefone2 || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][parentesco]"]`, depData.parentesco || '');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][plano_anterior]"]`, depData.plano_anterior || 'NAO');
        setVal(`[name="titulares[${titularIdx}][dependentes][${depIndex}][operadora_anterior_id]"]`, depData.operadora_anterior_id || '');

        // Show operadora anterior if plano_anterior = SIM
        if (depData.plano_anterior === 'SIM') {
            const opAnteriorField = newBlock.querySelector('.field-op-anterior');
            if (opAnteriorField) opAnteriorField.style.display = 'block';
        }

        // Apply masks
        newBlock.querySelectorAll('.mask-telefone').forEach(applyPhoneMask);
        newBlock.querySelectorAll('.mask-cpf').forEach(applyCpfMask);
        newBlock.querySelectorAll('.flatpickr-nascimento').forEach(applyFlatpickrNascimento);
    }

    // ============================================
    // Initialize - Add first titular on load or restore old
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('titulares-container');

        // Tentar restaurar dados old primeiro
        const hasOldData = restoreOldTitulares();

        // Se não há dados old, adicionar titular vazio
        if (!hasOldData && container && container.children.length === 0) {
            addTitular();
        }
    });

})();
