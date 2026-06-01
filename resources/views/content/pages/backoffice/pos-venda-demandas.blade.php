@extends('layouts/layoutMaster')

@section('title', 'Demandas de Pós-Venda')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/pos-venda-demandas.scss', 'resources/assets/vendor/scss/pages/pos-venda.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/pos-venda-demandas.js', 'resources/assets/js/boas-vindas.js'])
@endsection

@section('content')
    <div class="pvd-page">
        {{-- Config para o JS (evita duplicar strings/labels) --}}
        <script>
            window.posVendaDemandas = {
                csrf: '{{ csrf_token() }}',
                isAdmin: @json($isAdmin),
                tipoLabels: @json($tipoLabels),
                urls: {
                    data: '{{ route('backoffice.posVendaDemandas.data') }}',
                    templates: '{{ route('backoffice.posVendaDemandas.templates') }}',
                    concluirTodas: '{{ url('back-office/pos-venda-demandas') }}',
                    storeDemanda: '{{ route('backoffice.storeDemandaContrato') }}',
                    demandaBase: '{{ url('back-office/demandas-contrato') }}',
                    buscarContratos: '{{ route('backoffice.posVendaDemandas.buscarContratos') }}'
                }
            };
        </script>

        {{-- Header --}}
        <div class="pvd-header">
            <div class="pvd-title-group">
                <div class="pvd-title-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"></path>
                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                    </svg>
                </div>
                <div class="pvd-title-text">
                    <h4>Demandas de Pós-Venda</h4>
                    <span class="pvd-subtitle">Check-list das tarefas geradas após cada implantação</span>
                </div>
            </div>
            <button type="button" class="pvd-btn pvd-btn-primary" id="pvd-abrir-chamado">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Abrir chamado
            </button>
        </div>

        {{-- KPIs --}}
        <div class="pvd-kpis">
            <div class="pvd-kpi pvd-kpi-primary">
                <span class="pvd-kpi-label">Contratos pendentes</span>
                <span class="pvd-kpi-value" id="kpi-contratos">0</span>
            </div>
            <div class="pvd-kpi pvd-kpi-warning">
                <span class="pvd-kpi-label">Demandas pendentes</span>
                <span class="pvd-kpi-value" id="kpi-demandas">0</span>
            </div>
            <div class="pvd-kpi pvd-kpi-success">
                <span class="pvd-kpi-label">Concluídas hoje</span>
                <span class="pvd-kpi-value" id="kpi-hoje">0</span>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="pvd-filters">
            <div class="pvd-filter-search">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" id="filtro-busca" placeholder="Buscar por contrato, proposta, CNPJ ou operadora...">
            </div>
            <select id="filtro-operadora" class="pvd-select">
                <option value="">Todas as operadoras</option>
                @foreach ($operadoras as $op)
                    <option value="{{ $op }}">{{ $op }}</option>
                @endforeach
            </select>
            @if ($isAdmin)
                <select id="filtro-backoffice" class="pvd-select">
                    <option value="">Todos os responsáveis</option>
                    <option value="sem">Sem responsável</option>
                    @foreach ($backoffices as $bo)
                        <option value="{{ $bo->id }}">{{ $bo->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Estados --}}
        <div id="pvd-loading" class="pvd-loading">Carregando demandas...</div>
        <div id="pvd-empty" class="pvd-empty" style="display:none;">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <p>Nenhuma demanda de pós-venda pendente. 🎉</p>
        </div>

        {{-- Grid de contratos (renderizado via JS) --}}
        <div id="pvd-grid" class="pvd-grid"></div>

        {{-- Modal: adicionar/editar demanda (dentro de .pvd-page para herdar tema) --}}
        <div class="pvd-modal-overlay" id="pvd-modal" style="display:none;">
            <div class="pvd-modal" role="dialog" aria-modal="true" aria-labelledby="pvd-modal-title">
                <div class="pvd-modal-header">
                    <div class="pvd-modal-header-content">
                        <div class="pvd-modal-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                        <div>
                            <h5 id="pvd-modal-title">Adicionar demanda</h5>
                            <span class="pvd-modal-subtitle">Crie uma tarefa de pós-venda para o contrato</span>
                        </div>
                    </div>
                    <button type="button" class="pvd-modal-close" id="pvd-modal-close" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form id="pvd-form">
                    <div class="pvd-modal-body">
                        <input type="hidden" id="pvd-venda-id">
                        <input type="hidden" id="pvd-demanda-id">
                        <div class="pvd-form-group">
                            <label for="pvd-tipo">Tipo</label>
                            <select id="pvd-tipo" class="pvd-select" required></select>
                        </div>
                        <div class="pvd-form-group">
                            <label for="pvd-titulo">Título</label>
                            <input type="text" id="pvd-titulo" class="pvd-input" maxlength="255" required>
                        </div>
                        <div class="pvd-form-group">
                            <label for="pvd-descricao">Descrição <span class="pvd-optional">(opcional)</span></label>
                            <textarea id="pvd-descricao" class="pvd-input" rows="3" maxlength="1000"></textarea>
                        </div>
                    </div>
                    <div class="pvd-modal-actions">
                        <button type="button" class="pvd-btn" id="pvd-cancel">Cancelar</button>
                        <button type="submit" class="pvd-btn pvd-btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 11l3 3L22 4"></path></svg>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal: abrir chamado avulso (busca contrato implantado + cria demanda) --}}
        <div class="pvd-modal-overlay" id="pvd-chamado-modal" style="display:none;">
            <div class="pvd-modal" role="dialog" aria-modal="true" aria-labelledby="pvd-chamado-title">
                <div class="pvd-modal-header">
                    <div class="pvd-modal-header-content">
                        <div class="pvd-modal-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h5 id="pvd-chamado-title">Abrir chamado de pós-venda</h5>
                            <span class="pvd-modal-subtitle">Para contrato já implantado (ex.: reembolso)</span>
                        </div>
                    </div>
                    <button type="button" class="pvd-modal-close" id="pvd-chamado-close" aria-label="Fechar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form id="pvd-chamado-form">
                    <div class="pvd-modal-body">
                        {{-- Busca de contrato --}}
                        <div class="pvd-form-group" id="pvd-chamado-busca-group">
                            <label for="pvd-chamado-busca">Buscar contrato implantado</label>
                            <div class="pvd-chamado-search">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <input type="text" id="pvd-chamado-busca" class="pvd-input" placeholder="Nome, proposta, CNPJ ou operadora..." autocomplete="off">
                            </div>
                            <div id="pvd-chamado-results" class="pvd-chamado-results"></div>
                        </div>

                        {{-- Contrato selecionado + formulário --}}
                        <div id="pvd-chamado-selected" style="display:none;">
                            <div class="pvd-chamado-chip">
                                <div class="pvd-chamado-chip-info">
                                    <span class="pvd-chamado-chip-nome" id="pvd-chamado-sel-nome"></span>
                                    <span class="pvd-chamado-chip-meta" id="pvd-chamado-sel-meta"></span>
                                </div>
                                <button type="button" class="pvd-chamado-trocar" id="pvd-chamado-trocar">Trocar</button>
                            </div>
                            <input type="hidden" id="pvd-chamado-venda-id">
                            <div class="pvd-form-group">
                                <label for="pvd-chamado-tipo">Tipo</label>
                                <select id="pvd-chamado-tipo" class="pvd-select"></select>
                            </div>
                            <div class="pvd-form-group">
                                <label for="pvd-chamado-titulo">Título</label>
                                <input type="text" id="pvd-chamado-titulo" class="pvd-input" maxlength="255">
                            </div>
                            <div class="pvd-form-group">
                                <label for="pvd-chamado-descricao">Descrição <span class="pvd-optional">(opcional)</span></label>
                                <textarea id="pvd-chamado-descricao" class="pvd-input" rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="pvd-modal-actions">
                        <button type="button" class="pvd-btn" id="pvd-chamado-cancel">Cancelar</button>
                        <button type="submit" class="pvd-btn pvd-btn-primary" id="pvd-chamado-save" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 11l3 3L22 4"></path></svg>
                            Abrir chamado
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modais de Boas-Vindas (WhatsApp) — partilhados com a tela de Pós-Venda --}}
        @include('content.pages.backoffice.partials.boas-vindas-modals')
    </div>
@endsection
