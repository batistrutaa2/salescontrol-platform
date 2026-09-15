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
            <p>Organize os vencimentos mensais e acompanhe cada cliente, com ou sem contrato na carteira.</p>
        </div>
        <button class="btn btn-primary js-boleto-config" type="button" data-bs-toggle="modal" data-bs-target="#boleto-config"
            data-manual="1" data-metodo="POST" data-url="{{ route('backoffice.boletos.manual.store') }}" data-ativo="1">
            <i class="ri-add-line me-2" aria-hidden="true"></i>Novo cliente sem contrato
        </button>
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

        <form class="boletos-filters mb-6" action="{{ route('backoffice.boletos.index') }}" method="GET">
            <div><label class="form-label" for="boleto-busca">Cliente, proposta ou referência</label><input class="form-control" id="boleto-busca" name="busca" value="{{ request('busca') }}" maxlength="160" placeholder="Buscar contrato"></div>
            <div><label class="form-label" for="boleto-situacao">Vencimento</label><select class="form-select" id="boleto-situacao" name="situacao">
                @foreach (['todos' => 'Todos', 'sem_cadastro' => 'Sem cadastro', 'ativos' => 'Lembrete ativo', 'pausados' => 'Lembrete pausado'] as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('situacao', 'todos') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select></div>
            <div><label class="form-label" for="boleto-mes">Mês do vencimento</label><input class="form-control" type="month" id="boleto-mes" name="mes" value="{{ request('mes') }}"></div>
            <div><label class="form-label" for="boleto-quinzena">Quinzena do vencimento</label><select class="form-select" id="boleto-quinzena" name="quinzena">
                <option value="">Todas</option>
                <option value="1" @selected(request('quinzena') === '1')>1ª quinzena · dias 1 a 15</option>
                <option value="2" @selected(request('quinzena') === '2')>2ª quinzena · dia 16 ao fim do mês</option>
            </select></div>
            <button class="btn btn-outline-primary" type="submit">Filtrar</button>
            <a href="{{ route('backoffice.boletos.index') }}" class="btn btn-outline-secondary">Limpar filtros</a>
        </form>
    <section class="card card-body boletos-pendentes" aria-labelledby="boletos-pendentes-title">
        <div class="boletos-section-heading">
            <div><h2 id="boletos-pendentes-title">Acompanhamentos pendentes <span class="badge rounded-pill bg-label-primary ms-2">{{ $pendentes->total() }}</span></h2>
                <p>Marcar como tratado registra o acompanhamento da equipe; não confirma o pagamento do boleto.</p></div>
            @if (request('lembrete'))<a href="{{ route('backoffice.boletos.index') }}">Ver todos os lembretes</a>@endif
        </div>
        @forelse ($pendentes as $lembrete)
            <article class="boletos-reminder" id="lembrete-{{ $lembrete->id }}">
                <div>
                    <span class="badge {{ $lembrete->vencimento->gte($hoje) ? 'boletos-due' : 'boletos-overdue' }}">
                        {{ $lembrete->vencimento->isSameDay($hoje) ? 'Vence hoje' : ($lembrete->vencimento->gt($hoje) ? 'Vence em breve' : 'Acompanhamento pendente') }} · {{ $lembrete->vencimento->format('d/m/Y') }}
                    </span>
                    <p>Notificação: {{ $lembrete->data_notificacao->format('d/m/Y') }}</p>
                    <h3>{{ $lembrete->nome_cliente ?? $lembrete->venda?->nome_contrato ?? 'Cliente' }}</h3>
                    @if($lembrete->venda_id)
                    <p>{{ $lembrete->venda->operadora ?: 'Operadora não informada' }} · Proposta {{ $lembrete->venda->numero_proposta ?: '#'.$lembrete->venda_id }}</p>
                    @if($lembrete->referencia)<p>{{ $lembrete->referencia }}</p>@endif
                    <a href="{{ route('backoffice.openContract', ['idContrato' => $lembrete->venda_id]) }}">Abrir contrato <i class="ri-arrow-right-up-line" aria-hidden="true"></i></a>
                    @else
                        <p>{{ $lembrete->referencia ?: 'Cliente sem contrato na carteira' }}</p>
                    @endif
                </div>
                <div>
                <details @if($errors->any() && (string) (is_scalar(old('boleto_lembrete')) ? old('boleto_lembrete') : '') === (string) $lembrete->id) open @endif>
                    <summary>Editar boleto</summary>
                    <form action="{{ route('backoffice.boletos.lembretes.update', $lembrete) }}" method="POST">
                        @csrf @method('PUT')
                        <p class="small mb-3">Aviso já gerado em {{ $lembrete->data_notificacao->format('d/m/Y') }}. Para mudar os próximos avisos, edite o cadastro mensal abaixo.</p>
                        <label class="form-label" for="vencimento-{{ $lembrete->id }}">Vencimento real do boleto</label>
                        <input class="form-control mb-3" type="date" id="vencimento-{{ $lembrete->id }}" name="vencimento" value="{{ (string) (is_scalar(old('boleto_lembrete')) ? old('boleto_lembrete') : '') === (string) $lembrete->id && is_scalar(old('vencimento')) ? old('vencimento') : $lembrete->vencimento->toDateString() }}" min="{{ $lembrete->data_notificacao->toDateString() }}" required>
                        <label class="form-label" for="referencia-{{ $lembrete->id }}">Referência (opcional)</label>
                        <input class="form-control mb-3" id="referencia-{{ $lembrete->id }}" name="referencia" maxlength="160" value="{{ (string) (is_scalar(old('boleto_lembrete')) ? old('boleto_lembrete') : '') === (string) $lembrete->id && is_scalar(old('referencia')) ? old('referencia') : $lembrete->referencia }}">
                        <button class="btn btn-primary" type="submit">Salvar boleto</button>
                    </form>
                </details>
                <form class="mt-3" action="{{ route('backoffice.boletos.lembretes.destroy', $lembrete) }}" method="POST" data-confirm="Excluir este boleto? Os próximos meses continuam programados.">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger" type="submit">Excluir boleto</button>
                </form>
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
                </div>
            </article>
        @empty
            <div class="boletos-empty"><i class="ri-checkbox-circle-line" aria-hidden="true"></i><p>Nenhum lembrete pendente para os filtros selecionados. Os avisos aparecem 10 dias antes do vencimento.</p></div>
        @endforelse
        {{ $pendentes->links() }}
    </section>


    <section class="card card-body boletos-clients" aria-labelledby="boletos-clients-title">
        <div class="boletos-section-heading">
            <div><h2 id="boletos-clients-title">Clientes sem contrato <span class="badge bg-label-secondary ms-2">{{ $manuais->total() }}</span></h2><p>Cadastros independentes da carteira, com recorrência mensal.</p></div>
        </div>
        <div class="boletos-list">
            @forelse($manuais as $agenda)
                <article class="boletos-contract">
                    <div><h3>{{ $agenda->nome_cliente }}</h3><p>{{ $agenda->referencia ?: 'Sem referência adicional' }}</p></div>
                    <div class="boletos-schedule"><strong>Vence todo dia {{ $agenda->dia_vencimento }}</strong><span>Notificação: {{ $agenda->proxima_notificacao->format('d/m/Y') }}</span><span>{{ $agenda->ativo ? 'Vencimento: '.$agenda->proximo_vencimento->format('d/m/Y') : 'Lembrete pausado' }}</span></div>
                    <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary js-boleto-config" data-bs-toggle="modal" data-bs-target="#boleto-config"
                        data-manual="1" data-agenda="{{ $agenda->id }}" data-metodo="PUT" data-url="{{ route('backoffice.boletos.manual.update', $agenda) }}"
                        data-nome="{{ $agenda->nome_cliente }}" data-referencia="{{ $agenda->referencia }}" data-notificacao="{{ $agenda->proxima_notificacao->toDateString() }}" data-dia="{{ $agenda->dia_vencimento }}" data-proximo="{{ $agenda->proximo_vencimento->format('Y-m-d') }}" data-ativo="{{ (int) $agenda->ativo }}">Editar cadastro</button>
                    @if($agenda)
                        <form action="{{ route('backoffice.boletos.destroy', $agenda) }}" method="POST" data-confirm="Excluir o cadastro mensal e cancelar seus avisos pendentes e futuros? Acompanhamentos tratados serão preservados.">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger" type="submit">Excluir cadastro</button>
                        </form>
                    @endif
                    </div>
                </article>
            @empty
                <div class="boletos-empty"><p>Nenhum cliente sem contrato encontrado. Use “Novo cliente sem contrato” para cadastrar um vencimento mensal.</p></div>
            @endforelse
        </div>
        {{ $manuais->links() }}
    </section>

    <section class="card card-body boletos-contracts" aria-labelledby="boletos-contracts-title">
        <div class="boletos-section-heading">
            <div><h2 id="boletos-contracts-title">Contratos implantados</h2><p>{{ $totalImplantados }} contrato(s) · {{ $semCadastro }} sem vencimento cadastrado</p></div>
        </div>
        @if ($semCadastro > 0)
            <div class="alert alert-warning">Cadastre o vencimento dos {{ $semCadastro }} contrato(s) sem data para ativar os avisos. A data de implantação não é usada como vencimento.</div>
        @endif
        <div class="boletos-list">
            @forelse ($contratos as $contrato)
                @php $agenda = $configuracoes->get($contrato->id); @endphp
                <article class="boletos-contract">
                    <div><h3>{{ $contrato->nome_contrato }}</h3><p>{{ $contrato->operadora ?: 'Operadora não informada' }} · Proposta {{ $contrato->numero_proposta ?: '#'.$contrato->id }}</p></div>
                    <div class="boletos-schedule">
                        @if ($agenda)
                            <strong>Vence todo dia {{ $agenda->dia_vencimento }}</strong><span>Notificação: {{ $agenda->proxima_notificacao->format('d/m/Y') }}</span>
                            <span>{{ $agenda->ativo ? 'Vencimento: '.$agenda->proximo_vencimento->format('d/m/Y') : 'Lembrete pausado' }}</span>
                        @else
                            <strong>Sem vencimento cadastrado</strong><span>Este contrato ainda não gera avisos.</span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-primary js-boleto-config" data-bs-toggle="modal" data-bs-target="#boleto-config"
                        data-venda="{{ $contrato->id }}" data-url="{{ route('backoffice.boletos.configurar', $contrato) }}" data-nome="{{ $contrato->nome_contrato }}"
                        data-notificacao="{{ $agenda?->proxima_notificacao?->toDateString() }}" data-dia="{{ $agenda?->dia_vencimento }}" data-proximo="{{ $agenda?->proximo_vencimento->format('Y-m-d') }}" data-ativo="{{ $agenda ? (int) $agenda->ativo : 1 }}">
                        {{ $agenda ? 'Editar cadastro' : 'Cadastrar vencimento' }}
                    </button>
                    @if($agenda)
                        <form action="{{ route('backoffice.boletos.destroy', $agenda) }}" method="POST" data-confirm="Excluir o cadastro mensal e cancelar seus avisos pendentes e futuros? Acompanhamentos tratados serão preservados.">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-danger" type="submit">Excluir cadastro</button>
                        </form>
                    @endif
                    </div>
                </article>
            @empty
                <div class="boletos-empty"><p>Nenhum contrato implantado encontrado para este filtro.</p></div>
            @endforelse
        </div>
        {{ $contratos->links() }}
    </section>

    <details class="card card-body boletos-history">
        <summary>Últimos acompanhamentos registrados</summary>
        @forelse ($historico as $item)
            <article><strong>{{ $item->nome_cliente ?? $item->venda?->nome_contrato ?? 'Cliente indisponível' }}</strong><p>Vencimento {{ $item->vencimento->format('d/m/Y') }} · Tratado por {{ $item->tratadoPor?->name ?? 'Usuário indisponível' }} em {{ $item->tratado_em->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
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
                <div id="boleto-restaurar" hidden data-venda="{{ $contratoAnterior->id }}" data-url="{{ route('backoffice.boletos.configurar', $contratoAnterior) }}" data-nome="{{ $contratoAnterior->nome_contrato }}" data-notificacao="{{ is_scalar(old('proxima_notificacao')) ? old('proxima_notificacao') : '' }}" data-dia="{{ (is_scalar(old('dia_vencimento')) ? old('dia_vencimento') : '') }}" data-proximo="{{ (is_scalar(old('proximo_vencimento')) ? old('proximo_vencimento') : '') }}" data-ativo="{{ (is_scalar(old('ativo')) ? old('ativo') : 0) }}"></div>
            @endif
            @if($manualAnterior)
                <div id="boleto-restaurar" hidden data-manual="1" data-agenda="{{ $manualAnterior->id }}"
                    data-metodo="{{ $manualAnterior->exists ? 'PUT' : 'POST' }}" data-url="{{ $manualAnterior->exists ? route('backoffice.boletos.manual.update', $manualAnterior) : route('backoffice.boletos.manual.store') }}"
                    @foreach(['nome' => 'nome_cliente', 'referencia' => 'referencia', 'dia' => 'dia_vencimento', 'proximo' => 'proximo_vencimento', 'notificacao' => 'proxima_notificacao', 'ativo' => 'ativo'] as $atributo => $campo)
                        data-{{ $atributo }}="{{ is_scalar(old($campo)) ? old($campo) : '' }}"
                    @endforeach></div>
            @endif
            @csrf @method('PUT')
            <div class="modal-header"><h2 class="modal-title h5" id="boleto-config-title">Vencimento mensal</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="modal-body">
                @if($contratoAnterior || $manualAnterior)
                    <div class="alert alert-danger" id="boleto-config-erros" role="alert"><ul class="mb-0">@foreach($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach</ul></div>
                @endif
                <p id="boleto-config-cliente" class="fw-semibold"></p>
                <div id="boleto-manual-fields" hidden>
                    <div class="mb-4"><label class="form-label" for="boleto-nome">Nome do cliente</label><input class="form-control" id="boleto-nome" name="nome_cliente" maxlength="160" disabled></div>
                    <div class="mb-4"><label class="form-label" for="boleto-referencia">Referência (opcional)</label><input class="form-control" id="boleto-referencia" name="referencia" maxlength="160" placeholder="Ex.: operadora, plano ou identificação do boleto" disabled></div>
                </div>
                <div class="mb-4"><label class="form-label" for="boleto-dia">Dia do vencimento</label><input type="number" class="form-control" id="boleto-dia" name="dia_vencimento" min="1" max="31" required aria-describedby="boleto-dia-help"><small id="boleto-dia-help" class="d-block mt-2">Nos meses sem esse dia, o vencimento usa o último dia do mês.</small></div>
                <div class="mb-4"><label class="form-label" for="boleto-proximo">Vencimento real do boleto</label><input type="date" class="form-control" id="boleto-proximo" name="proximo_vencimento" min="{{ $hoje->toDateString() }}" data-hoje="{{ $hoje->toDateString() }}" required></div>
                <div class="mb-4"><label class="form-label" for="boleto-notificacao">Data do alerta automático</label><input type="date" class="form-control" id="boleto-notificacao" readonly aria-describedby="boleto-notificacao-help"><small class="d-block mt-2" id="boleto-notificacao-help">O alerta aparece 10 dias antes de cada vencimento. Se esse prazo já passou, aparece ao salvar.</small></div>
                <input type="hidden" name="ativo" value="0">
                <div class="form-check"><input type="checkbox" class="form-check-input" name="ativo" value="1" id="boleto-ativo" checked><label class="form-check-label" for="boleto-ativo">Gerar lembretes mensais</label></div>
                <p class="small mt-3 mb-0">Administradores e backoffice recebem o aviso no painel. Lembretes já gerados permanecem no histórico.</p>
            </div>
            <div class="modal-footer"><button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Salvar vencimento</button></div>
        </form>
    </div></div>
</div>
@endsection
