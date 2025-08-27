@extends('layouts/layoutMaster')

@section('title', 'Faturamento de Comissões')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-faturamento.js')
@endsection

@section('content')

    <div id="comissionamento-root" data-url="{{ route('comissionamento.faturamento') }}"
        data-empresa-id="{{ auth()->user()->empresa_id }}">
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filtro-mes" class="form-label">Mês de referência</label>
                    <input type="month" id="filtro-mes" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="filtro-vendedor" class="form-label">Vendedor (opcional)</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                    </select>
                </div>

                {{-- NOVO: filtro por grade --}}
                <div class="col-md-3">
                    <label for="filtro-grade" class="form-label">Grade</label>
                    <select id="filtro-grade" class="form-select select2" data-placeholder="Todas">
                        <option value="">Todas</option>
                        <option value="junior">Junior</option>
                        <option value="senior">Senior</option>
                        <option value="admin">Admin</option>
                        <option value="comercial">Comercial</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <button id="btn-aplicar-filtro" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i> Aplicar
                    </button>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col text-end">
                    <div class="text-muted small">
                        Somente contratos <strong>sem comissão paga</strong> no período selecionado.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="resumo-geral" class="row g-4 mb-6">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Vendedores</div>
                            <h4 class="mb-0" id="kpi-vendedores">0</h4>
                        </div>
                        <i class="ri-group-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Contratos pendentes</div>
                            <h4 class="mb-0" id="kpi-contratos">0</h4>
                        </div>
                        <i class="ri-file-list-3-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Total contratos (R$)</div>
                            <h4 class="mb-0" id="kpi-total-contratos">0,00</h4>
                        </div>
                        <i class="ri-money-dollar-circle-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted">Total comissão (R$)</div>
                            <h4 class="mb-0" id="kpi-total-comissao">0,00</h4>
                        </div>
                        <i class="ri-bar-chart-2-fill ri-28px"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="lista-vendedores" class="row g-4">
        <!-- Render via JS: JUNIOR/SENIOR -->
    </div>

    <!-- ADMIN -->
    <div class="card mt-6" id="grade-admin-card" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-shield-user-fill me-2"></i> Grade ADMIN</h5>
            <div class="text-muted small" id="grade-admin-resumo"></div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="tabela-grade-admin">
                    <thead>
                        <tr>
                            <th>Admin</th>
                            <th class="text-end">% Base</th>
                            <th class="text-end">Comissão bruta</th>
                            <th class="text-end">Imposto (%)</th>
                            <th class="text-end">Comissão líquida</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Total líquido</th>
                            <th class="text-end" id="admin-total-liquido">R$ 0,00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- COMERCIAL -->
    <div class="card mt-4" id="grade-comercial-card" style="display:none;">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="ri-briefcase-4-fill me-2"></i> Grade COMERCIAL</h5>
            <div class="text-muted small" id="grade-comercial-resumo"></div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3" id="grade-comercial-kpis"></div>

            <div class="table-responsive">
                <table class="table align-middle" id="tabela-grade-comercial">
                    <thead>
                        <tr>
                            <th>Supervisor</th>
                            <th class="text-end">Quota</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end">Total distribuído</th>
                            <th class="text-end" id="comercial-total-distribuido">R$ 0,00</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection
