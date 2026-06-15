@extends('layouts/layoutMaster')

@section('title', 'Escola LK Brokers')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/escola.scss')
@endsection

@section('content')
<div class="esc-page">

    <div class="esc-header">
        <div class="esc-header-main">
            <div class="esc-title-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
            </div>
            <div class="esc-title-text">
                <h4>Escola LK Brokers</h4>
                <span>Evolua com nossos treinamentos: portabilidade, negociação, produto e muito mais.</span>
            </div>
        </div>

        <form class="esc-search" method="GET" action="{{ route('escola.index') }}">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input type="text" name="q" value="{{ $busca }}" placeholder="Buscar módulo ou aula...">
            @if($busca !== '')
                <a href="{{ route('escola.index') }}" class="esc-search-clear" title="Limpar">&times;</a>
            @endif
        </form>
    </div>

    @if($modulos->isEmpty())
        <div class="esc-empty">
            <p>Nenhum módulo disponível {{ $busca !== '' ? 'para a sua busca' : 'no momento' }}.</p>
        </div>
    @else
        <div class="esc-grid">
            @foreach($modulos as $modulo)
                <a href="{{ route('escola.modulos.show', $modulo->id) }}" class="esc-card">
                    <div class="esc-card-cover">
                        @if($modulo->capa_url)
                            <img src="{{ $modulo->capa_url }}" alt="{{ $modulo->titulo }}">
                        @else
                            <div class="esc-card-cover-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polygon points="5 3 19 12 5 21 5 3"/>
                                </svg>
                            </div>
                        @endif
                        @if($modulo->percentual_modulo >= 100)
                            <span class="esc-badge-completo">Concluído</span>
                        @endif
                    </div>
                    <div class="esc-card-body">
                        <h5>{{ $modulo->titulo }}</h5>
                        @if($modulo->descricao)
                            <p>{{ \Illuminate\Support\Str::limit($modulo->descricao, 90) }}</p>
                        @endif
                        <div class="esc-card-meta">
                            <span>{{ $modulo->total_aulas }} {{ $modulo->total_aulas == 1 ? 'aula' : 'aulas' }}</span>
                            <span>{{ $modulo->aulas_concluidas }}/{{ $modulo->total_aulas }} concluídas</span>
                        </div>
                        <div class="esc-progress">
                            <div class="esc-progress-bar" style="width: {{ $modulo->percentual_modulo }}%"></div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
@endsection
