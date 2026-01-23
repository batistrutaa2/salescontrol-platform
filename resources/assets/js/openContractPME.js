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
            dateFormat: 'd/m/Y'
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
    // Initialize
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        loadAcessos();
        initEditTitular();
        initEditDependente();
        initEditPortabilidade();
    });

})();
