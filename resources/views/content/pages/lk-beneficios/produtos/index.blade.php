@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Catálogo de Produtos')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/lk-beneficios-produtos.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/lk-beneficios-produtos-index.js')
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="lkb-produtos-page">

    {{-- Header --}}
    <div class="lkb-page-header">
        <div class="lkb-header-title-group">
            <div class="lkb-title-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2 2 7l10 5 10-5-10-5z"/>
                    <polyline points="2 17 12 22 22 17"/>
                    <polyline points="2 12 12 17 22 12"/>
                </svg>
            </div>
            <div class="lkb-title-text">
                <span class="lkb-greeting-label">Administrativo</span>
                <h4>Catálogo de Produtos</h4>
                <p class="lkb-subtitle">Defina o que você comercializa — Vida, Odonto, Previdência, Patrimoniais</p>
            </div>
        </div>

        <div class="lkb-header-actions">
            <button type="button" class="lkb-btn lkb-btn-primary" id="lkb-btn-novo-produto">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Novo produto
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="lkb-filters-card">
        <div class="lkb-filters-grid">
            <div class="lkb-form-group">
                <label class="lkb-form-label" for="lkb-filter-tipo">Tipo</label>
                <div class="lkb-select-wrap">
                    <select id="lkb-filter-tipo" class="lkb-select">
                        <option value="">Todos</option>
                        @foreach($tipos as $t)
                            <option value="{{ $t['value'] }}">{{ $t['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="lkb-form-group">
                <label class="lkb-form-label" for="lkb-filter-status">Status</label>
                <div class="lkb-select-wrap">
                    <select id="lkb-filter-status" class="lkb-select">
                        <option value="">Todos</option>
                        <option value="ativo">Ativos</option>
                        <option value="inativo">Inativos</option>
                    </select>
                </div>
            </div>
            <div class="lkb-form-group lkb-filter-search">
                <label class="lkb-form-label" for="lkb-filter-search">Buscar</label>
                <input type="text" id="lkb-filter-search" class="lkb-input" placeholder="Nome ou subtipo…" autocomplete="off">
            </div>
        </div>
    </div>

    {{-- Tabela --}}
    <div class="lkb-table-card">
        <table id="lkb-tabela-produtos" class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Subtipo</th>
                    <th>Operadora</th>
                    <th>Status</th>
                    <th>Criado</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- Modal único de criar/editar --}}
<div class="modal fade lkb-modal lkb-produto-modal" id="lkb-modal-produto" tabindex="-1" aria-labelledby="lkb-modal-produto-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <div class="lkb-modal-header">
                <div class="lkb-modal-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div class="lkb-modal-titles">
                    <span class="lkb-modal-eyebrow">Catálogo · Produtos</span>
                    <h5 class="lkb-modal-title" id="lkb-modal-produto-title">Novo produto</h5>
                    <p class="lkb-modal-subtitle">Defina o produto que será oferecido aos clientes</p>
                </div>
                <button type="button" class="lkb-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="lkb-modal-body">
                <form id="lkb-form-produto" class="lkb-form" novalidate>
                    <input type="hidden" id="lkb-produto-id" name="id">

                    <div class="lkb-form-grid">
                        <div class="lkb-form-group lkb-col-12">
                            <label class="lkb-form-label" for="lkb-produto-nome">
                                Nome <span class="lkb-required">*</span>
                            </label>
                            <input type="text" name="nome" id="lkb-produto-nome" class="lkb-input" maxlength="120" required autocomplete="off" placeholder="Ex.: Vida Individual Premium">
                            <div class="lkb-field-error" data-error-for="nome"></div>
                        </div>

                        <div class="lkb-form-group lkb-col-6">
                            <label class="lkb-form-label" for="lkb-produto-tipo">
                                Tipo <span class="lkb-required">*</span>
                            </label>
                            <div class="lkb-select-wrap">
                                <select name="tipo" id="lkb-produto-tipo" class="lkb-select" required>
                                    <option value="">Selecione…</option>
                                    @foreach($tipos as $t)
                                        <option value="{{ $t['value'] }}">{{ $t['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lkb-field-error" data-error-for="tipo"></div>
                        </div>

                        <div class="lkb-form-group lkb-col-6">
                            <label class="lkb-form-label" for="lkb-produto-subtipo">Subtipo</label>
                            <input type="text" name="subtipo" id="lkb-produto-subtipo" class="lkb-input" maxlength="80" autocomplete="off" placeholder="Ex.: Individual, Empresarial, PME">
                            <div class="lkb-field-error" data-error-for="subtipo"></div>
                        </div>

                        <div class="lkb-form-group lkb-col-12">
                            <label class="lkb-form-label" for="lkb-produto-operadora">
                                Operadora
                                <span class="lkb-hint">opcional</span>
                            </label>
                            <div class="lkb-select-wrap">
                                <select name="operadora_id" id="lkb-produto-operadora" class="lkb-select">
                                    <option value="">— Sem operadora —</option>
                                    @foreach($operadoras as $op)
                                        <option value="{{ $op->id }}">{{ $op->nome }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="lkb-field-error" data-error-for="operadora_id"></div>
                        </div>

                        <div class="lkb-form-group lkb-col-12">
                            <label class="lkb-form-label" for="lkb-produto-descricao">Descrição</label>
                            <textarea name="descricao" id="lkb-produto-descricao" class="lkb-textarea" rows="3" maxlength="1000" placeholder="Resumo do produto, coberturas principais, diferenciais…"></textarea>
                            <div class="lkb-field-error" data-error-for="descricao"></div>
                        </div>

                        <div class="lkb-form-group lkb-col-12">
                            <label class="lkb-toggle-row" for="lkb-produto-ativo">
                                <span class="lkb-toggle-row-text">
                                    <strong>Produto ativo</strong>
                                    <small>Quando inativo, deixa de aparecer nos selects de leads e Base Saúde.</small>
                                </span>
                                <span class="lkb-switch">
                                    <input type="checkbox" name="ativo" id="lkb-produto-ativo" value="1" checked>
                                    <span class="lkb-switch-track"></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="lkb-modal-footer">
                <button type="button" class="lkb-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="lkb-form-produto" class="lkb-btn-primary" id="lkb-btn-salvar-produto" data-state="idle">
                    <span class="lkb-btn-label">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                            <polyline points="17 21 17 13 7 13 7 21"/>
                            <polyline points="7 3 7 8 15 8"/>
                        </svg>
                        Salvar produto
                    </span>
                    <span class="lkb-btn-spinner" aria-hidden="true">
                        <span class="lkb-spinner"></span>
                        Salvando…
                    </span>
                    <span class="lkb-btn-success" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Salvo!
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
    window.lkbProdutos = {
        datatableUrl: @json(route('lk-beneficios.produtos.datatable')),
        storeUrl: @json(route('lk-beneficios.produtos.store')),
        updateUrlTemplate: @json(route('lk-beneficios.produtos.update', ['id' => '__ID__'])),
        toggleUrlTemplate: @json(route('lk-beneficios.produtos.toggle', ['id' => '__ID__'])),
        deleteUrlTemplate: @json(route('lk-beneficios.produtos.destroy', ['id' => '__ID__'])),
        csrf: @json(csrf_token()),
        tipos: @json($tipos),
    };
</script>
@endsection
