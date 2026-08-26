@extends('layouts/layoutMaster')

@section('title', 'Importar Mailing')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/dropzone/dropzone.scss',
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/scss/pages/importar-mailing.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/dropzone/dropzone.js',
        'resources/assets/vendor/libs/toastr/toastr.js',
        'resources/assets/vendor/libs/select2/select2.js'
    ])
@endsection

@section('page-script')
    @vite(['resources/assets/js/importmailing.js'])
@endsection

@section('content')
    <main class="mailing-import" aria-labelledby="mailing-import-title">
        <header class="mi-header">
            <div>
                <h1 id="mailing-import-title">Importar mailing</h1>
                <p>Analise a base, importe os leads novos e decida o destino dos duplicados.</p>
            </div>
            <span class="mi-pending-label d-none" id="pending-label">
                <i class="ri-time-line" aria-hidden="true"></i> Análise pendente
            </span>
        </header>

        <section class="mi-upload-panel" id="upload-panel" aria-labelledby="upload-title">
            <div class="mi-section-heading">
                <h2 id="upload-title">Selecione a base</h2>
                <p>Os leads novos entram no reservatório. Duplicados continuam seguindo a análise de segurança.</p>
            </div>

            <form enctype="multipart/form-data" id="mailing-import-form" novalidate>
                <div class="mi-form-grid">
                    <div class="form-floating form-floating-outline">
                        <input type="text" class="form-control" id="base" name="base" maxlength="255"
                            placeholder="Base Agosto" required>
                        <label for="base">Nome da base</label>
                    </div>
                    <div class="form-floating form-floating-outline">
                        <select id="mailing-layout" class="form-select" name="tipo_layout" required>
                            <option value="padrao">Padrão</option>
                            <option value="com_dependentes">Com dependentes</option>
                        </select>
                        <label for="mailing-layout">Layout do arquivo</label>
                    </div>
                </div>

                <div class="dropzone needsclick mi-dropzone" id="dropzone-basic">
                    <div class="dz-message needsclick">
                        <i class="ri-file-excel-2-line" aria-hidden="true"></i>
                        <strong>Arraste a planilha ou clique para selecionar</strong>
                        <span>Arquivos XLS ou XLSX de até 5 MB</span>
                    </div>
                    <div class="fallback"><input name="file" type="file" accept=".xls,.xlsx"></div>
                </div>

                <div class="mi-form-actions">
                    <button type="submit" class="btn btn-primary" id="analyze-button" disabled>
                        <i class="ri-search-eye-line me-1" aria-hidden="true"></i> Analisar arquivo
                    </button>
                    <span class="mi-form-hint">Nenhum dado será alterado nesta etapa.</span>
                </div>
            </form>
        </section>

        <section class="mi-analysis d-none" id="analysis-panel" aria-labelledby="analysis-title">
            <div class="mi-analysis-heading">
                <div>
                    <h2 id="analysis-title">Conferência da importação</h2>
                    <p id="analysis-description"></p>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm" id="discard-analysis">
                    <i class="ri-delete-bin-line me-1" aria-hidden="true"></i> Descartar análise
                </button>
            </div>

            <div class="mi-summary" aria-label="Resumo da análise">
                <div class="mi-summary-item is-total"><span>Total analisado</span><strong id="summary-total">0</strong></div>
                <div class="mi-summary-item is-new"><span>Novos</span><strong id="summary-new">0</strong></div>
                <div class="mi-summary-item is-duplicate"><span>Duplicados</span><strong id="summary-duplicate">0</strong></div>
                <div class="mi-summary-item is-invalid"><span>Inválidos</span><strong id="summary-invalid">0</strong></div>
                <div class="mi-summary-item is-resolved"><span>Tratados</span><strong id="summary-resolved">0</strong></div>
            </div>

            <div class="mi-primary-action" id="new-leads-action">
                <div>
                    <strong id="new-leads-title">Leads novos prontos para o reservatório</strong>
                    <span id="new-leads-copy">Nenhum vendedor será escolhido nesta etapa. Os duplicados continuarão disponíveis nesta tela.</span>
                </div>
                <button type="button" class="btn btn-primary" id="import-new-button">Enviar novos ao reservatório</button>
            </div>

            <div class="mi-duplicates-heading">
                <div>
                    <h3>Leads duplicados</h3>
                    <p>Somente leads descartados ou na preditiva, sem vendedor atual, podem ser reciclados.</p>
                </div>
                <div class="mi-search-wrap">
                    <i class="ri-search-line" aria-hidden="true"></i>
                    <input type="search" class="form-control" id="duplicate-search"
                        placeholder="Buscar por nome ou CPF" aria-label="Buscar leads duplicados">
                </div>
            </div>

            <div class="mi-bulk-bar" id="bulk-bar">
                <div class="mi-selection-count" aria-live="polite">
                    <strong id="selected-count">0</strong><span>selecionados</span>
                    <small><i class="ri-eraser-line" aria-hidden="true"></i> Histórico será limpo</small>
                </div>
                <div class="mi-bulk-fields">
                    <div class="form-floating form-floating-outline">
                        <select class="form-select" id="duplicate-destination">
                            <option value="VENDEDOR">Enviar para vendedor</option>
                            <option value="PREDITIVA">Enviar para preditiva</option>
                        </select>
                        <label for="duplicate-destination">Destino</label>
                    </div>
                    <div class="form-floating form-floating-outline mi-vendor-field">
                        <select class="form-select" id="duplicate-vendor">
                            <option value="">Manter vendedor atual</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ strtoupper($user->name) }}</option>
                            @endforeach
                        </select>
                        <label for="duplicate-vendor">Vendedor</label>
                    </div>
                    <div class="form-floating form-floating-outline mi-vendor-field">
                        <select class="form-select" id="duplicate-status">
                            <option value="">Manter status atual</option>
                            @foreach ($tabulacoes as $tabulacao)
                                <option value="{{ $tabulacao->id }}">{{ strtoupper($tabulacao->descricao) }}</option>
                            @endforeach
                        </select>
                        <label for="duplicate-status">Status do lead</label>
                    </div>
                    <button type="button" class="btn btn-primary" id="resolve-button" disabled>Aplicar destino</button>
                </div>
            </div>

            <div class="mi-table-wrap">
                <table class="table mi-table">
                    <thead>
                        <tr>
                            <th class="mi-check-column"><input class="form-check-input" type="checkbox"
                                id="select-all-duplicates" aria-label="Selecionar duplicados visíveis"></th>
                            <th>Lead identificado</th><th>Jornada atual</th><th>Responsável</th>
                            <th>Propostas vinculadas</th><th>Decisão</th>
                        </tr>
                    </thead>
                    <tbody id="duplicate-table-body"></tbody>
                </table>
                <div class="mi-empty d-none" id="duplicates-empty">
                    <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                    <strong>Nenhum duplicado pendente</strong>
                    <span>Todos os leads desta análise já receberam uma decisão.</span>
                </div>
            </div>

            <footer class="mi-table-footer">
                <span id="table-range">0 resultados</span>
                <div class="btn-group" aria-label="Paginação dos duplicados">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="previous-page">Anterior</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="next-page">Próxima</button>
                </div>
            </footer>
        </section>

        <div class="visually-hidden" id="mailing-live-region" aria-live="polite"></div>
    </main>
@endsection
