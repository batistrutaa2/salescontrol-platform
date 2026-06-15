@extends('layouts/layoutMaster')

@section('title', 'Gestão da Escola — Módulos')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/escola.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite([
        'resources/assets/js/escola-common.js',
        'resources/assets/js/escola-gestao.js',
    ])
@endsection

@section('content')
<div class="esc-page esc-gestao-page"
     data-store-url="{{ route('escola.gestao.modulos.store') }}"
     data-update-url="{{ url('escola/gestao/modulos') }}"
     data-aulas-url="{{ url('escola/gestao/modulos') }}">

    <div class="esc-header">
        <div class="esc-header-main">
            <div class="esc-title-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <div class="esc-title-text">
                <h4>Gestão da Escola</h4>
                <span>Crie e organize os módulos e aulas de treinamento.</span>
            </div>
        </div>
        <div class="esc-header-actions">
            <a href="{{ route('escola.gestao.relatorio') }}" class="esc-btn">Relatório de progresso</a>
            <button class="esc-btn esc-btn-primary" id="btn-novo-modulo">+ Novo módulo</button>
        </div>
    </div>

    <div class="esc-admin-list">
        <table class="table esc-admin-table">
            <thead>
                <tr>
                    <th style="width:70px">Ordem</th>
                    <th>Módulo</th>
                    <th style="width:90px">Aulas</th>
                    <th style="width:90px">Status</th>
                    <th style="width:200px" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($modulos as $modulo)
                    <tr data-id="{{ $modulo->id }}"
                        data-titulo="{{ $modulo->titulo }}"
                        data-descricao="{{ $modulo->descricao }}"
                        data-ordem="{{ $modulo->ordem }}"
                        data-ativo="{{ $modulo->ativo ? 1 : 0 }}">
                        <td>{{ $modulo->ordem }}</td>
                        <td>
                            <strong>{{ $modulo->titulo }}</strong>
                            @if($modulo->descricao)
                                <div class="text-muted small">{{ \Illuminate\Support\Str::limit($modulo->descricao, 80) }}</div>
                            @endif
                        </td>
                        <td>{{ $modulo->aulas_count }}</td>
                        <td>
                            <span class="esc-tag {{ $modulo->ativo ? 'esc-tag-ok' : 'esc-tag-off' }}">
                                {{ $modulo->ativo ? 'Ativo' : 'Inativo' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('escola.gestao.aulas.index', $modulo->id) }}" class="esc-btn esc-btn-sm">Aulas</a>
                            <button class="esc-btn esc-btn-sm btn-editar-modulo">Editar</button>
                            <button class="esc-btn esc-btn-sm esc-btn-danger btn-excluir-modulo">Excluir</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Nenhum módulo criado ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

<!-- Modal módulo -->
<div class="modal fade esc-modal" id="modal-modulo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="form-modulo" enctype="multipart/form-data">
            <div class="esc-modal-header">
                <div class="esc-modal-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </div>
                <div class="esc-modal-titles">
                    <span class="esc-modal-eyebrow">Escola · Módulos</span>
                    <h5 class="esc-modal-title">Novo módulo</h5>
                    <p class="esc-modal-subtitle">Organize as aulas por tema de aprendizado</p>
                </div>
                <button type="button" class="esc-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="esc-modal-body">
                <input type="hidden" name="id" id="modulo-id">
                <div class="mb-3">
                    <label class="esc-form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="titulo" id="modulo-titulo" required maxlength="255" placeholder="Ex.: Portabilidade">
                </div>
                <div class="mb-3">
                    <label class="esc-form-label">Descrição</label>
                    <textarea class="form-control" name="descricao" id="modulo-descricao" rows="3" maxlength="2000" placeholder="Sobre o que é este módulo"></textarea>
                </div>
                <div class="row">
                    <div class="col-4 mb-3">
                        <label class="esc-form-label">Ordem</label>
                        <input type="number" class="form-control" name="ordem" id="modulo-ordem" value="0" min="0">
                    </div>
                    <div class="col-8 mb-3">
                        <label class="esc-form-label">Capa (imagem)</label>
                        <input type="file" class="form-control" name="capa" id="modulo-capa" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="ativo" id="modulo-ativo" value="1" checked>
                    <label class="form-check-label" for="modulo-ativo">Ativo</label>
                </div>
            </div>
            <div class="esc-modal-footer">
                <button type="button" class="esc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="esc-btn esc-btn-primary">Salvar módulo</button>
            </div>
        </form>
    </div>
</div>
@endsection
