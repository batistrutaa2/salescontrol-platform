@extends('layouts/layoutMaster')
@section('title', 'Vencimentos de boletos')
@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/boletos.scss'])
@endsection
@section('page-script')
    @vite(['resources/assets/js/boletos.js'])
@endsection
@section('content')
<div class="boletos">
    <header class="boletos-header">
        <div>
            <h1>Vencimentos de boletos</h1>
            <p>Acompanhe os boletos dos contratos implantados e organize os lembretes mensais.</p>
        </div>
        <span class="boletos-date"><i class="ri-calendar-line" aria-hidden="true"></i> {{ $hoje->format('d/m/Y') }}</span>
    </header>

    @if (session('boleto_status'))
        <div class="alert alert-success" role="status">{{ session('boleto_status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Não foi possível salvar.</strong>
            <ul class="mb-0">@foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="boletos-pendentes" aria-labelledby="boletos-pendentes-title">
        <div class="boletos-section-heading">
            <div><h2 id="boletos-pendentes-title">{{ $pendentes->total() }} lembrete(s) pendente(s)</h2>
                <p>Marcar como tratado registra o acompanhamento da equipe; não confirma o pagamento do boleto.</p></div>
            @if (request('lembrete'))<a href="{{ route('backoffice.boletos.index') }}">Ver todos os lembretes</a>@endif
        </div>
        @forelse ($pendentes as $lembrete)
            <article class="boletos-reminder" id="lembrete-{{ $lembrete->id }}">
                <div>
                    <span class="badge {{ $lembrete->vencimento->isSameDay($hoje) ? 'boletos-due' : 'boletos-overdue' }}">
                        {{ $lembrete->vencimento->isSameDay($hoje) ? 'Vence hoje' : 'Acompanhamento pendente' }} · {{ $lembrete->vencimento->format('d/m/Y') }}
                    </span>
                    <h3>{{ $lembrete->venda->nome_contrato }}</h3>
                    <p>{{ $lembrete->venda->operadora ?: 'Operadora não informada' }} · Proposta {{ $lembrete->venda->numero_proposta ?: '#'.$lembrete->venda_id }}</p>
                    <a href="{{ route('backoffice.openContract', ['idContrato' => $lembrete->venda_id]) }}">Abrir contrato <i class="ri-arrow-right-up-line" aria-hidden="true"></i></a>
                </div>
                <details class="boletos-treatment" @if($errors->any() && (string) (is_scalar(old('boleto_lembrete')) ? old('boleto_lembrete') : '') === (string) $lembrete->id) open @endif>
                    <summary>Registrar acompanhamento</summary>
                    <form action="{{ route('backoffice.boletos.tratar', $lembrete) }}" method="POST">
                        @csrf
                        <input type="hidden" name="boleto_lembrete" value="{{ $lembrete->id }}">
                        <label class="form-label" for="observacao-{{ $lembrete->id }}">Observação (opcional)</label>
                        <textarea class="form-control" id="observacao-{{ $lembrete->id }}" name="observacao" maxlength="1000" rows="2" placeholder="Ex.: vencimento conferido e cliente orientado.">{{ (string) (is_scalar(old('boleto_lembrete')) ? old('boleto_lembrete') : '') === (string) $lembrete->id ? (is_scalar(old('observacao')) ? old('observacao') : '') : '' }}</textarea>
                        <button class="btn btn-primary mt-3" type="submit">Marcar como tratado</button>
                    </form>
                </details>
            </article>
        @empty
            <div class="boletos-empty"><i class="ri-checkbox-circle-line" aria-hidden="true"></i><p>Nenhum lembrete pendente. Os avisos aparecem no dia do vencimento cadastrado.</p></div>
        @endforelse
        {{ $pendentes->links() }}
    </section>

    <section class="boletos-contracts" aria-labelledby="boletos-contracts-title">
        <div class="boletos-section-heading">
            <div><h2 id="boletos-contracts-title">Contratos implantados</h2><p>{{ $totalImplantados }} contrato(s) · {{ $semCadastro }} sem vencimento cadastrado</p></div>
        </div>
        @if ($semCadastro > 0)
            <div class="alert alert-warning">Cadastre o vencimento dos {{ $semCadastro }} contrato(s) sem data para ativar os avisos. A data de implantação não é usada como vencimento.</div>
        @endif
        <form class="boletos-filters" action="{{ route('backoffice.boletos.index') }}" method="GET">
            <div><label class="form-label" for="boleto-busca">Cliente ou proposta</label><input class="form-control" id="boleto-busca" name="busca" value="{{ request('busca') }}" maxlength="160" placeholder="Buscar contrato"></div>
            <div><label class="form-label" for="boleto-situacao">Vencimento</label><select class="form-select" id="boleto-situacao" name="situacao">
                @foreach (['todos' => 'Todos', 'sem_cadastro' => 'Sem cadastro', 'ativos' => 'Lembrete ativo', 'pausados' => 'Lembrete pausado'] as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('situacao', 'todos') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select></div>
            <button class="btn btn-outline-primary" type="submit">Filtrar</button>
        </form>
        <div class="boletos-list">
            @forelse ($contratos as $contrato)
                @php $agenda = $configuracoes->get($contrato->id); @endphp
                <article class="boletos-contract">
                    <div><h3>{{ $contrato->nome_contrato }}</h3><p>{{ $contrato->operadora ?: 'Operadora não informada' }} · Proposta {{ $contrato->numero_proposta ?: '#'.$contrato->id }}</p></div>
                    <div class="boletos-schedule">
                        @if ($agenda)
                            <strong>Todo dia {{ $agenda->dia_vencimento }}</strong>
                            <span>{{ $agenda->ativo ? 'Próximo: '.$agenda->proximo_vencimento->format('d/m/Y') : 'Lembrete pausado' }}</span>
                        @else
                            <strong>Sem vencimento cadastrado</strong><span>Este contrato ainda não gera avisos.</span>
                        @endif
                    </div>
                    <button type="button" class="btn btn-outline-primary js-boleto-config" data-bs-toggle="modal" data-bs-target="#boleto-config"
                        data-venda="{{ $contrato->id }}" data-url="{{ route('backoffice.boletos.configurar', $contrato) }}" data-nome="{{ $contrato->nome_contrato }}"
                        data-dia="{{ $agenda?->dia_vencimento }}" data-proximo="{{ $agenda?->proximo_vencimento->format('Y-m-d') }}" data-ativo="{{ $agenda ? (int) $agenda->ativo : 1 }}">
                        {{ $agenda ? 'Editar vencimento' : 'Cadastrar vencimento' }}
                    </button>
                </article>
            @empty
                <div class="boletos-empty"><p>Nenhum contrato implantado encontrado para este filtro.</p></div>
            @endforelse
        </div>
        {{ $contratos->links() }}
    </section>

    <details class="boletos-history">
        <summary>Últimos acompanhamentos registrados</summary>
        @forelse ($historico as $item)
            <article><strong>{{ $item->venda?->nome_contrato ?? 'Contrato indisponível' }}</strong><p>Vencimento {{ $item->vencimento->format('d/m/Y') }} · Tratado por {{ $item->tratadoPor?->name ?? 'Usuário indisponível' }} em {{ $item->tratado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
                @if ($item->observacao)<p>{{ $item->observacao }}</p>@endif
            </article>
        @empty<p>Nenhum acompanhamento registrado.</p>@endforelse
    </details>
</div>

<div class="modal fade" id="boleto-config" tabindex="-1" aria-labelledby="boleto-config-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form id="boleto-config-form" method="POST">
            <input type="hidden" name="boleto_venda" id="boleto-venda">
            @if($contratoAnterior)
                <div id="boleto-restaurar" hidden data-venda="{{ $contratoAnterior->id }}" data-url="{{ route('backoffice.boletos.configurar', $contratoAnterior) }}" data-nome="{{ $contratoAnterior->nome_contrato }}" data-dia="{{ (is_scalar(old('dia_vencimento')) ? old('dia_vencimento') : '') }}" data-proximo="{{ (is_scalar(old('proximo_vencimento')) ? old('proximo_vencimento') : '') }}" data-ativo="{{ (is_scalar(old('ativo')) ? old('ativo') : 0) }}"></div>
            @endif
            @csrf @method('PUT')
            <div class="modal-header"><h2 class="modal-title h5" id="boleto-config-title">Vencimento mensal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body">
                @if($contratoAnterior)
                    <div class="alert alert-danger" id="boleto-config-erros" role="alert"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
                @endif
                <p id="boleto-config-cliente" class="fw-semibold"></p>
                <div class="mb-4"><label class="form-label" for="boleto-dia">Dia do vencimento</label><input type="number" class="form-control" id="boleto-dia" name="dia_vencimento" min="1" max="31" required aria-describedby="boleto-dia-help"><small id="boleto-dia-help" class="d-block mt-2">Nos meses sem esse dia, o aviso usa o último dia do mês.</small></div>
                <div class="mb-4"><label class="form-label" for="boleto-proximo">Próximo vencimento</label><input type="date" class="form-control" id="boleto-proximo" name="proximo_vencimento" min="{{ $hoje->toDateString() }}" data-hoje="{{ $hoje->toDateString() }}" required></div>
                <input type="hidden" name="ativo" value="0">
                <div class="form-check"><input type="checkbox" class="form-check-input" name="ativo" value="1" id="boleto-ativo" checked><label class="form-check-label" for="boleto-ativo">Gerar lembretes mensais</label></div>
                <p class="small mt-3 mb-0">Administradores e backoffice recebem o aviso no painel. Lembretes já gerados permanecem no histórico.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Salvar vencimento</button></div>
        </form>
    </div></div>
</div>
@endsection
