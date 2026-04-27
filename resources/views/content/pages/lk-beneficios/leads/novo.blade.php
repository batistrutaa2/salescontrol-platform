@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Novo Lead')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-lead-novo.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Pipeline</span>
                <h1 class="main-title">Novo Lead</h1>
                <p class="subtitle">Cadastre manualmente um cliente com produto de interesse</p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.leads.kanban') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Voltar ao Pipeline
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card chart-large" style="max-width: 820px; margin: 0 auto;">
        <div class="chart-header">
            <div class="chart-title-group">
                <h3 class="chart-title">Dados do Lead</h3>
                <span class="chart-subtitle">Opcionalmente enriqueça via Lemit antes de salvar</span>
            </div>
        </div>
        <div class="chart-body">
            <form id="lkb-form-lead-novo">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Tipo</label>
                        <select name="cliente_tipo" id="lkb-cliente-tipo" class="form-select" required>
                            <option value="PF">Pessoa Física (CPF)</option>
                            <option value="PJ">Pessoa Jurídica (CNPJ)</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="kpi-label d-block mb-1">CPF/CNPJ</label>
                        <input type="text" name="cpf_cnpj" id="lkb-cpf-cnpj" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="kpi-label d-block mb-1">&nbsp;</label>
                        <button type="button" id="lkb-btn-lemit" class="btn btn-outline-primary w-100">
                            <i class="ri-search-line me-1"></i> Lemit
                        </button>
                    </div>

                    <div class="col-md-8">
                        <label class="kpi-label d-block mb-1">Nome</label>
                        <input type="text" name="nome" id="lkb-nome" class="form-control" required maxlength="150">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Telefone</label>
                        <input type="text" name="telefone" id="lkb-telefone" class="form-control">
                    </div>

                    <div class="col-md-8">
                        <label class="kpi-label d-block mb-1">E-mail</label>
                        <input type="email" name="email" id="lkb-email" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Produto de Interesse</label>
                        <select name="produto_interesse_id" id="lkb-produto" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach($produtos as $p)
                                <option value="{{ $p->id }}" data-tipo="{{ $p->tipo }}">
                                    {{ $p->nome }} — {{ TipoBeneficio::label($p->tipo) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="kpi-label d-block mb-1">Observações</label>
                        <textarea name="observacoes" id="lkb-observacoes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('lk-beneficios.leads.kanban') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="lkb-btn-salvar">
                        <i class="ri-save-line me-1"></i> Criar Lead
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.lkbLeadNovo = {
        storeUrl: @json(route('lk-beneficios.leads.store')),
        lemitCpfUrl: @json(route('lk-beneficios.lemit.cpf')),
        lemitCnpjUrl: @json(route('lk-beneficios.lemit.cnpj')),
        kanbanUrl: @json(route('lk-beneficios.leads.kanban')),
        csrf: @json(csrf_token()),
    };
</script>
@endsection
