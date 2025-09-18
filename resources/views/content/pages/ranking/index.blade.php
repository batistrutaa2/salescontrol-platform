@extends('layouts/layoutMaster')

@section('title', 'Ranking de Vendas')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss', 'resources/assets/vendor/libs/swiper/swiper.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/cards-statistics.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/swiper/swiper.js', 'resources/assets/vendor/libs/chartjs/chartjs.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/rankingVendas.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Metas Cadastradas</h5>
            <a href="#" class="btn btn-primary">Nova Meta</a>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="metas-table" class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo Cálculo</th>
                            <th>Escopo</th>
                            <th>Período</th>
                            <th>Meta</th>
                            <th>Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($metas as $meta)
                            <tr>
                                <td>{{ $meta->nome }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $meta->tipo_calculo === 'VALOR' ? 'primary' : 'info' }}">
                                        {{ $meta->tipo_calculo }}
                                    </span>
                                </td>
                                <td>{{ $meta->escopo }}</td>
                                <td>{{ $meta->periodo }}</td>
                                <td>
                                    @if ($meta->tipo_calculo === 'VALOR')
                                        R$ {{ number_format($meta->valor_meta, 2, ',', '.') }}
                                    @else
                                        {{ $meta->quantidade_meta }} unidades
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-label-{{ $meta->ativa ? 'success' : 'secondary' }}">
                                        {{ $meta->ativa ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('ranking.edit', [$meta->id]) }}"
                                        class="btn btn-sm btn-outline-primary me-1">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <form method="POST" action="" class="d-inline-block"
                                        onsubmit="return confirm('Tem certeza que deseja excluir esta meta?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
