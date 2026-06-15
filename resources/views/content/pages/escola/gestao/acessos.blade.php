@extends('layouts/layoutMaster')

@section('title', 'Liberar Acesso — Escola')

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
        'resources/assets/js/escola-acessos.js',
    ])
@endsection

@php
    $roleLabels = [
        1 => 'Vendedor',
        2 => 'Administrativo',
        3 => 'Backoffice',
        4 => 'Developer',
        5 => 'Supervisor',
        6 => 'Benefícios',
    ];
@endphp

@section('content')
<div class="esc-page esc-gestao-page esc-acessos-page"
     data-toggle-base="{{ url('escola/gestao/acessos') }}">

    <div class="esc-breadcrumb">
        <a href="{{ route('escola.gestao.index') }}">Gestão da Escola</a>
        <span>/</span>
        <strong>Liberar Acesso</strong>
    </div>

    <div class="esc-header">
        <div class="esc-header-main">
            <div class="esc-title-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
            </div>
            <div class="esc-title-text">
                <h4>Liberar Acesso</h4>
                <span>Escolha quais usuários podem acessar a Escola LK Brokers.</span>
            </div>
        </div>

        <form class="esc-search" method="GET" action="{{ route('escola.gestao.acessos') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="q" value="{{ $busca }}" placeholder="Buscar por nome ou e-mail...">
            @if($busca !== '')
                <a href="{{ route('escola.gestao.acessos') }}" class="esc-search-clear" title="Limpar">&times;</a>
            @endif
        </form>
    </div>

    <div class="esc-acessos-resumo">
        <span class="esc-tag esc-tag-ok">{{ $totalLiberados }} com acesso</span>
        <span class="esc-tag esc-tag-off">{{ $usuarios->count() - $totalLiberados }} sem acesso</span>
    </div>

    <div class="esc-admin-list mt-3">
        <table class="table esc-admin-table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th style="width:150px">Perfil</th>
                    <th style="width:160px" class="text-center">Acesso à Escola</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $u)
                    <tr data-id="{{ $u->id }}" class="{{ $u->ativo === 'N' ? 'esc-row-inativo' : '' }}">
                        <td>
                            <strong>{{ $u->name }}</strong>
                            <div class="text-muted small">{{ $u->email }}</div>
                        </td>
                        <td>
                            <span class="esc-tag esc-tag-new">{{ $roleLabels[$u->user_role_id] ?? 'Usuário' }}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-flex justify-content-center">
                                <input class="form-check-input esc-toggle-acesso" type="checkbox"
                                       data-id="{{ $u->id }}" {{ $u->escola_habilitada ? 'checked' : '' }}>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
