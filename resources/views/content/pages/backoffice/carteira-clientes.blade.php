@extends('layouts/layoutMaster')

@section('title', 'Carteira de Clientes')

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

  {{-- =========================================================
       Page Header
  ========================================================= --}}
  <div class="cc-page-header">
    <span class="cc-header-label">Back-office</span>
    <h1 class="cc-header-title">Carteira de Clientes</h1>
    <p class="cc-header-subtitle">Visão consolidada de todos os clientes por CNPJ — contratos, valores e histórico de relacionamento</p>
  </div>

  {{-- =========================================================
       KPI Cards
  ========================================================= --}}
  <div class="cc-kpi-grid">

    {{-- Total de Clientes --}}
    <div class="cc-kpi-card kpi-primary">
      <div class="cc-kpi-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <div class="cc-kpi-content">
        <span class="cc-kpi-label">Total de Clientes</span>
        <h2 id="kpi-total" class="cc-kpi-value">—</h2>
      </div>
      <div class="cc-kpi-glow"></div>
    </div>

    {{-- Clientes Ativos --}}
    <div class="cc-kpi-card kpi-success">
      <div class="cc-kpi-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
          <polyline points="22 4 12 14.01 9 11.01"/>
        </svg>
      </div>
      <div class="cc-kpi-content">
        <span class="cc-kpi-label">Clientes Ativos</span>
        <h2 id="kpi-ativos" class="cc-kpi-value">—</h2>
      </div>
      <div class="cc-kpi-glow"></div>
    </div>

    {{-- Clientes Inativos --}}
    <div class="cc-kpi-card kpi-danger">
      <div class="cc-kpi-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <line x1="15" y1="9" x2="9" y2="15"/>
          <line x1="9" y1="9" x2="15" y2="15"/>
        </svg>
      </div>
      <div class="cc-kpi-content">
        <span class="cc-kpi-label">Clientes Inativos</span>
        <h2 id="kpi-inativos" class="cc-kpi-value">—</h2>
      </div>
      <div class="cc-kpi-glow"></div>
    </div>

    {{-- Valor da Carteira --}}
    <div class="cc-kpi-card kpi-info">
      <div class="cc-kpi-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
        </svg>
      </div>
      <div class="cc-kpi-content">
        <span class="cc-kpi-label">Valor da Carteira</span>
        <h2 id="kpi-valor" class="cc-kpi-value">—</h2>
      </div>
      <div class="cc-kpi-glow"></div>
    </div>

    {{-- Total de Vidas --}}
    <div class="cc-kpi-card kpi-warning">
      <div class="cc-kpi-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
      </div>
      <div class="cc-kpi-content">
        <span class="cc-kpi-label">Total de Vidas</span>
        <h2 id="kpi-vidas" class="cc-kpi-value">—</h2>
      </div>
      <div class="cc-kpi-glow"></div>
    </div>

  </div>

  {{-- =========================================================
       Filtros
  ========================================================= --}}
  <div class="cc-filters-card">

    <div class="cc-filter-group">
      <span class="cc-filter-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </span>
      <input type="text" id="cc-busca" placeholder="Buscar por nome ou CNPJ…">
    </div>

    <div class="cc-filter-group no-icon">
      <select id="cc-status">
        <option value="">Todos os status</option>
        <option value="ativo">Ativos</option>
        <option value="misto">Mistos</option>
        <option value="inativo">Inativos</option>
      </select>
    </div>

    <div class="cc-filter-group no-icon">
      <select id="cc-operadora">
        <option value="">Todas as operadoras</option>
        @foreach($operadoras as $op)
          <option value="{{ $op }}">{{ $op }}</option>
        @endforeach
      </select>
    </div>

    <button id="cc-btn-clear" class="cc-btn-clear">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
      Limpar
    </button>

  </div>

  {{-- =========================================================
       Tabela
  ========================================================= --}}
  <div id="cc-table-card" class="cc-table-card">

    <div class="cc-table-header">
      <div class="cc-table-title-group">
        <div class="cc-table-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
        <div>
          <h3>Clientes</h3>
          <span>Agrupados por CNPJ/CPF</span>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <span id="cc-total-badge" class="cc-table-badge">0 clientes</span>
        <div style="display:flex;align-items:center;gap:6px;font-size:0.78rem;color:var(--cc-text-muted);">
          <span>Exibir</span>
          <select id="cc-per-page" style="background:var(--cc-glass-bg);border:1px solid var(--cc-card-border);border-radius:8px;padding:5px 10px;font-size:0.78rem;color:var(--cc-text-primary);outline:none;">
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span>por página</span>
        </div>
      </div>
    </div>

    {{-- Loading --}}
    <div id="cc-loading" class="cc-loading d-none">
      <span class="spinner-border spinner-border-sm"></span>
      Carregando clientes…
    </div>

    {{-- Empty state --}}
    <div id="cc-empty" class="cc-empty d-none">
      <div class="cc-empty-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </div>
      <h4>Nenhum cliente encontrado</h4>
      <p>Tente ajustar os filtros ou a busca</p>
    </div>

    <div class="cc-table-wrap">
      <table class="cc-table">
        <thead>
          <tr>
            <th>Cliente</th>
            <th>Contratos</th>
            <th>Operadoras</th>
            <th>Valor Ativo</th>
            <th>Vidas</th>
            <th>Cliente desde</th>
            <th>Tempo</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="cc-tbody"></tbody>
      </table>
    </div>

    {{-- Pagination --}}
    <div id="cc-pagination" class="cc-pagination d-none">
      <span id="cc-pagination-info" class="cc-pagination-info"></span>
      <div class="cc-pagination-controls">
        <button id="cc-btn-first" class="cc-page-btn" title="Primeira">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/>
          </svg>
        </button>
        <button id="cc-btn-prev" class="cc-page-btn" title="Anterior">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"/>
          </svg>
        </button>
        <span id="cc-page-indicator" class="cc-page-indicator">1 / 1</span>
        <button id="cc-btn-next" class="cc-page-btn" title="Próxima">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"/>
          </svg>
        </button>
        <button id="cc-btn-last" class="cc-page-btn" title="Última">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/>
          </svg>
        </button>
      </div>
    </div>

  </div>

</div>

{{-- =========================================================
     Modal: Detalhe do Cliente
========================================================= --}}
<div class="modal fade cc-detalhe-modal" id="modalDetalheCliente" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="cc-modal-header">
        <div class="cc-modal-client-info">
          <h5 id="cc-detalhe-nome" class="cc-modal-nome">—</h5>
          <span id="cc-detalhe-cnpj" class="cc-modal-cnpj">—</span>
          <div id="cc-detalhe-tempo" class="cc-modal-tempo">—</div>
        </div>
        <button type="button" class="cc-modal-close" data-bs-dismiss="modal" aria-label="Fechar">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>

      <div class="cc-modal-body">
        <div id="cc-detalhe-body">
          <div class="cc-loading">
            <span class="spinner-border spinner-border-sm"></span>
            Carregando contratos…
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

@endsection
