@extends('layouts/layoutMaster')

@section('title', 'Contrato #' . $contrato->id)

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-analytics.scss')
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\StatusContrato;
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
    use App\Modules\LkBeneficios\Enums\TipoMovimentacao;

    $statusColors = [
        StatusContrato::EM_IMPLANTACAO => 'warning',
        StatusContrato::ATIVO => 'success',
        StatusContrato::SUSPENSO => 'info',
        StatusContrato::CANCELADO => 'danger',
    ];
    $statusColor = $statusColors[$contrato->status] ?? 'info';
@endphp
<div class="dashboard-wrapper">

    {{-- Header --}}
    <div class="dashboard-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Contrato #{{ $contrato->id }}</span>
                <h1 class="main-title">{{ $contrato->nome_cliente }}</h1>
                <p class="subtitle">
                    {{ $contrato->cpf_cnpj }}
                    ·
                    {{ $contrato->produto?->nome ?? '—' }}
                    ·
                    <span style="color: var(--dash-{{ $statusColor }}); font-weight: 700;">
                        {{ StatusContrato::label($contrato->status) }}
                    </span>
                </p>
            </div>
            <div class="header-filters">
                <div class="filter-group">
                    <a href="{{ route('lk-beneficios.contratos.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="ri-arrow-left-line me-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- KPIs do contrato --}}
    <div class="kpi-grid">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Valor Mensal</span>
                <h2 class="kpi-value">R$ {{ number_format((float) $contrato->valor_mensal, 2, ',', '.') }}</h2>
                <span class="kpi-trend trend-neutral">{{ $contrato->forma_pagamento }}</span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vidas no Contrato</span>
                <h2 class="kpi-value">{{ $contrato->vidas_total }}</h2>
                <span class="kpi-trend trend-neutral">
                    {{ $contrato->vidas_titulares }} titular(es) · {{ $contrato->vidas_dependentes }} dep.
                </span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Vigência</span>
                <h2 class="kpi-value" style="font-size: 1.125rem;">
                    {{ $contrato->data_vigencia_inicio ? $contrato->data_vigencia_inicio->format('d/m/Y') : '—' }}
                </h2>
                <span class="kpi-trend trend-neutral">
                    até {{ $contrato->data_vigencia_fim ? $contrato->data_vigencia_fim->format('d/m/Y') : 'Contínuo' }}
                </span>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Aniversário Reajuste</span>
                <h2 class="kpi-value" style="font-size: 1.125rem;">
                    {{ $contrato->data_aniversario_reajuste ? $contrato->data_aniversario_reajuste->format('d/m') : '—' }}
                </h2>
                <span class="kpi-trend trend-neutral">
                    {{ $contrato->data_aniversario_reajuste ? 'próximo reajuste' : 'não definido' }}
                </span>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Dados + Movimentações --}}
    <div class="charts-section">
        <div class="chart-row">
            <div class="chart-card chart-large">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Dados do Contrato</h3>
                        <span class="chart-subtitle">{{ TipoBeneficio::label($contrato->produto?->tipo ?? '') }}</span>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Corretor Responsável</span>
                            <strong style="color: var(--dash-text-primary);">{{ $contrato->user?->name ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Apólice / Proposta</span>
                            <strong style="color: var(--dash-text-primary);">
                                {{ $contrato->numero_apolice ?? '—' }} / {{ $contrato->numero_proposta ?? '—' }}
                            </strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Valor Anual</span>
                            <strong style="color: var(--dash-primary); font-family: 'JetBrains Mono', monospace;">
                                R$ {{ number_format((float) $contrato->valor_anual, 2, ',', '.') }}
                            </strong>
                        </div>
                        <div class="col-md-6">
                            <span class="kpi-label d-block">Dia do Vencimento</span>
                            <strong style="color: var(--dash-text-primary);">{{ $contrato->dia_vencimento ?? '—' }}</strong>
                        </div>
                        @if($contrato->observacoes)
                            <div class="col-12">
                                <span class="kpi-label d-block">Observações</span>
                                <p class="mb-0" style="color: var(--dash-text-primary);">{{ $contrato->observacoes }}</p>
                            </div>
                        @endif
                    </div>

                    @if($contrato->detalhesVida)
                        <hr style="border-color: var(--dash-card-border);">
                        <h6 class="chart-title mb-3">Detalhes — Seguro de Vida</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Capital Segurado</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-primary);">R$ {{ number_format((float) $contrato->detalhesVida->capital_segurado, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Cobertura Morte</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-text-primary);">R$ {{ number_format((float) $contrato->detalhesVida->cobertura_morte, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Cobertura Invalidez</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-text-primary);">R$ {{ number_format((float) $contrato->detalhesVida->cobertura_invalidez, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Assistência Funeral</span>
                                <strong style="color: var(--dash-text-primary);">{{ $contrato->detalhesVida->possui_assistencia_funeral ? 'Sim' : 'Não' }}</strong>
                            </div>
                        </div>
                    @endif

                    @if($contrato->detalhesPrevidencia)
                        <hr style="border-color: var(--dash-card-border);">
                        <h6 class="chart-title mb-3">Detalhes — Previdência Privada</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Modalidade</span>
                                <strong style="color: var(--dash-text-primary);">{{ $contrato->detalhesPrevidencia->modalidade }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Tabela de Imposto</span>
                                <strong style="color: var(--dash-text-primary);">{{ $contrato->detalhesPrevidencia->tabela_imposto }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Aporte Mensal</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-success);">R$ {{ number_format((float) $contrato->detalhesPrevidencia->aporte_mensal, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Aporte Inicial</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-text-primary);">R$ {{ number_format((float) $contrato->detalhesPrevidencia->aporte_inicial, 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif

                    @if($contrato->detalhesPatrimonial)
                        <hr style="border-color: var(--dash-card-border);">
                        <h6 class="chart-title mb-3">Detalhes — Patrimonial</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Tipo de Bem</span>
                                <strong style="color: var(--dash-text-primary);">{{ $contrato->detalhesPatrimonial->tipo_bem }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Identificador</span>
                                <strong style="color: var(--dash-text-primary);">{{ $contrato->detalhesPatrimonial->identificador_bem ?? '—' }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Valor Segurado</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-primary);">R$ {{ number_format((float) $contrato->detalhesPatrimonial->valor_segurado, 2, ',', '.') }}</strong>
                            </div>
                            <div class="col-md-6">
                                <span class="kpi-label d-block">Franquia</span>
                                <strong style="font-family: 'JetBrains Mono', monospace; color: var(--dash-warning);">R$ {{ number_format((float) $contrato->detalhesPatrimonial->franquia, 2, ',', '.') }}</strong>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="chart-card chart-medium">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Movimentações</h3>
                        <span class="chart-subtitle">Histórico do contrato</span>
                    </div>
                </div>
                <div class="chart-body" style="max-height: 520px; overflow-y: auto; padding: 0;">
                    @forelse($contrato->movimentacoes->sortByDesc('created_at') as $m)
                        <div style="padding: 1rem 1.5rem; border-bottom: 1px solid var(--dash-card-border);">
                            <small style="color: var(--dash-text-muted);">
                                {{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }} · {{ $m->user?->name ?? 'Sistema' }}
                            </small>
                            <div style="color: var(--dash-primary); font-weight: 700; font-size: 0.875rem; margin-top: 0.25rem;">
                                {{ TipoMovimentacao::label($m->tipo) }}
                            </div>
                            <p class="mb-0" style="font-size: 0.8125rem; color: var(--dash-text-secondary);">{{ $m->descricao }}</p>
                        </div>
                    @empty
                        <p style="color: var(--dash-text-muted); padding: 2rem; text-align: center; margin: 0;">Sem movimentações.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Beneficiários --}}
    <div class="tables-section">
        <div class="table-card">
            <div class="table-header">
                <div class="table-title-group">
                    <div class="table-icon implantados">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="table-title">Beneficiários</h3>
                        <span class="table-subtitle">Titulares e dependentes do contrato</span>
                    </div>
                </div>
                <div class="table-badge implantados">
                    <span>{{ $contrato->beneficiarios->count() }}</span> pessoas
                </div>
            </div>
            <div class="table-body">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Nascimento</th>
                            <th>Parentesco</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contrato->beneficiarios as $b)
                            <tr>
                                <td>
                                    <span class="contract-value {{ $b->tipo === 'TITULAR' ? '' : 'muted' }}">
                                        {{ $b->tipo }}
                                    </span>
                                </td>
                                <td class="contract-name">{{ $b->nome }}</td>
                                <td>{{ $b->cpf ?? '—' }}</td>
                                <td>{{ $b->data_nascimento?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $b->grau_parentesco ?? '—' }}</td>
                                <td>
                                    <span class="contract-value {{ $b->status === 'ATIVO' ? 'success' : 'muted' }}">
                                        {{ $b->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 2rem; color: var(--dash-text-muted);">
                                    Nenhum beneficiário cadastrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
