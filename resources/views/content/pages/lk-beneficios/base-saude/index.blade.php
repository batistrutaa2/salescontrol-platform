@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Base Saúde')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite([
        'resources/assets/vendor/scss/pages/dashboard-analytics.scss',
        'resources/assets/vendor/scss/pages/lk-beneficios-base-saude.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-base-saude.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="lkb-base-saude-page">
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
</div>

{{--
    Modal "Pegar Lead" — design system --lkb-* aplicado.
    Estados visuais ficam em data-attributes/classes para o JS controlar
    sem reescrever HTML. Produto cards são renderizados aqui via Blade
    (single source of truth do servidor) e o JS só controla seleção/submit.
--}}
<div class="modal fade" id="lkb-modal-pegar" tabindex="-1" aria-labelledby="lkb-modal-pegar-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="lkb-modal-header">
                <div class="lkb-modal-icon" aria-hidden="true">
                    {{-- Ícone: arrow-right-circle (mover para o pipeline) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7h13"/>
                        <path d="M3 12h13"/>
                        <path d="M3 17h9"/>
                        <path d="M16 7l5 5-5 5"/>
                    </svg>
                </div>
                <div class="lkb-modal-titles">
                    <span class="lkb-modal-eyebrow">Pipeline · Aquisição</span>
                    <h5 class="lkb-modal-title" id="lkb-modal-pegar-title">Adicionar ao Pipeline</h5>
                    <p class="lkb-modal-subtitle">Crie um lead a partir deste cliente da Base Saúde</p>
                </div>
                <button type="button" class="lkb-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="lkb-modal-body">
                <input type="hidden" id="lkb-bs-cpf-cnpj">
                <input type="hidden" id="lkb-bs-produto-id">

                {{-- Cliente selecionado --}}
                <div class="lkb-cliente-card">
                    <div class="lkb-cliente-avatar" id="lkb-bs-cliente-avatar">—</div>
                    <div class="lkb-cliente-info">
                        <span class="lkb-cliente-eyebrow">Cliente selecionado</span>
                        <p class="lkb-cliente-nome" id="lkb-bs-cliente-nome">—</p>
                        <span class="lkb-cliente-doc">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M7 8h6"/><path d="M7 12h10"/><path d="M7 16h6"/>
                            </svg>
                            <span id="lkb-bs-cliente-doc">—</span>
                        </span>
                        <div class="lkb-cliente-tags" id="lkb-bs-cliente-tags"></div>
                    </div>
                </div>

                {{-- Section label --}}
                <div class="lkb-section-label">
                    <span class="lkb-section-label-text">Produto de Interesse</span>
                    <span class="lkb-section-label-hint">Escolha um produto para criar o lead</span>
                </div>

                @if($produtos->isEmpty())
                    <div class="lkb-produtos-empty">
                        <div class="lkb-produtos-empty-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9"  x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        </div>
                        <div class="lkb-produtos-empty-title">Nenhum produto cadastrado</div>
                        <p class="lkb-produtos-empty-text mb-0">
                            Cadastre ao menos um produto de benefício antes de mover clientes
                            da Base Saúde para o pipeline.
                        </p>
                    </div>
                @else
                    <div class="lkb-produtos-grid" role="radiogroup" aria-label="Produto de interesse">
                        @foreach($produtos as $p)
                            <label class="lkb-produto-card" data-tipo="{{ $p->tipo }}" data-id="{{ $p->id }}" tabindex="0" role="radio" aria-checked="false">
                                <input type="radio" name="lkb-bs-produto" value="{{ $p->id }}">
                                <div class="lkb-produto-icon" aria-hidden="true">
                                    @switch($p->tipo)
                                        @case(TipoBeneficio::VIDA)
                                            {{-- heart pulse --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3.5 12.5h3l2-4 4 8 2-4h6"/>
                                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l8.84 8.84 8.84-8.84a5.5 5.5 0 0 0 0-7.78z" opacity=".35"/>
                                            </svg>
                                            @break
                                        @case(TipoBeneficio::ODONTO)
                                            {{-- tooth --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 5.5c-2 0-2.5-1.5-5-1.5C4.5 4 3 6.5 3 9c0 4 2 7 3 11 .5 2 2 2 2.5 0 .5-2 1-5 1.5-5h2c.5 0 1 3 1.5 5 .5 2 2 2 2.5 0 1-4 3-7 3-11 0-2.5-1.5-5-4-5-2.5 0-3 1.5-5 1.5z"/>
                                            </svg>
                                            @break
                                        @case(TipoBeneficio::PREVIDENCIA)
                                            {{-- safe / shield with check --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5l-8-3z"/>
                                                <polyline points="9 12 11.5 14.5 16 10"/>
                                            </svg>
                                            @break
                                        @case(TipoBeneficio::PATRIMONIAL)
                                            {{-- home --}}
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 11.5 12 4l9 7.5"/>
                                                <path d="M5 10.5V20a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9.5"/>
                                            </svg>
                                            @break
                                        @default
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2 4 5v7c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5l-8-3z"/>
                                            </svg>
                                    @endswitch
                                </div>
                                <div class="lkb-produto-text">
                                    <span class="lkb-produto-name">{{ $p->nome }}</span>
                                    <span class="lkb-produto-tipo">{{ TipoBeneficio::label($p->tipo) }}</span>
                                </div>
                                <div class="lkb-produto-check" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="lkb-modal-footer">
                <button type="button" class="lkb-btn" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button" id="lkb-bs-confirmar" class="lkb-btn-primary" data-state="idle"
                        @if($produtos->isEmpty()) disabled @endif>
                    <span class="lkb-btn-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Adicionar ao Pipeline
                    </span>
                    <span class="lkb-btn-spinner" aria-hidden="true">
                        <span class="lkb-spinner"></span>
                        Criando lead...
                    </span>
                    <span class="lkb-btn-success" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Lead criado!
                    </span>
                </button>
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
