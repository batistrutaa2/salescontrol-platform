@extends('layouts/layoutMaster')

@section('title', 'Solicitar Pós-venda')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/comercial-minhas-demandas.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/moment/moment.js',
        'resources/assets/vendor/libs/select2/select2.js',
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/comercial-minhas-demandas.js')
@endsection

@section('content')
<div class="mdv-page"
     data-list="{{ route('comercial.minhasDemandas.list') }}"
     data-contratos="{{ route('comercial.minhasDemandas.contratos') }}"
     data-store="{{ route('comercial.minhasDemandas.store') }}">

    {{-- Header --}}
    <div class="mdv-header">
        <div class="mdv-title-group">
            <div class="mdv-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                </svg>
            </div>
            <div>
                <h1 class="mdv-title">Solicitar Pós-venda</h1>
                <p class="mdv-subtitle">Abra demandas para o time administrativo tratar seus contratos implantados</p>
            </div>
        </div>
        <div class="mdv-header-actions">
            <button id="mdv-rever-tour" type="button" class="mdv-btn mdv-btn-ghost" title="Rever tutorial">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                Como funciona
            </button>
            <button id="mdv-nova" type="button" class="mdv-btn mdv-btn-primary" data-tour="nova">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Nova solicitação
            </button>
        </div>
    </div>

    {{-- Lista de solicitações --}}
    <div class="mdv-list-card" data-tour="lista">
        <div class="mdv-list-header">
            <h3 class="mdv-list-title">Minhas solicitações</h3>
            <span class="mdv-list-badge"><span id="mdv-count">0</span> no total</span>
        </div>
        <div id="mdv-lista" class="mdv-list-body" aria-live="polite" aria-busy="true">
            {{-- Cards renderizados via JS --}}
        </div>
        <div id="mdv-empty" class="mdv-empty" hidden>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <p>Você ainda não abriu nenhuma solicitação. Clique em <strong>Nova solicitação</strong> para começar.</p>
        </div>
    </div>
</div>

{{-- Modal Nova solicitação --}}
<div class="modal fade mdv-modal" id="mdv-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="mdv-form" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Nova solicitação de pós-venda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="mdv-form-group" data-tour="contrato">
                    <label class="mdv-label" for="mdv-venda">Contrato implantado *</label>
                    <select id="mdv-venda" class="mdv-select" style="width:100%"></select>
                    <small class="mdv-hint">Busque por nome, proposta ou CPF/CNPJ. Só aparecem contratos seus já implantados.</small>
                </div>

                <div class="mdv-form-group" data-tour="tipo">
                    <label class="mdv-label" for="mdv-tipo">Tipo de demanda *</label>
                    <select id="mdv-tipo" class="mdv-select" style="width:100%">
                        <option value="">Selecione o que você precisa…</option>
                        @foreach ($tipoLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mdv-form-group">
                    <label class="mdv-label" for="mdv-titulo">Título *</label>
                    <input type="text" id="mdv-titulo" class="mdv-input" maxlength="255" placeholder="Ex.: Enviar boleto de junho">
                </div>

                <div class="mdv-form-group">
                    <label class="mdv-label" for="mdv-descricao">Detalhes</label>
                    <textarea id="mdv-descricao" class="mdv-textarea" rows="4" maxlength="1000" placeholder="Explique o que o pós-venda precisa fazer (opcional)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="mdv-btn mdv-btn-outline" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="mdv-btn mdv-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                    Enviar ao pós-venda
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
