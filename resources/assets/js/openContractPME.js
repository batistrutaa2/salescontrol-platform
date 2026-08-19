'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    const vendaId = document.querySelector('input[name="venda_id"]')?.value ||
                    document.querySelector('input[name="id"]')?.value;
    let currentPropostaType = document.getElementById('tipo_contrato')?.value || 'PME';
    let cpfCnpjCleaveInstance = null;

    // Máscara de digitação dd/mm/aaaa — auto-insere as barras conforme o usuário digita.
    // Convive com flatpickr (usar allowInput: true) porque só toca em `value`.
    function attachDateMaskBr(el) {
        if (!el || el.dataset.dateMaskAttached) return;
        el.dataset.dateMaskAttached = '1';
        el.setAttribute('maxlength', '10');
        el.setAttribute('inputmode', 'numeric');
        if (!el.placeholder) el.placeholder = 'dd/mm/aaaa';
        el.addEventListener('input', function () {
            const digits = el.value.replace(/\D/g, '').slice(0, 8);
            let out = digits;
            if (digits.length > 4) out = digits.slice(0, 2) + '/' + digits.slice(2, 4) + '/' + digits.slice(4);
            else if (digits.length > 2) out = digits.slice(0, 2) + '/' + digits.slice(2);
            el.value = out;
        });
    }

    // ============================================
    // Modo read-only (backoffice)
    // ============================================
    function ativarModoReadOnly() {
        document.querySelectorAll('form#form-empresa, form[data-readonly]').forEach(function (form) {
            form.setAttribute('data-readonly', 'true');
            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                el.disabled = true;
            });
        });
        document.querySelectorAll('.btn-add-dep, .btn-edit, .btn-add-port, .btn-add-acesso, [data-bs-target="#modalAddAcesso"], .pme-btn-save').forEach(function (el) {
            el.style.display = 'none';
        });
    }

    var readonlyForms = document.querySelectorAll('form[data-readonly="true"]');
    if (readonlyForms.length) {
        ativarModoReadOnly();
    }

    // ============================================
    // Modal obrigatorio para assumir contrato (backoffice)
    // ============================================
    var modalEl = document.getElementById('modalAssumirContrato');
    if (modalEl) {
        var modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Apenas Visualizar -> fecha modal e ativa read-only
        var btnVisualizar = document.getElementById('btn-apenas-visualizar');
        if (btnVisualizar) {
            btnVisualizar.addEventListener('click', function () {
                modal.hide();
                ativarModoReadOnly();
            });
        }

        // Assumir Contrato -> AJAX e reload
        var btnAssumir = document.getElementById('btn-assumir-contrato');
        if (btnAssumir) {
            btnAssumir.addEventListener('click', function () {
                var vendaIdVal = this.dataset.vendaId;
                var btn = this;
                btn.disabled = true;
                btn.textContent = 'Assumindo...';
                if (btnVisualizar) btnVisualizar.disabled = true;

                fetch('/back-office/assumir-contrato', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ venda_id: vendaIdVal }),
                }).then(function (r) { return r.json(); }).then(function (data) {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        var msg = data.message || 'Erro ao assumir contrato.';
                        if (typeof Swal !== 'undefined') { Swal.fire('Erro', msg, 'error'); }
                        else { alert(msg); }
                        btn.disabled = false;
                        btn.textContent = 'Assumir Contrato';
                        if (btnVisualizar) btnVisualizar.disabled = false;
                    }
                }).catch(function () {
                    btn.disabled = false;
                    btn.textContent = 'Assumir Contrato';
                    if (btnVisualizar) btnVisualizar.disabled = false;
                });
            });
        }
    }

    // ============================================
    // Reatribuir Contrato (backoffice)
    // ============================================
    const btnReatribuir = document.getElementById('btn-reatribuir');
    if (btnReatribuir) {
        btnReatribuir.addEventListener('click', function () {
            const vendaIdVal = this.dataset.vendaId;
            const select = document.getElementById('reatribuir-select');
            const backofficeId = select ? select.value : '';
            const btn = this;

            if (!backofficeId) {
                if (typeof Swal !== 'undefined') { Swal.fire('Atencao', 'Selecione um usuario para reatribuir.', 'warning'); }
                else { alert('Selecione um usuario para reatribuir.'); }
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Reatribuindo...';
            fetch('/back-office/reatribuir-contrato', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ venda_id: vendaIdVal, backoffice_id: backofficeId }),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data.success) {
                    window.location.reload();
                } else {
                    var msg = data.message || 'Erro ao reatribuir.';
                    if (typeof Swal !== 'undefined') { Swal.fire('Erro', msg, 'error'); }
                    else { alert(msg); }
                    btn.disabled = false;
                    btn.textContent = 'Reatribuir';
                }
            }).catch(function () {
                btn.disabled = false;
                btn.textContent = 'Reatribuir';
            });
        });
    }

    // ============================================
    // Initialize Flatpickr
    // ============================================
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr-date', {
            dateFormat: 'd/m/Y',
            allowInput: true
        });
    }
    document.querySelectorAll('.flatpickr-date').forEach(attachDateMaskBr);

    // ============================================
    // CNPJ/CPF Mask (dynamic based on tipo_contrato)
    // ============================================
    const inputCpfCnpj = document.getElementById('cpf_cnpj');
    if (inputCpfCnpj && typeof Cleave !== 'undefined') {
        // Inicializa mascara baseado no tipo atual
        if (currentPropostaType === 'ADESAO') {
            cpfCnpjCleaveInstance = new Cleave(inputCpfCnpj, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
        } else {
            cpfCnpjCleaveInstance = new Cleave(inputCpfCnpj, {
                delimiters: ['.', '.', '/', '-'],
                blocks: [2, 3, 3, 4, 2],
                numericOnly: true
            });
        }
    }

    // Fallback para elementos com classe .mask-cnpj que nao sao o campo principal
    document.querySelectorAll('.mask-cnpj:not(#cpf_cnpj)').forEach(function(el) {
        if (typeof Cleave !== 'undefined') {
            new Cleave(el, {
                delimiters: ['.', '.', '/', '-'],
                blocks: [2, 3, 3, 4, 2],
                numericOnly: true
            });
        }
    });

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
    // Plano Dental Toggle
    // ============================================
    const planoDentalStatus = document.getElementById('plano_dental');
    const planoDentalBadge = document.getElementById('plano-dental-badge');

    if (planoDentalStatus && planoDentalBadge) {
        planoDentalStatus.addEventListener('change', function () {
            const isSim = this.value === 'SIM';
            planoDentalBadge.className = `status-badge ${isSim ? 'badge-ativo' : 'badge-inativo'}`;
            planoDentalBadge.textContent = isSim ? 'Ativo' : 'Inativo';
        });
    }

    // ============================================
    // Toggle ADESAO/PME
    // ============================================
    function switchToAdesaoMode() {
        currentPropostaType = 'ADESAO';

        // Atualizar hidden inputs
        const tipoContratoInput = document.getElementById('tipo_contrato');
        if (tipoContratoInput) tipoContratoInput.value = 'ADESAO';

        // Setar layout_venda como NOVO para ADESAO
        const layoutVendaInput = document.getElementById('layout_venda');
        if (layoutVendaInput) layoutVendaInput.value = 'NOVO';

        // Atualizar badge do toggle
        const badge = document.getElementById('tipo-proposta-badge');
        if (badge) {
            badge.textContent = 'Adesao';
            badge.classList.remove('badge-ativo');
            badge.classList.add('badge-warning');
        }

        // Atualizar badge do header
        const headerBadge = document.getElementById('header-badge-tipo');
        if (headerBadge) {
            headerBadge.textContent = 'ADESAO';
        }

        // Atualizar subtitle do header
        const pageSubtitle = document.getElementById('page-subtitle-doc');
        if (pageSubtitle) {
            const cpfCnpjValue = document.getElementById('cpf_cnpj')?.value || '';
            const contratoId = vendaId || '';
            pageSubtitle.textContent = `CPF: ${cpfCnpjValue} | Contrato #${contratoId}`;
        }

        // Trocar mascara CNPJ -> CPF
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        if (cpfCnpjInput && typeof Cleave !== 'undefined') {
            const currentValue = cpfCnpjInput.value.replace(/\D/g, '');
            if (cpfCnpjCleaveInstance) {
                cpfCnpjCleaveInstance.destroy();
            }
            cpfCnpjCleaveInstance = new Cleave(cpfCnpjInput, {
                delimiters: ['.', '.', '-'],
                blocks: [3, 3, 3, 2],
                numericOnly: true
            });
            // Restaurar valor sem formatacao para a nova mascara formatar
            if (currentValue.length <= 11) {
                cpfCnpjInput.value = currentValue;
                cpfCnpjInput.dispatchEvent(new Event('input'));
            }
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

        // Limpar valor do tipo_empresa
        const tipoEmpresaSelect = document.getElementById('tipo_empresa');
        if (tipoEmpresaSelect) {
            tipoEmpresaSelect.value = '';
        }

        // Ocultar campo Cargo no modal de edicao
        const fieldCargoModal = document.querySelector('.field-cargo-modal');
        if (fieldCargoModal) {
            fieldCargoModal.style.display = 'none';
        }

        // Ocultar tags de cargo nos titulares
        document.querySelectorAll('.tag-cargo').forEach(tag => {
            tag.style.display = 'none';
        });
    }

    function switchToPmeMode() {
        currentPropostaType = 'PME';

        // Atualizar hidden input
        const tipoContratoInput = document.getElementById('tipo_contrato');
        if (tipoContratoInput) tipoContratoInput.value = 'PME';

        // Atualizar badge do toggle
        const badge = document.getElementById('tipo-proposta-badge');
        if (badge) {
            badge.textContent = 'PME';
            badge.classList.remove('badge-warning');
            badge.classList.add('badge-ativo');
        }

        // Atualizar badge do header
        const headerBadge = document.getElementById('header-badge-tipo');
        if (headerBadge) {
            headerBadge.textContent = 'PME';
        }

        // Atualizar subtitle do header
        const pageSubtitle = document.getElementById('page-subtitle-doc');
        if (pageSubtitle) {
            const cpfCnpjValue = document.getElementById('cpf_cnpj')?.value || '';
            const contratoId = vendaId || '';
            pageSubtitle.textContent = `CNPJ: ${cpfCnpjValue} | Contrato #${contratoId}`;
        }

        // Trocar mascara CPF -> CNPJ
        const cpfCnpjInput = document.getElementById('cpf_cnpj');
        if (cpfCnpjInput && typeof Cleave !== 'undefined') {
            const currentValue = cpfCnpjInput.value.replace(/\D/g, '');
            if (cpfCnpjCleaveInstance) {
                cpfCnpjCleaveInstance.destroy();
            }
            cpfCnpjCleaveInstance = new Cleave(cpfCnpjInput, {
                delimiters: ['.', '.', '/', '-'],
                blocks: [2, 3, 3, 4, 2],
                numericOnly: true
            });
            // Restaurar valor sem formatacao para a nova mascara formatar
            cpfCnpjInput.value = currentValue;
            cpfCnpjInput.dispatchEvent(new Event('input'));
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

        // Mostrar campo Cargo no modal de edicao
        const fieldCargoModal = document.querySelector('.field-cargo-modal');
        if (fieldCargoModal) {
            fieldCargoModal.style.display = '';
        }

        // Mostrar tags de cargo nos titulares
        document.querySelectorAll('.tag-cargo').forEach(tag => {
            tag.style.display = '';
        });
    }

    // Event listener para toggle de tipo de proposta
    const tipoPropostaToggle = document.getElementById('tipo_proposta_toggle');
    if (tipoPropostaToggle) {
        tipoPropostaToggle.addEventListener('change', function() {
            if (this.value === 'ADESAO') {
                switchToAdesaoMode();
            } else {
                switchToPmeMode();
            }
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
                    const outroContrato = acesso.venda_id && String(acesso.venda_id) !== String(vendaId);
                    const card = document.createElement('div');
                    card.className = 'acesso-card';
                    card.innerHTML = `
                        <div class="acesso-email">${acesso.email}</div>
                        <div class="acesso-senha">${acesso.senha}</div>
                        ${acesso.cpf ? `<div class="acesso-cpf">CPF: ${acesso.cpf}</div>` : ''}
                        ${outroContrato ? `<div class="acesso-origem">Cadastrado no contrato #${acesso.venda_id}</div>` : ''}
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
    // Add Titular (PME)
    // ============================================
    function initAddTitular() {
        const btnOpen = document.getElementById('btn-open-add-titular');
        if (!btnOpen) return;

        btnOpen.addEventListener('click', function () {
            // Clear form
            const form = document.getElementById('form-add-titular-pme');
            if (form) form.reset();

            // Reset plano anterior visibility
            const opContainer = document.getElementById('add_titular_operadora_anterior_container');
            if (opContainer) opContainer.style.display = 'none';

            const modal = new bootstrap.Modal(document.getElementById('modalAddTitularPME'));
            modal.show();
        });

        // Plano anterior change toggle
        const planoAnteriorSelect = document.getElementById('add_titular_plano_anterior');
        if (planoAnteriorSelect) {
            planoAnteriorSelect.addEventListener('change', function () {
                const opContainer = document.getElementById('add_titular_operadora_anterior_container');
                if (opContainer) {
                    opContainer.style.display = this.value === 'SIM' ? 'block' : 'none';
                }
            });
        }

        // Form submit
        const form = document.getElementById('form-add-titular-pme');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const data = {
                    venda_id: vendaId,
                    nome: document.getElementById('add_titular_nome').value,
                    cpf: document.getElementById('add_titular_cpf').value,
                    data_nascimento: document.getElementById('add_titular_data_nascimento').value,
                    email: document.getElementById('add_titular_email').value,
                    telefone: document.getElementById('add_titular_telefone').value,
                    telefone2: document.getElementById('add_titular_telefone2').value,
                    cargo: document.getElementById('add_titular_cargo')?.value || '',
                    plano_id: document.getElementById('add_titular_plano_id').value,
                    coparticipacao: document.getElementById('add_titular_coparticipacao').value,
                    plano_anterior: document.getElementById('add_titular_plano_anterior').value,
                    operadora_anterior_id: document.getElementById('add_titular_operadora_anterior_id')?.value || ''
                };

                const btn = document.getElementById('btn-submit-add-titular');
                if (btn) btn.disabled = true;

                fetch('/backoffice/titulares-pme', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            bootstrap.Modal.getInstance(document.getElementById('modalAddTitularPME'))?.hide();
                            window.location.reload();
                        } else {
                            alert(result.message || 'Erro ao cadastrar titular');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao cadastrar titular');
                    })
                    .finally(() => {
                        if (btn) btn.disabled = false;
                    });
            });
        }
    }

    // ============================================
    // Edit Titular
    // ============================================
    function initEditTitular() {
        // Initialize masks for modal fields
        document.querySelectorAll('.mask-cpf-modal').forEach(function (el) {
            if (typeof Cleave !== 'undefined' && !el.dataset.maskApplied) {
                el.dataset.maskApplied = '1';
                new Cleave(el, {
                    delimiters: ['.', '.', '-'],
                    blocks: [3, 3, 3, 2],
                    numericOnly: true
                });
            }
        });

        document.querySelectorAll('.mask-telefone-modal').forEach(function (el) {
            if (typeof Cleave !== 'undefined' && !el.dataset.maskApplied) {
                el.dataset.maskApplied = '1';
                new Cleave(el, {
                    delimiters: ['(', ') ', '-'],
                    blocks: [0, 2, 5, 4],
                    numericOnly: true
                });
            }
        });

        // Initialize flatpickr for modal date fields
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.flatpickr-modal', {
                dateFormat: 'd/m/Y',
                allowInput: true
            });
        }
        document.querySelectorAll('.flatpickr-modal').forEach(attachDateMaskBr);

        // Edit titular button click
        document.querySelectorAll('.titular-card .btn-edit[data-titular-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const titularId = this.dataset.titularId;
                document.getElementById('edit_titular_id').value = titularId;
                document.getElementById('edit_titular_nome').value = this.dataset.nome || '';
                document.getElementById('edit_titular_cpf').value = this.dataset.cpf || '';
                document.getElementById('edit_titular_data_nascimento').value = this.dataset.dataNascimento || '';
                document.getElementById('edit_titular_email').value = this.dataset.email || '';
                document.getElementById('edit_titular_telefone').value = this.dataset.telefone || '';
                document.getElementById('edit_titular_telefone2').value = this.dataset.telefone2 || '';
                document.getElementById('edit_titular_plano_id').value = this.dataset.planoId || '';
                document.getElementById('edit_titular_cargo').value = this.dataset.cargo || '';
                document.getElementById('edit_titular_coparticipacao').value = this.dataset.coparticipacao || '';
                document.getElementById('edit_titular_plano_anterior').value = this.dataset.planoAnterior || 'NAO';
                document.getElementById('edit_titular_operadora_anterior_id').value = this.dataset.operadoraAnteriorId || '';

                // Show/hide operadora anterior field
                const planoAnterior = this.dataset.planoAnterior || 'NAO';
                const opAnteriorContainer = document.getElementById('edit_titular_operadora_anterior_container');
                if (opAnteriorContainer) {
                    opAnteriorContainer.style.display = planoAnterior === 'SIM' ? 'block' : 'none';
                }

                const modal = new bootstrap.Modal(document.getElementById('modalEditTitular'));
                modal.show();
            });
        });

        // Plano anterior change toggle
        const planoAnteriorSelect = document.getElementById('edit_titular_plano_anterior');
        if (planoAnteriorSelect) {
            planoAnteriorSelect.addEventListener('change', function () {
                const opAnteriorContainer = document.getElementById('edit_titular_operadora_anterior_container');
                if (opAnteriorContainer) {
                    opAnteriorContainer.style.display = this.value === 'SIM' ? 'block' : 'none';
                }
            });
        }

        // Form submit
        const formEditTitular = document.getElementById('form-edit-titular');
        if (formEditTitular) {
            formEditTitular.addEventListener('submit', function (e) {
                e.preventDefault();

                const titularId = document.getElementById('edit_titular_id').value;

                const data = {
                    venda_id: vendaId,
                    nome: document.getElementById('edit_titular_nome').value,
                    cpf: document.getElementById('edit_titular_cpf').value,
                    data_nascimento: document.getElementById('edit_titular_data_nascimento').value,
                    email: document.getElementById('edit_titular_email').value,
                    telefone: document.getElementById('edit_titular_telefone').value,
                    telefone2: document.getElementById('edit_titular_telefone2').value,
                    cargo: document.getElementById('edit_titular_cargo').value,
                    plano_id: document.getElementById('edit_titular_plano_id').value,
                    coparticipacao: document.getElementById('edit_titular_coparticipacao').value,
                    plano_anterior: document.getElementById('edit_titular_plano_anterior').value,
                    operadora_anterior_id: document.getElementById('edit_titular_operadora_anterior_id').value
                };

                fetch(`/backoffice/titulares-pme/${titularId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            bootstrap.Modal.getInstance(document.getElementById('modalEditTitular'))?.hide();
                            // Reload page to show updated data
                            window.location.reload();
                        } else {
                            alert(result.message || 'Erro ao atualizar titular');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao atualizar titular');
                    });
            });
        }
    }

    // ============================================
    // Add/Edit Dependente
    // ============================================
    function initEditDependente() {
        const modalTitle = document.getElementById('modalDependenteTitle');

        // Add dependente button click
        document.querySelectorAll('.btn-add-dep').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const titularId = this.dataset.titularId;

                // Clear form and set for add mode
                document.getElementById('edit_dependente_id').value = '';
                document.getElementById('edit_dependente_titular_id').value = titularId;
                document.getElementById('edit_dependente_nome').value = '';
                document.getElementById('edit_dependente_cpf').value = '';
                document.getElementById('edit_dependente_data_nascimento').value = '';
                document.getElementById('edit_dependente_email').value = '';
                document.getElementById('edit_dependente_telefone1').value = '';
                document.getElementById('edit_dependente_telefone2').value = '';
                document.getElementById('edit_dependente_parentesco').value = '';
                document.getElementById('edit_dependente_plano_id').value = '';
                document.getElementById('edit_dependente_coparticipacao').value = '';
                document.getElementById('edit_dependente_plano_anterior').value = 'NAO';
                document.getElementById('edit_dependente_operadora_anterior_id').value = '';

                // Hide operadora anterior field
                const opAnteriorContainer = document.getElementById('edit_dependente_operadora_anterior_container');
                if (opAnteriorContainer) {
                    opAnteriorContainer.style.display = 'none';
                }

                // Update modal for add mode
                if (modalTitle) {
                    modalTitle.textContent = 'Adicionar Dependente';
                }
                const modalSubtitle = document.getElementById('modalDependenteSubtitle');
                if (modalSubtitle) modalSubtitle.textContent = 'Cadastre um novo dependente ao titular';
                const btnText = document.getElementById('btnSaveDependenteText');
                if (btnText) btnText.textContent = 'Adicionar';
                // Toggle icons
                const iconAdd = document.querySelector('.icon-add-dep');
                const iconEdit = document.querySelector('.icon-edit-dep');
                if (iconAdd) iconAdd.style.display = '';
                if (iconEdit) iconEdit.style.display = 'none';

                const modal = new bootstrap.Modal(document.getElementById('modalEditDependente'));
                modal.show();
            });
        });

        // Edit dependente button click
        document.querySelectorAll('.btn-edit-dep').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const dependenteId = this.dataset.dependenteId;
                document.getElementById('edit_dependente_id').value = dependenteId;
                document.getElementById('edit_dependente_titular_id').value = '';
                document.getElementById('edit_dependente_nome').value = this.dataset.nome || '';
                document.getElementById('edit_dependente_cpf').value = this.dataset.cpf || '';
                document.getElementById('edit_dependente_data_nascimento').value = this.dataset.dataNascimento || '';
                document.getElementById('edit_dependente_email').value = this.dataset.email || '';
                document.getElementById('edit_dependente_telefone1').value = this.dataset.telefone1 || '';
                document.getElementById('edit_dependente_telefone2').value = this.dataset.telefone2 || '';
                document.getElementById('edit_dependente_parentesco').value = this.dataset.parentesco || '';
                document.getElementById('edit_dependente_plano_id').value = this.dataset.planoId || '';
                document.getElementById('edit_dependente_coparticipacao').value = this.dataset.coparticipacao || '';
                document.getElementById('edit_dependente_plano_anterior').value = this.dataset.planoAnterior || 'NAO';
                document.getElementById('edit_dependente_operadora_anterior_id').value = this.dataset.operadoraAnteriorId || '';

                // Show/hide operadora anterior field
                const planoAnterior = this.dataset.planoAnterior || 'NAO';
                const opAnteriorContainer = document.getElementById('edit_dependente_operadora_anterior_container');
                if (opAnteriorContainer) {
                    opAnteriorContainer.style.display = planoAnterior === 'SIM' ? 'block' : 'none';
                }

                // Update modal for edit mode
                if (modalTitle) {
                    modalTitle.textContent = 'Editar Dependente';
                }
                const modalSubtitle = document.getElementById('modalDependenteSubtitle');
                if (modalSubtitle) modalSubtitle.textContent = 'Atualize os dados do dependente';
                const btnText = document.getElementById('btnSaveDependenteText');
                if (btnText) btnText.textContent = 'Salvar';
                // Toggle icons
                const iconAdd = document.querySelector('.icon-add-dep');
                const iconEdit = document.querySelector('.icon-edit-dep');
                if (iconAdd) iconAdd.style.display = 'none';
                if (iconEdit) iconEdit.style.display = '';

                const modal = new bootstrap.Modal(document.getElementById('modalEditDependente'));
                modal.show();
            });
        });

        // Delete dependente button click
        document.querySelectorAll('.btn-delete-dep').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const dependenteId = this.dataset.dependenteId;
                const nome = this.dataset.nome || 'este dependente';

                document.getElementById('delete_dependente_id').value = dependenteId;
                document.getElementById('delete-dependente-nome').textContent = nome;

                const modal = new bootstrap.Modal(document.getElementById('modalDeleteDependente'));
                modal.show();
            });
        });

        // Confirm delete dependente
        const btnConfirmDeleteDependente = document.getElementById('btn-confirm-delete-dependente');
        if (btnConfirmDeleteDependente) {
            btnConfirmDeleteDependente.addEventListener('click', function () {
                const dependenteId = document.getElementById('delete_dependente_id').value;

                fetch(`/backoffice/dependentes-pme/${dependenteId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
                    .then(res => res.json())
                    .then(result => {
                        bootstrap.Modal.getInstance(document.getElementById('modalDeleteDependente'))?.hide();
                        if (result.success) {
                            window.location.reload();
                        } else {
                            alert(result.message || 'Erro ao remover dependente');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao remover dependente');
                    });
            });
        }

        // Plano anterior change toggle
        const planoAnteriorSelect = document.getElementById('edit_dependente_plano_anterior');
        if (planoAnteriorSelect) {
            planoAnteriorSelect.addEventListener('change', function () {
                const opAnteriorContainer = document.getElementById('edit_dependente_operadora_anterior_container');
                if (opAnteriorContainer) {
                    opAnteriorContainer.style.display = this.value === 'SIM' ? 'block' : 'none';
                }
            });
        }

        // Form submit (add or edit)
        const formEditDependente = document.getElementById('form-edit-dependente');
        if (formEditDependente) {
            formEditDependente.addEventListener('submit', function (e) {
                e.preventDefault();

                const dependenteId = document.getElementById('edit_dependente_id').value;
                const titularId = document.getElementById('edit_dependente_titular_id').value;
                const isAddMode = !dependenteId && titularId;

                const data = {
                    venda_id: vendaId,
                    nome: document.getElementById('edit_dependente_nome').value,
                    cpf: document.getElementById('edit_dependente_cpf').value,
                    data_nascimento: document.getElementById('edit_dependente_data_nascimento').value,
                    email: document.getElementById('edit_dependente_email').value,
                    telefone1: document.getElementById('edit_dependente_telefone1').value,
                    telefone2: document.getElementById('edit_dependente_telefone2').value,
                    parentesco: document.getElementById('edit_dependente_parentesco').value,
                    plano_id: document.getElementById('edit_dependente_plano_id').value,
                    coparticipacao: document.getElementById('edit_dependente_coparticipacao').value,
                    plano_anterior: document.getElementById('edit_dependente_plano_anterior').value,
                    operadora_anterior_id: document.getElementById('edit_dependente_operadora_anterior_id').value
                };

                let url, method;
                if (isAddMode) {
                    data.titular_id = titularId;
                    url = '/backoffice/dependentes-pme';
                    method = 'POST';
                } else {
                    url = `/backoffice/dependentes-pme/${dependenteId}`;
                    method = 'PUT';
                }

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            bootstrap.Modal.getInstance(document.getElementById('modalEditDependente'))?.hide();
                            // Reload page to show updated data
                            window.location.reload();
                        } else {
                            alert(result.message || (isAddMode ? 'Erro ao adicionar dependente' : 'Erro ao atualizar dependente'));
                        }
                    })
                    .catch(() => {
                        alert(isAddMode ? 'Erro ao adicionar dependente' : 'Erro ao atualizar dependente');
                    });
            });
        }
    }

    // ============================================
    // Add/Edit/Delete Portabilidade
    // ============================================
    function initEditPortabilidade() {
        const modalTitle = document.getElementById('modalPortabilidadeTitle');
        const operadoraDestino = document.getElementById('edit_port_operadora_destino_id');
        const planoDestino = document.getElementById('edit_port_plano_destino_id');
        const planosPortabilidade = Array.isArray(window.portabilidadePlanos) ? window.portabilidadePlanos : [];
        const escapePortHtml = value => String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        function preencherPlanosDestino(operadoraId, selectedId = '') {
            if (!planoDestino) return;
            const planos = planosPortabilidade.filter(plano => String(plano.operadora_id) === String(operadoraId));
            planoDestino.disabled = !operadoraId || planos.length === 0;
            planoDestino.innerHTML = planos.length === 0
                ? `<option value="">${operadoraId ? 'Nenhum plano ativo' : 'Selecione a operadora...'}</option>`
                : `<option value="">Selecione...</option>${planos.map(plano => `<option value="${plano.id}">${escapePortHtml(plano.nome)}</option>`).join('')}`;
            planoDestino.value = String(selectedId || '');
        }

        operadoraDestino?.addEventListener('change', () => preencherPlanosDestino(operadoraDestino.value));

        // Add portabilidade button click
        document.querySelectorAll('.btn-add-port').forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Clear form for add mode
                document.getElementById('edit_port_id').value = '';
                document.getElementById('edit_port_nome').value = '';
                document.getElementById('edit_port_cpf').value = '';
                document.getElementById('edit_port_data_nascimento').value = '';
                document.getElementById('edit_port_operadora_anterior_id').value = '';
                document.getElementById('edit_port_plano_anterior').value = '';
                document.getElementById('edit_port_numero_carteirinha').value = '';
                document.getElementById('edit_port_operadora_destino_id').value = '';
                preencherPlanosDestino('');

                // Update modal title
                if (modalTitle) {
                    modalTitle.textContent = 'Adicionar Beneficiário';
                }
            });
        });

        // Edit portabilidade button click
        document.querySelectorAll('.portabilidade-btn-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const portId = this.dataset.portId;
                document.getElementById('edit_port_id').value = portId;
                document.getElementById('edit_port_nome').value = this.dataset.nome || '';
                document.getElementById('edit_port_cpf').value = this.dataset.cpf || '';
                document.getElementById('edit_port_data_nascimento').value = this.dataset.dataNascimento || '';
                document.getElementById('edit_port_operadora_anterior_id').value = this.dataset.operadoraAnteriorId || '';
                document.getElementById('edit_port_plano_anterior').value = this.dataset.planoAnterior || '';
                document.getElementById('edit_port_numero_carteirinha').value = this.dataset.numeroCarteirinha || '';
                document.getElementById('edit_port_operadora_destino_id').value = this.dataset.operadoraDestinoId || '';
                preencherPlanosDestino(this.dataset.operadoraDestinoId || '', this.dataset.planoDestinoId || '');

                // Update modal title
                if (modalTitle) {
                    modalTitle.textContent = 'Editar Beneficiário';
                }

                const modal = new bootstrap.Modal(document.getElementById('modalEditPortabilidade'));
                modal.show();
            });
        });

        // Delete portabilidade button click
        document.querySelectorAll('.portabilidade-btn-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const portId = this.dataset.portId;
                const nome = this.dataset.nome || 'este beneficiário';

                document.getElementById('delete_port_id').value = portId;
                document.getElementById('delete-port-nome').textContent = nome;

                const modal = new bootstrap.Modal(document.getElementById('modalDeletePortabilidade'));
                modal.show();
            });
        });

        // Confirm delete portabilidade
        const btnConfirmDeletePort = document.getElementById('btn-confirm-delete-port');
        if (btnConfirmDeletePort) {
            btnConfirmDeletePort.addEventListener('click', function () {
                const portId = document.getElementById('delete_port_id').value;

                fetch(`/backoffice/portabilidades-pme/${portId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
                    .then(res => res.json())
                    .then(result => {
                        bootstrap.Modal.getInstance(document.getElementById('modalDeletePortabilidade'))?.hide();
                        if (result.success) {
                            window.location.reload();
                        } else {
                            alert(result.message || 'Erro ao remover beneficiário');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao remover beneficiário');
                    });
            });
        }

        // Form submit (add or edit)
        const formEditPortabilidade = document.getElementById('form-edit-portabilidade');
        if (formEditPortabilidade) {
            formEditPortabilidade.addEventListener('submit', function (e) {
                e.preventDefault();

                const portId = document.getElementById('edit_port_id').value;
                const isAddMode = !portId;

                const data = {
                    venda_id: vendaId,
                    nome: document.getElementById('edit_port_nome').value,
                    cpf: document.getElementById('edit_port_cpf').value,
                    data_nascimento: document.getElementById('edit_port_data_nascimento').value,
                    operadora_anterior_id: document.getElementById('edit_port_operadora_anterior_id').value,
                    plano_anterior: document.getElementById('edit_port_plano_anterior').value,
                    numero_carteirinha: document.getElementById('edit_port_numero_carteirinha').value,
                    operadora_destino_id: document.getElementById('edit_port_operadora_destino_id').value,
                    plano_destino_id: document.getElementById('edit_port_plano_destino_id').value
                };

                let url, method;
                if (isAddMode) {
                    url = '/backoffice/portabilidades-pme';
                    method = 'POST';
                } else {
                    url = `/backoffice/portabilidades-pme/${portId}`;
                    method = 'PUT';
                }

                fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    },
                    body: JSON.stringify(data)
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            bootstrap.Modal.getInstance(document.getElementById('modalEditPortabilidade'))?.hide();
                            window.location.reload();
                        } else {
                            alert(result.message || (isAddMode ? 'Erro ao adicionar beneficiário' : 'Erro ao atualizar beneficiário'));
                        }
                    })
                    .catch(() => {
                        alert(isAddMode ? 'Erro ao adicionar beneficiário' : 'Erro ao atualizar beneficiário');
                    });
            });
        }
    }

    // ============================================
    // Delete Titular
    // ============================================
    function initDeleteTitular() {
        document.querySelectorAll('.btn-delete-titular').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const titularId = this.dataset.titularId;
                const nome = this.dataset.titularNome || 'este titular';

                document.getElementById('delete_titular_id').value = titularId;
                document.getElementById('delete-titular-text').textContent =
                    `O titular "${nome}" e todos os seus dependentes serão removidos.`;

                const modal = new bootstrap.Modal(document.getElementById('modalDeleteTitular'));
                modal.show();
            });
        });

        const btnConfirmDeleteTitular = document.getElementById('btn-confirm-delete-titular');
        if (btnConfirmDeleteTitular) {
            btnConfirmDeleteTitular.addEventListener('click', function () {
                const titularId = document.getElementById('delete_titular_id').value;
                const btn = this;

                btn.disabled = true;
                btn.textContent = 'Removendo...';

                fetch(`/backoffice/titulares/${titularId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
                    .then(res => res.json())
                    .then(result => {
                        bootstrap.Modal.getInstance(document.getElementById('modalDeleteTitular'))?.hide();
                        if (result.success) {
                            window.location.reload();
                        } else {
                            alert(result.message || 'Erro ao remover titular');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao remover titular');
                    })
                    .finally(() => {
                        btn.disabled = false;
                        btn.textContent = 'Remover';
                    });
            });
        }
    }

    // ============================================
    // Initialize
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        loadAcessos();
        initAddTitular();
        initEditTitular();
        initEditDependente();
        initEditPortabilidade();
        initDeleteTitular();
    });

})();
