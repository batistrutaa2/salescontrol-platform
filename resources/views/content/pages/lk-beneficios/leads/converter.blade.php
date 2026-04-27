@extends('layouts/layoutMaster')

@section('title', 'Converter Lead #' . $lead->id . ' em Contrato')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-lead-converter.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Fechamento</span>
                <h1 class="main-title">Converter em Contrato</h1>
                <p class="subtitle">
                    Lead #{{ $lead->id }} · {{ $lead->nome }} · {{ $lead->cpf_cnpj }} ·
                    Produto: <strong>{{ $lead->produtoInteresse?->nome }}</strong>
                    ({{ TipoBeneficio::label($lead->produtoInteresse?->tipo ?? '') }})
                </p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.leads.show', $lead->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Voltar ao Lead
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="chart-card chart-large" style="max-width: 960px; margin: 0 auto;">
        <div class="chart-header">
            <div class="chart-title-group">
                <h3 class="chart-title">Dados do contrato</h3>
                <span class="chart-subtitle">Preencha as informações comerciais e finalize</span>
            </div>
        </div>
        <div class="chart-body">
            <form id="lkb-form-converter" data-lead-id="{{ $lead->id }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="kpi-label d-block mb-1">Número da Apólice</label>
                        <input type="text" name="numero_apolice" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="kpi-label d-block mb-1">Número da Proposta</label>
                        <input type="text" name="numero_proposta" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Data da Proposta</label>
                        <input type="date" name="data_proposta" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Início Vigência</label>
                        <input type="date" name="data_vigencia_inicio" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Fim Vigência</label>
                        <input type="date" name="data_vigencia_fim" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Valor Mensal</label>
                        <input type="number" step="0.01" min="0" name="valor_mensal" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Forma de Pagamento</label>
                        <select name="forma_pagamento" class="form-select">
                            <option value="MENSAL">Mensal</option>
                            <option value="TRIMESTRAL">Trimestral</option>
                            <option value="SEMESTRAL">Semestral</option>
                            <option value="ANUAL">Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Dia de Vencimento</label>
                        <input type="number" min="1" max="31" name="dia_vencimento" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Vidas Total</label>
                        <input type="number" min="0" name="vidas_total" class="form-control" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Titulares</label>
                        <input type="number" min="0" name="vidas_titulares" class="form-control" value="1">
                    </div>
                    <div class="col-md-4">
                        <label class="kpi-label d-block mb-1">Dependentes</label>
                        <input type="number" min="0" name="vidas_dependentes" class="form-control" value="0">
                    </div>

                    <div class="col-12">
                        <label class="kpi-label d-block mb-1">Observações</label>
                        <textarea name="observacoes" class="form-control" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('lk-beneficios.leads.show', $lead->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-success" id="lkb-btn-converter">
                        <i class="ri-check-double-line me-1"></i> Criar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    window.lkbConverter = {
        submitUrl: @json(route('lk-beneficios.leads.converter.submit', ['id' => $lead->id])),
        csrf: @json(csrf_token()),
    };
</script>
@endsection
