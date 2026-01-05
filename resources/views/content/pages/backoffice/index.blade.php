@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Fila de Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/scss/pages/backoffice-kanban.scss', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js'])
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/backoffice.js'])
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
                <div class="kanban-filters">
                    <div class="filter-item">
                        <label>Vendedor</label>
                        <select id="filter-vendedor" class="form-select form-select-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>

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

    <!-- Modal Alterar Status -->
    <div class="modal fade" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #696cff 0%, #8592ff 100%); border: none;">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-white">
                        <div class="rounded p-1" style="background: rgba(255,255,255,0.2);">
                            <i class="ri-file-transfer-line" style="font-size: 1.1rem;"></i>
                        </div>
                        Atualizar Status
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <form id="transferLead" class="row" action="{{ route('backoffice.alterStatusContract') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="idSale" name="idSale" value="">
                        <div class="col">
                            <div class="mb-3">
                                <label for="label" class="form-label fw-semibold">Novo Status</label>
                                <select class="form-select" id="label" name="tabulacao_id" required>
                                    <option value="">Selecione o Status</option>
                                    @foreach ($tabulacoes as $tabulation)
                                        <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="proof-group-data-implantacao" class="mb-3" style="display: none;">
                                <label for="data_implantacao" class="form-label fw-semibold">Data de Implantação</label>
                                <input type="date" id="data_implantacao" name="data_implantacao" class="form-control">
                            </div>

                            <div id="proof-group-numero_proposta" class="mb-3" style="display: none;">
                                <label for="numero_proposta" class="form-label fw-semibold">Número da Proposta</label>
                                <input type="text" id="numero_proposta" name="numero_proposta" class="form-control">
                            </div>

                            <div id="proof-group-data-pendencia" class="mb-3" style="display: none;">
                                <label for="data_pendencia" class="form-label fw-semibold">Motivo da Pendência</label>
                                <textarea id="data_pendencia" name="motivo_pendencia" class="form-control" rows="4"
                                    placeholder="Descreva o motivo da pendência..." style="min-height: 120px; resize: vertical;"></textarea>
                            </div>

                            <div id="proof-group" class="mb-3" style="display: none;">
                                <label for="comprovante" class="form-label fw-semibold">Comprovante de Pagamento</label>
                                <input type="file" id="comprovante" name="comprovante" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>

                            <div id="proof-group-boleto-disponivel" class="mb-3" style="display: none;">
                                <label for="boleto_disponivel" class="form-label fw-semibold">Boleto Disponível</label>
                                <input type="file" id="boleto_disponivel" name="boleto_disponivel" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>

                            {{-- Acesso da Empresa (opcional - apenas para IMPLANTADO) --}}
                            <div id="proof-group-acesso-empresa" class="mb-3" style="display: none;">
                                <div class="border rounded-3 overflow-hidden" style="border-color: rgba(113, 221, 55, 0.3) !important;">
                                    <div class="px-3 py-2 d-flex align-items-center gap-2" style="background: linear-gradient(135deg, rgba(113, 221, 55, 0.08) 0%, rgba(113, 221, 55, 0.02) 100%);">
                                        <div class="rounded p-1" style="background: linear-gradient(135deg, #71dd37 0%, #5cb82e 100%);">
                                            <i class="ri-key-2-line text-white" style="font-size: 1rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-semibold" style="font-size: 0.9rem;">Acesso Empresa</span>
                                        </div>
                                        <span class="badge rounded-pill" style="background: rgba(113, 221, 55, 0.15); color: #5cb82e; font-size: 0.7rem;">Opcional</span>
                                    </div>
                                    <div class="p-3" style="background: rgba(113, 221, 55, 0.02);">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="acesso_email" class="form-label small fw-semibold text-muted mb-1">E-MAIL</label>
                                                <input type="email" name="acesso_email" id="acesso_email"
                                                    class="form-control form-control-sm" placeholder="email@operadora.com.br">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="acesso_senha" class="form-label small fw-semibold text-muted mb-1">SENHA</label>
                                                <div class="input-group input-group-sm">
                                                    <input type="password" name="acesso_senha" id="acesso_senha"
                                                        class="form-control" placeholder="••••••••">
                                                    <button type="button" class="btn btn-outline-secondary" id="toggle-acesso-senha" style="border-color: #d9dee3;">
                                                        <i class="ri-eye-line" id="icon-eye" style="font-size: 0.875rem;"></i>
                                                        <i class="ri-eye-off-line d-none" id="icon-eye-off" style="font-size: 0.875rem;"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <label for="acesso_cpf" class="form-label small fw-semibold text-muted mb-1">CPF <span class="fw-normal">(opcional)</span></label>
                                                <input type="text" name="acesso_cpf" id="acesso_cpf"
                                                    class="form-control form-control-sm acesso-mask-cpf" placeholder="000.000.000-00">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-check-line me-1"></i> Confirmar Alteração
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Histórico -->
    <div class="modal fade" id="modalHistorico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #03c3ec 0%, #06b6d4 100%); border: none;">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-white">
                        <div class="rounded p-1" style="background: rgba(255,255,255,0.2);">
                            <i class="ri-history-line" style="font-size: 1.1rem;"></i>
                        </div>
                        Histórico do Contrato
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" id="historico-content">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>
@endsection
