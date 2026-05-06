@extends('layouts/layoutMaster')

@section('title', 'Cadastro de usuarios')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/toastr/toastr.scss',
        'resources/assets/vendor/libs/animate-css/animate.scss',
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss',
        'resources/assets/vendor/libs/@form-validation/form-validation.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/scss/pages/usuarios.scss'
    ])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/usuarios.js')
@endsection

@section('content')

<div class="usuarios-wrapper">

    <div id="contas-root" data-save-conta-base="{{ route('contasPagamento.save', ['user' => 'USER_ID']) }}"></div>

    @if (session('status') == 'success')
        <div class="alert-modern alert-success d-flex align-items-center gap-2" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert-modern alert-danger d-flex align-items-center gap-2" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <line x1="15" y1="9" x2="9" y2="15"/>
                <line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
            {{ session('message') }}
        </div>
    @endif

    {{-- Header Section --}}
    <div class="usr-header">
        <div class="header-content">
            <div class="header-text">
                <span class="greeting-label">Gerenciamento</span>
                <h1 class="main-title">Usuarios</h1>
                <p class="subtitle">Gerencie os usuarios do sistema, seus acessos e contas bancarias</p>
            </div>
            <div class="header-actions">
                <button type="button" class="btn-dash btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasEcommerceCustomerAdd">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                    Criar Usuario
                </button>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="kpi-grid" id="usuarios-kpis">
        <div class="kpi-card kpi-primary">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Total de Usuarios</span>
                <h2 class="kpi-value" id="kpi-total">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Usuarios Ativos</span>
                <h2 class="kpi-value" id="kpi-ativos">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Usuarios Inativos</span>
                <h2 class="kpi-value" id="kpi-inativos">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </h2>
            </div>
            <div class="kpi-glow"></div>
        </div>

        <div class="kpi-card kpi-info">
            <div class="kpi-icon-wrapper">
                <div class="kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="8.5" cy="7" r="4"/>
                        <line x1="20" y1="8" x2="20" y2="14"/>
                        <line x1="23" y1="11" x2="17" y2="11"/>
                    </svg>
                </div>
                <div class="kpi-pulse"></div>
            </div>
            <div class="kpi-content">
                <span class="kpi-label">Novos este Mes</span>
                <h2 class="kpi-value" id="kpi-novos">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </h2>
            </div>
            <div class="kpi-glow"></div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="table-card">
        <div class="table-header">
            <div class="table-title-group">
                <div class="table-icon usuarios">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <div>
                    <h3 class="table-title">Lista de Usuarios</h3>
                    <span class="table-subtitle">Todos os usuarios cadastrados no sistema</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="status-filters" id="status-filter-buttons">
                    <button type="button" class="filter-btn active" data-status="all">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        Todos
                    </button>
                    <button type="button" class="filter-btn" data-status="Y">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        Ativos
                    </button>
                    <button type="button" class="filter-btn" data-status="N">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="15" y1="9" x2="9" y2="15"/>
                            <line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                        Inativos
                    </button>
                </div>
                <div class="table-badge usuarios">
                    <span id="badge-total-count">0</span> registros
                </div>
            </div>
        </div>

        <div class="table-body">
            <div class="table-responsive">
                <table class="datatables-customers custom-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>NOME</th>
                            <th>EMAIL</th>
                            <th>FUNCAO</th>
                            <th>STATUS</th>
                            <th class="text-nowrap">CRIADO EM</th>
                            <th class="text-nowrap">ACOES</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Offcanvas: Criar Usuario --}}
    <div class="offcanvas offcanvas-end offcanvas-modern" tabindex="-1" id="offcanvasEcommerceCustomerAdd"
        aria-labelledby="offcanvasEcommerceCustomerAddLabel">
        <div class="offcanvas-header">
            <h5 id="offcanvasEcommerceCustomerAddLabel" class="offcanvas-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="8.5" cy="7" r="4"/>
                    <line x1="20" y1="8" x2="20" y2="14"/>
                    <line x1="23" y1="11" x2="17" y2="11"/>
                </svg>
                Criar Usuario
            </h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form method="POST" class="ecommerce-customer-add pt-0" id="eCommerceCustomerAddForm">
                @csrf
                <div class="ecommerce-customer-add-basic mb-6">
                    <h6 class="section-title">Informacoes basicas</h6>
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="text" class="form-control" id="ecommerce-customer-add-name"
                            placeholder="John Doe" name="customerName" aria-label="John Doe" />
                        <label for="ecommerce-customer-add-name">Nome*</label>
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="email" id="ecommerce-customer-add-email" class="form-control"
                            placeholder="john.doe@example.com" aria-label="john.doe@example.com" name="customerEmail" />
                        <label for="ecommerce-customer-add-email">Email*</label>
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="text" id="ecommerce-customer-add-whatsapp" class="form-control phone-mask"
                            placeholder="(11) 99999-8888" aria-label="(11) 99999-8888" name="customerWhats" />
                        <label for="ecommerce-customer-add-whatsapp">WhatsApp (recebe o resumo diário se for ADMINISTRATIVO/DEVELOPER/SUPERVISOR)</label>
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        <select id="tipo_user" class="form-select" name="user_role_id">
                            <option value="1">VENDEDOR</option>
                            <option value="2">ADMINISTRATIVO</option>
                            <option value="3">BACKOFFICE</option>
                            @if ($tipo_usuario === 'DEVELOPER')
                                <option value="4">DEVELOPER</option>
                            @endif
                            <option value="5">SUPERVISOR</option>
                        </select>
                        <label for="tipo_user">Tipo de Acesso</label>
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        @if (is_iterable($companies) && count($companies) > 0)
                            <select id="empresas" class="form-select" {{ count($companies) == 1 ? 'disabled' : '' }}
                                name="empresa_id">
                                @foreach ($companies as $companie)
                                    <option value="{{ $companie->id }}" {{ count($companies) == 1 ? 'selected' : '' }}>
                                        {{ $companie->nome_fantasia }}</option>
                                @endforeach
                            </select>
                            <label for="empresas">Empresa</label>
                        @elseif(is_object($companies))
                            <select id="empresas" class="form-select" disabled name="empresa_id">
                                <option value="{{ $companies->id }}" selected>{{ $companies->nome_fantasia }}</option>
                            </select>
                            <label for="empresas">Empresa</label>
                        @else
                            <select id="empresas" class="form-select" disabled name="empresa_id">
                                <option value="">Nenhuma empresa disponivel</option>
                            </select>
                            <label for="empresas">Empresa</label>
                        @endif
                    </div>
                    <div class="form-floating form-floating-outline mb-5">
                        <input type="password" id="ecommerce-customer-add-password" class="form-control"
                            placeholder="******" name="customerPassword" />
                        <label for="ecommerce-customer-add-password">Senha*</label>
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <button type="submit" class="btn-dash btn-primary data-submit submit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Cadastrar
                    </button>
                    <button type="reset" class="btn-dash btn-outline-danger" data-bs-dismiss="offcanvas">Fechar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Nova conta bancaria --}}
    <div class="modal fade modal-modern" id="modalConta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formConta" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                            <line x1="1" y1="10" x2="23" y2="10"/>
                        </svg>
                        Nova conta bancaria
                        <small class="d-block fs-6">para <span id="conta_user_name">-</span></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" id="conta_user_id">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Descricao (opcional)</label>
                            <input type="text" class="form-control" id="conta_descricao" placeholder="Ex.: Conta principal, PJ, etc.">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Banco</label>
                            <input type="text" class="form-control" id="conta_banco" placeholder="Ex.: Nubank, Itau...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Chave PIX</label>
                            <input type="text" class="form-control" id="conta_pix" placeholder="E-mail, CPF/CNPJ ou chave aleatoria">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Agencia</label>
                            <input type="text" class="form-control" id="conta_agencia" placeholder="0000">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Conta</label>
                            <input type="text" class="form-control" id="conta_conta" placeholder="000000">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Digito</label>
                            <input type="text" class="form-control" id="conta_digito" placeholder="0">
                        </div>
                        <div class="col-md-12">
                            <div class="alert alert-info mb-0">
                                Esta conta sera marcada como <strong>padrao</strong> (default = 1). Caso exista uma conta padrao anterior para este usuario, o backend deve desmarcar.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-dash btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-dash btn-success">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        Salvar conta
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@endsection
