@extends('layouts/layoutMaster')

@section('title', 'Relatório de Progresso — Escola')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/escola.scss')
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
    ])
@endsection

@section('page-script')
    @vite('resources/assets/js/escola-relatorio.js')
@endsection

@section('content')
<div class="esc-page esc-gestao-page esc-relatorio-page"
     data-url="{{ route('escola.gestao.relatorio.data') }}">

    <div class="esc-breadcrumb">
        <a href="{{ route('escola.gestao.index') }}">Gestão da Escola</a>
        <span>/</span>
        <strong>Relatório de progresso</strong>
    </div>

    <div class="esc-header">
        <div class="esc-title-text">
            <h4>Relatório de progresso</h4>
            <span>Acompanhe a evolução do time nos treinamentos.</span>
        </div>
    </div>

    <div class="esc-filtros">
        <div>
            <label class="form-label">Módulo</label>
            <select class="form-select esc-select2" id="filtro-modulo">
                <option value="">Todos os módulos</option>
                @foreach($modulos as $m)
                    <option value="{{ $m->id }}">{{ $m->titulo }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">Vendedor</label>
            <select class="form-select esc-select2" id="filtro-vendedor">
                <option value="">Todos os vendedores</option>
                @foreach($vendedores as $v)
                    <option value="{{ $v->id }}">{{ $v->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <table class="table" id="tabela-relatorio" style="width:100%">
                <thead>
                    <tr>
                        <th>Vendedor</th>
                        <th>Concluídas</th>
                        <th>Iniciadas</th>
                        <th>Total de aulas</th>
                        <th>Progresso</th>
                        <th>Última atividade</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
