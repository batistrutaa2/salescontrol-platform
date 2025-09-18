@extends('layouts/layoutMaster')

@section('title', 'Editar Meta')

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
        <div class="card-header">
            <h5 class="mb-0">Editar Meta: {{ $meta->nome }}</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" value="{{ old('nome', $meta->nome) }}"
                            required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo de Cálculo</label>
                        <select name="tipo_calculo" class="form-select" required>
                            <option value="VALOR" @selected($meta->tipo_calculo === 'VALOR')>Valor (R$)</option>
                            <option value="QUANTIDADE" @selected($meta->tipo_calculo === 'QUANTIDADE')>Quantidade</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Escopo</label>
                        <select name="escopo" class="form-select" required>
                            <option value="INDIVIDUAL" @selected($meta->escopo === 'INDIVIDUAL')>Individual</option>
                            <option value="EQUIPE" @selected($meta->escopo === 'EQUIPE')>Equipe</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Período</label>
                        <select name="periodo" class="form-select" required>
                            <option value="MENSAL" @selected($meta->periodo === 'MENSAL')>Mensal</option>
                            <option value="SEMANAL" @selected($meta->periodo === 'SEMANAL')>Semanal</option>
                            <option value="PERSONALIZADO" @selected($meta->periodo === 'PERSONALIZADO')>Personalizado</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control"
                            value="{{ old('data_inicio', $meta->data_inicio) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control"
                            value="{{ old('data_fim', $meta->data_fim) }}">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Meta (Valor R$)</label>
                        <input type="number" step="0.01" name="valor_meta" class="form-control"
                            value="{{ old('valor_meta', $meta->valor_meta) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Meta (Quantidade)</label>
                        <input type="number" name="quantidade_meta" class="form-control"
                            value="{{ old('quantidade_meta', $meta->quantidade_meta) }}">
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="ativa" class="form-select">
                            <option value="1" @selected($meta->ativa)>Ativa</option>
                            <option value="0" @selected(!$meta->ativa)>Inativa</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('ranking.index') }}" class="btn btn-outline-secondary">Voltar</a>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
@endsection
