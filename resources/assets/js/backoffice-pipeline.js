'use strict';

/**
 * Backoffice Pipeline - JavaScript
 * Gerencia a visualização em pipeline dos contratos do backoffice
 */

(function () {
    // Status IDs (baseados na tabela tabulacoes)
    const STATUS_IDS = {
        VENDA: 16,
        ANALISE_DOCUMENTOS: 54,
        PENDENCIA: 55,
        CONTRATO_GERADO: 56,
        REGULARIZADO: 57,
        BOLETO_DISPONIVEL: 58,
        ANALISE_OPERADORA: 59,
        AGUARD_ASSINATURA: 60,
        IMPLANTADO: 18,
        ESTORNO: 17,
        DECLINADO: 53
    };

    // Status que são saídas negativas (não fazem parte do fluxo principal)
    const NEGATIVE_EXITS = [STATUS_IDS.ESTORNO, STATUS_IDS.DECLINADO];

    // Status de desvio (pendência/regularizado - saem temporariamente do fluxo)
    const DESVIO_STATUS = [STATUS_IDS.PENDENCIA, STATUS_IDS.REGULARIZADO];

    // Ordem correta do fluxo principal
    const FLUXO_PRINCIPAL_ORDEM = [
        STATUS_IDS.VENDA,
        STATUS_IDS.ANALISE_DOCUMENTOS,
        STATUS_IDS.ANALISE_OPERADORA,
        STATUS_IDS.CONTRATO_GERADO,
        STATUS_IDS.AGUARD_ASSINATURA,
        STATUS_IDS.BOLETO_DISPONIVEL,
        STATUS_IDS.IMPLANTADO
    ];

    // Mapeamento de status para classes CSS
    const STATUS_CSS_MAP = {
        [STATUS_IDS.VENDA]: 'status-venda',
        [STATUS_IDS.ANALISE_DOCUMENTOS]: 'status-analise-docs',
        [STATUS_IDS.PENDENCIA]: 'status-pendencia',
        [STATUS_IDS.REGULARIZADO]: 'status-regularizado',
        [STATUS_IDS.ANALISE_OPERADORA]: 'status-analise-operadora',
        [STATUS_IDS.CONTRATO_GERADO]: 'status-contrato-gerado',
        [STATUS_IDS.AGUARD_ASSINATURA]: 'status-aguard-assinatura',
        [STATUS_IDS.BOLETO_DISPONIVEL]: 'status-boleto-disponivel',
        [STATUS_IDS.IMPLANTADO]: 'status-implantado',
        [STATUS_IDS.ESTORNO]: 'status-estorno',
        [STATUS_IDS.DECLINADO]: 'status-declinado'
    };

    // Mapeamento de status para ícones
    const STATUS_ICONS = {
        [STATUS_IDS.VENDA]: 'ri-shopping-cart-2-line',
        [STATUS_IDS.ANALISE_DOCUMENTOS]: 'ri-file-search-line',
        [STATUS_IDS.PENDENCIA]: 'ri-error-warning-line',
        [STATUS_IDS.REGULARIZADO]: 'ri-checkbox-circle-line',
        [STATUS_IDS.ANALISE_OPERADORA]: 'ri-building-line',
        [STATUS_IDS.CONTRATO_GERADO]: 'ri-file-text-line',
        [STATUS_IDS.AGUARD_ASSINATURA]: 'ri-edit-line',
        [STATUS_IDS.BOLETO_DISPONIVEL]: 'ri-bank-card-line',
        [STATUS_IDS.IMPLANTADO]: 'ri-check-double-line',
        [STATUS_IDS.ESTORNO]: 'ri-arrow-go-back-line',
        [STATUS_IDS.DECLINADO]: 'ri-close-circle-line'
    };

    // Helpers
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCurrency(value) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(value || 0);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        // Se já está no formato BR, retornar como está
        if (/^\d{2}\/\d{2}\/\d{4}/.test(dateStr)) {
            return dateStr;
        }
        // Converter de ISO para BR
        const date = new Date(dateStr);
        if (isNaN(date)) return dateStr;
        return date.toLocaleDateString('pt-BR');
    }

    function getInitials(name) {
        if (!name) return '??';
        const parts = name.trim().split(/\s+/);
        if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function getStatusCss(statusId) {
        return STATUS_CSS_MAP[statusId] || 'status-venda';
    }

    function getStatusIcon(statusId) {
        return STATUS_ICONS[statusId] || 'ri-question-line';
    }

    // Estado global
    let pipelineData = null;

    // Carregar dados do pipeline
    async function loadPipelineData() {
        const vendedor = document.getElementById('filter-vendedor')?.value || '';
        const mes = document.getElementById('filter-mes')?.value || '';
        const ano = document.getElementById('filter-ano')?.value || '';

        const params = new URLSearchParams();
        if (vendedor) params.append('vendedor_id', vendedor);
        if (mes) params.append('mes', mes);
        if (ano) params.append('ano', ano);

        try {
            const response = await fetch(`/back-office/pipeline-data?${params.toString()}`);
            if (!response.ok) throw new Error('Erro ao carregar dados');
            pipelineData = await response.json();
            renderPipeline();
            updateKPIs();
            populateVendedoresFilter();
        } catch (error) {
            console.error('Erro ao carregar pipeline:', error);
            showError('Não foi possível carregar os dados do pipeline.');
        }
    }

    // Atualizar KPIs
    function updateKPIs() {
        if (!pipelineData?.kpis) return;

        document.getElementById('kpi-total').textContent = pipelineData.kpis.total || 0;
        document.getElementById('kpi-andamento').textContent = pipelineData.kpis.em_andamento || 0;
        document.getElementById('kpi-implantados').textContent = pipelineData.kpis.implantados || 0;
        document.getElementById('kpi-conversao').textContent = `${pipelineData.kpis.taxa_conversao || 0}%`;
    }

    // Popular filtro de vendedores
    function populateVendedoresFilter() {
        if (!pipelineData?.vendedores) return;

        const select = document.getElementById('filter-vendedor');
        if (!select) return;

        const currentValue = select.value;
        select.innerHTML = '<option value="">Todos os Corretores</option>';

        pipelineData.vendedores.forEach(v => {
            const option = document.createElement('option');
            option.value = v.id;
            option.textContent = v.name;
            if (currentValue == v.id) option.selected = true;
            select.appendChild(option);
        });
    }

    // Renderizar pipeline completo
    function renderPipeline() {
        if (!pipelineData?.pipeline) return;

        const stagesContainer = document.getElementById('pipeline-stages');
        const exitsContainer = document.getElementById('pipeline-exits');
        const desvioContainer = document.getElementById('pipeline-desvio');

        if (!stagesContainer) return;

        // Criar mapa de stages por ID para acesso rápido
        const stagesMap = {};
        pipelineData.pipeline.forEach(s => { stagesMap[s.id] = s; });

        // Separar stages por tipo
        const mainStages = FLUXO_PRINCIPAL_ORDEM
            .filter(id => stagesMap[id])
            .map(id => stagesMap[id]);

        const desvioStages = DESVIO_STATUS
            .filter(id => stagesMap[id])
            .map(id => stagesMap[id]);

        const exitStages = NEGATIVE_EXITS
            .filter(id => stagesMap[id])
            .map(id => stagesMap[id]);

        // Renderizar fluxo principal com setas de conexão
        let mainHtml = '<div class="pipeline-flow">';
        mainStages.forEach((stage, index) => {
            mainHtml += renderStage(stage, false, index);
            // Adicionar seta de conexão (exceto após ANÁLISE OPERADORA que tem desvio e após IMPLANTADO)
            if (index < mainStages.length - 1) {
                const isDesvioPoint = stage.id === STATUS_IDS.ANALISE_OPERADORA;
                mainHtml += `<div class="flow-connector ${isDesvioPoint ? 'has-desvio' : ''}">
                    <div class="connector-line"></div>
                    <div class="connector-arrow"><i class="ri-arrow-right-s-line"></i></div>
                </div>`;
            }
        });
        mainHtml += '</div>';

        stagesContainer.innerHTML = mainHtml;

        // Renderizar desvio (PENDÊNCIA/REGULARIZADO)
        if (desvioStages.length > 0 && desvioContainer) {
            desvioContainer.style.display = 'block';
            let desvioHtml = `
                <div class="desvio-header">
                    <div class="desvio-connector-up">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                    <span class="desvio-label">Desvio Temporário</span>
                </div>
                <div class="desvio-stages">
            `;
            desvioStages.forEach((stage, index) => {
                desvioHtml += renderStage(stage, false, index, true);
                if (index < desvioStages.length - 1) {
                    desvioHtml += `<div class="desvio-flow-connector">
                        <i class="ri-arrow-left-right-line"></i>
                    </div>`;
                }
            });
            desvioHtml += '</div>';
            desvioContainer.innerHTML = desvioHtml;
        } else if (desvioContainer) {
            desvioContainer.style.display = 'none';
        }

        // Renderizar saídas negativas
        if (exitStages.length > 0 && exitsContainer) {
            exitsContainer.style.display = 'flex';
            let exitsHtml = `
                <div class="exits-header">
                    <i class="ri-error-warning-line"></i>
                    <span>Saídas Negativas</span>
                </div>
                <div class="exits-stages">
            `;
            exitStages.forEach(stage => {
                exitsHtml += renderStage(stage, true);
            });
            exitsHtml += '</div>';
            exitsContainer.innerHTML = exitsHtml;
        } else if (exitsContainer) {
            exitsContainer.style.display = 'none';
        }

        // Adicionar event listeners
        attachCardEventListeners();
    }

    // Renderizar um stage do pipeline
    function renderStage(stage, isExit = false, index = 0, isDesvio = false) {
        const statusCss = getStatusCss(stage.id);
        const statusIcon = getStatusIcon(stage.id);
        const total = stage.contratos?.length || 0;
        const totalGeral = pipelineData?.kpis?.total || 1;
        const progressPercent = Math.round((total / totalGeral) * 100);
        const atrasados = stage.atrasados || 0;

        // Classe adicional para tipo de stage
        let stageTypeClass = '';
        if (isExit) stageTypeClass = 'exit-stage';
        else if (isDesvio) stageTypeClass = 'desvio-stage';
        else if (stage.id === STATUS_IDS.IMPLANTADO) stageTypeClass = 'final-stage';

        let contractsHtml = '';
        if (stage.contratos && stage.contratos.length > 0) {
            contractsHtml = stage.contratos.map(c => renderContractCard(c)).join('');
        } else {
            contractsHtml = `
                <div class="stage-empty">
                    <i class="ri-inbox-line"></i>
                    <p>Nenhum contrato</p>
                </div>
            `;
        }

        // Badge de atrasados se houver
        const atrasadosBadge = atrasados > 0
            ? `<span class="stage-alert" title="${atrasados} contrato(s) atrasado(s)"><i class="ri-alarm-warning-line"></i> ${atrasados}</span>`
            : '';

        return `
            <div class="pipeline-stage ${stageTypeClass}" data-stage-id="${stage.id}" style="--stage-index: ${index}">
                <div class="stage-header ${statusCss}">
                    <div class="stage-title">
                        <span class="title-text">
                            <i class="${statusIcon}"></i>
                            ${escapeHtml(stage.nome || stage.descricao || 'Sem nome')}
                        </span>
                        <div class="stage-badges">
                            ${atrasadosBadge}
                            <span class="stage-count">${total}</span>
                        </div>
                    </div>
                    <div class="stage-progress">
                        <div class="progress-bar" style="width: ${progressPercent}%"></div>
                    </div>
                </div>
                <div class="stage-body">
                    ${contractsHtml}
                </div>
            </div>
        `;
    }

    // Renderizar card de contrato
    function renderContractCard(contract) {
        // O backend retorna 'id' como venda_id
        const vendaId = contract.id || contract.venda_id;
        const delayClass = getDelayClass(contract.dias_no_status, contract.prazo_maximo);
        const delayBadge = getDelayBadge(contract.dias_no_status, contract.prazo_maximo);

        return `
            <div class="contract-card ${delayClass}"
                 data-venda-id="${vendaId}"
                 data-contato-id="${contract.contato_id}">
                <div class="card-header-info">
                    <h6 class="contract-name">${escapeHtml(contract.nome_contrato || 'Sem nome')}</h6>
                    ${delayBadge}
                </div>
                <div class="card-info">
                    <div class="info-row">
                        <i class="ri-building-line"></i>
                        <span class="info-value">${escapeHtml(contract.operadora || 'N/D')}</span>
                    </div>
                    <div class="info-row">
                        <i class="ri-money-dollar-circle-line"></i>
                        <span class="info-value">${formatCurrency(contract.valor || contract.valor_contrato)}</span>
                    </div>
                    <div class="info-row">
                        <i class="ri-calendar-line"></i>
                        <span class="info-label">Desde:</span>
                        <span class="info-value">${formatDate(contract.data_venda || contract.data_status)}</span>
                    </div>
                </div>
                <div class="card-footer-info">
                    <div class="broker-info">
                        <span class="broker-avatar">${getInitials(contract.vendedor || contract.corretor)}</span>
                        <span>${escapeHtml(contract.vendedor || contract.corretor || 'N/D')}</span>
                    </div>
                    <div class="card-actions">
                        <a href="/back-office/abrir-contrato/${vendaId}"
                           class="action-btn btn-view"
                           title="Ver contrato">
                            <i class="ri-eye-line"></i>
                        </a>
                        <button type="button"
                                class="action-btn btn-history js-ver-historico"
                                data-venda-id="${vendaId}"
                                title="Ver histórico">
                            <i class="ri-history-line"></i>
                        </button>
                        <button type="button"
                                class="action-btn btn-status js-alterar-status"
                                data-venda-id="${vendaId}"
                                data-contato-id="${contract.contato_id}"
                                title="Alterar status">
                            <i class="ri-refresh-line"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    // Obter classe de atraso
    function getDelayClass(diasNoStatus, prazoMaximo) {
        if (!prazoMaximo || prazoMaximo === 0) return 'delay-ok';
        if (diasNoStatus >= prazoMaximo) return 'delay-danger';
        if (diasNoStatus >= prazoMaximo * 0.7) return 'delay-warning';
        return 'delay-ok';
    }

    // Obter badge de atraso
    function getDelayBadge(diasNoStatus, prazoMaximo) {
        if (!prazoMaximo || prazoMaximo === 0) {
            return `<span class="delay-badge badge-ok"><i class="ri-time-line"></i> ${diasNoStatus}d</span>`;
        }

        if (diasNoStatus >= prazoMaximo) {
            const atraso = diasNoStatus - prazoMaximo;
            return `<span class="delay-badge badge-danger"><i class="ri-alarm-warning-line"></i> +${atraso}d atraso</span>`;
        }

        if (diasNoStatus >= prazoMaximo * 0.7) {
            const restante = prazoMaximo - diasNoStatus;
            return `<span class="delay-badge badge-warning"><i class="ri-time-line"></i> ${restante}d restante</span>`;
        }

        return `<span class="delay-badge badge-ok"><i class="ri-time-line"></i> ${diasNoStatus}d</span>`;
    }

    // Adicionar event listeners aos cards
    function attachCardEventListeners() {
        // Ver histórico
        document.querySelectorAll('.js-ver-historico').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const vendaId = this.dataset.vendaId;
                openHistoricoModal(vendaId);
            });
        });

        // Alterar status
        document.querySelectorAll('.js-alterar-status').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const vendaId = this.dataset.vendaId;
                const contatoId = this.dataset.contatoId;
                openAlterarStatusModal(vendaId, contatoId);
            });
        });

        // Click no card inteiro para abrir contrato
        document.querySelectorAll('.contract-card').forEach(card => {
            card.addEventListener('click', function (e) {
                // Não abrir se clicou em um botão de ação
                if (e.target.closest('.card-actions')) return;
                const vendaId = this.dataset.vendaId;
                window.location.href = `/back-office/abrir-contrato/${vendaId}`;
            });
        });
    }

    // Abrir modal de histórico
    async function openHistoricoModal(vendaId) {
        const modal = new bootstrap.Modal(document.getElementById('modalHistorico'));
        const timelineContainer = document.getElementById('timeline-container');

        // Mostrar loading
        timelineContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
            </div>
        `;

        modal.show();

        try {
            const response = await fetch(`/back-office/historico/${vendaId}`);
            if (!response.ok) throw new Error('Erro ao carregar histórico');

            const data = await response.json();

            // Atualizar informações do contrato
            document.getElementById('historico-nome-contrato').textContent = data.venda?.nome_contrato || '-';
            document.getElementById('historico-corretor').textContent = data.venda?.corretor || '-';
            document.getElementById('historico-valor').textContent = formatCurrency(data.venda?.valor_contrato).replace('R$', '').trim();

            // Renderizar timeline
            if (data.historico && data.historico.length > 0) {
                timelineContainer.innerHTML = data.historico.map((item, index) => renderTimelineItem(item, index === 0)).join('');
            } else {
                timelineContainer.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="ri-history-line" style="font-size: 3rem;"></i>
                        <p class="mt-2">Nenhum histórico encontrado</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Erro ao carregar histórico:', error);
            timelineContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="ri-error-warning-line me-2"></i>
                    Não foi possível carregar o histórico.
                </div>
            `;
        }
    }

    // Mapeamento de nome de status para classe CSS
    const STATUS_NAME_CSS_MAP = {
        'VENDA': 'status-venda',
        'ANALISE DE DOCUMENTOS': 'status-analise-docs',
        'PENDENCIA': 'status-pendencia',
        'REGULARIZADO': 'status-regularizado',
        'ANALISE OPERADORA': 'status-analise-operadora',
        'CONTR. GERADO - AGUARDANDO ASSINATURA': 'status-contrato-gerado',
        'AGUARD. ASSINATURA DA DS': 'status-aguard-assinatura',
        'BOLETO DISPONIVEL': 'status-boleto-disponivel',
        'IMPLANTADO': 'status-implantado',
        'ESTORNO': 'status-estorno',
        'DECLINADO': 'status-declinado'
    };

    function getStatusCssByName(statusName) {
        if (!statusName) return 'status-venda';
        const normalized = statusName.toUpperCase().trim();
        return STATUS_NAME_CSS_MAP[normalized] || 'status-venda';
    }

    // Renderizar item da timeline
    function renderTimelineItem(item, isFirst) {
        // O histórico retorna status_novo como string, não ID
        const statusCss = getStatusCssByName(item.status_novo);
        const tempoHtml = item.tempo_formatado && item.tempo_formatado !== 'Início'
            ? `<span class="timeline-duration"><i class="ri-time-line"></i> ${escapeHtml(item.tempo_formatado)}</span>`
            : '';

        const obsHtml = item.observacao
            ? `<div class="timeline-obs">"${escapeHtml(item.observacao)}"</div>`
            : '';

        const pendenciaHtml = item.motivo_pendencia
            ? `<div class="timeline-pendencia"><i class="ri-alert-line"></i> ${escapeHtml(item.motivo_pendencia)}</div>`
            : '';

        return `
            <div class="timeline-item ${statusCss}">
                <div class="timeline-content">
                    <div class="timeline-status">${escapeHtml(item.status_novo || '-')}</div>
                    <div class="timeline-date">
                        ${escapeHtml(item.data || item.data_formatada || '-')} -
                        <span class="timeline-user">${escapeHtml(item.usuario || 'Sistema')}</span>
                    </div>
                    ${tempoHtml}
                    ${obsHtml}
                    ${pendenciaHtml}
                </div>
            </div>
        `;
    }

    // Abrir modal de alterar status
    function openAlterarStatusModal(vendaId, contatoId) {
        const modal = new bootstrap.Modal(document.getElementById('modalAlterarStatus'));
        // O controller espera idSale (venda_id), não contato_id
        document.getElementById('modal-contato-id').value = vendaId;

        // Limpar campos
        document.getElementById('modal-tabulacao').value = '';
        hideAllConditionalFields();

        modal.show();
    }

    // Ocultar todos os campos condicionais
    function hideAllConditionalFields() {
        const fields = [
            'field-data-implantacao',
            'field-numero-proposta',
            'field-motivo-pendencia',
            'field-comprovante',
            'field-boleto'
        ];
        fields.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.style.display = 'none';
                const input = el.querySelector('input, textarea, select');
                if (input) {
                    input.required = false;
                    input.value = '';
                }
            }
        });
    }

    // Mostrar campo condicional
    function showConditionalField(fieldId, required = false) {
        const el = document.getElementById(fieldId);
        if (el) {
            el.style.display = 'block';
            const input = el.querySelector('input, textarea, select');
            if (input) input.required = required;
        }
    }

    // Mostrar erro
    function showError(message) {
        const stagesContainer = document.getElementById('pipeline-stages');
        if (stagesContainer) {
            stagesContainer.innerHTML = `
                <div class="alert alert-danger w-100 m-3">
                    <i class="ri-error-warning-line me-2"></i>
                    ${escapeHtml(message)}
                </div>
            `;
        }
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', function () {
        // Carregar dados iniciais
        loadPipelineData();

        // Event listeners para filtros
        document.getElementById('btn-filtrar')?.addEventListener('click', loadPipelineData);

        document.getElementById('btn-limpar')?.addEventListener('click', function () {
            document.getElementById('filter-vendedor').value = '';
            document.getElementById('filter-mes').value = '';
            document.getElementById('filter-ano').value = new Date().getFullYear();
            loadPipelineData();
        });

        // Listener para mudança de status no modal
        document.getElementById('modal-tabulacao')?.addEventListener('change', function () {
            hideAllConditionalFields();

            const statusId = parseInt(this.value);

            // Implantado (18)
            if (statusId === STATUS_IDS.IMPLANTADO) {
                showConditionalField('field-data-implantacao', true);
                showConditionalField('field-numero-proposta', true);
                showConditionalField('field-comprovante', true);
            }

            // Pendência, Estorno ou Declinado
            if ([STATUS_IDS.PENDENCIA, STATUS_IDS.ESTORNO, STATUS_IDS.DECLINADO].includes(statusId)) {
                showConditionalField('field-motivo-pendencia', true);
            }

            // Boleto Disponível (58)
            if (statusId === STATUS_IDS.BOLETO_DISPONIVEL) {
                showConditionalField('field-boleto', true);
            }
        });

        // Listener para submissão do formulário de alteração de status
        document.getElementById('formAlterarStatus')?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            try {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processando...';

                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.redirected) {
                    // Se redirecionou, significa que foi sucesso (comportamento padrão do Laravel)
                    window.location.href = response.url;
                    return;
                }

                const result = await response.json();

                if (result.success || response.ok) {
                    // Fechar modal
                    bootstrap.Modal.getInstance(document.getElementById('modalAlterarStatus'))?.hide();

                    // Mostrar sucesso
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: 'Status alterado com sucesso.',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Recarregar pipeline
                    loadPipelineData();
                } else {
                    throw new Error(result.message || 'Erro ao alterar status');
                }
            } catch (error) {
                console.error('Erro:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erro',
                    text: error.message || 'Não foi possível alterar o status.'
                });
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    });
})();
