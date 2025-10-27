@extends('layouts/layoutMaster')

@section('title', 'Cadastro de usuarios')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/toastr/toastr.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/toastr/toastr.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/usuarios.js')
@endsection

@section('content')

    <div id="contas-root" data-save-conta-base="{{ route('contasPagamento.save', ['user' => 'USER_ID']) }}">
    </div>

    @if (session('status') == 'success')
        <div class="alert alert-solid-success d-flex align-items-center" role="alert">
            <span class="alert-icon rounded">
                <i class="ri-checkbox-circle-line ri-22px"></i>
            </span>
            {{ session('message') }}
        </div>
    @elseif(session('status') == 'error')
        <div class="alert alert-danger">
            {{ session('message') }}
        </div>
    @endif

    <!-- KPIs -->
    <div class="row g-4 mb-6" id="usuarios-kpis">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Total de Usuários</h6>
                            <h3 class="mb-0" id="kpi-total">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-primary rounded">
                                <i class="ri-group-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Usuários Ativos</h6>
                            <h3 class="mb-0 text-success" id="kpi-ativos">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-success rounded">
                                <i class="ri-user-follow-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Usuários Inativos</h6>
                            <h3 class="mb-0 text-secondary" id="kpi-inativos">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-secondary rounded">
                                <i class="ri-user-unfollow-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-1">Novos este Mês</h6>
                            <h3 class="mb-0 text-info" id="kpi-novos">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </h3>
                        </div>
                        <div class="avatar">
                            <div class="avatar-initial bg-label-info rounded">
                                <i class="ri-user-add-line ri-26px"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- customers List Table -->
    <div class="card">
        <div class="card-header pb-0">
            <h5 class="card-title mb-3">Lista de Usuários</h5>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div class="d-flex gap-2" id="status-filter-buttons">
                    <button type="button" class="btn btn-sm btn-primary" data-status="all">
                        <i class="ri-user-line me-1"></i>Todos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" data-status="Y">
                        <i class="ri-user-follow-line me-1"></i>Ativos
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-status="N">
                        <i class="ri-user-unfollow-line me-1"></i>Inativos
                    </button>
                </div>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="datatables-customers table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>NOME</th>
                        <th>EMAIL</th>
                        <th>FUNÇÃO</th>
                        <th>STATUS</th>
                        <th class="text-nowrap">CRIADO EM</th>
                        <th class="text-nowrap">AÇÕES</th>
                    </tr>
                </thead>
            </table>
        </div>
        <!-- Offcanvas to add new customer -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEcommerceCustomerAdd"
            aria-labelledby="offcanvasEcommerceCustomerAddLabel">
            <div class="offcanvas-header border-bottom">
                <h5 id="offcanvasEcommerceCustomerAddLabel" class="offcanvas-title">Criar Usuario</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0">
                <form method="POST" class="ecommerce-customer-add pt-0" id="eCommerceCustomerAddForm">
                    @csrf
                    <div class="ecommerce-customer-add-basic mb-6">
                        <h6 class="mb-5">Informações basicas</h6>
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
                                    <option value="">Nenhuma empresa disponível</option>
                                </select>
                                <label for="empresas">Empresa</label>
                            @endif
                        </div>
                        <div class="form-floating form-floating-outline mb-5">
                            <input type="password" id="ecommerce-customer-add-email" class="form-control"
                                placeholder="******" name="customerPassword" />
                            <label for="ecommerce-customer-add-email">Senha*</label>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit submit">Cadastrar</button>
                        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Fechar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    {{-- Modal: Nova conta bancária --}}
<div class="modal fade" id="modalConta" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formConta" class="modal-content">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">
          Nova conta bancária <small class="text-muted d-block fs-6">para <span id="conta_user_name">—</span></small>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="conta_user_id">

        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Descrição (opcional)</label>
            <input type="text" class="form-control" id="conta_descricao" placeholder="Ex.: Conta principal, PJ, etc.">
          </div>
          <div class="col-md-4">
            <label class="form-label">Banco</label>
            <input type="text" class="form-control" id="conta_banco" placeholder="Ex.: Nubank, Itaú…">
          </div>
          <div class="col-md-4">
            <label class="form-label">Chave PIX</label>
            <input type="text" class="form-control" id="conta_pix" placeholder="E-mail, CPF/CNPJ ou chave aleatória">
          </div>

          <div class="col-md-3">
            <label class="form-label">Agência</label>
            <input type="text" class="form-control" id="conta_agencia" placeholder="0000">
          </div>
          <div class="col-md-5">
            <label class="form-label">Conta</label>
            <input type="text" class="form-control" id="conta_conta" placeholder="000000">
          </div>
          <div class="col-md-2">
            <label class="form-label">Dígito</label>
            <input type="text" class="form-control" id="conta_digito" placeholder="0">
          </div>
          <div class="col-md-12">
            <div class="alert alert-info mb-0">
              Esta conta será marcada como <strong>padrão</strong> (default = 1). Caso exista uma conta padrão anterior para este usuário, o backend deve desmarcá-la.
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">
          <i class="ri-bank-card-line me-1"></i> Salvar conta
        </button>
      </div>
    </form>
  </div>
</div>


@endsection
