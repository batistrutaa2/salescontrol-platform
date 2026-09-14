@if (auth()->check() && in_array((int) auth()->user()->user_role_id, [\App\Enums\UserRole::ADMINISTRATIVO, \App\Enums\UserRole::BACKOFFICE], true) && app(\App\Support\TenantContext::class)->isResolved())
    @php
        $boletosPendentes = app(\App\Services\BoletoLembreteService::class)->pendentes(app(\App\Support\TenantContext::class)->id())->count();
    @endphp
    <div id="boleto-painel-aviso" class="boleto-painel-aviso alert alert-warning mb-4 {{ $boletosPendentes ? '' : 'd-none' }}" role="status" aria-live="polite" data-resumo-url="{{ route('backoffice.boletos.resumo') }}">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div><strong>Vencimentos de boletos</strong><p class="mb-0" id="boleto-painel-texto">{{ $boletosPendentes }} lembrete(s) aguardando acompanhamento.</p></div>
            <a class="btn btn-sm btn-dark" href="{{ route('backoffice.boletos.index') }}">Ver lembretes</a>
        </div>
    </div>
    @vite(['resources/assets/js/boleto-lembretes-painel.js'])
@endif
