@extends('layouts/layoutMaster')

@section('title', 'LK Benefícios - Novo Lead')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/lk-beneficios-lead-novo.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/cleavejs/cleave.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/lk-beneficios-lead-novo.js'])
@endsection

@section('content')
@php
    use App\Modules\LkBeneficios\Enums\TipoBeneficio;
@endphp
<div class="lkb-novo-page">

    <div class="lkb-page-header">
        <div class="lkb-header-title-group">
            <div class="lkb-title-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <div class="lkb-title-text">
                <span class="lkb-greeting-label">Pipeline</span>
                <h4>Novo Lead</h4>
                <p class="lkb-subtitle">Cadastre manualmente um cliente com produto de interesse</p>
            </div>
        </div>

        <div class="lkb-header-actions">
            <a href="{{ route('lk-beneficios.leads.kanban') }}" class="lkb-btn">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
                Voltar ao Pipeline
            </a>
        </div>
    </div>

    <div class="lkb-form-shell">
        <form id="lkb-form-lead-novo" novalidate>
            @csrf
            <div class="lkb-form-layout">

                {{-- COLUNA PRINCIPAL --}}
                <div class="lkb-form-main">
                    <div class="lkb-form-card">
                        <div class="lkb-form-card-header">
                            <div class="lkb-form-card-title">
                                <h3>Dados do Lead</h3>
                                <span class="lkb-form-card-subtitle">Identificação, contato e observações</span>
                            </div>
                        </div>

                        <div class="lkb-form-card-body">

                            {{-- Seção 1: Identificação --}}
                            <section class="lkb-form-section">
                                <h4 class="lkb-form-section-title">Identificação</h4>
                                <div class="lkb-form-grid">
                                    <div class="lkb-form-group lkb-col-3">
                                        <label class="lkb-form-label" for="lkb-cliente-tipo">
                                            Tipo <span class="lkb-required">*</span>
                                        </label>
                                        <div class="lkb-select-wrap">
                                            <select name="cliente_tipo" id="lkb-cliente-tipo" class="lkb-select" required>
                                                <option value="PF">Pessoa Física (CPF)</option>
                                                <option value="PJ">Pessoa Jurídica (CNPJ)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="lkb-form-group lkb-col-9">
                                        <label class="lkb-form-label" for="lkb-cpf-cnpj">
                                            CPF / CNPJ <span class="lkb-required">*</span>
                                        </label>
                                        <div class="lkb-input-attached">
                                            <input type="text" name="cpf_cnpj" id="lkb-cpf-cnpj" class="lkb-input lkb-input-mono" required autocomplete="off">
                                            <button type="button" id="lkb-btn-lemit" class="lkb-btn lkb-btn-info">
                                                <i class="ri-search-line"></i>
                                                Consultar Lemit
                                            </button>
                                        </div>
                                    </div>

                                    <div class="lkb-form-group lkb-col-12">
                                        <label class="lkb-form-label" for="lkb-nome">
                                            Nome <span class="lkb-required">*</span>
                                        </label>
                                        <input type="text" name="nome" id="lkb-nome" class="lkb-input" required maxlength="150" autocomplete="off">
                                    </div>
                                </div>
                            </section>

                            {{-- Seção 2: Contato --}}
                            <section class="lkb-form-section">
                                <h4 class="lkb-form-section-title">Contato</h4>
                                <div class="lkb-form-grid">
                                    <div class="lkb-form-group lkb-col-5">
                                        <label class="lkb-form-label" for="lkb-telefone">Telefone</label>
                                        <input type="text" name="telefone" id="lkb-telefone" class="lkb-input lkb-input-mono" autocomplete="off" placeholder="(00) 00000-0000">
                                    </div>

                                    <div class="lkb-form-group lkb-col-7">
                                        <label class="lkb-form-label" for="lkb-email">E-mail</label>
                                        <input type="email" name="email" id="lkb-email" class="lkb-input" autocomplete="off" placeholder="cliente@exemplo.com">
                                    </div>
                                </div>
                            </section>

                            {{-- Seção 3: Observações --}}
                            <section class="lkb-form-section">
                                <h4 class="lkb-form-section-title">Observações</h4>
                                <div class="lkb-form-grid">
                                    <div class="lkb-form-group lkb-col-12">
                                        <label class="lkb-form-label" for="lkb-observacoes">Anotações</label>
                                        <textarea name="observacoes" id="lkb-observacoes" class="lkb-textarea" rows="3" placeholder="Notas sobre a abordagem, contexto da indicação, preferências…"></textarea>
                                    </div>
                                </div>
                            </section>

                        </div>
                    </div>
                </div>

                {{-- COLUNA ASIDE --}}
                <aside class="lkb-form-aside">
                    <div class="lkb-form-card lkb-form-card--aside">
                        <div class="lkb-form-card-header">
                            <div class="lkb-form-card-title">
                                <h3>Interesse</h3>
                                <span class="lkb-form-card-subtitle">Produto que motivou o contato</span>
                            </div>
                        </div>

                        <div class="lkb-form-card-body">
                            <div class="lkb-form-grid">
                                <div class="lkb-form-group lkb-col-12">
                                    <label class="lkb-form-label" for="lkb-produto">
                                        Produto <span class="lkb-required">*</span>
                                    </label>
                                    <div class="lkb-select-wrap">
                                        <select name="produto_interesse_id" id="lkb-produto" class="lkb-select" required>
                                            <option value="">Selecione...</option>
                                            @foreach($produtos as $p)
                                                <option value="{{ $p->id }}" data-tipo="{{ $p->tipo }}">
                                                    {{ $p->nome }} — {{ TipoBeneficio::label($p->tipo) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="lkb-form-aside-info">
                                <div class="lkb-form-aside-info-icon" aria-hidden="true">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                                    </svg>
                                </div>
                                <div class="lkb-form-aside-info-text">
                                    <strong>Dica:</strong> use <strong>Consultar Lemit</strong> ao lado do CPF/CNPJ para preencher nome, telefone e e-mail automaticamente.
                                </div>
                            </div>
                        </div>
                    </div>
                </aside>

            </div>

            <div class="lkb-form-actions">
                <a href="{{ route('lk-beneficios.leads.kanban') }}" class="lkb-btn lkb-btn-ghost">Cancelar</a>
                <button type="submit" class="lkb-btn lkb-btn-primary" id="lkb-btn-salvar">
                    <i class="ri-save-line"></i>
                    Criar Lead
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal de resultado da consulta Lemit --}}
<div class="modal fade lkb-lemit-modal" id="lkbLemitModal" tabindex="-1" aria-labelledby="lkbLemitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header lkb-lemit-modal-header">
                <div class="lkb-lemit-modal-title-group">
                    <div class="lkb-lemit-modal-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <div>
                        <h5 class="modal-title" id="lkbLemitModalLabel">Consulta Lemit</h5>
                        <span class="lkb-lemit-modal-subtitle" id="lkb-lemit-modal-doc">—</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body lkb-lemit-modal-body">
                {{-- Loading --}}
                <div class="lkb-lemit-state lkb-lemit-state-loading" id="lkb-lemit-loading">
                    <div class="lkb-lemit-spinner" aria-hidden="true"></div>
                    <p>Consultando dados na base Lemit…</p>
                </div>

                {{-- Erro --}}
                <div class="lkb-lemit-state lkb-lemit-state-error d-none" id="lkb-lemit-error">
                    <div class="lkb-lemit-state-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <p id="lkb-lemit-error-msg">Falha ao consultar.</p>
                </div>

                {{-- Resultado --}}
                <div class="lkb-lemit-state lkb-lemit-result d-none" id="lkb-lemit-result">

                    <section class="lkb-lemit-section" id="lkb-lemit-section-identificacao">
                        <h6 class="lkb-lemit-section-title">Identificação</h6>
                        <div class="lkb-lemit-fields" id="lkb-lemit-fields-identificacao"></div>
                    </section>

                    <section class="lkb-lemit-section">
                        <header class="lkb-lemit-section-header">
                            <h6 class="lkb-lemit-section-title">Telefones</h6>
                            <span class="lkb-lemit-section-count" id="lkb-lemit-count-telefones">0</span>
                        </header>
                        <ul class="lkb-lemit-list" id="lkb-lemit-list-telefones"></ul>
                        <p class="lkb-lemit-empty d-none" id="lkb-lemit-empty-telefones">Nenhum telefone retornado.</p>
                    </section>

                    <section class="lkb-lemit-section">
                        <header class="lkb-lemit-section-header">
                            <h6 class="lkb-lemit-section-title">E-mails</h6>
                            <span class="lkb-lemit-section-count" id="lkb-lemit-count-emails">0</span>
                        </header>
                        <ul class="lkb-lemit-list" id="lkb-lemit-list-emails"></ul>
                        <p class="lkb-lemit-empty d-none" id="lkb-lemit-empty-emails">Nenhum e-mail retornado.</p>
                    </section>

                </div>
            </div>

            <div class="modal-footer lkb-lemit-modal-footer">
                <button type="button" class="lkb-btn lkb-btn-ghost" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="lkb-btn lkb-btn-primary" id="lkb-lemit-fill-all" disabled>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                    </svg>
                    Preencher campos automaticamente
                </button>
            </div>
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
