@extends('layouts/layoutMaster')

@section('title', 'Configuração de funis')

@section('vendor-style')
    @vite('resources/assets/vendor/scss/pages/page-funnel-manager.scss')
@endsection

@section('content')
    <main class="funnel-manager">
        @if (session('message'))
            <div class="alert alert-{{ session('status') === 'success' ? 'success' : 'danger' }}" role="status">
                {{ session('message') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Não foi possível salvar.</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="funnel-hero" aria-labelledby="funnel-page-title">
            <div class="funnel-hero__main">
                <h1 class="funnel-hero__title" id="funnel-page-title">Desenhe o caminho da operação.</h1>
                <p class="funnel-hero__copy">Organize as etapas que a equipe usa todos os dias. Nomes e prazos podem refletir a rotina da corretora sem quebrar as regras que sustentam vendas e relatórios.</p>
                <div class="funnel-company">
                    <span class="funnel-company__marker"><i class="ri-building-4-line" aria-hidden="true"></i> Empresa ativa</span>
                    <span class="funnel-company__name">{{ $empresa->nome_fantasia }}</span>
                </div>
            </div>
            <aside class="funnel-hero__guardrail" aria-label="Proteções da configuração">
                <h2>Alterações com limite seguro</h2>
                <p>Esta configuração pertence somente à empresa exibida ao lado.</p>
                <ul>
                    <li><i class="ri-shield-check-line" aria-hidden="true"></i><span>Códigos técnicos nunca são enviados ou alterados pelo formulário.</span></li>
                    <li><i class="ri-git-merge-line" aria-hidden="true"></i><span>Etapas estruturais mantêm as regras internas mesmo quando o nome muda.</span></li>
                    <li><i class="ri-lock-2-line" aria-hidden="true"></i><span>Uma empresa não consegue consultar ou editar etapas de outra.</span></li>
                </ul>
            </aside>
        </section>

        <details class="funnel-create" @if($errors->any() && old('_operation') === 'create') open @endif>
            <summary>
                <strong><i class="ri-add-line" aria-hidden="true"></i> Criar etapa própria</strong>
                <span>Adicione uma etapa sem alterar o catálogo estrutural do sistema.</span>
            </summary>
            <form method="POST" action="{{ route('manager.funis.store') }}" class="funnel-form-grid">
                @csrf
                <input type="hidden" name="_operation" value="create">
                <div class="funnel-field">
                    <label for="new-stage-description">Nome da etapa</label>
                    <input class="form-control" id="new-stage-description" name="descricao" value="{{ old('descricao') }}" maxlength="255" required>
                </div>
                <div class="funnel-field">
                    <label for="new-stage-type">Fluxo</label>
                    <select class="form-select" id="new-stage-type" name="tipo_tabulacao" required>
                        <option value="C" @selected(old('tipo_tabulacao') === 'C')>Comercial</option>
                        <option value="A" @selected(old('tipo_tabulacao') === 'A')>Pós-venda</option>
                    </select>
                </div>
                <div class="funnel-field">
                    <label for="new-stage-effective">Conta como contato efetivo?</label>
                    <select class="form-select" id="new-stage-effective" name="efetivo" required>
                        <option value="Y" @selected(old('efetivo') === 'Y')>Sim</option>
                        <option value="N" @selected(old('efetivo') === 'N')>Não</option>
                    </select>
                </div>
                <div class="funnel-field">
                    <label for="new-stage-deadline">Prazo de referência</label>
                    <input class="form-control" id="new-stage-deadline" name="prazo" value="{{ old('prazo') }}" maxlength="100" placeholder="Ex.: 48 horas">
                </div>
                <button class="btn btn-primary" type="submit">Criar etapa</button>
            </form>
        </details>

        @foreach ($funis as $tipo => $funil)
            <section class="funnel-board" aria-labelledby="funnel-{{ strtolower($tipo) }}-title">
                <header class="funnel-board__head">
                    <div>
                        <h2 id="funnel-{{ strtolower($tipo) }}-title">{{ $funil['titulo'] }}</h2>
                        <p>{{ $funil['descricao'] }}</p>
                    </div>
                    <span class="funnel-board__count">{{ $funil['etapas']->count() }} etapas</span>
                </header>

                @if ($funil['etapas']->isEmpty())
                    <p class="funnel-empty">Nenhuma etapa configurada neste fluxo.</p>
                @else
                    <ol class="funnel-ledger">
                        @foreach ($funil['etapas'] as $index => $etapa)
                            <li>
                                <details class="funnel-stage">
                                    <summary class="funnel-stage__summary">
                                        <span class="funnel-stage__position">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="funnel-stage__identity">
                                            <span class="funnel-stage__name">{{ $etapa->descricao }}</span>
                                            <span class="funnel-stage__code">{{ $etapa->codigo ?: 'ETAPA PERSONALIZADA' }}</span>
                                        </span>
                                        <span class="funnel-stage__meta">
                                            <span class="funnel-chip {{ $etapa->codigo ? 'funnel-chip--core' : '' }}">{{ $etapa->codigo ? 'Estrutural' : 'Própria' }}</span>
                                            <span class="funnel-chip {{ $etapa->status === 'Y' ? 'funnel-chip--active' : '' }}">{{ $etapa->status === 'Y' ? 'Ativa' : 'Arquivada' }}</span>
                                        </span>
                                        <span class="funnel-stage__deadline">
                                            <span class="funnel-chip">{{ $etapa->prazo ?: 'Sem prazo' }}</span>
                                        </span>
                                        <span class="funnel-stage__toggle" aria-hidden="true"><i class="ri-edit-line"></i></span>
                                    </summary>

                                    <div class="funnel-stage__editor">
                                      <div class="funnel-stage__order" aria-label="Alterar posição de {{ $etapa->descricao }}">
                                        <form method="POST" action="{{ route('manager.funis.move', $etapa->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direction" value="up">
                                            <button class="btn btn-sm btn-icon btn-outline-secondary" type="submit" aria-label="Mover {{ $etapa->descricao }} para cima" @disabled($loop->first)><i class="ri-arrow-up-line" aria-hidden="true"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('manager.funis.move', $etapa->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="direction" value="down">
                                            <button class="btn btn-sm btn-icon btn-outline-secondary" type="submit" aria-label="Mover {{ $etapa->descricao }} para baixo" @disabled($loop->last)><i class="ri-arrow-down-line" aria-hidden="true"></i></button>
                                        </form>
                                        <span>Posição {{ $index + 1 }} de {{ $funil['etapas']->count() }}</span>
                                      </div>

                                    <form method="POST" action="{{ route('manager.funis.update', $etapa->id) }}" class="funnel-form-grid">
                                        @csrf
                                        @method('PUT')
                                        <div class="funnel-field">
                                            <label for="stage-{{ $etapa->id }}-description">Nome exibido</label>
                                            <input class="form-control" id="stage-{{ $etapa->id }}-description" name="descricao" value="{{ $etapa->descricao }}" maxlength="255" required>
                                        </div>
                                        <input type="hidden" name="tipo_tabulacao" value="{{ $etapa->tipo_tabulacao }}">
                                        @if ($etapa->codigo)
                                            <input type="hidden" name="efetivo" value="{{ $etapa->efetivo }}">
                                            <input type="hidden" name="status" value="{{ $etapa->status }}">
                                            <div class="funnel-field">
                                                <label>Classificação</label>
                                                <input class="form-control" value="Etapa estrutural protegida" disabled>
                                            </div>
                                        @else
                                            <div class="funnel-field">
                                                <label for="stage-{{ $etapa->id }}-effective">Contato efetivo?</label>
                                                <select class="form-select" id="stage-{{ $etapa->id }}-effective" name="efetivo" required>
                                                    <option value="Y" @selected($etapa->efetivo === 'Y')>Sim</option>
                                                    <option value="N" @selected($etapa->efetivo === 'N')>Não</option>
                                                </select>
                                            </div>
                                            <div class="funnel-field">
                                                <label for="stage-{{ $etapa->id }}-status">Disponibilidade</label>
                                                <select class="form-select" id="stage-{{ $etapa->id }}-status" name="status" required>
                                                    <option value="Y" @selected($etapa->status === 'Y')>Ativa</option>
                                                    <option value="N" @selected($etapa->status === 'N')>Arquivada</option>
                                                </select>
                                            </div>
                                        @endif
                                        <div class="funnel-field">
                                            <label for="stage-{{ $etapa->id }}-deadline">Prazo de referência</label>
                                            <input class="form-control" id="stage-{{ $etapa->id }}-deadline" name="prazo" value="{{ $etapa->prazo }}" maxlength="100" placeholder="Sem prazo">
                                        </div>
                                        <button class="btn btn-primary" type="submit">Salvar</button>
                                    </form>
                                    </div>
                                </details>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        @endforeach
    </main>
@endsection
