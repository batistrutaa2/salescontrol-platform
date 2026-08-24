@extends('layouts/layoutMaster')

@section('title', 'Implantados recentes')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/scss/pages/carteira-clientes.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  @vite(['resources/assets/js/carteira-clientes.js'])
@endsection

@section('content')
<div class="cc-wrapper">
  <header class="cc-page-header">
    <div>
      <h1 class="cc-header-title">Contratos implantados recentemente</h1>
      <p class="cc-header-subtitle">Acompanhe o que acabou de entrar na carteira e identifique portabilidades ou cancelamentos que precisam avançar.</p>
    </div>
    <nav class="cc-view-switch" aria-label="Escolher visão da carteira">
      <button type="button" class="cc-view-button is-active" data-cc-view="recentes" aria-pressed="true">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>
        Implantados recentes
      </button>
      <button type="button" class="cc-view-button" data-cc-view="carteira" aria-pressed="false">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
        Carteira completa
      </button>
    </nav>
  </header>

  <section class="cc-period-panel" id="cc-period-panel" aria-labelledby="cc-period-title">
    <div class="cc-period-copy">
      <h2 id="cc-period-title">Período de implantação</h2>
      <p>Use a idade do contrato para decidir o momento certo de iniciar cada tratativa.</p>
    </div>
    <div class="cc-period-options" role="group" aria-label="Período de implantação">
      <button type="button" class="cc-period-button is-active" data-period="30" aria-pressed="true">Último mês</button>
      <button type="button" class="cc-period-button" data-period="60" aria-pressed="false">Últimos 2 meses</button>
      <button type="button" class="cc-period-button" data-period="365" aria-pressed="false">Últimos 12 meses</button>
    </div>
  </section>

  <section class="cc-kpi-grid" aria-label="Resumo dos contratos">
    <article class="cc-kpi-card kpi-primary">
      <span class="cc-kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg></span>
      <span class="cc-kpi-label" id="kpi-total-label">Implantados no período</span>
      <strong id="kpi-total" class="cc-kpi-value">—</strong>
    </article>
    <article class="cc-kpi-card kpi-attention">
      <span class="cc-kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 3.7 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z"/></svg></span>
      <span class="cc-kpi-label" id="kpi-attention-label">Com ação pendente</span>
      <strong id="kpi-atencao" class="cc-kpi-value">—</strong>
    </article>
    <article class="cc-kpi-card kpi-info">
      <span class="cc-kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></span>
      <span class="cc-kpi-label" id="kpi-port-label">Com portabilidade</span>
      <strong id="kpi-portabilidades" class="cc-kpi-value">—</strong>
    </article>
    <article class="cc-kpi-card kpi-danger">
      <span class="cc-kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg></span>
      <span class="cc-kpi-label" id="kpi-cancel-label">Com cancelamento</span>
      <strong id="kpi-cancelamentos" class="cc-kpi-value">—</strong>
    </article>
    <article class="cc-kpi-card kpi-success">
      <span class="cc-kpi-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21.2l7.8-7.8a5.5 5.5 0 0 0 1-7.8Z"/></svg></span>
      <span class="cc-kpi-label" id="kpi-vidas-label">Vidas implantadas</span>
      <strong id="kpi-vidas" class="cc-kpi-value">—</strong>
    </article>
  </section>

  <section class="cc-responsaveis" id="cc-responsaveis" aria-labelledby="cc-responsaveis-title">
    <div class="cc-section-heading">
      <div>
        <h2 id="cc-responsaveis-title">Implantados por backoffice</h2>
        <p>Selecione um responsável para filtrar a lista.</p>
      </div>
      <span id="cc-responsaveis-periodo">Último mês</span>
    </div>
    <div id="cc-responsaveis-lista" class="cc-responsaveis-lista" role="list"></div>
  </section>

  <section class="cc-filters-card" aria-label="Filtros da listagem">
    <label class="cc-filter-group cc-search-field" for="cc-busca">
      <span>Buscar</span>
      <span class="cc-filter-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.4-4.4"/></svg></span>
      <input type="search" id="cc-busca" placeholder="Cliente, CNPJ ou proposta" autocomplete="off">
    </label>
    <label class="cc-filter-group cc-recentes-only" for="cc-backoffice">
      <span>Backoffice</span>
      <select id="cc-backoffice">
        <option value="">Todos os responsáveis</option>
        <option value="sem_responsavel">Sem responsável</option>
        @foreach($backoffices as $backoffice)
          <option value="{{ $backoffice->id }}">{{ $backoffice->name }}</option>
        @endforeach
      </select>
    </label>
    <label class="cc-filter-group cc-recentes-only" for="cc-acao">
      <span>Ação necessária</span>
      <select id="cc-acao">
        <option value="">Todas as situações</option>
        <option value="portabilidade">Portabilidade pendente</option>
        <option value="cancelamento">Cancelamento pendente</option>
        <option value="sem_acao">Sem ação sinalizada</option>
      </select>
    </label>
    <label class="cc-filter-group cc-carteira-only d-none" for="cc-status">
      <span>Status do cliente</span>
      <select id="cc-status">
        <option value="">Todos os status</option>
        <option value="ativo">Ativos</option>
        <option value="misto">Mistos</option>
        <option value="inativo">Inativos</option>
      </select>
    </label>
    <label class="cc-filter-group" for="cc-operadora">
      <span>Operadora</span>
      <select id="cc-operadora">
        <option value="">Todas as operadoras</option>
        @foreach($operadoras as $operadora)
          <option value="{{ $operadora }}">{{ $operadora }}</option>
        @endforeach
      </select>
    </label>
    <button type="button" id="cc-btn-clear" class="cc-btn-clear">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
      Limpar filtros
    </button>
  </section>

  <section id="cc-table-card" class="cc-table-card" aria-labelledby="cc-table-title">
    <header class="cc-table-header">
      <div class="cc-table-title-group">
        <h2 id="cc-table-title">Contratos que acabaram de ser implantados</h2>
        <p id="cc-table-subtitle">Mais recentes primeiro, para facilitar o acompanhamento.</p>
      </div>
      <div class="cc-table-tools">
        <span id="cc-total-badge" class="cc-table-badge" aria-live="polite">0 contratos</span>
        <label for="cc-per-page">Exibir</label>
        <select id="cc-per-page" class="cc-per-page">
          <option value="15">15</option><option value="30">30</option><option value="50">50</option><option value="100">100</option>
        </select>
      </div>
    </header>

    <div id="cc-loading" class="cc-loading d-none" aria-label="Carregando contratos">
      @for($row = 0; $row < 6; $row++)<span class="cc-skeleton-row"></span>@endfor
    </div>
    <div id="cc-error" class="cc-state cc-error d-none" role="alert">
      <span class="cc-state-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><circle cx="12" cy="12" r="10"/></svg></span>
      <h3>Não foi possível carregar os contratos</h3><p>Confira sua conexão e tente novamente.</p>
      <button type="button" id="cc-btn-retry">Tentar novamente</button>
    </div>
    <div id="cc-empty" class="cc-state cc-empty d-none">
      <span class="cc-state-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.4-4.4"/></svg></span>
      <h3 id="cc-empty-title">Nenhum contrato neste período</h3>
      <p id="cc-empty-copy">Amplie o período ou limpe os filtros para consultar outros contratos.</p>
      <button type="button" id="cc-empty-action">Limpar filtros</button>
    </div>

    <div class="cc-table-wrap">
      <table class="cc-table"><thead id="cc-thead"></thead><tbody id="cc-tbody"></tbody></table>
    </div>
    <footer id="cc-pagination" class="cc-pagination d-none">
      <span id="cc-pagination-info" class="cc-pagination-info"></span>
      <div class="cc-pagination-controls">
        <button type="button" id="cc-btn-first" class="cc-page-btn" title="Primeira página" aria-label="Primeira página"><svg viewBox="0 0 24 24"><path d="m11 17-5-5 5-5M18 17l-5-5 5-5"/></svg></button>
        <button type="button" id="cc-btn-prev" class="cc-page-btn" title="Página anterior" aria-label="Página anterior"><svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg></button>
        <span id="cc-page-indicator" class="cc-page-indicator">1 / 1</span>
        <button type="button" id="cc-btn-next" class="cc-page-btn" title="Próxima página" aria-label="Próxima página"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg></button>
        <button type="button" id="cc-btn-last" class="cc-page-btn" title="Última página" aria-label="Última página"><svg viewBox="0 0 24 24"><path d="m13 17 5-5-5-5M6 17l5-5-5-5"/></svg></button>
      </div>
    </footer>
  </section>
</div>

<div class="modal fade cc-detalhe-modal" id="modalDetalheCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
    <header class="cc-modal-header">
      <div class="cc-modal-client-info"><h2 id="cc-detalhe-nome" class="cc-modal-nome">—</h2><span id="cc-detalhe-cnpj" class="cc-modal-cnpj">—</span><div id="cc-detalhe-tempo" class="cc-modal-tempo">—</div></div>
      <button type="button" class="cc-modal-close" data-bs-dismiss="modal" aria-label="Fechar detalhes"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg></button>
    </header>
    <div class="cc-modal-body"><div id="cc-detalhe-body"></div></div>
  </div></div>
</div>
@endsection
