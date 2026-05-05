@extends('layouts/layoutMaster')

@section('title', 'Meus Estornos | ' . auth()->user()->name)

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/meus-estornos.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/meus-estornos.js'])
@endsection

@section('content')
<div class="me-wrapper">

    <div class="me-page-header">
        <div class="me-title-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 14 4 9l5-5"/>
                <path d="M4 9h10.5a5.5 5.5 0 0 1 5.5 5.5v0a5.5 5.5 0 0 1-5.5 5.5H11"/>
            </svg>
        </div>
        <div class="me-title-text">
            <span class="me-eyebrow">Vendas devolvidas</span>
            <h1>Meus Estornos</h1>
            <p>Vendas que o backoffice devolveu para correção. Ajuste os dados e reenvie.</p>
        </div>
        <div class="me-counter">
            <span class="me-counter-value js-counter-total">0</span>
            <span class="me-counter-label">aguardando<br>correção</span>
        </div>
    </div>

    @if (session('status') === 'success')
        <div class="me-flash me-flash-success">
            {{ session('message') }}
        </div>
    @elseif (session('status') === 'error')
        <div class="me-flash me-flash-error">
            {{ session('message') }}
        </div>
    @endif

    <div class="me-list js-estornos-list">
        <div class="me-empty js-empty d-none">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <h3>Nenhuma venda estornada</h3>
            <p>Quando o backoffice devolver uma venda, ela aparecerá aqui.</p>
        </div>
    </div>

    <div class="me-skeleton js-skeleton">
        @for ($i = 0; $i < 3; $i++)
            <div class="me-skeleton-card"></div>
        @endfor
    </div>

</div>
@endsection
