@extends('layouts/layoutMaster')

@section('title', 'Operadoras e Planos')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/operadoras-planos.scss')
@endsection

@section('page-script')
    @vite('resources/assets/js/operadoras-planos.js')
@endsection

@section('content')
<div id="op-planos" data-csrf="{{ csrf_token() }}">
    <div class="op-header">
        <div>
            <h4 class="op-title">Operadoras e Planos</h4>
            <span class="op-subtitle">Cadastre a operadora e os planos dela numa tela só</span>
        </div>
        <div class="op-doc-health" id="op-doc-health" role="status" aria-live="polite">Carregando saúde dos documentos…</div>
    </div>

    <div class="op-layout">
        {{-- Master: operadoras --}}
        <aside class="op-master">
            <div class="op-master-head">
                <h5>Operadoras</h5>
                <button type="button" class="op-btn op-btn-primary op-btn-sm" id="op-add-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova
                </button>
            </div>

            <form class="op-form" id="op-form" hidden autocomplete="off">
                <label for="op-nome">Nome da operadora</label>
                <input type="text" class="op-input" id="op-nome" placeholder="Nome da operadora" maxlength="120" required>
                <div class="op-form-row">
                    <label class="visually-hidden" for="op-status">Status da operadora</label>
                    <select class="op-input" id="op-status">
                        <option value="Y">Ativa</option>
                        <option value="N">Inativa</option>
                    </select>
                    <button type="submit" class="op-btn op-btn-primary">Salvar</button>
                    <button type="button" class="op-btn op-btn-ghost" id="op-cancel">Cancelar</button>
                </div>
            </form>

            <div class="op-list" id="op-list">
                <div class="op-loading">Carregando…</div>
            </div>
        </aside>

        {{-- Detail: planos da operadora selecionada --}}
        <section class="op-detail" id="op-detail">
            <div class="op-empty-detail">
                <svg xmlns="http://www.w3.org/2000/svg" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3h7v7H3z"/><path d="M14 3h7v7h-7z"/><path d="M14 14h7v7h-7z"/><path d="M3 14h7v7H3z"/></svg>
                <p>Selecione uma operadora à esquerda para ver e cadastrar seus planos.</p>
            </div>
        </section>
    </div>
</div>
@endsection
