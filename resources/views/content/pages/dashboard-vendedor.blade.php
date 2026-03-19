@extends('layouts/layoutMaster')

@section('title', 'Dashboard Vendedor')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('page-style')
    @vite('resources/assets/vendor/scss/pages/dashboard-vendedor.scss')
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/dashboard-vendedor.js'])
@endsection

@section('content')
<div class="dv-wrapper">

    <!-- Page Header -->
    <div class="dv-page-header">
        <div class="dv-header-content">
            <div class="dv-header-text">
                <span class="dv-greeting-label">Meu Dashboard</span>
                <h1 class="dv-main-title">Meus Resultados</h1>
                <p class="dv-subtitle">Acompanhe seus resultados e metas</p>
            </div>
            <div class="dv-header-filters">
                <div class="dv-filter-group">
                    <div class="dv-filter-item">
                        <span class="dv-filter-label">Mês</span>
                        <select class="dv-filter-select" id="dv-select-month">
                            <option value="1">Janeiro</option>
                            <option value="2">Fevereiro</option>
                            <option value="3">Março</option>
                            <option value="4">Abril</option>
                            <option value="5">Maio</option>
                            <option value="6">Junho</option>
                            <option value="7">Julho</option>
                            <option value="8">Agosto</option>
                            <option value="9">Setembro</option>
                            <option value="10">Outubro</option>
                            <option value="11">Novembro</option>
                            <option value="12">Dezembro</option>
                        </select>
                    </div>
                    <div class="dv-filter-item">
                        <span class="dv-filter-label">Ano</span>
                        <select class="dv-filter-select" id="dv-select-year">
                            <option value="2026">2026</option>
                            <option value="2025">2025</option>
                            <option value="2024">2024</option>
                            <option value="2023">2023</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="dv-kpi-grid">
        <!-- Vendas Cadastradas R$ -->
        <div class="dv-kpi-card dv-kpi-primary">
            <div class="dv-kpi-icon-wrapper">
                <div class="dv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div class="dv-kpi-pulse"></div>
            </div>
            <div class="dv-kpi-content">
                <span class="dv-kpi-label">Vendas Cadastradas</span>
                <h2 class="dv-kpi-value" id="dv-sales-registered">R$ 0,00</h2>
            </div>
            <div class="dv-kpi-glow"></div>
        </div>

        <!-- Vendas Implantadas R$ -->
        <div class="dv-kpi-card dv-kpi-success">
            <div class="dv-kpi-icon-wrapper">
                <div class="dv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <div class="dv-kpi-pulse"></div>
            </div>
            <div class="dv-kpi-content">
                <span class="dv-kpi-label">Vendas Implantadas</span>
                <h2 class="dv-kpi-value" id="dv-sales-implanted">R$ 0,00</h2>
            </div>
            <div class="dv-kpi-glow"></div>
        </div>

        <!-- Ticket Médio -->
        <div class="dv-kpi-card dv-kpi-info">
            <div class="dv-kpi-icon-wrapper">
                <div class="dv-kpi-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"/>
                        <line x1="12" y1="20" x2="12" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                </div>
                <div class="dv-kpi-pulse"></div>
            </div>
            <div class="dv-kpi-content">
                <span class="dv-kpi-label">Ticket Médio</span>
                <h2 class="dv-kpi-value" id="dv-ticket-medio">R$ 0,00</h2>
            </div>
            <div class="dv-kpi-glow"></div>
        </div>
    </div>

    <!-- Secondary Cards -->
    <div class="dv-info-grid">
        <!-- Ranking Mensal -->
        <div class="dv-highlight-card dv-ranking-card">
            <div class="dv-highlight-header">
                <div class="dv-highlight-icon ranking-mes">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="20" x2="12" y2="10"/>
                        <line x1="18" y1="20" x2="18" y2="4"/>
                        <line x1="6" y1="20" x2="6" y2="16"/>
                    </svg>
                </div>
                <div>
                    <h3 class="dv-highlight-title">Ranking do Mês</h3>
                    <span class="dv-highlight-subtitle" id="dv-ranking-mes-label">Sua posição este mês</span>
                </div>
            </div>
            <div class="dv-ranking-position">
                <span class="dv-ranking-number" id="dv-ranking-mes-pos">—</span>
                <span class="dv-ranking-suffix" id="dv-ranking-mes-suffix">º</span>
            </div>
            <div class="dv-highlight-detail" id="dv-ranking-mes-detail"></div>
        </div>

        <!-- Ranking Trimestral -->
        <div class="dv-highlight-card dv-ranking-card">
            <div class="dv-highlight-header">
                <div class="dv-highlight-icon ranking-tri">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                        <polyline points="17 6 23 6 23 12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="dv-highlight-title">Ranking do Trimestre</h3>
                    <span class="dv-highlight-subtitle" id="dv-ranking-tri-label">Sua posição no trimestre</span>
                </div>
            </div>
            <div class="dv-ranking-position">
                <span class="dv-ranking-number" id="dv-ranking-tri-pos">—</span>
                <span class="dv-ranking-suffix" id="dv-ranking-tri-suffix">º</span>
            </div>
            <div class="dv-highlight-detail" id="dv-ranking-tri-detail"></div>
        </div>

        <!-- Operadora Mais Vendida -->
        <div class="dv-highlight-card">
            <div class="dv-highlight-header">
                <div class="dv-highlight-icon operadora">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="8" r="7"/>
                        <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                    </svg>
                </div>
                <div>
                    <h3 class="dv-highlight-title">Operadora Mais Vendida</h3>
                    <span class="dv-highlight-subtitle">Dados do ano inteiro</span>
                </div>
            </div>
            <div class="dv-highlight-value" id="dv-operadora-nome">—</div>
            <div class="dv-highlight-detail" id="dv-operadora-detail"></div>
            <div class="dv-progress-bar">
                <div class="dv-progress-fill" id="dv-operadora-progress" style="width: 0%"></div>
            </div>
        </div>

        <!-- Taxa de Conversão -->
        <div class="dv-highlight-card">
            <div class="dv-highlight-header">
                <div class="dv-highlight-icon conversao">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="dv-highlight-title">Taxa de Conversão</h3>
                    <span class="dv-highlight-subtitle">Leads vs Vendas</span>
                </div>
            </div>
            <div class="dv-highlight-value" id="dv-taxa-conversao">0%</div>
            <div class="dv-conversion-breakdown">
                <div class="dv-breakdown-item">
                    <span class="dv-breakdown-label">Leads Trabalhados</span>
                    <span class="dv-breakdown-value" id="dv-leads-trabalhados">0</span>
                </div>
                <div class="dv-breakdown-item">
                    <span class="dv-breakdown-label">Vendas Realizadas</span>
                    <span class="dv-breakdown-value" id="dv-vendas-realizadas">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="dv-chart-card" style="margin-bottom: 2rem;">
        <div class="dv-chart-header">
            <div class="dv-chart-title-group">
                <h3 class="dv-chart-title">Evolução Mensal</h3>
                <span class="dv-chart-subtitle">Vendas cadastradas por mês</span>
            </div>
            <div class="dv-chart-legend">
                <span class="dv-legend-item">
                    <span class="dv-legend-dot primary"></span>
                    Vendas (R$)
                </span>
            </div>
        </div>
        <div class="dv-chart-body">
            <div id="dvMonthlyChart"></div>
        </div>
    </div>

    <!-- Recent Sales Table -->
    <div class="dv-table-card">
        <div class="dv-table-header">
            <div class="dv-table-title-group">
                <div class="dv-table-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                        <polyline points="10 9 9 9 8 9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="dv-table-title">Últimas Vendas</h3>
                    <span class="dv-table-subtitle">Vendas mais recentes</span>
                </div>
            </div>
            <div class="dv-table-badge" id="dv-table-count">
                <span>0</span> vendas
            </div>
        </div>
        <div class="dv-table-body">
            <table class="dv-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Operadora</th>
                        <th>Plano</th>
                        <th>Valor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="dv-recent-sales-body">
                    <tr>
                        <td colspan="6" class="dv-empty-state">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
