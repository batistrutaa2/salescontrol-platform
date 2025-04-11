@extends('layouts/layoutMaster')

@section('title', 'Editar Usuario - Manager')

@section('page-script')
    @vite('resources/assets/js/form-input-group.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
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
            <div class="card mb-6">
                <h5 class="card-header">Editar Usuario</h5>
                <form action="{{ route('usuarios.updateUser') }}" method="POST">
                    @csrf
                    <div class="card-body demo-vertical-spacing demo-only-element">
                        <div class="input-group input-group-merge">
                            <div class="form-floating form-floating-outline">
                                <input type="text" class="form-control" id="basic-addon13" placeholder="john.doe"
                                    name="name" value="{{ $user->name }}" aria-describedby="basic-addon13" />
                                <label for="basic-addon13">Nome Completo</label>
                            </div>
                        </div>

                        <div class="input-group input-group-merge">
                            <div class="form-floating form-floating-outline">
                                <input type="email" class="form-control" id="basic-addon13" placeholder="john.doe"
                                    readonly name="email" value="{{ $user->email }}" aria-describedby="basic-addon13" />
                                <label for="basic-addon13">Email</label>
                            </div>
                        </div>

                        <div class="form-floating form-floating-outline">
                            <select class="form-select" id="floatingSelect" name="ativo"
                                aria-label="Floating label select example">
                                <option {{ $user->ativo === 'Y' ? 'selected' : '' }} value="Y">ATIVADO</option>
                                <option {{ $user->ativo === 'N' ? 'selected' : '' }} value="N">DESATIVADO</option>
                            </select>
                        </div>

                        <div class="form-password-toggle">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline">
                                    <input type="password" class="form-control" id="basic-default-password12" name="senha"
                                        required
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="basic-default-password12" />
                                    <label for="basic-default-password12">Senha</label>
                                </div>
                                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                            </div>
                        </div>

                        <div class="form-password-toggle">
                            <div class="input-group input-group-merge">
                                <div class="form-floating form-floating-outline">
                                    <input type="password" class="form-control" id="basic-default-password12" required
                                        name="senhaConfirma"
                                        placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                                        aria-describedby="basic-default-password12" />
                                    <label for="basic-default-password12">Confirma senha</label>
                                </div>
                                <span class="input-group-text cursor-pointer"><i class="ri-eye-off-line ri-20px"></i></span>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-primary mt-5 btn--twitter">Salvar Usuario</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    @endsection
