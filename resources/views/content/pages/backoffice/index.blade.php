@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Fila de Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/scss/pages/backoffice-kanban.scss', 'resources/assets/vendor/scss/pages/busca-contrato.scss', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/backoffice.js', 'resources/assets/js/busca-contrato.js'])
@endsection

@section('content')
    @if (session('status') == 'success')
        <div class="alert alert-solid-success d-flex align-items-center mb-3" role="alert">
            <span class="alert-icon rounded">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert alert-danger mb-3">
            {{ session('message') }}
        </div>
    @endif

    <div class="kanban-page">
        {{-- Pesquisar contrato: abre qualquer contrato da base inteira (não filtra só o kanban) --}}
        <div class="bc-search" id="bc-search">
            <div class="bc-search-box">
                <svg xmlns="http://www.w3.org/2000/svg" class="bc-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" id="bc-search-input" class="bc-search-input" autocomplete="off"
                    placeholder="Pesquisar contrato por nome, CNPJ/CPF ou nº da proposta…">
                <span class="bc-search-tag">Abre o contrato completo</span>
            </div>
            <div class="bc-results" id="bc-results" hidden></div>
        </div>

        <!-- Header -->
        <div class="kanban-header">
            <div class="header-title">
                <div class="title-icon">
                    <i class="ri-kanban-view"></i>
                </div>
                <div>
                    <h4>Fila de Contratos</h4>
                    <p class="subtitle">Acompanhe o progresso dos contratos em tempo real</p>
                </div>
            </div>

            <div class="header-actions">
                <button type="button" id="btn-abrir-estornos" class="btn-estornos">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 14 4 9 9 4"/>
                        <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                    </svg>
                    <span>Estornos</span>
                    <span class="btn-estornos-count d-none" id="estornos-count">0</span>
                </button>

                <div class="kanban-filters">
                    @if ($isBackoffice ?? false)
                    <div class="filter-item">
                        <label>Custodia</label>
                        <select id="filter-custodia" class="form-select form-select-sm">
                            <option value="meus">Meus Contratos</option>
                            <option value="todos">Todos</option>
                        </select>
                    </div>
                    @endif

                    <div class="filter-item">
                        <label>Vendedor</label>
                        <select id="filter-vendedor" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

                    @if (!($isBackoffice ?? false))
                    <div class="filter-item">
                        <label>Responsável</label>
                        <select id="filter-backoffice" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="sem">Sem responsável</option>
                        </select>
                    </div>
                    @endif

                    <div class="filter-item">
                        <label>Mês</label>
                        <select id="filter-mes" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                    </div>

                    <div class="filter-item">
                        <label>Ano</label>
                        <select id="filter-ano" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            @for ($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="filter-item filter-search">
                        <label>Buscar</label>
                        <div class="position-relative">
                            <i class="ri-search-line search-icon"></i>
                            <input type="text" id="filter-busca" class="form-control form-control-sm"
                                placeholder="Nome, proposta...">
                        </div>
                    </div>

                    <div class="filter-item" style="align-self: flex-end;">
                        <button type="button" id="btn-clear-filters" class="btn btn-clear-filters">
                            <i class="ri-refresh-line me-1"></i> Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban -->
        <div class="kb-tab-pane active" id="pane-kanban">
            <!-- Loading State -->
            <div class="kanban-loading" id="kanban-loading">
                <div class="spinner-wrapper">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p>Carregando contratos...</p>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="kanban-board" id="kanban-board" style="display: none;">
                <!-- Columns will be rendered by JavaScript -->
            </div>
        </div>

    </div>

    <!-- Modal Alterar Status -->
    <div class="modal fade" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" id="modalcommentsDialog">
            <div class="modal-content kb-modal">
                <div class="kb-modal-header kb-modal-header-primary">
                    <div class="kb-modal-header-content">
                        <div class="kb-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="17 1 21 5 17 9"/>
                                <path d="M3 11V9a4 4 0 0 1 4-4h14"/>
                                <polyline points="7 23 3 19 7 15"/>
                                <path d="M21 13v2a4 4 0 0 1-4 4H3"/>
                            </svg>
                        </div>
                        <div class="kb-modal-title-group">
                            <h5 class="kb-modal-title">Atualizar Status</h5>
                            <span class="kb-modal-subtitle">Mova o contrato para um novo estágio</span>
                        </div>
                    </div>
                    <button type="button" class="kb-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="kb-modal-body">
                    <form id="transferLead" action="{{ route('backoffice.alterStatusContract') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="idSale" name="idSale" value="">

                        <div class="kb-form-group">
                            <label for="label" class="kb-form-label">Novo Status</label>
                            <select class="kb-form-select" id="label" name="tabulacao_id" required>
                                <option value="">Selecione o Status</option>
                                @foreach ($tabulacoes as $tabulation)
                                    <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- IMPLANTADO: dados da implantação e demandas de pós-venda lado a lado --}}
                        <div id="implantado-grid" class="kb-implantado-grid" style="display: none;">
                            {{-- Coluna esquerda: dados da implantação --}}
                            <div class="kb-implantado-col">
                                <span class="kb-implantado-col-label">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                                    Dados da Implantação
                                </span>

                                <div id="proof-group-data-implantacao" class="kb-form-group" style="display: none;">
                                    <label for="data_implantacao" class="kb-form-label">Data de Implantação</label>
                                    <input type="date" id="data_implantacao" name="data_implantacao" class="kb-form-input">
                                </div>

                                <div id="proof-group-numero_proposta" class="kb-form-group" style="display: none;">
                                    <label for="numero_proposta" class="kb-form-label">Número da Proposta</label>
                                    <input type="text" id="numero_proposta" name="numero_proposta" class="kb-form-input" placeholder="Ex: 123456">
                                </div>

                                <div id="proof-group" class="kb-form-group" style="display: none;">
                                    <label for="comprovante" class="kb-form-label">Comprovante de Pagamento</label>
                                    <div class="kb-file-upload">
                                        <input type="file" id="comprovante" name="comprovante" class="kb-file-input"
                                            accept="image/*,application/pdf">
                                        <div class="kb-file-upload-content">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                                <polyline points="17 8 12 3 7 8"/>
                                                <line x1="12" y1="3" x2="12" y2="15"/>
                                            </svg>
                                            <span>Clique para enviar ou arraste o arquivo</span>
                                            <small>PDF, PNG, JPG até 10MB</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Acesso da Empresa (opcional) --}}
                                <div id="proof-group-acesso-empresa" class="kb-form-group" style="display: none;">
                                    <div class="kb-card-section kb-card-section-success">
                                        <div class="kb-card-section-header">
                                            <div class="kb-card-section-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                                                </svg>
                                            </div>
                                            <div class="kb-card-section-title">Acesso Empresa</div>
                                            <span class="kb-badge kb-badge-success">Opcional</span>
                                        </div>
                                        <div class="kb-card-section-body">
                                            <div class="kb-form-row">
                                                <div class="kb-form-col">
                                                    <label for="acesso_email" class="kb-form-label-sm">E-mail</label>
                                                    <input type="email" name="acesso_email" id="acesso_email"
                                                        class="kb-form-input" placeholder="email@operadora.com.br">
                                                </div>
                                                <div class="kb-form-col">
                                                    <label for="acesso_senha" class="kb-form-label-sm">Senha</label>
                                                    <div class="kb-input-group">
                                                        <input type="password" name="acesso_senha" id="acesso_senha"
                                                            class="kb-form-input" placeholder="••••••••">
                                                        <button type="button" class="kb-input-addon" id="toggle-acesso-senha">
                                                            <i class="ri-eye-line" id="icon-eye"></i>
                                                            <i class="ri-eye-off-line d-none" id="icon-eye-off"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="kb-form-group-sm">
                                                <label for="acesso_cpf" class="kb-form-label-sm">CPF (opcional)</label>
                                                <input type="text" name="acesso_cpf" id="acesso_cpf"
                                                    class="kb-form-input acesso-mask-cpf" placeholder="000.000.000-00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Coluna direita: demandas de pós-venda --}}
                            <div class="kb-implantado-col kb-implantado-col-right">
                                <div id="proof-group-demandas" class="kb-form-group">
                                    <div class="kb-card-section kb-card-section-info">
                                        <div class="kb-card-section-header">
                                            <div class="kb-card-section-icon">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M9 11l3 3L22 4"/>
                                                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                                </svg>
                                            </div>
                                            <div class="kb-card-section-title">Demandas de Pós-Venda</div>
                                            <span class="kb-badge kb-badge-info" id="kb-demandas-counter">0</span>
                                        </div>
                                        <div class="kb-card-section-body">
                                            <div class="kb-demandas-toolbar">
                                                <small class="text-muted">Tarefas geradas para o pós-venda deste contrato.</small>
                                                <button type="button" class="kb-demandas-selectall" id="kb-demandas-toggle-all">Desmarcar todas</button>
                                            </div>
                                            {{-- Sinaliza ao back-end que a seleção veio deste modal (mesmo que vazia). --}}
                                            <input type="hidden" name="demandas_enviadas" value="1">
                                            <div class="kb-demandas-checklist" id="kb-demandas-checklist">
                                                @foreach ($posVendaTemplates as $template)
                                                    <label class="kb-demanda-check">
                                                        <input type="checkbox" name="demandas[]" value="{{ $template->id }}"
                                                            {{ $template->gerar_automatico ? 'checked' : '' }}>
                                                        <span>{{ $template->titulo }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Grupos de outros status (largura total) --}}
                        <div id="proof-group-data-pendencia" class="kb-form-group" style="display: none;">
                            <label for="data_pendencia" class="kb-form-label" id="label-motivo-pendencia">Motivo da Pendência</label>
                            <textarea id="data_pendencia" name="motivo_pendencia" class="kb-form-textarea"
                                placeholder="Descreva o motivo..."></textarea>
                            <small id="hint-motivo-estorno" class="text-muted d-none">
                                O vendedor receberá esta venda na aba "Meus Estornos" com este motivo. Mínimo 10 caracteres.
                            </small>
                        </div>

                        <div id="proof-group-observacao-estorno" class="kb-form-group" style="display: none;">
                            <label for="observacao_estorno" class="kb-form-label">Observação interna (opcional)</label>
                            <textarea id="observacao_estorno" name="observacao_estorno" class="kb-form-textarea"
                                placeholder="Notas internas — não vão para o vendedor."></textarea>
                        </div>

                        <div id="proof-group-boleto-disponivel" class="kb-form-group" style="display: none;">
                            <label for="boleto_disponivel" class="kb-form-label">Boleto Disponível</label>
                            <div class="kb-file-upload">
                                <input type="file" id="boleto_disponivel" name="boleto_disponivel" class="kb-file-input"
                                    accept="image/*,application/pdf">
                                <div class="kb-file-upload-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                        <polyline points="17 8 12 3 7 8"/>
                                        <line x1="12" y1="3" x2="12" y2="15"/>
                                    </svg>
                                    <span>Clique para enviar ou arraste o arquivo</span>
                                    <small>PDF, PNG, JPG até 10MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="kb-modal-actions">
                            <button type="button" class="kb-btn kb-btn-ghost" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="kb-btn kb-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                Confirmar Alteração
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Estornos: propostas devolvidas ao vendedor, do estorno mais recente para o mais antigo --}}
    <div class="modal fade" id="modalEstornos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content kb-modal est-modal">
                <div class="kb-modal-header kb-modal-header-estorno">
                    <div class="kb-modal-header-content">
                        <div class="kb-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 14 4 9 9 4"/>
                                <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                            </svg>
                        </div>
                        <div class="kb-modal-title-group">
                            <h5 class="kb-modal-title">Estornos</h5>
                            <span class="kb-modal-subtitle">Propostas que estão com o vendedor aguardando correção</span>
                        </div>
                    </div>
                    <button type="button" class="kb-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>

                <div class="est-toolbar">
                    <div class="est-search">
                        <svg xmlns="http://www.w3.org/2000/svg" class="est-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        <input type="text" id="estornos-busca" class="est-search-input" autocomplete="off"
                            aria-label="Buscar proposta estornada"
                            placeholder="Buscar cliente, CPF/CNPJ, proposta ou vendedor…">
                    </div>
                    <span class="est-resumo" id="estornos-resumo" aria-live="polite">Carregando…</span>
                </div>

                <div class="kb-modal-body est-body" id="estornos-lista">
                    <div class="est-empty">
                        <div class="est-empty-title">Carregando estornos…</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Histórico -->
    <div class="modal fade" id="modalHistorico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content kb-modal">
                <div class="kb-modal-header kb-modal-header-info">
                    <div class="kb-modal-header-content">
                        <div class="kb-modal-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div class="kb-modal-title-group">
                            <h5 class="kb-modal-title">Histórico do Contrato</h5>
                            <span class="kb-modal-subtitle">Acompanhe todas as movimentações</span>
                        </div>
                    </div>
                    <button type="button" class="kb-modal-close" data-bs-dismiss="modal" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                </div>
                <div class="kb-modal-body kb-modal-body-flush" id="historico-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
@endsection
