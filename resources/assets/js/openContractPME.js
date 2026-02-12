'use strict';

(function () {
    // ============================================
    // State
    // ============================================
    const vendaId = document.querySelector('input[name="venda_id"]')?.value ||
                    document.querySelector('input[name="id"]')?.value;
    let currentPropostaType = document.getElementById('tipo_contrato')?.value || 'PME';
    let cpfCnpjCleaveInstance = null;

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
            dateFormat: 'd/m/Y'
        });
    }

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
                dateFormat: 'd/m/Y'
            });
        }

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

                // Update modal title
                if (modalTitle) {
                    modalTitle.textContent = 'Adicionar Dependente';
                }

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

                // Update modal title
                if (modalTitle) {
                    modalTitle.textContent = 'Editar Dependente';
                }

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
                    numero_carteirinha: document.getElementById('edit_port_numero_carteirinha').value
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
    // Demandas do Contrato
    // ============================================
    const demandasList = document.getElementById('demandas-list');
    const demandasEmpty = document.getElementById('demandas-empty');
    const demandasLoading = document.getElementById('demandas-loading');
    const demandasCount = document.getElementById('demandas-count');

    const tipoLabels = {
        'CANCELAMENTO': 'Cancelamento',
        'CARTA_PERMANENCIA': 'Carta de Permanência',
        'PORTABILIDADE': 'Portabilidade',
        'TROCA_EMAIL': 'Troca de E-mail',
        'OUTRO': 'Outro'
    };

    // Auto-fill title based on tipo selection
    const demandaTipo = document.getElementById('demanda_tipo');
    if (demandaTipo) {
        demandaTipo.addEventListener('change', function () {
            const tipo = this.value;
            const tituloEl = document.getElementById('demanda_titulo');
            const demandaId = document.getElementById('demanda_id').value;

            if (tipo && tipo !== 'OUTRO') {
                tituloEl.value = tipoLabels[tipo] || tipo;
                tituloEl.readOnly = true;
            } else {
                if (!demandaId) tituloEl.value = '';
                tituloEl.readOnly = false;
            }
        });
    }

    // Panel collapse/expand toggle
    const panelToggle = document.getElementById('demandas-panel-toggle');
    if (panelToggle) {
        panelToggle.addEventListener('click', function () {
            const body = document.getElementById('demandas-panel-body');
            const chevron = document.getElementById('btn-panel-chevron');
            if (body) body.classList.toggle('collapsed');
            if (chevron) chevron.classList.toggle('rotated');
        });
    }

    // Stat header click toggles panel
    const statToggle = document.getElementById('stat-demandas-toggle');
    if (statToggle) {
        statToggle.addEventListener('click', function () {
            const body = document.getElementById('demandas-panel-body');
            const chevron = document.getElementById('btn-panel-chevron');
            if (body && body.classList.contains('collapsed')) {
                body.classList.remove('collapsed');
                if (chevron) chevron.classList.remove('rotated');
            }
            // Scroll to panel
            const panel = document.querySelector('.demandas-panel');
            if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    function loadDemandas() {
        if (!vendaId || !demandasList) return;

        demandasLoading.style.display = 'flex';
        demandasEmpty.style.display = 'none';
        demandasList.innerHTML = '';

        const progressEl = document.getElementById('demandas-progress');
        const progressFill = document.getElementById('demandas-progress-fill');
        const progressText = document.getElementById('demandas-progress-text');

        fetch(`/back-office/demandas-contrato/${vendaId}`)
            .then(res => res.json())
            .then(data => {
                demandasLoading.style.display = 'none';

                if (!data.demandas || data.demandas.length === 0) {
                    demandasEmpty.style.display = 'block';
                    if (demandasCount) demandasCount.textContent = 'Nenhuma demanda';
                    const statCount = document.getElementById('demandas-stat-count');
                    if (statCount) statCount.textContent = '0';
                    if (progressEl) progressEl.style.display = 'none';
                    return;
                }

                const pendentes = data.pendentes || 0;
                const total = data.total || 0;
                const concluidas = total - pendentes;
                const pct = total > 0 ? Math.round((concluidas / total) * 100) : 0;

                if (demandasCount) demandasCount.textContent = `${pendentes} pendente${pendentes !== 1 ? 's' : ''} de ${total}`;

                const statCount = document.getElementById('demandas-stat-count');
                if (statCount) statCount.textContent = pendentes;

                // Update progress bar
                if (progressEl) progressEl.style.display = 'flex';
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = pct + '%';

                data.demandas.forEach((demanda, index) => {
                    const item = criarCardDemandaPME(demanda, index);
                    demandasList.appendChild(item);
                });

                attachDemandaEvents();
            })
            .catch(() => {
                demandasLoading.style.display = 'none';
                demandasEmpty.style.display = 'block';
                demandasEmpty.innerHTML = '<p>Erro ao carregar demandas</p>';
            });
    }

    function criarCardDemandaPME(demanda, index) {
        const isConcluida = demanda.status === 'CONCLUIDA';
        const tipoLabel = tipoLabels[demanda.tipo] || demanda.tipo;
        const el = document.createElement('div');
        el.className = `demanda-item ${isConcluida ? 'demanda-concluida' : 'demanda-pendente'}`;
        el.dataset.demandaId = demanda.id;
        el.style.animationDelay = `${index * 60}ms`;

        const checkIcon = isConcluida
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"></path></svg>'
            : '';

        const descricaoHtml = demanda.descricao
            ? `<span class="demanda-desc-text">${demanda.descricao}</span>`
            : '';

        const concluidaInfo = isConcluida && demanda.concluida_por
            ? `<span class="demanda-meta-sep">&middot;</span><span class="demanda-concluida-info">Concluída por ${demanda.concluida_por.name}</span>`
            : '';

        el.innerHTML = `
            <button type="button" class="demanda-checkbox" data-demanda-id="${demanda.id}" title="${isConcluida ? 'Reabrir' : 'Concluir'}">
                <span class="checkbox-visual ${isConcluida ? 'checked' : ''}">${checkIcon}</span>
            </button>
            <div class="demanda-body">
                <div class="demanda-row-top">
                    <span class="demanda-tipo tipo-${demanda.tipo.toLowerCase()}">${tipoLabel}</span>
                    <span class="demanda-titulo-text">${demanda.titulo}</span>
                    ${descricaoHtml}
                </div>
                <div class="demanda-row-bottom">
                    <span class="demanda-meta-info">${demanda.criador ? demanda.criador.name : ''}</span>
                    <span class="demanda-meta-sep">&middot;</span>
                    <span class="demanda-meta-info">${demanda.created_at || ''}</span>
                    ${concluidaInfo}
                </div>
            </div>
            <div class="demanda-actions">
                <button type="button" class="btn-edit-demanda" data-demanda-id="${demanda.id}" data-tipo="${demanda.tipo}" data-titulo="${demanda.titulo}" data-descricao="${demanda.descricao || ''}" title="Editar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                </button>
                <button type="button" class="btn-delete-demanda" data-demanda-id="${demanda.id}" title="Remover">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `;

        return el;
    }

    function attachDemandaEvents() {
        // Toggle status
        document.querySelectorAll('.demanda-checkbox').forEach(btn => {
            btn.addEventListener('click', function () {
                const demandaId = this.dataset.demandaId;
                this.disabled = true;

                fetch(`/back-office/demandas-contrato/${demandaId}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                })
                    .then(res => res.json())
                    .then(result => {
                        if (result.success) {
                            loadDemandas();
                        } else {
                            alert(result.message || 'Erro ao alterar status');
                        }
                    })
                    .catch(() => {
                        alert('Erro ao alterar status da demanda');
                    });
            });
        });

        // Edit buttons
        document.querySelectorAll('.btn-edit-demanda').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.demandaId;
                const tipo = this.dataset.tipo;
                const titulo = this.dataset.titulo;
                const descricao = this.dataset.descricao;

                document.getElementById('demanda_id').value = id;
                document.getElementById('demanda_tipo').value = tipo;
                document.getElementById('demanda_titulo').value = titulo;
                document.getElementById('demanda_descricao').value = descricao;

                const tituloEl = document.getElementById('demanda_titulo');
                tituloEl.readOnly = tipo !== 'OUTRO';

                const labelEl = document.getElementById('modalAddDemandaLabel');
                if (labelEl) labelEl.textContent = 'Editar Demanda';

                const btnText = document.getElementById('btn-save-demanda-text');
                if (btnText) btnText.textContent = 'Atualizar';

                const modal = new bootstrap.Modal(document.getElementById('modalAddDemanda'));
                modal.show();
            });
        });

        // Delete buttons
        document.querySelectorAll('.btn-delete-demanda').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('delete_demanda_id').value = this.dataset.demandaId;
                const modal = new bootstrap.Modal(document.getElementById('modalDeleteDemanda'));
                modal.show();
            });
        });
    }

    // Form submit for demanda
    const formDemanda = document.getElementById('form-demanda');
    if (formDemanda) {
        formDemanda.addEventListener('submit', function (e) {
            e.preventDefault();

            const demandaId = document.getElementById('demanda_id').value;
            const tipo = document.getElementById('demanda_tipo').value;
            const titulo = document.getElementById('demanda_titulo').value;
            const descricao = document.getElementById('demanda_descricao').value;

            if (!tipo || !titulo) {
                alert('Preencha todos os campos obrigatórios.');
                return;
            }

            const url = demandaId
                ? `/back-office/demandas-contrato/${demandaId}`
                : '/back-office/demandas-contrato';

            const method = demandaId ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    venda_id: vendaId,
                    tipo: tipo,
                    titulo: titulo,
                    descricao: descricao || null
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        bootstrap.Modal.getInstance(document.getElementById('modalAddDemanda'))?.hide();
                        formDemanda.reset();
                        document.getElementById('demanda_id').value = '';
                        document.getElementById('demanda_titulo').readOnly = false;
                        loadDemandas();
                    } else {
                        alert(data.message || 'Erro ao salvar demanda');
                    }
                })
                .catch(() => {
                    alert('Erro ao salvar demanda');
                });
        });
    }

    // Confirm delete demanda
    const btnConfirmDeleteDemanda = document.getElementById('btn-confirm-delete-demanda');
    if (btnConfirmDeleteDemanda) {
        btnConfirmDeleteDemanda.addEventListener('click', function () {
            const demandaId = document.getElementById('delete_demanda_id').value;

            fetch(`/back-office/demandas-contrato/${demandaId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            })
                .then(res => res.json())
                .then(data => {
                    bootstrap.Modal.getInstance(document.getElementById('modalDeleteDemanda'))?.hide();
                    loadDemandas();
                })
                .catch(() => {
                    alert('Erro ao remover demanda');
                });
        });
    }

    // Reset demanda modal on close
    const modalAddDemanda = document.getElementById('modalAddDemanda');
    if (modalAddDemanda) {
        modalAddDemanda.addEventListener('hidden.bs.modal', function () {
            formDemanda?.reset();
            document.getElementById('demanda_id').value = '';
            document.getElementById('demanda_titulo').readOnly = false;
            const labelEl = document.getElementById('modalAddDemandaLabel');
            if (labelEl) labelEl.textContent = 'Nova Demanda';
            const btnText = document.getElementById('btn-save-demanda-text');
            if (btnText) btnText.textContent = 'Salvar';
        });
    }

    // ============================================
    // Initialize
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        loadAcessos();
        loadDemandas();
        initEditTitular();
        initEditDependente();
        initEditPortabilidade();
    });

})();
