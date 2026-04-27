@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Base Saúde')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-base-saude.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Aquisição</span>
                <h1 class="main-title">Base Saúde</h1>
                <p class="subtitle">Clientes do CRM Saúde ordenados por primeira implantação (mais antigos primeiro)</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.leads.kanban') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-kanban-view me-1"></i> Ver Pipeline
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon cadastrados">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"/>
                            <path d="M3 5v14a9 3 0 0 0 18 0V5"/>
                            <path d="M3 12a9 3 0 0 0 18 0"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Clientes para Ativação</h3>
                        <span class="table-subtitle">Agrupados por CPF/CNPJ · múltiplos contratos contam como um cliente</span>
                    </div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="lkb-bs-ocultar-leads" checked>
                        <label class="form-check-label small" for="lkb-bs-ocultar-leads">Ocultar já abordados</label>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" id="lkb-bs-ocultar-contratos">
                        <label class="form-check-label small" for="lkb-bs-ocultar-contratos">Ocultar já clientes</label>
                    </div>
                    <input type="search" id="lkb-bs-busca" class="form-control form-control-sm"
                           placeholder="Buscar CPF/Nome..." style="border-radius: 20px; min-width: 220px;">
                </div>
            </div>
            <div class="table-body">
                <table id="lkb-tabela-base-saude" class="custom-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>CPF/CNPJ</th>
                            <th>Nome</th>
                            <th class="text-center">Contratos</th>
                            <th>Operadoras</th>
                            <th>1ª Implantação</th>
                            <th>Última Implantação</th>
                            <th>Status</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="lkb-modal-pegar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pegar Lead para o Pipeline</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="lkb-bs-cpf-cnpj">
                <div class="mb-3">
                    <span class="kpi-label d-block mb-1">Cliente</span>
                    <p id="lkb-bs-cliente-label" class="mb-0" style="color: var(--dash-text-primary); font-weight: 600;">—</p>
                </div>
                <div class="mb-3">
                    <label class="kpi-label d-block mb-1">Produto de Interesse</label>
                    <select id="lkb-bs-produto" class="form-select">
                        <option value="">Selecione...</option>
                        @foreach($produtos as $p)
                            <option value="{{ $p->id }}">{{ $p->nome }} — {{ TipoBeneficio::label($p->tipo) }}</option>
                        @endforeach
                    </select>
                    @if($produtos->isEmpty())
                        <small class="text-danger">Nenhum produto cadastrado.</small>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button id="lkb-bs-confirmar" class="btn btn-primary">Criar Lead</button>
            </div>
        </div>
    </div>
</div>

<script>
    window.lkbBaseSaude = {
        datatableUrl: @json(route('lk-beneficios.base-saude.datatable')),
        pegarUrl: @json(route('lk-beneficios.base-saude.pegar')),
        kanbanUrl: @json(route('lk-beneficios.leads.kanban')),
        csrf: @json(csrf_token()),
    };
</script>
@endsection
