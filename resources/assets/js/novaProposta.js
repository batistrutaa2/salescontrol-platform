'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    let planosDaOperadoraAtual = [];
    let titularIndex = 0;
    let usaCoparticipacaoDetalhada = false;
    let currentPropostaType = document.getElementById('tipo_contrato')?.value || 'PME';
    let cnpjCleaveInstance = null;
    let portabilidades = [];
    const isGuidedProposal = Boolean(document.querySelector('.np-wizard-shell'));

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

    function cpfValido(value) {
        const cpf = digitsOnly(value);
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        for (let pos = 9; pos < 11; pos++) {
            let soma = 0;
            for (let i = 0; i < pos; i++) soma += Number(cpf[i]) * ((pos + 1) - i);
            let digito = (10 * soma) % 11;
            if (digito === 10) digito = 0;
            if (Number(cpf[pos]) !== digito) return false;
        }
        return true;
    }

    function cnpjValido(value) {
        const cnpj = digitsOnly(value);
        if (cnpj.length !== 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
        const pesos = [[5,4,3,2,9,8,7,6,5,4,3,2], [6,5,4,3,2,9,8,7,6,5,4,3,2]];
        return pesos.every((lista, etapa) => {
            const soma = lista.reduce((total, peso, i) => total + Number(cnpj[i]) * peso, 0);
            const resto = soma % 11;
            return Number(cnpj[12 + etapa]) === (resto < 2 ? 0 : 11 - resto);
        });
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
                    <button type="button" class="toast-close" aria-label="Fechar notificação" onclick="Swal.close()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
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
        if (usaCoparticipacaoDetalhada) {
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
        updateTitularActionState();
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
            delimiters: currentPropostaType === 'ADESAO' ? ['.', '.', '-'] : ['.', '.', '/', '-'],
            blocks: currentPropostaType === 'ADESAO' ? [3, 3, 3, 2] : [2, 3, 3, 4, 2],
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
    // Enriquecimento de CPF/CNPJ da proposta
    // ============================================
    const lookupStates = new WeakMap();

    function lookupStatus(input) {
        return input.closest('.np-field')?.querySelector('.np-lookup-status')
            || document.querySelector(`[data-lookup-status-for="${input.id}"]`);
    }

    function setLookupState(input, state, message) {
        const field = input.closest('.np-field');
        const status = lookupStatus(input);
        const lookupButton = input.id === 'portabilidade_cpf'
            ? document.getElementById('btn-consultar-portabilidade-cpf')
            : null;
        field?.classList.toggle('is-looking-up', state === 'loading');
        if (lookupButton) {
            lookupButton.disabled = state === 'loading';
            lookupButton.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
        }
        if (status) {
            status.className = `np-lookup-status is-${state}`;
            status.textContent = message;
        }
    }

    function preencherSeVazio(field, value) {
        if (!field || !value || String(field.value || '').trim() !== '') return;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function aplicarEnriquecimento(input, dados) {
        if (input.id === 'cpf_cnpj') {
            preencherSeVazio(document.getElementById('nome_contrato'), dados.nome);
            preencherSeVazio(document.getElementById('data_abertura'), dados.data_abertura);
            preencherSeVazio(document.getElementById('email'), dados.email);
            preencherSeVazio(document.getElementById('telefone1'), dados.telefone1);
            preencherSeVazio(document.getElementById('telefone2'), dados.telefone2);
            return;
        }
        if (input.id === 'dep_cpf') {
            preencherSeVazio(document.getElementById('dep_nome'), dados.nome);
            preencherSeVazio(document.getElementById('dep_data_nascimento'), dados.data_nascimento);
            preencherSeVazio(document.getElementById('dep_email'), dados.email);
            preencherSeVazio(document.getElementById('dep_telefone1'), dados.telefone1);
            preencherSeVazio(document.getElementById('dep_telefone2'), dados.telefone2);
            return;
        }
        if (input.id === 'portabilidade_cpf') {
            preencherSeVazio(document.getElementById('portabilidade_nome'), dados.nome);
            return;
        }
        const card = input.closest('.titular-card');
        if (!card) return;
        const field = suffix => card.querySelector(`[name$="${suffix}"]`);
        preencherSeVazio(field('[nome]'), dados.nome);
        preencherSeVazio(field('[data_nascimento]'), dados.data_nascimento);
        preencherSeVazio(field('[email]'), dados.email);
        preencherSeVazio(field('[telefone1]'), dados.telefone1);
        preencherSeVazio(field('[telefone2]'), dados.telefone2);
    }

    async function consultarDocumento(input, force = false) {
        const documento = digitsOnly(input.value);
        const valido = documento.length === 11 ? cpfValido(documento) : cnpjValido(documento);
        const estado = lookupStates.get(input) || {};
        if (!valido) {
            setLookupState(input, 'idle', 'Digite um CPF válido para realizar a consulta.');
            input.focus();
            return;
        }
        if (!force && estado.last === documento) return;
        estado.controller?.abort();
        estado.controller = new AbortController();
        estado.last = documento;
        lookupStates.set(input, estado);
        setLookupState(input, 'loading', 'Consultando dados…');
        try {
            const response = await fetch(window.propostaLookupUrl, {
                method: 'POST',
                signal: estado.controller.signal,
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ documento })
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Consulta indisponível.');
            if (!payload.encontrado) {
                setLookupState(input, 'empty', 'Nenhum dado localizado. Preencha manualmente.');
                return;
            }
            aplicarEnriquecimento(input, payload.dados || {});
            setLookupState(input, 'success', 'Dados localizados. Confira e ajuste se necessário.');
        } catch (error) {
            if (error.name !== 'AbortError') setLookupState(input, 'error', 'Não foi possível consultar. Continue manualmente.');
        }
    }

    document.addEventListener('input', event => {
        const input = event.target.closest('#cpf_cnpj, .js-documento-pessoa');
        if (!input || !isGuidedProposal || !window.propostaLookupUrl) return;
        const documento = digitsOnly(input.value);
        const esperado = input.id === 'cpf_cnpj' && currentPropostaType === 'PME' ? 14 : 11;
        const valido = esperado === 14 ? cnpjValido(documento) : cpfValido(documento);
        const estado = lookupStates.get(input) || {};
        clearTimeout(estado.timer);
        estado.controller?.abort();
        if (!valido) {
            estado.last = null;
            setLookupState(input, 'idle', documento.length === esperado ? 'Documento inválido. Revise os números.' : 'Digite o documento completo para consultar.');
        } else {
            estado.timer = setTimeout(() => consultarDocumento(input), 500);
        }
        lookupStates.set(input, estado);
    });

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

        const totalTitulares = document.getElementById('total-titulares');
        const totalDependentes = document.getElementById('total-dependentes');
        const totalVidasElement = document.getElementById('total-vidas');
        const vidas = document.getElementById('vidas');
        if (totalTitulares) totalTitulares.textContent = titulares;
        if (totalDependentes) totalDependentes.textContent = dependentes;
        if (totalVidasElement) totalVidasElement.textContent = totalVidas;
        if (vidas) vidas.value = totalVidas;
        updateTitularActionState();
        if (typeof atualizarResumoProposta === 'function') atualizarResumoProposta();

        // Add/remove de titular e dependente tambem persiste o rascunho
        scheduleDraftSave();
    }

    function pendenciasTitular(card) {
        if (!card) return ['titular'];
        const value = selector => card.querySelector(selector)?.value?.trim() || '';
        const pendencias = [];

        if (!cpfValido(value('.mask-cpf'))) pendencias.push('CPF válido');
        if (!value('[name$="[nome]"]')) pendencias.push('nome completo');
        if (!isValidDateBr(value('[name$="[data_nascimento]"]'))) pendencias.push('data de nascimento');
        if (!isValidEmail(value('[name$="[email]"]'))) pendencias.push('e-mail');
        if (digitsOnly(value('[name$="[telefone1]"]')).length < 10) pendencias.push('telefone');
        if (currentPropostaType === 'PME' && !value('[name$="[cargo]"]')) pendencias.push('cargo');
        if (!value('[name$="[plano_id]"]')) pendencias.push('plano');
        if (!value('[name$="[coparticipacao]"]')) pendencias.push('coparticipação');
        if (value('[name$="[plano_anterior]"]') === 'SIM' && !value('[name$="[operadora_anterior_id]"]')) {
            pendencias.push('operadora anterior');
        }

        return pendencias;
    }

    function updateTitularActionState() {
        if (!isGuidedProposal) return;
        const cards = Array.from(document.querySelectorAll('#titulares-container .titular-card'));

        cards.forEach(card => {
            const pendencias = pendenciasTitular(card);
            const completo = pendencias.length === 0;
            const addDependente = card.querySelector('.btn-add-dep');
            const status = card.querySelector('.titular-completion');

            card.classList.toggle('is-complete', completo);
            if (addDependente) {
                addDependente.disabled = !completo;
                addDependente.setAttribute('aria-disabled', String(!completo));
                addDependente.title = completo
                    ? 'Adicionar dependente a este titular'
                    : `Complete o titular: ${pendencias.join(', ')}`;
            }
            if (status) {
                status.classList.toggle('is-complete', completo);
                status.textContent = completo
                    ? 'Titular completo'
                    : `Falta: ${pendencias[0]}${pendencias.length > 1 ? ` e mais ${pendencias.length - 1}` : ''}`;
            }
        });

        const podeAdicionarTitular = cards.length > 0 && cards.every(card => pendenciasTitular(card).length === 0);
        const addTitularButton = document.getElementById('btn-add-titular');
        const addTitularHint = document.getElementById('np-add-titular-hint');
        if (addTitularButton) {
            addTitularButton.disabled = !podeAdicionarTitular;
            addTitularButton.setAttribute('aria-disabled', String(!podeAdicionarTitular));
            addTitularButton.title = podeAdicionarTitular
                ? 'Adicionar outro titular'
                : 'Complete todos os titulares antes de adicionar outro.';
        }
        if (addTitularHint) addTitularHint.hidden = podeAdicionarTitular;
    }

    document.addEventListener('input', event => {
        if (event.target.closest?.('.titular-card')) updateTitularActionState();
    });
    document.addEventListener('change', event => {
        if (event.target.closest?.('.titular-card')) updateTitularActionState();
    });

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

        const templateCard = template.content?.querySelector('.titular-card');
        if (!templateCard) {
            showModernToast('error', 'Não foi possível abrir beneficiários', 'Atualize a página e tente novamente.');
            return;
        }

        const number = titularIndex + 1;

        // Get HTML from template and replace placeholders
        const html = templateCard.outerHTML
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
    const DEP_FIELDS = isGuidedProposal
        ? ['nome', 'cpf', 'data_nascimento', 'email', 'telefone1', 'telefone2', 'parentesco', 'plano_id', 'coparticipacao', 'herda_plano', 'plano_anterior', 'operadora_anterior_id']
        : ['nome', 'cpf', 'data_nascimento', 'email', 'telefone1', 'telefone2', 'parentesco', 'plano_anterior', 'operadora_anterior_id'];

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
        depItem: null,
        trigger: null
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
        if (row) row.hidden = !show;
    }

    function syncDepPlanoComTitular() {
        const herda = document.getElementById('dep_herda_plano')?.checked;
        const plano = document.getElementById('dep_plano_id');
        const copart = document.getElementById('dep_coparticipacao');
        if (!plano || !copart) return;
        plano.disabled = Boolean(herda);
        copart.disabled = Boolean(herda);
        if (herda && depModal.titularCard) {
            plano.value = depModal.titularCard.querySelector('.select-plano-titular')?.value || '';
            copart.value = depModal.titularCard.querySelector('.select-coparticipacao-titular')?.value || '';
        }
    }

    function openDependenteModal(titularCard, depItem = null) {
        const overlay = document.getElementById('dep-modal-overlay');
        if (!overlay || !titularCard) return;

        depModal.mode = depItem ? 'edit' : 'add';
        depModal.trigger = document.activeElement;
        depModal.titularCard = titularCard;
        depModal.depItem = depItem;

        const fields = getDepModalFields();
        const values = depItem ? getDepValues(depItem) : {};

        if (fields.plano_id) fields.plano_id.innerHTML = renderPlanOptions();
        if (fields.coparticipacao) fields.coparticipacao.innerHTML = getCoparticipacaoOptions();

        DEP_FIELDS.forEach(field => {
            const el = fields[field];
            if (!el) return;
            if (field === 'herda_plano') {
                el.checked = depItem ? values.herda_plano !== 'NAO' : true;
            } else {
                el.value = values[field] || (field === 'plano_anterior' ? 'NAO' : '');
            }
        });

        if (!depItem) syncDepPlanoComTitular();
        else syncDepPlanoComTitular();

        toggleDepOperadoraAnterior((values.plano_anterior || 'NAO') === 'SIM');
        clearInvalid(overlay);

        const title = document.getElementById('dep-modal-title');
        if (title) title.textContent = depItem ? 'Editar Dependente' : 'Adicionar Dependente';

        const saveLabel = document.getElementById('dep-modal-save-label');
        if (saveLabel) saveLabel.textContent = depItem ? 'Salvar alterações' : 'Adicionar dependente';

        overlay.classList.add('dep-modal-open');
        document.body.style.overflow = 'hidden';
        overlay.setAttribute('aria-hidden', 'false');
        fields.cpf?.focus();
    }

    function closeDependenteModal() {
        const overlay = document.getElementById('dep-modal-overlay');
        if (!overlay) return;

        overlay.classList.remove('dep-modal-open');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        depModal.titularCard = null;
        depModal.depItem = null;
        depModal.trigger?.focus?.();
        depModal.trigger = null;
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
        if (isGuidedProposal) {
            checks.push(
                { el: fields.plano_id, ok: fields.plano_id?.value !== '', msg: 'Selecione o plano do dependente.' },
                { el: fields.coparticipacao, ok: fields.coparticipacao?.value !== '', msg: 'Selecione a coparticipação do dependente.' }
            );
        }

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
            values[field] = field === 'herda_plano'
                ? (fields[field]?.checked ? 'SIM' : 'NAO')
                : (fields[field]?.value || '');
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
        document.getElementById('dep_herda_plano')?.addEventListener('change', syncDepPlanoComTitular);

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
            if (e.key === 'Tab' && overlay.classList.contains('dep-modal-open')) {
                const focusable = Array.from(overlay.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled)'));
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last?.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first?.focus(); }
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
        updateTitularActionState();
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

        const selectedOption = this.options[this.selectedIndex];
        usaCoparticipacaoDetalhada = selectedOption?.dataset?.coparticipacaoFormato === 'PARCIAL_COMPLETA';

        const angariacao = document.getElementById('angariacao_status');
        if (angariacao) {
            angariacao.value = selectedOption?.dataset?.angariacaoPadrao === '1' ? 'SIM' : 'NAO';
            angariacao.dispatchEvent(new Event('change'));
        }

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
                fieldRowOpAnterior.hidden = e.target.value !== 'SIM';
            }
        }

        if (e.target.classList.contains('select-plano-titular') || e.target.classList.contains('select-coparticipacao-titular')) {
            const card = e.target.closest('.titular-card');
            if (!card) return;
            const plano = card.querySelector('.select-plano-titular')?.value || '';
            const copart = card.querySelector('.select-coparticipacao-titular')?.value || '';
            card.querySelectorAll('.dependente-item').forEach(dep => {
                if (dep.querySelector('[data-dep-field="herda_plano"]')?.value !== 'SIM') return;
                const depPlano = dep.querySelector('[data-dep-field="plano_id"]');
                const depCopart = dep.querySelector('[data-dep-field="coparticipacao"]');
                if (depPlano) depPlano.value = plano;
                if (depCopart) depCopart.value = copart;
            });
        }
    });

    // ============================================
    // Angariacao Toggle
    // ============================================
    document.getElementById('angariacao_status')?.addEventListener('change', function () {
        const isSim = this.value === 'SIM';
        const field = document.getElementById('field-taxa-angariacao');
        if (field) field.hidden = !isSim;

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

    const angariacaoSelect = document.getElementById('angariacao_status');
    if (angariacaoSelect) angariacaoSelect.dispatchEvent(new Event('change'));

    document.querySelectorAll('[name="tem_portabilidade_escolha"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            const stage = document.getElementById('np-portabilidade-stage');
            if (stage) stage.hidden = radio.value !== 'SIM';
            if (radio.value === 'NAO' && portabilidades.length) {
                portabilidades = [];
                renderPortabilidades();
            }
        });
    });

    // ============================================
    // Portabilidade — inclusão e edição em modal
    // ============================================
    const portabilidadeContainer = document.getElementById('portabilidade-container');
    const portabilidadeEmpty = document.getElementById('portabilidade-empty');
    const portabilidadeStatus = document.getElementById('portabilidade_status');
    const portabilidadeQuantidade = document.getElementById('qtd_portabilidade');
    const portabilidadeBadge = document.getElementById('portabilidade-badge');
    const portabilidadeLive = document.getElementById('portabilidade-live');
    const portabilidadeModal = document.getElementById('portabilidade-modal');
    const portabilidadeModalTitle = document.getElementById('portabilidade-modal-title');
    const portabilidadeModalError = document.getElementById('portabilidade-modal-error');
    const portabilidadeCpf = document.getElementById('portabilidade_cpf');
    const portabilidadeCpfLookup = document.getElementById('btn-consultar-portabilidade-cpf');
    const portabilidadeNome = document.getElementById('portabilidade_nome');
    const portabilidadeOperadoraAnterior = document.getElementById('portabilidade_operadora_anterior_id');
    const portabilidadeOperadora = document.getElementById('portabilidade_operadora_destino_id');
    const portabilidadePlano = document.getElementById('portabilidade_plano_destino_id');
    const portabilidadeSave = document.getElementById('btn-save-portabilidade');
    const portabilidadeAdd = document.getElementById('btn-add-portabilidade');
    const planosPortabilidade = Array.isArray(window.portabilidadePlanos) ? window.portabilidadePlanos : [];
    let portabilidadeEditIndex = null;
    let portabilidadeModalTrigger = null;

    function normalizarPortabilidade(item) {
        return {
            cpf: String(item?.cpf || ''),
            nome: String(item?.nome || '').trim(),
            operadora_anterior_id: String(item?.operadora_anterior_id || ''),
            operadora_destino_id: String(item?.operadora_destino_id || ''),
            plano_destino_id: String(item?.plano_destino_id || '')
        };
    }

    function nomeOperadoraPortabilidade(id) {
        return Array.from(portabilidadeOperadora?.options || []).find(option => option.value === String(id))?.textContent.trim() || 'Operadora não informada';
    }

    function nomePlanoPortabilidade(id) {
        return planosPortabilidade.find(plano => String(plano.id) === String(id))?.nome || 'Plano não informado';
    }

    function renderPortabilidades() {
        if (!portabilidadeContainer) return;

        portabilidadeContainer.innerHTML = portabilidades.map((item, index) => `
            <article class="port-item" role="listitem" data-port-index="${index}">
                <span class="port-num" aria-hidden="true">${index + 1}</span>
                <div class="port-summary">
                    <strong>${escapeHtml(item.nome)}</strong>
                    <span>${escapeHtml(item.cpf)} · ${escapeHtml(nomeOperadoraPortabilidade(item.operadora_anterior_id))} → ${escapeHtml(nomeOperadoraPortabilidade(item.operadora_destino_id))}</span>
                    <small>${escapeHtml(nomePlanoPortabilidade(item.plano_destino_id))}</small>
                </div>
                <input type="hidden" name="portabilidades[${index}][nome]" value="${escapeHtml(item.nome)}">
                <input type="hidden" name="portabilidades[${index}][cpf]" value="${escapeHtml(item.cpf)}">
                <input type="hidden" name="portabilidades[${index}][operadora_anterior_id]" value="${escapeHtml(item.operadora_anterior_id)}">
                <input type="hidden" name="portabilidades[${index}][operadora_destino_id]" value="${escapeHtml(item.operadora_destino_id)}">
                <input type="hidden" name="portabilidades[${index}][plano_destino_id]" value="${escapeHtml(item.plano_destino_id)}">
                <div class="port-actions">
                    <button type="button" class="btn-edit-port" data-port-edit="${index}" aria-label="Editar portabilidade de ${escapeHtml(item.nome)}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"></path></svg>
                    </button>
                    <button type="button" class="btn-remove-port" data-port-remove="${index}" aria-label="Remover portabilidade de ${escapeHtml(item.nome)}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"></path></svg>
                    </button>
                </div>
            </article>
        `).join('');

        const quantidade = portabilidades.length;
        if (portabilidadeStatus) portabilidadeStatus.value = quantidade > 0 ? 'SIM' : 'NAO';
        if (portabilidadeQuantidade) portabilidadeQuantidade.value = quantidade;
        if (portabilidadeEmpty) portabilidadeEmpty.hidden = quantidade > 0;
        if (portabilidadeBadge) {
            portabilidadeBadge.className = `status-badge ${quantidade > 0 ? 'badge-ativo' : 'badge-inativo'}`;
            portabilidadeBadge.textContent = quantidade === 0 ? 'Nenhuma' : `${quantidade} ${quantidade === 1 ? 'cliente' : 'clientes'}`;
        }
        scheduleDraftSave();
    }

    function preencherPlanosPortabilidade(operadoraId, selectedId = '') {
        if (!portabilidadePlano) return;
        const planos = planosPortabilidade.filter(plano => String(plano.operadora_id) === String(operadoraId));
        portabilidadePlano.disabled = !operadoraId || planos.length === 0;
        portabilidadePlano.innerHTML = planos.length === 0
            ? `<option value="">${operadoraId ? 'Nenhum plano ativo para esta operadora' : 'Selecione primeiro a operadora'}</option>`
            : `<option value="">Selecione o plano...</option>${planos.map(plano => `<option value="${plano.id}">${escapeHtml(plano.nome)}</option>`).join('')}`;
        portabilidadePlano.value = String(selectedId || '');
    }

    function abrirModalPortabilidade(index = null) {
        if (!portabilidadeModal || !portabilidadeCpf || !portabilidadeNome || !portabilidadeOperadoraAnterior || !portabilidadeOperadora || !portabilidadePlano) return;
        if (index === null && portabilidades.length >= 50) {
            showModernToast('warning', 'Limite atingido', 'A proposta aceita até 50 clientes em portabilidade.');
            return;
        }

        portabilidadeEditIndex = index;
        portabilidadeModalTrigger = document.activeElement;
        const item = index === null ? normalizarPortabilidade({}) : portabilidades[index];
        portabilidadeCpf.value = item.cpf;
        portabilidadeNome.value = item.nome;
        portabilidadeOperadoraAnterior.value = item.operadora_anterior_id;
        portabilidadeOperadora.value = item.operadora_destino_id;
        preencherPlanosPortabilidade(item.operadora_destino_id, item.plano_destino_id);
        portabilidadeModalTitle.textContent = index === null ? 'Adicionar Portabilidade' : 'Editar Portabilidade';
        portabilidadeSave.textContent = index === null ? 'Adicionar Portabilidade' : 'Salvar alterações';
        portabilidadeModalError.hidden = true;
        setLookupState(
            portabilidadeCpf,
            'idle',
            item.cpf ? 'Você pode consultar novamente para conferir os dados.' : 'Digite um CPF válido para consultar na Lemit.'
        );
        clearInvalid(portabilidadeModal);
        portabilidadeModal.classList.add('is-open');
        portabilidadeModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('port-modal-lock');
        requestAnimationFrame(() => portabilidadeCpf.focus());
    }

    function fecharModalPortabilidade() {
        if (!portabilidadeModal?.classList.contains('is-open')) return;
        portabilidadeModal.classList.remove('is-open');
        portabilidadeModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('port-modal-lock');
        portabilidadeModalTrigger?.focus?.();
    }

    function salvarPortabilidade() {
        const item = normalizarPortabilidade({
            cpf: portabilidadeCpf?.value,
            nome: portabilidadeNome?.value,
            operadora_anterior_id: portabilidadeOperadoraAnterior?.value,
            operadora_destino_id: portabilidadeOperadora?.value,
            plano_destino_id: portabilidadePlano?.value
        });
        clearInvalid(portabilidadeModal);
        const erros = [];
        if (!cpfValido(item.cpf)) {
            erros.push('Informe um CPF válido.');
            setInvalid(portabilidadeCpf);
        }
        if (!item.nome) {
            erros.push('Informe o nome completo do cliente.');
            setInvalid(portabilidadeNome);
        }
        if (!item.operadora_anterior_id) {
            erros.push('Selecione a operadora anterior.');
            setInvalid(portabilidadeOperadoraAnterior);
        }
        if (!item.operadora_destino_id) {
            erros.push('Selecione a operadora de destino.');
            setInvalid(portabilidadeOperadora);
        }
        if (!item.plano_destino_id) {
            erros.push('Selecione o plano de destino.');
            setInvalid(portabilidadePlano);
        }
        if (erros.length > 0) {
            portabilidadeModalError.textContent = erros[0];
            portabilidadeModalError.hidden = false;
            [portabilidadeCpf, portabilidadeNome, portabilidadeOperadoraAnterior, portabilidadeOperadora, portabilidadePlano].find(field => field?.classList.contains('is-invalid'))?.focus();
            return;
        }

        if (portabilidadeEditIndex === null) portabilidades.push(item);
        else portabilidades[portabilidadeEditIndex] = item;
        renderPortabilidades();
        fecharModalPortabilidade();
        portabilidadeLive.textContent = portabilidadeEditIndex === null ? 'Portabilidade adicionada.' : 'Portabilidade atualizada.';
    }

    function restaurarPortabilidades(items) {
        portabilidades = (Array.isArray(items) ? items : [])
            .map(normalizarPortabilidade)
            .filter(item => item.nome || item.cpf || item.operadora_destino_id || item.plano_destino_id);
        renderPortabilidades();
        return portabilidades.length > 0;
    }

    portabilidadeAdd?.addEventListener('click', () => abrirModalPortabilidade());
    applyCpfMask(portabilidadeCpf);
    portabilidadeCpfLookup?.addEventListener('click', () => consultarDocumento(portabilidadeCpf, true));
    document.getElementById('btn-close-portabilidade')?.addEventListener('click', fecharModalPortabilidade);
    document.getElementById('btn-cancel-portabilidade')?.addEventListener('click', fecharModalPortabilidade);
    portabilidadeSave?.addEventListener('click', salvarPortabilidade);
    portabilidadeOperadora?.addEventListener('change', () => preencherPlanosPortabilidade(portabilidadeOperadora.value));
    portabilidadeModal?.addEventListener('click', event => {
        if (event.target === portabilidadeModal) fecharModalPortabilidade();
    });
    portabilidadeContainer?.addEventListener('click', event => {
        const edit = event.target.closest('[data-port-edit]');
        const remove = event.target.closest('[data-port-remove]');
        if (edit) abrirModalPortabilidade(Number(edit.dataset.portEdit));
        if (remove) {
            const index = Number(remove.dataset.portRemove);
            const nome = portabilidades[index]?.nome || 'Cliente';
            portabilidades.splice(index, 1);
            renderPortabilidades();
            portabilidadeLive.textContent = `Portabilidade de ${nome} removida.`;
            (portabilidadeContainer.querySelector('[data-port-edit]') || portabilidadeAdd)?.focus();
        }
    });
    document.addEventListener('keydown', event => {
        if (!portabilidadeModal?.classList.contains('is-open')) return;
        if (event.key === 'Escape') fecharModalPortabilidade();
        if (event.key === 'Tab') {
            const focusable = Array.from(portabilidadeModal.querySelectorAll('button:not(:disabled), input:not(:disabled), select:not(:disabled)'));
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });

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
        if (fieldTipoEmpresa) fieldTipoEmpresa.hidden = true;
        if (fieldDataAbertura) fieldDataAbertura.hidden = true;

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
        updateTitularActionState();
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
        if (fieldTipoEmpresa) fieldTipoEmpresa.hidden = false;
        if (fieldDataAbertura) fieldDataAbertura.hidden = false;

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
        updateTitularActionState();
    }

    // Event listener para toggle de tipo de proposta
    document.getElementById('tipo_proposta_toggle')?.addEventListener('change', function() {
        if (this.value === 'ADESAO') {
            switchToAdesaoMode();
        } else {
            switchToPmeMode();
        }
    });

    document.querySelectorAll('[name="tipo_proposta_escolha"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (!radio.checked) return;
            const toggle = document.getElementById('tipo_proposta_toggle');
            toggle.value = radio.value;
            toggle.dispatchEvent(new Event('change', { bubbles: true }));
            document.getElementById('np-summary-type').textContent = radio.value;
        });
    });

    // ============================================
    // Form Validation
    // ============================================
    function validateForm(includePortabilidade = false) {
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
        const documentoContratoValido = isGuidedProposal
            ? (currentPropostaType === 'PME' ? cnpjValido(cpfCnpj?.value) : cpfValido(cpfCnpj?.value))
            : Boolean(cpfCnpj?.value.trim());
        if (!documentoContratoValido) {
            fail(cpfCnpj, currentPropostaType === 'PME' ? 'Informe um CNPJ válido.' : 'Informe um CPF válido.');
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

        const valorContrato = document.getElementById('valor_contrato');
        if (isGuidedProposal && Number(digitsOnly(valorContrato?.value)) <= 0) fail(valorContrato, 'Informe o valor mensal do contrato.');
        if (isGuidedProposal && document.getElementById('angariacao_status')?.value === 'SIM') {
            const taxa = document.getElementById('taxa_angariacao');
            if (Number(digitsOnly(taxa?.value)) <= 0) fail(taxa, 'Informe o valor da angariação.');
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
            if (isGuidedProposal ? !cpfValido(cpf?.value) : digitsOnly(cpf?.value).length !== 11) fail(cpf, `Informe um CPF válido para o Titular ${num}.`);

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
                    && (isGuidedProposal ? cpfValido(dep.cpf) : digitsOnly(dep.cpf).length === 11)
                    && isValidDateBr(dep.data_nascimento)
                    && isValidEmail(dep.email)
                    && digitsOnly(dep.telefone1).length >= 10
                    && dep.parentesco !== ''
                    && (!isGuidedProposal || (dep.plano_id !== '' && dep.coparticipacao !== ''))
                    && (dep.plano_anterior !== 'SIM' || dep.operadora_anterior_id !== '');

                if (!depOk) {
                    const summaryCard = depItem.querySelector('.dep-summary-card');
                    fail(summaryCard, `Complete os dados do dependente "${dep.nome.trim() || 'sem nome'}" do Titular ${num} (clique em editar).`);
                }
            });
        });

        if (includePortabilidade && document.querySelector('[name="tem_portabilidade_escolha"]:checked')?.value === 'SIM') {
            if (portabilidades.length === 0) errors.push('Adicione pelo menos uma pessoa em portabilidade.');
            portabilidades.forEach((item, index) => {
                if (cpfValido(item.cpf) && item.nome && item.operadora_anterior_id && item.operadora_destino_id && item.plano_destino_id) return;
                const editButton = portabilidadeContainer?.querySelector(`[data-port-edit="${index}"]`);
                fail(editButton, `Complete todos os dados da Portabilidade ${index + 1}.`);
            });
        }

        return { ok: errors.length === 0, errors, firstInvalid };
    }

    document.getElementById('formNovaProposta')?.addEventListener('submit', function (e) {
        const result = validateForm(true);
        if (result.ok) {
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
                const selectedOption = operadoraSelect.options[operadoraSelect.selectedIndex];
                usaCoparticipacaoDetalhada = selectedOption?.dataset?.coparticipacaoFormato === 'PARCIAL_COMPLETA';

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
            if (opAnteriorRow) opAnteriorRow.hidden = false;
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
            etapa: isGuidedProposal && typeof etapaAtual === 'number' ? etapaAtual : undefined,
            tipo_contrato: currentPropostaType,
            fields,
            titulares: Array.from(document.querySelectorAll('#titulares-container .titular-card')).map(serializeTitularCard),
            portabilidades: portabilidades.map(item => ({ ...item })),
            tem_portabilidade: document.querySelector('[name="tem_portabilidade_escolha"]:checked')?.value || 'NAO'
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
        const portabilidadeDigitada = (draft.portabilidades || []).some(item => String(item?.nome || '').trim() !== '');
        return camposDigitados || titularDigitado || portabilidadeDigitada;
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
            const radio = document.querySelector('[name="tipo_proposta_escolha"][value="ADESAO"]');
            if (radio) radio.checked = true;
            switchToAdesaoMode();
        }

        Object.entries(draft.fields || {}).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el && value !== undefined && value !== null) el.value = value;
        });

        // Badges e visibilidades que dependem do evento change
        ['plano_dental', 'angariacao_status'].forEach(id => {
            document.getElementById(id)?.dispatchEvent(new Event('change', { bubbles: true }));
        });

        restaurarPortabilidades(draft.portabilidades || []);
        if (draft.tem_portabilidade === 'SIM') {
            const radio = document.querySelector('[name="tem_portabilidade_escolha"][value="SIM"]');
            if (radio) radio.checked = true;
            const stage = document.getElementById('np-portabilidade-stage');
            if (stage) stage.hidden = false;
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
        if (isGuidedProposal && draft.etapa) mostrarEtapa(Math.min(5, Math.max(1, Number(draft.etapa))), false);
        return temTitulares;
    }

    // Qualquer digitacao/selecao no formulario agenda o salvamento
    const formNovaProposta = document.getElementById('formNovaProposta');
    formNovaProposta?.addEventListener('input', scheduleDraftSave);
    formNovaProposta?.addEventListener('change', scheduleDraftSave);
    formNovaProposta?.addEventListener('input', atualizarResumoProposta);
    formNovaProposta?.addEventListener('change', atualizarResumoProposta);

    // ============================================
    // Documentos da venda — criação + multipart individual
    // ============================================
    const documentosInput = document.getElementById('documentos-venda');
    const documentosLista = document.getElementById('documentos-lista');
    const documentosStatus = document.getElementById('documentos-status');
    const documentosDropzone = document.getElementById('documentos-dropzone');
    const documentosSelecionar = document.getElementById('documentos-selecionar');
    const documentosResumo = document.getElementById('documentos-resumo');
    const documentosResumoQuantidade = document.getElementById('documentos-resumo-quantidade');
    const documentosResumoTamanho = document.getElementById('documentos-resumo-tamanho');
    const documentosLimpar = document.getElementById('documentos-limpar');
    const documentosSource = document.querySelector('.np-documents-source');
    const documentosDestino = document.getElementById('np-documentos-destino');
    const avancarEtapaButton = document.getElementById('np-avancar-etapa');
    const voltarEtapaButton = document.getElementById('np-voltar-etapa');
    const finalizarVendaButton = document.getElementById('np-finalizar-venda');
    const concluirSemDocumentosButton = document.getElementById('np-concluir-sem-documentos');
    const irVendasLink = document.getElementById('np-ir-vendas');
    const documentosSelecionados = [];
    const maxDocumentos = 30;
    const maxDocumentoBytes = 25 * 1024 * 1024;
    let vendaCriada = null;
    let etapaAtual = 1;

    if (documentosSource && documentosDestino) {
        documentosSource.classList.remove('np-documents-source');
        documentosDestino.appendChild(documentosSource);
    }

    function atualizarResumoProposta() {
        const nome = document.getElementById('nome_contrato')?.value.trim() || 'Empresa não informada';
        const operadora = document.getElementById('operadora');
        const nomeOperadora = operadora?.selectedOptions?.[0]?.textContent.trim() || 'Não informada';
        const vidas = Number(document.getElementById('vidas')?.value || 0);
        const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
        setText('np-resumo-empresa', nome);
        setText('np-resumo-operadora', nomeOperadora);
        setText('np-resumo-vidas', `${vidas} ${vidas === 1 ? 'vida' : 'vidas'}`);
        setText('np-summary-type', currentPropostaType);
        setText('np-summary-name', nome);
        setText('np-summary-operator', nomeOperadora);
        setText('np-summary-value', document.getElementById('valor_contrato')?.value || 'R$ 0,00');
    }

    function erroEtapa(errors, firstInvalid) {
        const extra = errors.length > 1 ? ` (+${errors.length - 1} outras pendências)` : '';
        showModernToast('error', 'Etapa incompleta', errors[0] + extra);
        firstInvalid?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => firstInvalid?.focus?.({ preventScroll: true }), 300);
    }

    function validarEtapa(etapa) {
        if (etapa >= 3) return validateForm(etapa >= 4);
        const errors = [];
        let firstInvalid = null;
        const fail = (el, message) => { errors.push(message); setInvalid(el); if (!firstInvalid) firstInvalid = el; };
        clearInvalid(document.querySelector(`[data-step-panel="${etapa}"]`) || document);
        if (etapa === 1) {
            const documento = document.getElementById('cpf_cnpj');
            const documentoOk = currentPropostaType === 'PME' ? cnpjValido(documento?.value) : cpfValido(documento?.value);
            if (!documentoOk) fail(documento, `Informe um ${currentPropostaType === 'PME' ? 'CNPJ' : 'CPF'} válido.`);
            const nome = document.getElementById('nome_contrato');
            if (!nome?.value.trim()) fail(nome, currentPropostaType === 'PME' ? 'Informe a razão social.' : 'Informe o nome completo.');
            const tipo = document.getElementById('tipo_empresa');
            if (currentPropostaType === 'PME' && !tipo?.value) fail(tipo, 'Selecione o tipo da empresa.');
        }
        if (etapa === 2) {
            const operadora = document.getElementById('operadora');
            if (!operadora?.value) fail(operadora, 'Selecione a operadora vendida.');
            const valor = document.getElementById('valor_contrato');
            if (Number(digitsOnly(valor?.value)) <= 0) fail(valor, 'Informe o valor mensal do contrato.');
            const taxa = document.getElementById('taxa_angariacao');
            if (document.getElementById('angariacao_status')?.value === 'SIM' && Number(digitsOnly(taxa?.value)) <= 0) fail(taxa, 'Informe o valor da angariação.');
        }
        return { ok: errors.length === 0, errors, firstInvalid };
    }

    function mostrarEtapa(etapa, moverFoco = true) {
        if (!isGuidedProposal) return;
        etapaAtual = etapa;
        document.querySelectorAll('[data-step-panel]').forEach(panel => {
            const ativo = Number(panel.dataset.stepPanel) === etapa;
            panel.hidden = !ativo;
            panel.classList.toggle('is-active', ativo);
        });
        document.querySelectorAll('[data-step-indicator]').forEach(indicator => {
            const numero = Number(indicator.dataset.stepIndicator);
            const tituloEtapa = indicator.querySelector('.np-step-copy strong')?.textContent?.trim() || `Etapa ${numero}`;
            indicator.classList.toggle('is-active', numero === etapa);
            indicator.classList.toggle('is-complete', numero < etapa);
            if (numero === etapa) {
                indicator.setAttribute('aria-current', 'step');
                indicator.setAttribute('aria-label', `Etapa ${numero} de 5: ${tituloEtapa}, atual`);
            } else {
                indicator.removeAttribute('aria-current');
                indicator.setAttribute('aria-label', `Etapa ${numero} de 5: ${tituloEtapa}${numero < etapa ? ', concluída' : ''}`);
            }
        });
        const etapaAtiva = document.querySelector(`[data-step-indicator="${etapa}"]`);
        const trilhaEtapas = etapaAtiva?.closest('.np-steps');
        if (etapaAtiva && trilhaEtapas?.scrollWidth > trilhaEtapas.clientWidth) {
            requestAnimationFrame(() => etapaAtiva.scrollIntoView({
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
                block: 'nearest',
                inline: 'center'
            }));
        }
        if (avancarEtapaButton) avancarEtapaButton.hidden = etapa >= 5;
        if (voltarEtapaButton) voltarEtapaButton.hidden = etapa <= 1 || Boolean(vendaCriada);
        if (finalizarVendaButton) finalizarVendaButton.hidden = etapa !== 5 || (documentosSelecionados.length === 0 && !vendaCriada);
        if (concluirSemDocumentosButton) concluirSemDocumentosButton.hidden = etapa !== 5 || documentosSelecionados.length > 0 || Boolean(vendaCriada);
        document.querySelector('.nova-proposta-wrapper')?.classList.toggle('is-documents-step', etapa === 5);
        atualizarResumoProposta();
        if (moverFoco) {
            const titulo = document.getElementById(`np-step-${etapa}-title`);
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => titulo?.focus({ preventScroll: true }), 250);
        }
    }

    avancarEtapaButton?.addEventListener('click', () => {
        const result = validarEtapa(etapaAtual);
        if (!result.ok) {
            erroEtapa(result.errors, result.firstInvalid);
            return;
        }
        saveDraftNow();
        mostrarEtapa(Math.min(5, etapaAtual + 1));
    });
    voltarEtapaButton?.addEventListener('click', () => mostrarEtapa(Math.max(1, etapaAtual - 1)));
    concluirSemDocumentosButton?.addEventListener('click', () => formNovaProposta?.requestSubmit(finalizarVendaButton));
    document.getElementById('btn-open-docs-modal-step')?.addEventListener('click', () => {
        document.getElementById('btn-open-docs-modal')?.click();
    });

    function formatBytes(bytes) {
        if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
    }

    function arquivoPermitido(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        return file.type === 'application/pdf'
            || (file.type.startsWith('image/') && file.type !== 'image/svg+xml')
            || ['heic', 'heif', 'tif', 'tiff'].includes(extension);
    }

    function anunciarDocumentos(mensagem, erro = false) {
        if (!documentosStatus) return;
        documentosStatus.textContent = mensagem;
        documentosStatus.classList.toggle('is-error', erro);
    }

    function estadoDocumento(item) {
        if (item.status === 'uploading') return { texto: item.label, classe: 'is-uploading' };
        if (item.status === 'queued') return { texto: 'Recebido', classe: 'is-success' };
        if (item.status === 'error') return { texto: 'Falha no envio', classe: 'is-error' };
        return { texto: 'Pronto para enviar', classe: 'is-ready' };
    }

    function iconeDocumento(file) {
        const extension = file.name.split('.').pop().toLowerCase();
        const isPdf = file.type === 'application/pdf' || extension === 'pdf';
        return isPdf
            ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path><path d="M8 15h8M8 11h2M8 19h5"></path></svg><span>PDF</span>'
            : '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg><span>IMG</span>';
    }

    function renderDocumentos() {
        if (!documentosLista) return;
        documentosLista.innerHTML = '';
        const quantidade = documentosSelecionados.length;
        const tamanhoTotal = documentosSelecionados.reduce((total, item) => total + item.file.size, 0);
        if (documentosResumo) documentosResumo.hidden = quantidade === 0;
        if (documentosLimpar) documentosLimpar.hidden = Boolean(vendaCriada);
        if (documentosResumoQuantidade) documentosResumoQuantidade.textContent = `${quantidade} ${quantidade === 1 ? 'arquivo selecionado' : 'arquivos selecionados'}`;
        if (documentosResumoTamanho) documentosResumoTamanho.textContent = `${formatBytes(tamanhoTotal)} no total`;
        if (concluirSemDocumentosButton) concluirSemDocumentosButton.hidden = etapaAtual !== 5 || quantidade > 0 || Boolean(vendaCriada);
        if (finalizarVendaButton) finalizarVendaButton.hidden = etapaAtual !== 5 || (quantidade === 0 && !vendaCriada);
        if (finalizarVendaButton && !vendaCriada) {
            const label = finalizarVendaButton.querySelector('span');
            if (label) label.textContent = quantidade > 0 ? `Salvar venda e enviar ${quantidade} ${quantidade === 1 ? 'arquivo' : 'arquivos'}` : 'Salvar venda';
        }
        documentosSelecionados.forEach((item, index) => {
            const estado = estadoDocumento(item);
            const li = document.createElement('li');
            li.className = `np-document-item status-${item.status}`;
            li.innerHTML = `
                <div class="np-document-type ${item.file.type === 'application/pdf' ? 'is-pdf' : 'is-image'}">${iconeDocumento(item.file)}</div>
                <div class="np-document-info">
                    <strong title="${escapeHtml(item.file.name)}">${escapeHtml(item.file.name)}</strong>
                    <span>${formatBytes(item.file.size)}</span>
                    ${item.status === 'queued' || item.status === 'error' ? `<small>${escapeHtml(item.label)}</small>` : ''}
                    ${item.status === 'uploading' || item.progress === 100 ? `<div class="np-document-progress" role="progressbar" aria-label="Progresso de ${escapeHtml(item.file.name)}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${item.progress || 0}"><span style="transform:scaleX(${(item.progress || 0) / 100})"></span></div>` : ''}
                </div>
                <span class="np-document-state ${estado.classe}">${escapeHtml(estado.texto)}</span>
                ${item.status === 'selected' || item.status === 'error' ? `<button type="button" class="np-document-remove" data-index="${index}" aria-label="Remover ${escapeHtml(item.file.name)}" title="Remover arquivo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 11v5M14 11v5"></path></svg></button>` : ''}
            `;
            documentosLista.appendChild(li);
        });
    }

    function adicionarDocumentos(files) {
        const erros = [];
        Array.from(files).forEach(file => {
            if (documentosSelecionados.length >= maxDocumentos) {
                erros.push(`O limite é de ${maxDocumentos} documentos.`);
                return;
            }
            if (file.size > maxDocumentoBytes) {
                erros.push(`${file.name} tem ${formatBytes(file.size)}; o máximo é 25 MB.`);
                return;
            }
            if (!arquivoPermitido(file)) {
                erros.push(`${file.name} não é um PDF ou imagem permitida.`);
                return;
            }
            documentosSelecionados.push({ file, status: 'selected', label: 'Pronto para enviar', progress: 0, clientId: crypto.randomUUID() });
        });
        renderDocumentos();
        anunciarDocumentos(erros[0] || `${documentosSelecionados.length} documento(s) selecionado(s).`, erros.length > 0);
    }

    documentosInput?.addEventListener('change', event => {
        adicionarDocumentos(event.target.files);
        event.target.value = '';
    });
    documentosSelecionar?.addEventListener('click', () => documentosInput?.click());
    documentosLimpar?.addEventListener('click', () => {
        documentosSelecionados.splice(0, documentosSelecionados.length);
        renderDocumentos();
        anunciarDocumentos('Seleção de documentos removida.');
        documentosSelecionar?.focus();
    });
    documentosLista?.addEventListener('click', event => {
        const button = event.target.closest('.np-document-remove');
        if (!button) return;
        documentosSelecionados.splice(Number(button.dataset.index), 1);
        renderDocumentos();
        anunciarDocumentos(`${documentosSelecionados.length} documento(s) selecionado(s).`);
    });
    ['dragenter', 'dragover'].forEach(name => documentosDropzone?.addEventListener(name, event => {
        event.preventDefault();
        documentosDropzone.classList.add('is-dragging');
    }));
    ['dragleave', 'drop'].forEach(name => documentosDropzone?.addEventListener(name, event => {
        event.preventDefault();
        documentosDropzone.classList.remove('is-dragging');
        if (name === 'drop') adicionarDocumentos(event.dataTransfer.files);
    }));

    function uploadDocumento(vendaId, item) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            const data = new FormData();
            data.append('arquivo', item.file);
            data.append('client_upload_id', item.clientId);
            data.append('_token', formNovaProposta.querySelector('[name="_token"]').value);
            xhr.open('POST', `/vendas/${vendaId}/documentos`);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', event => {
                item.status = 'uploading';
                item.label = event.lengthComputable ? `Enviando ${Math.round((event.loaded / event.total) * 100)}%` : 'Enviando';
                item.progress = event.lengthComputable ? Math.round((event.loaded / event.total) * 100) : 0;
                renderDocumentos();
            });
            xhr.addEventListener('load', () => {
                let payload = {};
                try { payload = JSON.parse(xhr.responseText); } catch (_) { /* resposta inválida */ }
                if (xhr.status >= 200 && xhr.status < 300) {
                    item.status = 'queued';
                    item.label = payload.processamento_ativo
                        ? 'Recebido e aguardando verificação'
                        : 'Recebido e aguardando configuração do servidor';
                    item.progress = 100;
                    renderDocumentos();
                    resolve(payload);
                } else {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    reject(new Error(errors[0] || payload.message || 'Falha ao enviar o documento.'));
                }
            });
            xhr.addEventListener('error', () => reject(new Error('A conexão falhou durante o envio.')));
            xhr.send(data);
        });
    }

    formNovaProposta?.addEventListener('submit', async event => {
        // A tela de estorno reutiliza os campos e a validacao desta tela, mas
        // possui um fluxo de envio proprio em edit-estorno.js. Os controles de
        // etapas/documentos abaixo so existem na criacao de uma nova proposta.
        if (formNovaProposta.dataset.modo === 'estorno') return;

        if (event.defaultPrevented) return;
        event.preventDefault();
        const submitButton = finalizarVendaButton;
        submitButton.disabled = true;
        submitButton.setAttribute('aria-busy', 'true');
        voltarEtapaButton.hidden = true;
        concluirSemDocumentosButton.hidden = true;

        try {
            if (!vendaCriada) {
                const saleData = new FormData(formNovaProposta);
                const response = await fetch(formNovaProposta.action, {
                    method: 'POST', body: saleData, headers: { Accept: 'application/json' }
                });
                const payload = await response.json();
                if (!response.ok) {
                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    throw new Error(errors[0] || payload.message || 'Não foi possível cadastrar a venda.');
                }

                draftDisabled = true;
                vendaCriada = payload;
                clearTimeout(draftSaveTimer);
                clearDraft();
                if (irVendasLink) irVendasLink.href = payload.redirect_url;
            }

            if (documentosSelecionados.length === 0) {
                window.location.assign(vendaCriada.redirect_url);
                return;
            }

            anunciarDocumentos('Venda cadastrada. Enviando documentos…');
            const pendentes = documentosSelecionados.filter(item => item.status === 'selected' || item.status === 'error');
            let cursor = 0;
            const workers = Array.from({ length: Math.min(3, pendentes.length) }, async () => {
                while (cursor < pendentes.length) {
                    const item = pendentes[cursor++];
                    try {
                        await uploadDocumento(vendaCriada.venda_id, item);
                    } catch (error) {
                        item.status = 'error';
                        item.label = error.message;
                        renderDocumentos();
                    }
                }
            });
            await Promise.all(workers);
            const falhas = documentosSelecionados.filter(item => item.status === 'error').length;
            if (falhas) {
                anunciarDocumentos(`A venda foi salva. ${falhas} documento(s) falharam; revise o motivo e tente novamente.`, true);
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                const label = submitButton.querySelector('span');
                if (label) label.textContent = `Tentar novamente (${falhas})`;
                if (irVendasLink) irVendasLink.hidden = false;
                return;
            }
            anunciarDocumentos('Todos os documentos foram recebidos. Redirecionando…');
            window.location.assign(vendaCriada.redirect_url);
        } catch (error) {
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            voltarEtapaButton.hidden = Boolean(vendaCriada);
            concluirSemDocumentosButton.hidden = documentosSelecionados.length > 0 || Boolean(vendaCriada);
            anunciarDocumentos(error.message, true);
            showModernToast('error', 'Não foi possível salvar', error.message);
        }
    });

    // Garante o ultimo estado mesmo se o F5 vier antes do debounce e avisa
    // quando houver File objects, que o navegador não permite restaurar.
    window.addEventListener('beforeunload', event => {
        saveDraftNow();
        const temArquivosNaoEnviados = documentosSelecionados.some(item => item.status === 'selected' || item.status === 'error');
        if (temArquivosNaoEnviados && !vendaCriada) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    // ============================================
    // Initialize - Add first titular on load or restore old
    // ============================================
    function initializeProposal() {
        if (document.documentElement.dataset.novaPropostaInitialized === '1') return;
        document.documentElement.dataset.novaPropostaInitialized = '1';

        const container = document.getElementById('titulares-container');
        const operadoraSelecionada = document.getElementById('operadora')?.selectedOptions?.[0];
        usaCoparticipacaoDetalhada = operadoraSelecionada?.dataset?.coparticipacaoFormato === 'PARCIAL_COMPLETA';

        initDependenteModal();

        const hasOldPortabilidades = restaurarPortabilidades(window.oldPortabilidades || []);
        if (hasOldPortabilidades) {
            const radio = document.querySelector('[name="tem_portabilidade_escolha"][value="SIM"]');
            if (radio) radio.checked = true;
            const stage = document.getElementById('np-portabilidade-stage');
            if (stage) stage.hidden = false;
        }

        // Prioridade de restore: old() (erro de validacao no servidor)
        // vem antes do rascunho local (F5).
        const hasOldData = restoreOldTitulares();
        const hasDraft = !hasOldData && !hasOldPortabilidades && restoreDraft();

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
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeProposal, { once: true });
    } else {
        initializeProposal();
    }

})();
