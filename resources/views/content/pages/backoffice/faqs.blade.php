@extends('layouts/layoutMaster')

@section('title', 'FAQs por Operadora')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/faqs.scss'])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/faqs.js'])
@endsection

@section('content')
<div class="faq-wrapper">
    <!-- Page Header -->
    <div class="faq-page-header">
        <div class="faq-header-content">
            <div class="faq-header-text">
                <span class="faq-greeting-label">Back-office</span>
                <h1 class="faq-main-title">FAQs por Operadora</h1>
                <p class="faq-subtitle">Gerencie perguntas e respostas frequentes para consulta dos vendedores</p>
            </div>
            <div class="faq-header-actions">
                <button type="button" class="faq-btn-primary" id="btnNovoFaq">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Novo FAQ
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="faq-filter-bar">
        <div class="faq-filter-item">
            <span class="faq-filter-label">Operadora</span>
            <select class="faq-filter-select" id="filtro-operadora">
                <option value="">Todas as operadoras</option>
                @foreach ($operadoras as $operadora)
                    <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table Card -->
    <div class="faq-table-card">
        <div class="faq-table-header">
            <div class="faq-table-title-group">
                <div class="faq-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                </div>
                <div>
                    <h3 class="faq-table-title">Perguntas Frequentes</h3>
                    <span class="faq-table-subtitle">Lista de FAQs cadastrados</span>
                </div>
            </div>
        </div>
        <div class="faq-table-body">
            <table class="custom-table" id="tabela-faqs" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Operadora</th>
                        <th>Título</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar/Editar FAQ -->
<div class="faq-wrapper">
    <div class="modal fade" id="faqModal" tabindex="-1" aria-labelledby="faqModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="formFaq" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="faqModalLabel">Cadastrar Novo FAQ</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="faq_operadora_id" class="form-label">Operadora</label>
                            <select class="form-select" id="faq_operadora_id" name="operadora_id" required>
                                <option value="">Selecione...</option>
                                @foreach ($operadoras as $operadora)
                                    <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="faq_titulo" class="form-label">Título / Pergunta</label>
                            <input type="text" class="form-control" id="faq_titulo" name="titulo" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="faq_resposta" class="form-label">Resposta</label>
                            <textarea class="form-control" id="faq_resposta" name="resposta" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="faq_status" class="form-label">Status</label>
                                <select class="form-select" id="faq_status" name="status">
                                    <option value="Y">Ativo</option>
                                    <option value="N">Inativo</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="faq_ordem" class="form-label">Ordem</label>
                                <input type="number" class="form-control" id="faq_ordem" name="ordem" value="0" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="faq-btn-primary">Salvar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
