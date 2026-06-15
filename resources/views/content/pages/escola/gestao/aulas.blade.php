@extends('layouts/layoutMaster')

@section('title', 'Aulas — ' . $modulo->titulo)

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
        'resources/assets/js/escola-gestao-aulas.js',
    ])
@endsection

@section('content')
<div class="esc-page esc-gestao-page esc-aulas-gestao"
     data-modulo-id="{{ $modulo->id }}"
     data-store-aula-url="{{ route('escola.gestao.aulas.store', $modulo->id) }}"
     data-aula-base="{{ url('escola/gestao/aulas') }}"
     data-material-base="{{ url('escola/gestao/materiais') }}"
     data-presign-url="{{ route('escola.gestao.upload.presign') }}">

    <div class="esc-breadcrumb">
        <a href="{{ route('escola.gestao.index') }}">Gestão da Escola</a>
        <span>/</span>
        <strong>{{ $modulo->titulo }}</strong>
    </div>

    <div class="esc-header">
        <div class="esc-title-text">
            <h4>Aulas do módulo</h4>
            <span>{{ $modulo->titulo }}</span>
        </div>
        <div class="esc-header-actions">
            <button class="esc-btn esc-btn-primary" id="btn-nova-aula">+ Nova aula</button>
        </div>
    </div>

    <div class="esc-admin-list">
        <table class="table esc-admin-table">
            <thead>
                <tr>
                    <th style="width:70px">Ordem</th>
                    <th>Aula</th>
                    <th style="width:130px">Vídeo</th>
                    <th style="width:90px">Materiais</th>
                    <th style="width:90px">Status</th>
                    <th style="width:280px" class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($aulas as $aula)
                    <tr data-id="{{ $aula->id }}"
                        data-titulo="{{ $aula->titulo }}"
                        data-descricao="{{ $aula->descricao }}"
                        data-ordem="{{ $aula->ordem }}"
                        data-ativo="{{ $aula->ativo ? 1 : 0 }}"
                        data-materiais='@json($aula->materiais->map(fn($m) => ["id" => $m->id, "titulo" => $m->titulo ?? $m->nome_original]))'>

                        <td>{{ $aula->ordem }}</td>
                        <td><strong>{{ $aula->titulo }}</strong></td>
                        <td>
                            @if($aula->video_path)
                                <span class="esc-tag esc-tag-ok">Enviado</span>
                            @else
                                <span class="esc-tag esc-tag-off">Sem vídeo</span>
                            @endif
                        </td>
                        <td>{{ $aula->materiais_count }}</td>
                        <td>
                            <span class="esc-tag {{ $aula->ativo ? 'esc-tag-ok' : 'esc-tag-off' }}">
                                {{ $aula->ativo ? 'Ativa' : 'Inativa' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button class="esc-btn esc-btn-sm btn-video-aula">Vídeo</button>
                            <button class="esc-btn esc-btn-sm btn-materiais-aula">Materiais</button>
                            <button class="esc-btn esc-btn-sm btn-editar-aula">Editar</button>
                            <button class="esc-btn esc-btn-sm esc-btn-danger btn-excluir-aula">Excluir</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma aula criada ainda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal aula -->
<div class="modal fade esc-modal" id="modal-aula" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="form-aula">
            <div class="esc-modal-header">
                <div class="esc-modal-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2" ry="2"/></svg>
                </div>
                <div class="esc-modal-titles">
                    <span class="esc-modal-eyebrow">Escola · Aulas</span>
                    <h5 class="esc-modal-title">Nova aula</h5>
                    <p class="esc-modal-subtitle">Defina o conteúdo desta aula</p>
                </div>
                <button type="button" class="esc-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="esc-modal-body">
                <input type="hidden" name="id" id="aula-id">
                <div class="mb-3">
                    <label class="esc-form-label">Título <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="titulo" id="aula-titulo" required maxlength="255" placeholder="Ex.: Como abordar a portabilidade">
                </div>
                <div class="mb-3">
                    <label class="esc-form-label">Descrição</label>
                    <textarea class="form-control" name="descricao" id="aula-descricao" rows="4" maxlength="5000" placeholder="Resumo / pontos-chave da aula"></textarea>
                </div>
                <div class="row">
                    <div class="col-4 mb-3">
                        <label class="esc-form-label">Ordem</label>
                        <input type="number" class="form-control" name="ordem" id="aula-ordem" value="0" min="0">
                    </div>
                    <div class="col-8 d-flex align-items-end mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="aula-ativo" checked>
                            <label class="form-check-label" for="aula-ativo">Ativa</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="esc-modal-footer">
                <button type="button" class="esc-btn" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="esc-btn esc-btn-primary">Salvar aula</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal vídeo -->
<div class="modal fade esc-modal" id="modal-video" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="esc-modal-header">
                <div class="esc-modal-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div class="esc-modal-titles">
                    <span class="esc-modal-eyebrow">Escola · Vídeo</span>
                    <h5 class="esc-modal-title">Enviar vídeo da aula</h5>
                    <p class="esc-modal-subtitle">O envio vai direto para o armazenamento seguro</p>
                </div>
                <button type="button" class="esc-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="esc-modal-body">
                <input type="hidden" id="video-aula-id">
                <label class="esc-form-label">Arquivo de vídeo</label>
                <input type="file" class="form-control mb-2" id="video-file" accept="video/mp4,video/webm,video/quicktime">
                <p class="esc-modal-subtitle mb-3">Formatos: MP4, WebM ou MOV · Tamanho máximo: 2&nbsp;GB.</p>
                <div class="esc-upload-progress d-none" id="video-progress-wrap">
                    <div class="esc-progress flex-grow-1"><div class="esc-progress-bar" id="video-progress-bar" style="width:0%"></div></div>
                    <span class="small" id="video-progress-text">0%</span>
                </div>
            </div>
            <div class="esc-modal-footer">
                <button type="button" class="esc-btn" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="esc-btn esc-btn-primary" id="btn-enviar-video">Enviar vídeo</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal materiais -->
<div class="modal fade esc-modal" id="modal-materiais" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="esc-modal-header">
                <div class="esc-modal-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                </div>
                <div class="esc-modal-titles">
                    <span class="esc-modal-eyebrow">Escola · Materiais</span>
                    <h5 class="esc-modal-title">Materiais de apoio</h5>
                    <p class="esc-modal-subtitle">Anexe PDFs para download do vendedor</p>
                </div>
                <button type="button" class="esc-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="esc-modal-body">
                <input type="hidden" id="material-aula-id">
                <ul class="esc-materiais-list" id="materiais-lista"></ul>
                <label class="esc-form-label mt-2">Adicionar PDF</label>
                <input type="file" class="form-control mb-3" id="material-file" accept="application/pdf">
                <button type="button" class="esc-btn esc-btn-primary" id="btn-add-material">Enviar material</button>
            </div>
        </div>
    </div>
</div>
@endsection
