@extends('layouts/layoutMaster')

@section('title', 'Reciclagem de Leads')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/reciclagem-leads.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/reciclagem-leads.js'])
    <script>
        window.reciclagemConfig = {
            csrfToken: '{{ csrf_token() }}',
            urls: {
                elegiveis: '{{ route('comercial.reciclagem.elegiveis') }}',
                resumo: '{{ route('comercial.reciclagem.resumo') }}',
                enviar: '{{ route('comercial.reciclagem.enviar') }}',
                configGet: '{{ route('comercial.reciclagem.config.get') }}',
                configSave: '{{ route('comercial.reciclagem.config.save') }}',
                historico: '{{ route('comercial.reciclagem.historico') }}'
            }
        };
    </script>
@endsection

@section('content')
<div class="rl-page">

    {{-- Header --}}
    <div class="rl-header">
        <div class="rl-title-group">
            <div class="rl-title-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 4 23 10 17 10"/>
                    <polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            </div>
            <div>
                <h1 class="rl-title">Reciclagem de Leads</h1>
                <p class="rl-subtitle">Leads frios prontos para voltar à preditiva — com controle de enviado × trabalhado × aguardando</p>
            </div>
        </div>
        <button type="button" class="rl-btn rl-btn-ghost" id="btnAbrirConfig">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            Configurar reenvio
        </button>
    </div>

    {{-- KPI Cards --}}
    <div class="rl-kpi-grid">
        <div class="rl-kpi-card rl-kpi-warning rl-kpi-hero">
            <div class="rl-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <span class="rl-kpi-label">Elegíveis agora</span>
            <h2 class="rl-kpi-value" id="kpiElegiveis">{{ number_format($resumo['elegiveis_agora'], 0, ',', '.') }}</h2>
            <span class="rl-kpi-hint">+{{ $config->dias_sem_contato_reenvio }} dias sem contato</span>
        </div>

        <div class="rl-kpi-card rl-kpi-info">
            <div class="rl-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            </div>
            <span class="rl-kpi-label">Aguardando esfriar</span>
            <h2 class="rl-kpi-value" id="kpiAguardando">{{ number_format($resumo['aguardando'], 0, ',', '.') }}</h2>
            <span class="rl-kpi-hint">ainda dentro do prazo</span>
        </div>

        <div class="rl-kpi-card rl-kpi-primary">
            <div class="rl-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <span class="rl-kpi-label">Na vitrine</span>
            <h2 class="rl-kpi-value" id="kpiVitrine">{{ number_format($resumo['na_vitrine'], 0, ',', '.') }}</h2>
            <span class="rl-kpi-hint">na preditiva agora</span>
        </div>

        <div class="rl-kpi-card rl-kpi-success">
            <div class="rl-kpi-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </div>
            <span class="rl-kpi-label">Enviados ({{ $resumo['indicadores_janela_dias'] }}d)</span>
            <h2 class="rl-kpi-value" id="kpiEnviados">{{ number_format($resumo['enviados_na_janela'], 0, ',', '.') }}</h2>
            <span class="rl-kpi-hint">reciclados na janela</span>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-pills rl-tabs mb-3" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-elegiveis" type="button">Elegíveis para reenvio</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-historico" type="button">Histórico de envios</button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- Elegíveis --}}
        <div class="tab-pane fade show active" id="tab-elegiveis">
            <div class="rl-card">
                <div class="rl-toolbar">
                    <span class="rl-selected-count"><span id="selectedCount">0</span> selecionado(s)</span>
                    <div class="rl-toolbar-actions">
                        <button type="button" class="rl-btn rl-btn-primary" id="btnEnviarSelecionados" disabled>
                            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            Enviar selecionados
                        </button>
                        <button type="button" class="rl-btn rl-btn-soft" id="btnEnviarTodos">
                            Enviar todos os elegíveis
                        </button>
                    </div>
                </div>
                <div class="card-datatable table-responsive">
                    <table id="tabelaElegiveis" class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="checkAll" class="form-check-input"></th>
                                <th>Cliente</th>
                                <th>CPF</th>
                                <th>Telefone</th>
                                <th>Situação</th>
                                <th>Última atividade</th>
                                <th>Dias parado</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>

        {{-- Histórico --}}
        <div class="tab-pane fade" id="tab-historico">
            <div class="rl-card">
                <div class="card-datatable table-responsive">
                    <table id="tabelaHistorico" class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>CPF</th>
                                <th>Origem</th>
                                <th>Situação no envio</th>
                                <th>Dias inativo</th>
                                <th>Enviado em</th>
                                <th>Enviado por</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal: Configuração --}}
<div class="modal fade" id="modalConfigReciclagem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configuração do reenvio automático</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="cfgDias">Dias sem contato para reenviar</label>
                    <input type="number" min="1" max="3650" class="form-control" id="cfgDias">
                    <small class="text-muted">Um lead frio acima desse limite fica elegível à reciclagem.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cfgLimite">Limite por execução</label>
                    <input type="number" min="1" max="100000" class="form-control" id="cfgLimite">
                    <small class="text-muted">Teto de leads enviados por rodada da rotina automática.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cfgIndicadoresJanela">Janela dos indicadores</label>
                    <div class="input-group">
                        <input type="number" min="1" max="3650" class="form-control" id="cfgIndicadoresJanela">
                        <span class="input-group-text">dias</span>
                    </div>
                    <small class="text-muted">Período usado nos totais recentes de reciclagem e do reservatório.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alertas de inatividade no Kanban</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="number" min="1" max="3650" class="form-control" id="cfgKanbanAlerta" aria-label="Alerta inicial em dias">
                            <small class="text-muted">Alerta</small>
                        </div>
                        <div class="col-4">
                            <input type="number" min="2" max="3650" class="form-control" id="cfgKanbanUrgente" aria-label="Alerta urgente em dias">
                            <small class="text-muted">Urgente</small>
                        </div>
                        <div class="col-4">
                            <input type="number" min="3" max="3650" class="form-control" id="cfgKanbanCritica" aria-label="Alerta crítico em dias">
                            <small class="text-muted">Crítico</small>
                        </div>
                    </div>
                    <small class="text-muted">Os limites devem ser crescentes e valem somente para esta empresa.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cfgMascoteDias">Mascote: dias sem atividade</label>
                    <input type="number" min="1" max="3650" class="form-control" id="cfgMascoteDias">
                    <small class="text-muted">Tempo sem atividade para o mascote sugerir um contato ao vendedor.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cfgMascoteLimite">Mascote: máximo de sugestões</label>
                    <input type="number" min="1" max="100" class="form-control" id="cfgMascoteLimite">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="cfgLockHoras">Expiração da reserva na preditiva</label>
                    <div class="input-group">
                        <input type="number" min="1" max="168" class="form-control" id="cfgLockHoras">
                        <span class="input-group-text">horas</span>
                    </div>
                    <small class="text-muted">Após esse período sem ação, o lead reservado volta a ficar disponível.</small>
                </div>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" id="cfgAutomatico">
                    <label class="form-check-label" for="cfgAutomatico">Envio automático diário ativado</label>
                </div>
                <small class="text-muted">A rotina roda diariamente às 07:00 enviando os elegíveis (respeitando o limite).</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSalvarConfig">Salvar</button>
            </div>
        </div>
    </div>
</div>
@endsection
