@extends('layouts/layoutMaster')

@section('title', $modulo->titulo . ' — Escola LK Brokers')

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/escola.scss')
@endsection

@section('content')
<div class="esc-page">

    <div class="esc-breadcrumb">
        <a href="{{ route('escola.index') }}">Escola</a>
        <span>/</span>
        <strong>{{ $modulo->titulo }}</strong>
    </div>

    <div class="esc-modulo-header">
        <div class="esc-title-text">
            <h4>{{ $modulo->titulo }}</h4>
            @if($modulo->descricao)
                <span>{{ $modulo->descricao }}</span>
            @endif
        </div>
    </div>

    @if($aulas->isEmpty())
        <div class="esc-empty"><p>Este módulo ainda não possui aulas.</p></div>
    @else
        <div class="esc-aulas-list">
            @foreach($aulas as $i => $aula)
                @php
                    $prog = $aula->progressoDoUsuario;
                    $status = $prog?->concluida ? 'concluida' : (($prog && $prog->percentual > 0) ? 'andamento' : 'nova');
                @endphp
                <a href="{{ route('escola.aulas.assistir', $aula->id) }}" class="esc-aula-item esc-status-{{ $status }}">
                    <div class="esc-aula-num">{{ $i + 1 }}</div>
                    <div class="esc-aula-info">
                        <h6>{{ $aula->titulo }}</h6>
                        @if($aula->descricao)
                            <p>{{ \Illuminate\Support\Str::limit($aula->descricao, 110) }}</p>
                        @endif
                    </div>
                    <div class="esc-aula-status">
                        @if($status === 'concluida')
                            <span class="esc-tag esc-tag-ok">Concluída</span>
                        @elseif($status === 'andamento')
                            <span class="esc-tag esc-tag-prog">{{ $prog->percentual }}%</span>
                        @else
                            <span class="esc-tag esc-tag-new">Assistir</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif

</div>
@endsection
