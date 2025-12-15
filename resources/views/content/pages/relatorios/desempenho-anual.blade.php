@extends('layouts/layoutMaster')

@section('title', 'Relatório de Desempenho Anual')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/desempenho-anual.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('page-script')
    @vite(['resources/assets/js/desempenho-anual.js'])
@endsection

@section('content')
<div class="da-wrapper">
    {{-- Page Header --}}
    <div class="da-page-header">
        <div class="da-title-group">
            <div class="da-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </div>
            <div>
                <h1 class="da-title">Desempenho Anual</h1>
                <p class="da-subtitle">Análise completa de vendas e performance do ano</p>
            </div>
        </div>
    </div>

    {{-- Filter Card --}}
    <div class="da-filter-card">
        <div class="da-filter-row">
            <div class="da-filter-group">
                <label class="da-filter-label">Ano</label>
                <select id="filtroAno" class="da-filter-select">
                    @foreach($anos as $ano)
                        <option value="{{ $ano }}" {{ $ano == date('Y') ? 'selected' : '' }}>{{ $ano }}</option>
                    @endforeach
                </select>
            </div>
            <div class="da-filter-group">
                <label class="da-filter-label">Vendedor</label>
                <select id="filtroVendedor" class="da-filter-select">
                    <option value="">Todos os vendedores</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}">{{ $vendedor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="da-filter-actions">
                <button type="button" id="btnFiltrar" class="da-btn da-btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                    </svg>
                    Filtrar
                </button>
                <button type="button" id="btnLimpar" class="da-btn da-btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M8 16H3v5"/>
                    </svg>
                    Limpar
                </button>
            </div>
        </div>
    </div>

    {{-- Loading --}}
    <div id="loading" class="da-loading" style="display: none;">
        <div class="da-spinner"></div>
        <p class="da-loading-text">Carregando dados do relatório...</p>
    </div>

    {{-- Content --}}
    <div id="conteudoRelatorio" style="display: none;">

        {{-- KPI Stats --}}
        <div class="da-stats-grid">
            <div class="da-stat-card">
                <div class="da-stat-icon stat-leads">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <span class="da-stat-label">Leads Trabalhados</span>
                <div class="da-stat-value" id="totalLeadsUnicos">0</div>
                <div class="da-stat-glow glow-primary"></div>
            </div>

            <div class="da-stat-card">
                <div class="da-stat-icon stat-vendas">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                </div>
                <span class="da-stat-label">Total de Vendas</span>
                <div class="da-stat-value" id="totalVendas">0</div>
                <div class="da-stat-glow glow-success"></div>
            </div>

            <div class="da-stat-card">
                <div class="da-stat-icon stat-valor">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <span class="da-stat-label">Valor Total</span>
                <div class="da-stat-value" id="valorTotal">R$ 0,00</div>
                <div class="da-stat-glow glow-info"></div>
            </div>

            <div class="da-stat-card">
                <div class="da-stat-icon stat-ticket">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="20" x2="12" y2="10"/>
                        <line x1="18" y1="20" x2="18" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="16"/>
                    </svg>
                </div>
                <span class="da-stat-label">Ticket Médio</span>
                <div class="da-stat-value" id="ticketMedio">R$ 0,00</div>
                <div class="da-stat-glow glow-warning"></div>
            </div>
        </div>

        {{-- Trimestre Section --}}
        <div class="da-trimestre-section">
            <h3 class="da-section-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                Análise por Trimestre
            </h3>
            <div class="da-trimestre-grid" id="dadosTrimestre">
                {{-- Trimestre cards rendered via JS --}}
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="da-charts-row">
            {{-- Evolução Mensal --}}
            <div class="da-chart-card">
                <div class="da-chart-header">
                    <h3 class="da-chart-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                        </svg>
                        Evolução Mensal
                    </h3>
                    <div class="da-chart-legend">
                        <span class="da-legend-item">
                            <span class="da-legend-dot dot-primary"></span>
                            Leads
                        </span>
                        <span class="da-legend-item">
                            <span class="da-legend-dot dot-success"></span>
                            Vendas
                        </span>
                    </div>
                </div>
                <div class="da-chart-body">
                    <canvas id="chartEvolucaoMensal" height="100"></canvas>
                </div>
            </div>

            {{-- Taxa de Conversão --}}
            <div class="da-chart-card">
                <div class="da-chart-header">
                    <h3 class="da-chart-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                            <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                        </svg>
                        Taxa de Conversão
                    </h3>
                </div>
                <div class="da-chart-body" style="min-height: 280px; display: flex; align-items: center; justify-content: center;">
                    <canvas id="chartTaxaConversao"></canvas>
                    <div class="da-donut-center" id="donutCenter">
                        <div class="da-donut-value" id="donutValue">0%</div>
                        <div class="da-donut-label">Taxa Média</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tables Row --}}
        <div class="da-tables-row">
            {{-- Top 10 Planos --}}
            <div class="da-table-card">
                <div class="da-table-header">
                    <div class="da-table-icon icon-planos">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <h3 class="da-table-title">Top 10 Planos Mais Vendidos</h3>
                </div>
                <div class="da-table-body">
                    <table class="da-table" id="tabelaTopPlanos">
                        <thead>
                            <tr>
                                <th>Plano</th>
                                <th>Operadora</th>
                                <th style="text-align: center;">Vendas</th>
                                <th style="text-align: right;">Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Distribuição por Operadora --}}
            <div class="da-chart-card">
                <div class="da-chart-header">
                    <h3 class="da-chart-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="3" y1="9" x2="21" y2="9"/>
                            <line x1="9" y1="21" x2="9" y2="9"/>
                        </svg>
                        Distribuição por Operadora
                    </h3>
                </div>
                <div class="da-chart-body" style="min-height: 300px;">
                    <canvas id="chartOperadoras"></canvas>
                </div>
            </div>
        </div>

        {{-- Ranking de Vendedores --}}
        <div id="rankingSection" style="display: none;">
            <div class="da-table-card-full">
                <div class="da-table-header">
                    <div class="da-table-icon icon-ranking">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <h3 class="da-table-title">Ranking de Vendedores</h3>
                </div>
                <div class="da-table-body">
                    <table class="da-table" id="tabelaRankingVendedores">
                        <thead>
                            <tr>
                                <th style="text-align: center; width: 60px;">Posição</th>
                                <th>Vendedor</th>
                                <th style="text-align: center;">Leads</th>
                                <th style="text-align: center;">Vendas</th>
                                <th style="text-align: right;">Valor Total</th>
                                <th style="text-align: right;">Ticket Médio</th>
                                <th style="text-align: center;">Conversão</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Detalhes do Vendedor --}}
        <div id="detalhesVendedorSection" style="display: none;">
            <div class="da-table-card-full">
                <div class="da-table-header">
                    <div class="da-table-icon icon-planos">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h3 class="da-table-title">Planos Favoritos - <span id="nomeVendedor"></span></h3>
                </div>
                <div class="da-table-body">
                    <table class="da-table" id="tabelaPlanosFavoritos">
                        <thead>
                            <tr>
                                <th>Plano</th>
                                <th>Operadora</th>
                                <th style="text-align: center;">Total de Vendas</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Data via JS --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
