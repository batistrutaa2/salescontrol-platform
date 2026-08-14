@extends('layouts/layoutMaster')

@section('title', 'Relacionamento e Renovação')

@section('vendor-style')
  @vite(['resources/assets/vendor/scss/pages/renovacoes.scss'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/renovacoes.js'])
@endsection

@section('content')
<main class="renovacoes" id="renovacoesApp"
  data-dados-url="{{ route('backoffice.renovacoes.dados') }}"
  data-metricas-url="{{ route('backoffice.renovacoes.metricas') }}"
  data-base-url="{{ url('/back-office/renovacoes') }}"
  data-usuarios='@json($usuarios)'>
  <header class="renovacoes__hero">
    <div class="renovacoes__hero-copy">
      <span class="renovacoes__eyebrow"><i class="ri-sparkling-2-line" aria-hidden="true"></i> Central de relacionamento</span>
      <h1>Clientes que merecem<br><span>uma nova conversa.</span></h1>
      <p>Uma carteira inteligente para retomar relacionamentos, registrar cada contato e devolver oportunidades ao comercial.</p>
    </div>
    <div class="renovacoes__hero-rule" aria-label="Critério automático da carteira">
      <span class="hero-rule__icon"><i class="ri-timer-flash-line" aria-hidden="true"></i></span>
      <div><small>Critério automático</small><strong>24 meses sem nova implantação</strong><span>Consideramos sempre a venda mais recente do documento.</span></div>
    </div>
    <span class="hero-orb hero-orb--one" aria-hidden="true"></span>
    <span class="hero-orb hero-orb--two" aria-hidden="true"></span>
  </header>

  <section class="renovacoes__metrics" aria-label="Indicadores da carteira">
    @foreach ([['total','Carteira','ri-contacts-book-3-line'],['contatados','Contatados','ri-send-plane-line'],['respostas','Responderam','ri-chat-check-line'],['cotacoes','Cotações','ri-file-list-3-line'],['convertidos','Convertidos','ri-checkbox-circle-line']] as [$key,$label,$icon])
      <article class="metric-card metric-card--{{ $key }}"><span class="metric-card__icon"><i class="{{ $icon }}" aria-hidden="true"></i></span><div><small>{{ $label }}</small><strong data-metric="{{ $key }}">—</strong></div><span class="metric-card__trail" aria-hidden="true"></span></article>
    @endforeach
  </section>

  <section class="renovacoes__workspace">
    <div class="renovacoes__toolbar">
      <div><span class="renovacoes__eyebrow">Fila de atendimento</span><h2>Carteira ativa</h2><p>Comece pelos clientes há mais tempo sem contato.</p></div>
      <div class="renovacoes__toolbar-status"><span></span> Sincronização automática diária</div>
    </div>
    <form id="renovacoesFiltros" class="renovacoes__filters" role="search">
      <div class="filter-search"><label for="busca">Buscar cliente ou documento</label><div class="input-group input-group-merge"><span class="input-group-text"><i class="ri-search-line" aria-hidden="true"></i></span><input id="busca" name="busca" class="form-control" autocomplete="off" placeholder="Nome, CPF ou CNPJ"></div></div>
      <div><label for="status">Etapa</label><select id="status" name="status" class="form-select"><option value="">Todas</option><option value="ELEGIVEL">Elegível</option><option value="AGUARDANDO_RESPOSTA">Aguardando resposta</option><option value="EM_CONVERSA">Em conversa</option><option value="SEM_RESPOSTA">Sem resposta</option><option value="REAGENDADO">Reagendado</option><option value="COTACAO_SOLICITADA">Cotação solicitada</option><option value="SEM_INTERESSE">Sem interesse</option><option value="NAO_CONTATAR">Não contatar</option><option value="CONVERTIDO">Convertido</option><option value="SUSPENSO">Suspenso</option></select></div>
      <div><label for="responsavel_id">Responsável</label><select id="responsavel_id" name="responsavel_id" class="form-select"><option value="">Todos</option>@foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
      <div><label for="vendedor_id">Vendedor original</label><select id="vendedor_id" name="vendedor_id" class="form-select"><option value="">Todos</option>@foreach($vendedores as $vendedor)<option value="{{ $vendedor->id }}">{{ $vendedor->name }}{{ $vendedor->ativo !== 'Y' ? ' (inativo)' : '' }}</option>@endforeach</select></div>
      <div><label for="operadora">Operadora</label><select id="operadora" name="operadora" class="form-select"><option value="">Todas</option>@foreach($operadoras as $o)<option value="{{ $o }}">{{ $o }}</option>@endforeach</select></div>
      <button type="button" class="btn btn-text-secondary" id="limparFiltros"><i class="ri-filter-off-line" aria-hidden="true"></i> Limpar</button>
    </form>

    <div id="renovacoesStatus" class="visually-hidden" role="status" aria-live="polite"></div>
    <div id="renovacoesLoading" class="renovacoes__loading" aria-label="Carregando carteira"><span></span><span></span><span></span></div>
    <div id="renovacoesErro" class="renovacoes__feedback d-none" role="alert"><i class="ri-error-warning-line" aria-hidden="true"></i><div><strong>Não foi possível carregar a carteira.</strong><p>Tente novamente em alguns instantes.</p></div><button class="btn btn-outline-primary" type="button" id="tentarNovamente">Tentar novamente</button></div>
    <div id="renovacoesVazio" class="renovacoes__feedback d-none"><i class="ri-inbox-archive-line" aria-hidden="true"></i><div><strong>Nenhum cliente encontrado</strong><p>Ajuste os filtros ou aguarde a próxima sincronização.</p></div></div>
    <div id="renovacoesLista" class="renovacoes__list d-none"></div>
    <nav class="renovacoes__pagination d-none" id="renovacoesPaginacao" aria-label="Paginação da carteira"></nav>
  </section>

  <div class="offcanvas offcanvas-end renovacoes__drawer" tabindex="-1" id="renovacaoDetalhe" aria-labelledby="renovacaoDetalheTitulo">
    <div class="offcanvas-header"><div><span class="renovacoes__eyebrow">Ficha do relacionamento</span><h2 id="renovacaoDetalheTitulo" class="offcanvas-title">Cliente</h2></div><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button></div>
    <div class="offcanvas-body" id="renovacaoDetalheBody"><div class="renovacoes__loading"><span></span><span></span><span></span></div></div>
  </div>

  <template id="tratativaTemplate">
    <form class="tratativa-form">
      <label for="tratativaStatus">Registrar resultado</label>
      <select id="tratativaStatus" name="status" class="form-select" required><option value="">Selecione</option><option value="AGUARDANDO_RESPOSTA">Mensagem enviada</option><option value="EM_CONVERSA">Cliente respondeu / em conversa</option><option value="SEM_RESPOSTA">Sem resposta</option><option value="REAGENDADO">Falar em outra data</option><option value="COTACAO_SOLICITADA">Quer uma nova cotação</option><option value="SEM_INTERESSE">Sem interesse</option><option value="NAO_CONTATAR">Não deseja novos contatos</option></select>
      <div class="recontato-field d-none"><label for="recontatoEm">Próximo contato</label><input id="recontatoEm" name="recontato_em" type="date" class="form-control"></div>
      <label for="tratativaObservacao">Observação <span>(opcional)</span></label><textarea id="tratativaObservacao" name="observacao" class="form-control" rows="3" maxlength="2000" placeholder="Registre o contexto necessário para a próxima pessoa continuar."></textarea>
      <button class="btn btn-primary" type="submit"><i class="ri-save-line" aria-hidden="true"></i> Salvar tratativa</button>
    </form>
  </template>
</main>
@endsection
