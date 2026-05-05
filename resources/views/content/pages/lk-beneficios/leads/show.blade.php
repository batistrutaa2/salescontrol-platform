@extends('layouts/layoutMaster')

@section('title', 'Lead #' . $lead->id)

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/lk-beneficios-lead-show.scss',
    ])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/lk-beneficios-lead-show.js')
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\StatusLead;
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;

    $cor = StatusLead::corGradiente($lead->status);
    $usuarioAtual = auth()->id();
@endphp
<div class="dashboard-wrapper lkb-lead-show-page">

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

    {{-- Informação fixada --}}
    <div class="lkb-pinned-card {{ $lead->informacao_fixada ? 'is-filled' : 'is-empty' }}" id="lkb-pinned-card">
        <div class="lkb-pinned-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 17v5"/>
                <path d="M9 10.76V6h6v4.76l3 3.24v2H6v-2z"/>
            </svg>
        </div>
        <div class="lkb-pinned-body">
            <span class="lkb-pinned-eyebrow">Informação fixada</span>
            <p class="lkb-pinned-text" id="lkb-pinned-text">
                @if($lead->informacao_fixada)
                    {{ $lead->informacao_fixada }}
                @else
                    <span class="lkb-pinned-empty">Nenhuma informação fixada. Use este espaço para destacar dados críticos do contato — preferências, restrições, contexto sensível.</span>
                @endif
            </p>
        </div>
        <div class="lkb-pinned-actions">
            <button type="button" class="lkb-btn-pinned" id="lkb-btn-edit-pinned" title="Editar informação fixada">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                <span>{{ $lead->informacao_fixada ? 'Editar' : 'Adicionar' }}</span>
            </button>
            <button type="button" class="lkb-btn-pinned is-ghost" id="lkb-btn-clear-pinned" title="Remover informação fixada" {{ $lead->informacao_fixada ? '' : 'hidden' }}>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
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

            {{-- Comentários --}}
            <div class="chart-card chart-medium lkb-comments-card">
                <div class="chart-header">
                    <div class="chart-title-group">
                        <h3 class="chart-title">Comentários</h3>
                        <span class="chart-subtitle"><span id="lkb-comments-count">{{ $lead->comentarios->count() }}</span> registro(s)</span>
                    </div>
                </div>
                <div class="chart-body lkb-comments-body">

                    <form id="lkb-comment-form" class="lkb-comment-form" novalidate>
                        <textarea id="lkb-comment-input" class="lkb-comment-textarea" rows="2" maxlength="5000" placeholder="Escreva um comentário sobre este contato…" required></textarea>
                        <div class="lkb-comment-form-footer">
                            <span class="lkb-comment-counter"><span id="lkb-comment-len">0</span> / 5000</span>
                            <button type="submit" class="lkb-btn-comment-submit" id="lkb-comment-submit">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="22" y1="2" x2="11" y2="13"/>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                                </svg>
                                Comentar
                            </button>
                        </div>
                    </form>

                    <ul class="lkb-comment-list" id="lkb-comment-list">
                        @forelse($lead->comentarios as $c)
                            <li class="lkb-comment-item" data-comentario-id="{{ $c->id }}">
                                <div class="lkb-comment-avatar">
                                    {{ strtoupper(mb_substr($c->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="lkb-comment-content">
                                    <header class="lkb-comment-meta">
                                        <span class="lkb-comment-author">{{ $c->user?->name ?? 'Sistema' }}</span>
                                        <span class="lkb-comment-date">{{ $c->created_at->format('d/m/Y H:i') }}</span>
                                    </header>
                                    <p class="lkb-comment-text">{{ $c->anotacao }}</p>
                                </div>
                                @if($c->user_id === $usuarioAtual)
                                    <button type="button" class="lkb-comment-delete" data-action="delete" data-id="{{ $c->id }}" title="Excluir comentário">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        </svg>
                                    </button>
                                @endif
                            </li>
                        @empty
                            <li class="lkb-comment-empty" id="lkb-comment-empty">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                <p>Nenhum comentário ainda. Seja o primeiro a deixar uma anotação.</p>
                            </li>
                        @endforelse
                    </ul>

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

{{-- Modal de edição da informação fixada --}}
<div class="modal fade lkb-pinned-modal" id="lkb-pinned-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="lkb-pinned-modal-header">
                <div class="lkb-pinned-modal-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 17v5"/><path d="M9 10.76V6h6v4.76l3 3.24v2H6v-2z"/>
                    </svg>
                </div>
                <div class="lkb-pinned-modal-titles">
                    <span class="lkb-pinned-modal-eyebrow">Lead · destaque</span>
                    <h5 class="lkb-pinned-modal-title">Informação fixada</h5>
                    <p class="lkb-pinned-modal-subtitle">Anotação importante que fica sempre visível no topo do lead.</p>
                </div>
                <button type="button" class="lkb-pinned-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="lkb-pinned-modal-body">
                <textarea id="lkb-pinned-input" class="lkb-pinned-textarea" rows="4" maxlength="1000" placeholder="Ex.: cliente prefere contato após 18h. Tem restrição alimentar relevante para apólice de vida.">{{ $lead->informacao_fixada }}</textarea>
                <div class="lkb-pinned-counter"><span id="lkb-pinned-len">{{ mb_strlen($lead->informacao_fixada ?? '') }}</span> / 1000</div>
            </div>
            <div class="lkb-pinned-modal-footer">
                <button type="button" class="lkb-btn-pinned is-ghost" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="lkb-btn-pinned is-primary" id="lkb-pinned-save">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    window.lkbLeadShow = {
        leadId: @json($lead->id),
        usuarioAtual: @json($usuarioAtual),
        csrf: @json(csrf_token()),
        urls: {
            comentarioStore: @json(route('lk-beneficios.leads.comentarios.store', ['id' => $lead->id])),
            comentarioDeleteTemplate: @json(route('lk-beneficios.leads.comentarios.destroy', ['id' => $lead->id, 'comentarioId' => '__CID__'])),
            informacaoFixada: @json(route('lk-beneficios.leads.informacao-fixada.update', ['id' => $lead->id])),
        },
    };
</script>
@endsection
