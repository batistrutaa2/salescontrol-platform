@extends('layouts/layoutMaster')

@section('title', 'Fila de Demandas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/demandas.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/demandas.js')
@endsection

@section('content')
<div class="demandas-wrapper">
    {{-- Page Header --}}
    <div class="dm-page-header">
        <div class="dm-title-group">
            <div class="dm-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </div>
            <div>
                <h1 class="dm-title">Fila de Demandas</h1>
                <p class="dm-subtitle">Gerencie suas tarefas e acompanhe o progresso</p>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
        @if ($canManageDemandSettings)
        <button id="btn-config-demandas" type="button" class="dm-btn dm-btn-outline" data-bs-toggle="modal" data-bs-target="#modal-config-demandas">
            <i class="ri-settings-3-line" aria-hidden="true"></i>
            Configurar histórico
        </button>
        @endif
        <button id="btn-nova" type="button" class="dm-btn dm-btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Nova Demanda
        </button>
        </div>
    </div>

    {{-- Abas: demandas internas do setor x solicitações dos vendedores --}}
    <div class="dm-tabs">
        <button type="button" class="dm-tab is-active" data-origem="INTERNA">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            Internas
        </button>
        <button type="button" class="dm-tab" data-origem="VENDEDOR">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
            </svg>
            Demanda vendedores
        </button>
    </div>

    {{-- Stats Row --}}
    <div class="dm-stats-row">
        <div class="dm-stat-card">
            <div class="dm-stat-icon stat-aberta">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
            <div class="dm-stat-content">
                <div class="dm-stat-value" id="stat-aberta">0</div>
                <div class="dm-stat-label">Abertas</div>
            </div>
        </div>
        <div class="dm-stat-card">
            <div class="dm-stat-icon stat-andamento">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
            <div class="dm-stat-content">
                <div class="dm-stat-value" id="stat-andamento">0</div>
                <div class="dm-stat-label">Em Andamento</div>
            </div>
        </div>
        <div class="dm-stat-card">
            <div class="dm-stat-icon stat-concluida">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
            </div>
            <div class="dm-stat-content">
                <div class="dm-stat-value" id="stat-concluida">0</div>
                <div class="dm-stat-label">Concluídas</div>
            </div>
        </div>
        <div class="dm-stat-card">
            <div class="dm-stat-icon stat-cancelada">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <div class="dm-stat-content">
                <div class="dm-stat-value" id="stat-cancelada">0</div>
                <div class="dm-stat-label">Canceladas</div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="dm-filter-bar">
        <div class="dm-filter-group">
            <span class="dm-filter-label">Status</span>
            <select id="filtro-status" class="dm-filter-select">
                <option value="TODOS">Todos</option>
                <option value="ABERTA">Aberta</option>
                <option value="EM_ANDAMENTO">Em Andamento</option>
                <option value="CONCLUIDA">Concluída</option>
                <option value="CANCELADA">Cancelada</option>
            </select>
        </div>

        <div class="dm-filter-group">
            <span class="dm-filter-label">Prioridade</span>
            <select id="filtro-prioridade" class="dm-filter-select">
                <option value="TODAS">Todas</option>
                <option value="ALTA">Alta</option>
                <option value="MEDIA">Média</option>
                <option value="BAIXA">Baixa</option>
            </select>
        </div>

        <div class="dm-filter-group">
            <span class="dm-filter-label">Responsável</span>
            <select id="filtro-responsavel" class="dm-filter-select" style="min-width: 200px;">
                <option value="">Todos</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}">{{ strtoupper($u->name) }}</option>
                @endforeach
            </select>
        </div>

        <div class="dm-filter-group" id="date-filter-wrapper">
            <span class="dm-filter-label">Período</span>
            <select id="filtro-data-field" class="dm-filter-select">
                <option value="limite">Data Limite</option>
                <option value="criacao">Data Criação</option>
            </select>
            <input type="date" id="filtro-data-inicio" class="dm-filter-input" placeholder="Início">
            <input type="date" id="filtro-data-fim" class="dm-filter-input" placeholder="Fim">
            <button id="btn-clear-dates" type="button" class="dm-btn dm-btn-icon dm-btn-outline" title="Limpar datas">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Kanban Board --}}
    <div class="dm-kanban-board">
        @php
            $cols = [
                'ABERTA' => ['label' => 'Aberta', 'status_class' => 'status-aberta'],
                'EM_ANDAMENTO' => ['label' => 'Em Andamento', 'status_class' => 'status-em_andamento'],
                'CONCLUIDA' => ['label' => 'Concluída', 'status_class' => 'status-concluida'],
                'CANCELADA' => ['label' => 'Cancelada', 'status_class' => 'status-cancelada'],
            ];
        @endphp

        @foreach ($cols as $status => $config)
            <div class="dm-kanban-column">
                <div class="dm-column-header">
                    <div class="dm-column-title">
                        <span class="dm-status-dot {{ $config['status_class'] }}"></span>
                        <h3>{{ $config['label'] }}</h3>
                    </div>
                    <span class="dm-column-count" id="count-{{ $status }}">0</span>
                </div>
                <div class="dm-column-body dm-dropzone" data-status="{{ $status }}">
                    {{-- Cards renderizados via JS --}}
                </div>
            </div>
        @endforeach
    </div>
</div>

@if ($canManageDemandSettings)
<div class="modal fade dm-modal" id="modal-config-demandas" tabindex="-1" aria-labelledby="modal-config-demandas-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="form-config-demandas" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="modal-config-demandas-title">Histórico de demandas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <label class="dm-label" for="demandas_concluidas_janela_dias">Exibir demandas concluídas por</label>
                <div class="input-group">
                    <input type="number" class="form-control" id="demandas_concluidas_janela_dias"
                        name="demandas_concluidas_janela_dias" min="1" max="3650" required
                        value="{{ $demandasConcluidasJanelaDias }}">
                    <span class="input-group-text">dias</span>
                </div>
                <div class="form-text">A regra vale somente para {{ auth()->user()->isPlatformAdmin() ? 'a empresa ativa' : 'sua empresa' }}.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="dm-btn dm-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="dm-btn dm-btn-primary">Salvar configuração</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Modal de Cadastro/Edição --}}
<div class="modal fade dm-modal" id="modal-demanda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="form-demanda" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nova Demanda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="demanda_id">

                <div class="dm-form-group">
                    <label class="dm-label">Título *</label>
                    <input type="text" class="dm-input" id="titulo" name="titulo" required placeholder="Digite o título da demanda">
                </div>

                <div class="dm-form-group">
                    <label class="dm-label">Descrição</label>
                    <textarea class="dm-textarea" id="descricao" name="descricao" rows="6" placeholder="Descreva a demanda (opcional)"></textarea>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="dm-form-group">
                            <label class="dm-label">Prioridade *</label>
                            <select class="dm-select" id="prioridade" name="prioridade" required>
                                <option value="ALTA">Alta</option>
                                <option value="MEDIA" selected>Média</option>
                                <option value="BAIXA">Baixa</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="dm-form-group">
                            <label class="dm-label">Responsável</label>
                            <select class="dm-select" id="assigned_to" name="assigned_to">
                                <option value="">-- Sem responsável --</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dm-form-group">
                            <label class="dm-label">Data Limite</label>
                            <input type="date" class="dm-input" id="data_limite" name="data_limite">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="dm-btn dm-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="dm-btn dm-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Salvar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
