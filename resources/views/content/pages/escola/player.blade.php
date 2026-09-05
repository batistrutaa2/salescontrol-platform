@extends('layouts/layoutMaster')

@section('title', $aula->titulo . ' — Academia Comercial')

@section('vendor-style')
    @vite('resources/assets/vendor/libs/plyr/plyr.scss')
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/escola.scss')
@endsection

@section('vendor-script')
    @vite('resources/assets/vendor/libs/plyr/plyr.js')
@endsection

@section('page-script')
    @vite('resources/assets/js/escola-player.js')
@endsection

@section('content')
<div class="esc-page esc-player-page"
     data-progresso-url="{{ route('escola.aulas.progresso', $aula->id) }}"
     data-posicao-inicial="{{ $posicaoInicial }}">

    <div class="esc-breadcrumb">
        <a href="{{ route('escola.index') }}">Escola</a>
        <span>/</span>
        <a href="{{ route('escola.modulos.show', $modulo->id) }}">{{ $modulo->titulo }}</a>
        <span>/</span>
        <strong>{{ $aula->titulo }}</strong>
    </div>

    <div class="esc-player-layout">
        <div class="esc-player-main">
            <div class="esc-player-frame">
                @if($videoUrl)
                    <video id="escola-player" playsinline controls>
                        <source src="{{ $videoUrl }}" type="{{ $aula->video_mime ?? 'video/mp4' }}">
                    </video>
                @else
                    <div class="esc-player-empty">
                        <p>O vídeo desta aula ainda não foi disponibilizado.</p>
                    </div>
                @endif
            </div>

            <div class="esc-aula-detalhe">
                <h4>{{ $aula->titulo }}</h4>
                @if($aula->descricao)
                    <div class="esc-aula-desc">{!! nl2br(e($aula->descricao)) !!}</div>
                @endif

                @if($materiais->isNotEmpty())
                    <div class="esc-materiais">
                        <h6>Materiais de apoio</h6>
                        <ul>
                            @foreach($materiais as $material)
                                <li>
                                    <a href="{{ route('escola.materiais.download', $material->id) }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                        </svg>
                                        {{ $material->titulo ?? $material->nome_original }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <aside class="esc-player-sidebar">
            <div class="esc-sidebar-head">Aulas do módulo</div>
            <div class="esc-sidebar-list">
                @foreach($aulasIrmas as $i => $irma)
                    @php
                        $p = $irma->progressoDoUsuario;
                        $st = $p?->concluida ? 'concluida' : (($p && $p->percentual > 0) ? 'andamento' : 'nova');
                    @endphp
                    <a href="{{ route('escola.aulas.assistir', $irma->id) }}"
                       class="esc-sidebar-item esc-status-{{ $st }} {{ $irma->id === $aula->id ? 'esc-ativa' : '' }}">
                        <span class="esc-sidebar-num">{{ $i + 1 }}</span>
                        <span class="esc-sidebar-titulo">{{ $irma->titulo }}</span>
                        @if($st === 'concluida')
                            <svg class="esc-sidebar-check" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </aside>
    </div>

</div>
@endsection
