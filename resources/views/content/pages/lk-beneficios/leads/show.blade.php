@extends('layouts/layoutMaster')

@section('title', 'Lead #' . $lead->id)

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\StatusLead;
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;

    $cor = StatusLead::corGradiente($lead->status);
@endphp
<div class="dashboard-wrapper">

    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label" style="background: linear-gradient(135deg, {{ $cor[0] }} 20%, {{ $cor[1] }} 100%); color: #fff;">
                    {{ StatusLead::label($lead->status) }}
                </span>
                <h1 class="main-title">{{ $lead->nome }}</h1>
                <p class="subtitle">
                    {{ $lead->cpf_cnpj }} ·
                    Produto: {{ $lead->produtoInteresse?->nome }} ({{ TipoBeneficio::label($lead->produtoInteresse?->tipo ?? '') }}) ·
                    Origem: <strong>{{ $lead->origem === 'BASE_SAUDE' ? 'Base Saúde' : 'Manual' }}</strong>
                </p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    @if($lead->convertido_em === null)
                        <a href="{{ route('lk-beneficios.leads.converter.form', $lead->id) }}" class="btn btn-sm btn-success">
                            <i class="ri-check-double-line me-1"></i> Converter em Contrato
                        </a>
                    @else
                        <span class="btn btn-sm btn-outline-success disabled">
                            Convertido em {{ $lead->convertido_em->format('d/m/Y H:i') }}
                        </span>
                    @endif
                    <a href="{{ route('lk-beneficios.leads.kanban') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Pipeline
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="charts-section">
        <div class="chart-row">
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Dados de contato</h3>
                        <span class="chart-subtitle">Corretor responsável: {{ $lead->user?->name ?? '—' }}</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="kpi-label d-block">E-mail</span>
                            <strong style="color: var(--dash-text-primary);">{{ $lead->email ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Telefone</span>
                            <strong style="color: var(--dash-text-primary);">{{ $lead->telefone ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Tipo</span>
                            <strong style="color: var(--dash-text-primary);">{{ $lead->cliente_tipo === 'PF' ? 'Pessoa Física' : 'Pessoa Jurídica' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Criado em</span>
                            <strong style="color: var(--dash-text-primary);">{{ $lead->created_at->format('d/m/Y H:i') }}</strong>
                        </div>
                        @if($lead->observacoes)
                            <div class="col-12">
                                <span class="kpi-label d-block">Observações</span>
                                <p class="mb-0" style="color: var(--dash-text-primary);">{{ $lead->observacoes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Histórico</h3>
                        <span class="chart-subtitle">Movimentações no pipeline</span>
                    </div>
                </div>
                <div class="chart-body" style="max-height: 480px; overflow-y: auto; padding: 0;">
                    @forelse($lead->historico as $h)
                        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--dash-card-border);">
                            <small style="color: var(--dash-text-muted);">
                                {{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->name ?? 'Sistema' }}
                            </small>
                            <div style="color: var(--dash-primary); font-weight: 700; font-size: 0.875rem; margin-top: 0.25rem;">
                                @if($h->status_anterior)
                                    {{ StatusLead::label($h->status_anterior) }} → {{ StatusLead::label($h->status_novo) }}
                                @else
                                    Criado em {{ StatusLead::label($h->status_novo) }}
                                @endif
                            </div>
                            @if($h->observacao)
                                <p class="mb-0" style="font-size: 0.8125rem; color: var(--dash-text-secondary);">{{ $h->observacao }}</p>
                            @endif
                        </div>
                    @empty
                        <p style="color: var(--dash-text-muted); padding: 2rem; text-align: center; margin: 0;">Sem histórico.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @if($lead->contratos->isNotEmpty())
        <div class="tables-section">
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title-group">
                        <div class="table-icon implantados">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="table-title">Contratos gerados</h3>
                            <span class="table-subtitle">Originados a partir deste lead</span>
                        </div>
                    </div>
                </div>
                <div class="table-body">
                    <table class="custom-table">
                        <thead>
                            <tr><th>#</th><th>Produto</th><th>Status</th><th class="text-end">Valor mensal</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach($lead->contratos as $c)
                                <tr>
                                    <td class="contract-value muted">#{{ $c->id }}</td>
                                    <td class="contract-name">{{ $c->produto?->nome ?? '—' }}</td>
                                    <td><span class="contract-value success">{{ $c->status }}</span></td>
                                    <td class="text-end contract-value">R$ {{ number_format((float) $c->valor_mensal, 2, ',', '.') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('lk-beneficios.contratos.show', $c->id) }}" class="btn btn-sm btn-outline-primary" style="border-radius:20px; padding: 0.25rem 0.75rem;">
                                            <i class="ri-eye-line"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
