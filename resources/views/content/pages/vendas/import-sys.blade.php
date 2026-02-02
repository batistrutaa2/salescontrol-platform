@extends('layouts/layoutMaster')

@section('title', 'Importar Vendas - Sistema Legado')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/import-vendas-sys.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Importar Vendas do Sistema Legado (SYS)</h5>
                <a href="{{ route('vendas.import-sys.template') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-download me-1"></i> Baixar Template
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-4">
                    <h6 class="alert-heading mb-2">Instrucoes de Importacao</h6>
                    <ul class="mb-0">
                        <li>O arquivo deve estar no formato <strong>CSV</strong> com separador <strong>ponto e virgula (;)</strong></li>
                        <li>As colunas obrigatorias sao: <code>PropostaNumero</code>, <code>TxtOperadora</code>, <code>Segurado</code>, <code>Documento</code></li>
                        <li>As vendas serao importadas com status <strong>IMPLANTADO</strong></li>
                        <li>Propostas duplicadas serao ignoradas</li>
                        <li><strong>Operadoras, Planos e Corretores</strong> serao criados automaticamente se nao existirem</li>
                    </ul>
                </div>

                <form id="formImportVendas" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label for="arquivo" class="form-label">Arquivo CSV</label>
                        <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv,.txt" required>
                        <div class="form-text">Tamanho maximo: 50MB</div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="btnImportar">
                        <i class="ti ti-upload me-1"></i> Importar Vendas
                    </button>
                </form>
            </div>
        </div>

        <!-- Resultado da Importacao -->
        <div class="card mb-4 d-none" id="cardResultado">
            <div class="card-header">
                <h5 class="mb-0">Resultado da Importacao</h5>
            </div>
            <div class="card-body">
                <!-- Resumo principal -->
                <div class="row mb-3">
                    <div class="col-6 col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalImportados">0</h3>
                                <small>Importados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalDuplicados">0</h3>
                                <small>Duplicados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalErros">0</h3>
                                <small>Erros</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalWarnings">0</h3>
                                <small>Avisos</small>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Itens criados automaticamente -->
                <div class="row mb-4">
                    <div class="col-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalOperadoras">0</h3>
                                <small>Operadoras Criadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalPlanos">0</h3>
                                <small>Planos Criados</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card text-white" style="background-color: #6f42c1;">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-0" id="totalCorretores">0</h3>
                                <small>Corretores Criados</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alerta de itens criados automaticamente -->
                <div class="alert alert-primary d-none mb-4" id="alertCriados">
                    <h6 class="alert-heading mb-2"><i class="ti ti-info-circle me-1"></i> Itens Criados Automaticamente</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Operadoras:</strong>
                            <ul class="mb-0 small" id="listaOperadorasCriadas"></ul>
                        </div>
                        <div class="col-md-4">
                            <strong>Planos:</strong>
                            <ul class="mb-0 small" id="listaPlanosCriados"></ul>
                        </div>
                        <div class="col-md-4">
                            <strong>Corretores:</strong>
                            <ul class="mb-0 small" id="listaCorretoresCriados"></ul>
                        </div>
                    </div>
                </div>

                <!-- Tabs para detalhes -->
                <ul class="nav nav-tabs" id="resultTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="importados-tab" data-bs-toggle="tab" data-bs-target="#importados" type="button" role="tab">
                            Importados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="duplicados-tab" data-bs-toggle="tab" data-bs-target="#duplicados" type="button" role="tab">
                            Duplicados
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="erros-tab" data-bs-toggle="tab" data-bs-target="#erros" type="button" role="tab">
                            Erros
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="warnings-tab" data-bs-toggle="tab" data-bs-target="#warnings" type="button" role="tab">
                            Avisos
                        </button>
                    </li>
                </ul>
                <div class="tab-content border border-top-0 p-3" id="resultTabsContent">
                    <div class="tab-pane fade show active" id="importados" role="tabpanel">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyImportados"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="duplicados" role="tabpanel">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyDuplicados"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="erros" role="tabpanel">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyErros"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="warnings" role="tabpanel">
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm table-striped">
                                <thead class="sticky-top bg-white">
                                    <tr>
                                        <th>Mensagem</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyWarnings"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapeamento de Colunas -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Mapeamento de Colunas do CSV</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Coluna CSV (SYS)</th>
                                <th>Campo SalesControl</th>
                                <th>Observacao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>PropostaNumero</code></td>
                                <td>numero_proposta</td>
                                <td>Identificador unico da venda</td>
                            </tr>
                            <tr>
                                <td><code>Segurado</code></td>
                                <td>nome_contrato</td>
                                <td>Nome do cliente/empresa</td>
                            </tr>
                            <tr>
                                <td><code>Documento</code></td>
                                <td>cpf_cnpj</td>
                                <td>CPF ou CNPJ (apenas numeros)</td>
                            </tr>
                            <tr>
                                <td><code>TelCelular</code></td>
                                <td>telefone1</td>
                                <td>Telefone celular</td>
                            </tr>
                            <tr>
                                <td><code>TelComercial</code></td>
                                <td>telefone2</td>
                                <td>Telefone comercial</td>
                            </tr>
                            <tr>
                                <td><code>Email</code></td>
                                <td>email</td>
                                <td>E-mail do contato</td>
                            </tr>
                            <tr>
                                <td><code>VlProducao</code></td>
                                <td>valor_contrato</td>
                                <td>Formato: R$ X.XXX,XX</td>
                            </tr>
                            <tr>
                                <td><code>QtdeBeneficiarios</code></td>
                                <td>vidas</td>
                                <td>Numero de vidas</td>
                            </tr>
                            <tr>
                                <td><code>DtVigencia</code></td>
                                <td>data_vigencia</td>
                                <td>Formato: dd/mm/yyyy HH:mm</td>
                            </tr>
                            <tr>
                                <td><code>DtImplantacao</code></td>
                                <td>data_implantacao</td>
                                <td>Formato: dd/mm/yyyy HH:mm</td>
                            </tr>
                            <tr>
                                <td><code>TxtOperadora</code></td>
                                <td>operadora / operadora_id</td>
                                <td>Busca ou cria automaticamente se nao existir</td>
                            </tr>
                            <tr>
                                <td><code>TxtCorretor</code></td>
                                <td>user_id</td>
                                <td>Busca ou cria automaticamente como VENDEDOR</td>
                            </tr>
                            <tr>
                                <td><code>TxtModalidade</code></td>
                                <td>tipo_contrato</td>
                                <td>Pme = PME, Adesao = ADESAO</td>
                            </tr>
                            <tr>
                                <td><code>TxtPlano</code></td>
                                <td>nome_plano / plano_id</td>
                                <td>Busca ou cria automaticamente vinculado a operadora</td>
                            </tr>
                            <tr>
                                <td><code>CoParticipacao</code></td>
                                <td>coparticipacao</td>
                                <td>True = Y, False = N</td>
                            </tr>
                            <tr>
                                <td><code>DentalIncluso</code></td>
                                <td>plano_dental</td>
                                <td>True = SIM, False = NAO</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
