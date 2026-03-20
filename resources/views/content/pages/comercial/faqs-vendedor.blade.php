@php use Illuminate\Support\Str; @endphp
@extends('layouts/layoutMaster')

@section('title', 'FAQs - Perguntas Frequentes')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/faqs.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/faqs-vendedor.js'])
@endsection

@section('content')
<div class="faq-wrapper">
    <!-- Page Header -->
    <div class="faq-page-header">
        <div class="faq-header-content">
            <div class="faq-header-text">
                <span class="faq-greeting-label">Base de Conhecimento</span>
                <h1 class="faq-main-title">Perguntas Frequentes</h1>
                <p class="faq-subtitle">Consulte dúvidas frequentes organizadas por operadora</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="faq-filter-bar">
        <div class="faq-filter-item">
            <span class="faq-filter-label">Operadora</span>
            <select class="faq-filter-select" id="filtro-operadora">
                <option value="">Todas as operadoras</option>
                @foreach ($operadoras as $operadora)
                    <option value="{{ $operadora->id }}">{{ $operadora->nome }}</option>
                @endforeach
            </select>
        </div>
        <div class="faq-filter-item">
            <span class="faq-filter-label">Buscar</span>
            <input type="text" class="faq-filter-input" id="busca-faq" placeholder="Buscar por título ou resposta...">
        </div>
    </div>

    <!-- FAQ Groups by Operadora -->
    @foreach ($faqsPorOperadora as $operadoraNome => $faqs)
        <div class="faq-operadora-group" data-operadora="{{ $faqs->first()->operadora_id }}">
            <div class="faq-group-header">
                <div class="faq-group-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="faq-group-info">
                    <h3 class="faq-group-title">{{ $operadoraNome }}</h3>
                    <span class="faq-group-count">{{ $faqs->count() }} {{ $faqs->count() === 1 ? 'pergunta' : 'perguntas' }}</span>
                </div>
                <div class="faq-group-badge">{{ $faqs->count() }}</div>
            </div>
            <div class="faq-group-body">
                <div class="accordion faq-accordion" id="accordion-{{ Str::slug($operadoraNome) }}">
                    @foreach ($faqs as $faq)
                        <div class="accordion-item faq-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed faq-accordion-btn faq-titulo" type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#faq-{{ $faq->id }}"
                                    aria-expanded="false"
                                    aria-controls="faq-{{ $faq->id }}">
                                    {{ $faq->titulo }}
                                </button>
                            </h2>
                            <div id="faq-{{ $faq->id }}" class="accordion-collapse collapse"
                                data-bs-parent="#accordion-{{ Str::slug($operadoraNome) }}">
                                <div class="accordion-body faq-accordion-body faq-resposta">
                                    {!! nl2br(e($faq->resposta)) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    @if ($faqsPorOperadora->isEmpty())
        <div class="faq-empty-state">
            <div class="faq-empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            </div>
            <h3 class="faq-empty-title">Nenhum FAQ disponível</h3>
            <p class="faq-empty-text">Ainda não há perguntas frequentes cadastradas para consulta.</p>
        </div>
    @endif
</div>
@endsection
