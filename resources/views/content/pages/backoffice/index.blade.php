@extends('layouts/layoutMaster')

@section('title', 'Backoffice - Contratos')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@php
    $meses = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro',
    ];
@endphp

<!-- Page Scripts -->
@section('page-script')
    @vite(['resources/assets/js/backoffice.js'])
@endsection

@section('page-style')
    <style>
        tr.overdue {
            color: #dc3545 !important;
            font-weight: bold;
        }
    </style>


@section('content')
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

    <div class="card mb-3">
        <div class="card-body row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label">Status</label>
                <select id="status_filter" class="form-select">
                    <option value="">Todos</option>
                    <option value="IMPLANTADO">IMPLANTADO</option>
                    <option value="VENDA">VENDA</option>
                    <option value="ESTORNO">ESTORNO</option>
                    <option value="DECLINADO">DECLINADO</option>
                    <option value="ANALISE DOCUMENTO">ANALISE DOCUMENTO</option>
                    <option value="ANALISE OPERADORA">ANALISE OPERADORA</option>
                    <option value="PENDENCIA">PENDENCIA</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Mês</label>
                <select id="periodo_mes" class="form-select">
                    <option value="">Todos</option>
                    @foreach ($meses as $num => $nome)
                        <option value="{{ $num }}">{{ $nome }}</option>
                    @endforeach
                </select>
            </div>


            <div class="col-6 col-md-2">
                <label class="form-label">Ano</label>
                <select id="periodo_ano" class="form-select">
                    <option value="">Todos</option>
                    @for ($y = now()->year; $y >= now()->year - 6; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="col-12 col-md-4 d-flex gap-2">
                <button id="btn_limpar_filtro" class="btn btn-primary mb-3  " type="button">Limpar</button>
            </div>
        </div>
    </div>


    <!-- Ajax Sourced Server-side -->
    <div class="card mt-5">
        <h5 class="card-header">Contratos</h5>
        <div class="card-datatable text-nowrap">
            <table class="datatables-ajax table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Corretor</th>
                        <th>Nome Contrato</th>
                        <th>Status</th>
                        <th>Valor Contrato</th>
                        <th>Data Criação</th>
                        <th>Prazo</th>
                        <th>ultima Atualização</th>
                        <th>Ações</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    <!--/ Ajax Sourced Server-side -->


    <div class="modal fade" id="modalcomments" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">Alterar Status</h4>
                        <p>Selecione o status atual do contrato</p>
                    </div>
                    <form id="transferLead" class="row" action="{{ route('backoffice.alterStatusContract') }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="idSale" name="idSale" value="">
                        <div class="col">
                            <div class="form-floating form-floating-outline">
                                <select class="select2  form-select" id="label" name="tabulacao_id">
                                    <option value="">Selecione o Status</option>
                                    @foreach ($tabulacoes as $tabulation)
                                        <option value="{{ $tabulation->id }}">{{ strtoupper($tabulation->descricao) }}
                                        </option>
                                    @endforeach
                                </select>
                                <label for="ecommerce-product-name">Selecione o status</label>
                            </div>
                            <div id="proof-group-data-implantacao" class="mt-3" style="display: none;">
                                <label for="data_implantacao" class="form-label">Data de Implantação</label>
                                <input type="date" id="data_implantacao" name="data_implantacao" class="form-control"
                                    required>
                            </div>
                            <div id="proof-group-data-pendencia" class="mt-3" style="display: none;">
                                <label for="data_pendencia" class="form-label">Motivo da pendência</label>
                                <textarea id="data_pendencia" name="motivo_pendencia" class="form-control" rows="5"
                                    placeholder="Descreva o motivo da pendência..." required style="min-height: 120px; resize: vertical;"></textarea>
                            </div>

                            <div id="proof-group" class="mt-3" style="display: none;">
                                <label for="comprovante" class="form-label">Comprovante de Pagamento</label>
                                <input type="file" id="comprovante" name="comprovante" class="form-control"
                                    accept="image/*,application/pdf">
                            </div>
                            <div>
                                <button class="btn btn-danger btn--twitter mt-5">Alterar Status</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


@endsection
