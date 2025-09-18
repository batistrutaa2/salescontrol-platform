@extends('layouts/layoutMaster')

@section('title', 'Pagamentos de Comissão')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/comissionamento-pagamentos-index.js')
@endsection

@section('content')

    <div id="pgmts-root" data-url="{{ route('comissionamento.pagamentos.data') }}"
        data-pdf-base="{{ route('comissionamento.pagamento.pdf', ['pagamento' => 'PAYMENT_ID']) }}"
        data-estornar-url="{{ route('comissionamento.pagamentos.estornar', ['id' => 'PAYMENT_ID']) }}"
        data-role="{{ strtoupper(auth()->user()->user_role_id) }}">
    </div>

    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Mês</label>
                    <input type="month" id="filtro-mes" class="form-control"
                        value="{{ \Carbon\Carbon::now('America/Sao_Paulo')->format('Y-m') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendedor</label>
                    <select id="filtro-vendedor" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($vendedores as $v)
                            <option value="{{ $v->id }}">{{ $v->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Criado por</label>
                    <select id="filtro-criado-por" class="form-select select2" data-placeholder="Todos">
                        <option value="">Todos</option>
                        @foreach ($criadores as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="btn-aplicar" class="btn btn-primary w-100">
                        <i class="ri-filter-3-line me-1"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-pagamentos" class="table table-striped table-hover w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Vendedor</th>
                            <th>Mês</th>
                            <th>Data Pagto</th>
                            <th class="text-end">% Com.</th>
                            <th class="text-end">% Imp.</th>
                            <th class="text-end">Bruto</th>
                            <th class="text-end">Imposto</th>
                            <th class="text-end">Líquido</th>
                            <th class="text-end">Salário</th>
                            <th class="text-end">Total a Receber</th>
                            <th>Criado por</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="fw-semibold">
                            <td colspan="6" class="text-end">Totais:</td>
                            <td id="ft-bruto" class="text-end">R$ 0,00</td>
                            <td id="ft-imp" class="text-end">R$ 0,00</td>
                            <td id="ft-liq" class="text-end">R$ 0,00</td>
                            <td id="ft-sal" class="text-end">R$ 0,00</td>
                            <td id="ft-total" class="text-end">R$ 0,00</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@endsection
