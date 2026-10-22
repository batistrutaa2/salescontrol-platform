# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**SalesControl** is a comprehensive CRM (Customer Relationship Management) system built with Laravel 11 and PHP 8.2+ designed for sales companies, brokerages, and commercial teams. The system manages the complete sales cycle including contacts, leads, sales, commissions, and integrations with PABX systems.

## Technology Stack

### Backend
- **Laravel 11** with Jetstream for authentication and team management
- **PHP 8.2+** with strict typing
- **MySQL/PostgreSQL** as database
- **Eloquent ORM** for database interactions
- **Laravel Sanctum** for API authentication

### Frontend
- **Materio Template** - Admin UI theme
- **Vite 5.2** - Build tool and asset bundler
- **JavaScript ES6+** with jQuery
- **SCSS/CSS** for styling
- **DataTables** for data grids
- **ApexCharts** for visualizations

### Key Dependencies
- **maatwebsite/excel** - Excel import/export functionality
- **yajra/laravel-datatables-oracle** - Server-side DataTables
- **barryvdh/laravel-dompdf** - PDF generation

## Development Commands

### OBRIGATÓRIO: use Docker via `./dev`

O projeto usa o ambiente Docker enxuto próprio (`Dockerfile.dev` +
`compose.dev.yaml`). **Nunca rode PHP, Composer, Node, npm ou Artisan diretamente
no host.** Todo comando de desenvolvimento passa pelo wrapper `./dev`.

O MySQL é único e compartilhado entre os produtos, definido em
`../docker-compose.yml` (container `brsolutions-mysql`, rede `brsolutions`). O
SalesControl usa os databases `salescontrol`, `salescontrol_people` e `testing`;
não adicione um serviço MySQL ao Compose deste projeto.

```bash
# WRONG - Do NOT use:
php artisan migrate
php artisan serve
composer install

# CORRETO - sempre use o wrapper Docker:
./dev artisan migrate
./dev up
./dev composer install
```

### Initial Setup
```bash
# Install PHP dependencies
./dev composer install

# Install JavaScript dependencies
./dev npm install

# Setup environment
cp .env.example .env
./dev artisan key:generate

# Run migrations and seed database
./dev artisan migrate
./dev artisan db:seed
```

### Development Workflow
```bash
# Sobe app + Vite e garante o MySQL global
./dev up

# Serviços opcionais, sempre em containers separados
./dev queue
./dev scheduler
./dev reverb

# Watch and compile frontend assets (development)
./dev up

# Run code formatting
./dev pint

# Run tests
./dev test
```

**IMPORTANT:** Never run `npm run build` - the user handles asset compilation manually.

### Database Operations
```bash
# Create a new migration
./dev artisan make:migration create_table_name

# Run migrations
./dev artisan migrate

# Rollback last migration
./dev artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
./dev artisan migrate:fresh

# Seed database
./dev artisan db:seed
```

### Artisan Commands
```bash
# Clear all caches
./dev artisan optimize:clear

# Create controller
./dev artisan make:controller ControllerName

# Create model with migration
./dev artisan make:model ModelName -m

# Create repository
./dev artisan make:class Repositories/Eloquent/RepositoryName
```

## Architecture & Code Organization

The project follows **Repository Pattern** with a modular architecture:

```
app/
├── Http/Controllers/
│   ├── Auth/              # Authentication controllers
│   ├── api/               # API endpoints
│   ├── pages/             # Main application controllers
│   │   ├── comercial/     # Sales and lead management
│   │   ├── backoffice/    # Administrative operations
│   │   ├── vendas/        # Sales processing
│   │   ├── pabx/          # Phone system integration
│   │   ├── comissionamento/ # Commission calculations
│   │   ├── financeiro/    # Financial operations
│   │   ├── relatorios/    # Reports and analytics
│   │   └── manager/       # User and company management
├── Models/                # Eloquent models
│   ├── People/            # Person-related models
│   └── Legace/            # Legacy data models
├── Repositories/
│   ├── Contracts/         # Repository interfaces
│   └── Eloquent/          # Eloquent implementations
├── UseCases/              # Business logic layer
├── Imports/               # Excel import classes
├── Enums/                 # Enumerations and constants
├── Helpers/               # Helper functions
├── Notifications/         # Notification classes
├── Jobs/                  # Queue jobs
└── Modules/               # Feature modules
```

### Key Architectural Principles

1. **Repository Pattern**: All database interactions should go through repositories in `app/Repositories/Eloquent/`
2. **Use Cases**: Business logic is encapsulated in `app/UseCases/` classes
3. **Controllers**: Handle HTTP requests and delegate to use cases or repositories
4. **Models**: Use Eloquent relationships extensively for data associations

## Database Schema

### Core Tables

**empresas** - Companies/Organizations
- Multi-tenancy support - all data is scoped to empresa_id

**users** - System users (sellers, managers, admins)
- Links to: empresas (empresa_id), user_roles (user_role_id)
- Fields: name, email, password, ativo, obs_contrato, birthdate

**contatos** - Contact/Lead database
- Imported contacts from various sources
- Links to: empresas, users (user_import_id)
- Fields: nome_cliente, cpf, telefones (1-3), email, plano, categoria, entidade, idades, valor_plano_atual
- Important: id_unico for deduplication, tipo_layout for data source type, possui_cnpj flag

**contatos_corretores** - Contact-to-Broker assignment (pivot table)
- Links contacts to specific brokers/sellers
- Links to: empresas, contatos, users, tabulacoes, sub_tabulacao_id
- Fields: temperatura (lead temperature/score)

**tabulacoes** - Lead status/classification system
- Configurable lead statuses (kanban stages)
- Fields: descricao, tipo_tabulacao (C=Comercial, A=Administrativo), efetivo (Y/N), ordem_kanban
- Can have sub_tabulacao for hierarchical classification

**vendas** - Sales/Contracts
- Links to: empresas, users (seller), contatos, planos
- Fields: nome_contrato, cpf_cnpj, data_vigencia, data_implantacao, telefones (1-2), operadora, nome_plano, valor_contrato, vidas, numero_proposta
- Commission fields: comissao_valor, comissao_percentual, angariacao (NOVA/UPGRADE)
- Fields for payment status: motivo_pendencia, path_boleto

**vendas_titulares** - Primary beneficiaries on contracts
- Links to: vendas
- Fields: nome, cpf, data_nascimento, telefone, email

**agendamentos** - Scheduled appointments/meetings
- Links to: empresas, users, contatos
- Fields: horario_agendamento, observacao, notificado (Y/N)

**comentarios** - Comments/notes on contacts
- Links to: empresas, users, contatos
- Fields: anotacao (text), created_at for timeline

**ligacoes** - Call logs from PABX integration
- Links to: empresas, users, contatos, tabulacoes
- Fields: telefone, id_call
- Used to track all phone interactions

**ramais** - PABX extensions
- Links to: empresas, users
- Fields: ramal, senha, discador

**demandas** - Internal task/demand management
- Links to: empresas, created_by (user), assigned_to (user)
- Fields: titulo, descricao, prioridade (BAIXA/MEDIA/ALTA), status (ABERTA/EM_ANDAMENTO/CONCLUIDA/CANCELADA), data_limite

### Financial Tables

**operadoras** - Insurance operators/carriers
- Fields: nome, categoria, status

**planos** - Insurance plans
- Links to: operadoras
- Fields: nome, tipo, coparticipacao (Y/N), acomodacao, categoria

**comissionamento_configuracoes** - Commission rate configurations
- Links to: empresas, operadoras
- Fields: percentual_comissao, grade_imposto (tax bracket)

**regras_comissionamento** - Commission rules
- Links to: empresas, operadoras
- Fields: nome, percentual_comissao

**regras_comissionamento_parcelas** - Commission installment rules
- Links to: regras_comissionamento
- Fields: numero_parcela, percentual

**comissao_pagamentos** - Commission payment batches
- Links to: empresas, vendedor_id (user), created_by (user)
- Fields: mes (YYYY-MM), data_pagamento, percentual_comissao, percentual_imposto
- Totals: total_bruto, total_imposto, total_liquido, salario, total_receber

**comissao_pagamento_itens** - Individual commission line items
- Links to: comissao_pagamentos, vendas, conta_pagamento_id, ajuste_id
- Fields: descricao, tipo_lancamento (COMISSAO/BONIFICACAO/AJUSTE/PRESTACAO), angariacao (NOVA/UPGRADE)
- Values: valor_bruto, valor_imposto, valor_liquido

**lancamentos_debito_credito** - Debit/Credit transactions
- Links to: empresas, users, created_by
- Fields: descricao, tipo (DEBITO/CREDITO), tipo_lancamento, valor, data_lancamento, status
- Installment fields: numero_parcelas, numero_parcela_atual, lancamento_pai_id

**recebiveis** - Receivables tracking
- Links to: empresas, vendas, operadoras, created_by
- Fields: mes_referencia, valor_total, valor_pago, data_pagamento, status

**contas_pagamento** - Payment accounts
- Links to: empresas
- Fields: nome, tipo, saldo

### Supporting Tables

**dependentes** - Dependents on health plans
- Links to: contatos
- Fields: nome, cpf, data_nascimento, telefones (1-2), categoria, valor_plano

**transferencia_contatos** - Contact transfer logs
- Tracks when contacts are transferred between brokers
- Links to: empresas, contatos, de_user_id, para_user_id, autorizado_por

**meta_configuracoes** - Sales goal configurations
- Links to: empresas, users
- Fields: tipo_meta, periodo, valor_meta, quantidade_meta

**ranking_vendas** - Sales rankings/leaderboards
- Links to: empresas, users
- Tracks sales performance metrics

**log_atividades** - Activity audit log
- Links to: empresas, users
- Fields: acao, descricao, ip_address, user_agent

**preditiva** - Predictive dialer campaigns
- Links to: empresas
- Fields for managing automated calling campaigns

**log_preditiva** - Predictive dialer call logs
- Detailed logs of predictive dialer activities

**comercial_reunioes** - Commercial meetings
- Links to: empresas, users
- Meeting scheduling and tracking

## Important Business Logic

### Multi-Tenancy
- **All queries MUST filter by empresa_id** from authenticated user
- Use `auth()->user()->empresa_id` to scope data
- Never show data across companies

### Contact Management
- Contacts can be in multiple states via `contatos_corretores`
- A contact can belong to only one broker at a time (current assignment)
- Use `tabulacoes` to track lead status in sales funnel
- The `temperatura` field indicates lead quality/priority

### Sales Flow
1. Contact imported → `contatos`
2. Assigned to broker → `contatos_corretores`
3. Broker qualifies lead → update `tabulacao_id`
4. Sale closed → create `vendas` record
5. Commission calculated → `comissao_pagamentos` + `comissao_pagamento_itens`

### Commission Calculation
- Commission rates defined in `comissionamento_configuracoes` per operator
- Can use `regras_comissionamento` for custom rules with installment percentages
- Monthly batch processing creates `comissao_pagamentos` records
- Individual sale commissions stored in `comissao_pagamento_itens`
- Support for NOVA (new) vs UPGRADE angariacao types
- Tax deductions based on `grade_imposto` configuration

### PABX Integration
- Extensions managed in `ramais` table
- All calls logged to `ligacoes`
- Supports both manual dialing and predictive dialer (`preditiva` tables)
- Call outcomes classified via `tabulacoes`

## Common Patterns

### Query Scoping
Always scope queries by empresa_id:
```php
$contatos = Contatos::where('empresa_id', auth()->user()->empresa_id)->get();
```

### Using Repositories
```php
// Inject repository in controller
public function __construct(private ContatoRepository $contatoRepository) {}

// Use repository methods
$contatos = $this->contatoRepository->findByEmpresa($empresaId);
```

### DataTables Integration
Many list views use Yajra DataTables for server-side processing:
```php
return DataTables::of($query)
    ->addColumn('action', function($row) { /* actions */ })
    ->make(true);
```

### Excel Imports
Import classes in `app/Imports/` extend `Maatwebsite\Excel\Concerns\ToModel`:
```php
Excel::import(new ContatosImport, $file);
```

## File Locations

### Views
- Blade templates: `resources/views/content/pages/`
- Layouts: `resources/views/layouts/`
- Components: `resources/views/components/`

### Frontend Assets
- JavaScript: `resources/assets/js/`
- SCSS: `resources/assets/scss/`
- Compiled to: `public/build/` (via Vite)

### Routes
- Web routes: `routes/web.php`
- API routes: `routes/api.php`

### Configuration
- App config: `config/`
- Environment: `.env`

## Testing Considerations

- When adding features, consider multi-tenancy (empresa_id scoping)
- Test with different user roles (admin, manager, seller)
- Validate all foreign key relationships exist before insertion
- Consider timezone handling for datetime fields
- Excel imports should handle data validation and duplicates

## Database Migration Notes

- Use foreign keys with `onUpdate('no action')->onDelete('no action')` pattern
- Most tables have standard Laravel timestamps
- Many tables use `unsignedBigInteger` for foreign keys
- Enum fields are common for status/type columns
- Decimal fields for currency use (15,2) or (10,2) precision

## Modules

The `app/Modules/` directory contains feature-specific modules that encapsulate related functionality. When adding features related to existing modules, place code within the appropriate module structure.

## Frontend Guidelines

### ApexCharts Theme Configuration

When creating charts with ApexCharts, **ALWAYS** configure them to support both light and dark themes. Follow this pattern:

```javascript
'use strict';

let chartVariable;

(function () {
    // Configure colors based on current theme
    let cardColor, labelColor, headingColor, borderColor;

    if (isDarkStyle) {
        cardColor = config.colors_dark.cardColor;
        labelColor = config.colors_dark.textMuted;
        headingColor = config.colors_dark.headingColor;
        borderColor = config.colors_dark.borderColor;
    } else {
        cardColor = config.colors.cardColor;
        labelColor = config.colors.textMuted;
        headingColor = config.colors.headingColor;
        borderColor = config.colors.borderColor;
    }

    // Example chart configuration
    const chartOptions = {
        chart: {
            type: 'bar',
            height: 350
        },
        // Use labelColor for axis labels
        xaxis: {
            labels: {
                style: {
                    colors: labelColor  // Adapts to theme
                }
            }
        },
        yaxis: {
            title: {
                style: {
                    color: headingColor  // Adapts to theme
                }
            },
            labels: {
                style: {
                    colors: labelColor  // Adapts to theme
                }
            }
        },
        // Use white for data labels (contrast with chart colors)
        dataLabels: {
            enabled: true,
            style: {
                colors: ['#fff']
            }
        },
        // Use labelColor for legends
        legend: {
            labels: {
                colors: labelColor,
                useSeriesColors: false
            }
        }
    };
})();
```

**Key Points:**
- `isDarkStyle` is a global variable that detects current theme
- `config.colors` and `config.colors_dark` provide theme-specific color palettes
- Use `labelColor` for all axis labels and legend text
- Use `headingColor` for chart titles and axis titles
- Use `#fff` (white) for data labels inside/on chart elements for contrast
- **NEVER** hardcode colors like `#304758` for text elements - always use theme variables

**Common Variables:**
- `labelColor` - For secondary text (axis labels, legend)
- `headingColor` - For primary text (titles, headings)
- `borderColor` - For borders and grid lines
- `cardColor` - For card backgrounds

This ensures charts remain readable in both light and dark modes.

### Flatpickr Date Configuration

When using Flatpickr for date inputs, configure for Brazilian format:

```javascript
// Single date picker
flatpickr('.flatpickr-date', {
    dateFormat: 'd/m/Y',  // Brazilian format (DD/MM/YYYY)
    locale: 'pt'
});

// Date range picker
flatpickr('.flatpickr-range', {
    mode: 'range',
    dateFormat: 'd/m/Y',
    locale: 'pt',
    altInput: true,
    altFormat: 'd/m/Y'
});
```

**Converting dates for backend:**
```javascript
function converterDataParaBackend(dataBrasileira) {
    // Converts dd/mm/yyyy to yyyy-mm-dd for Laravel
    if (!dataBrasileira) return '';

    const partes = dataBrasileira.split('/');
    if (partes.length !== 3) return '';

    const dia = partes[0].padStart(2, '0');
    const mes = partes[1].padStart(2, '0');
    const ano = partes[2];

    return `${ano}-${mes}-${dia}`;
}
```

### DataTables Date Formatting

Dates from backend come pre-formatted as `'DD/MM/YYYY HH:mm:ss'`. **DO NOT** format them again with Moment.js:

```javascript
// CORRECT - dates already formatted
{
    data: 'created_at',
    render: function (data) {
        if (!data) return '<span class="text-muted">N/D</span>';
        return data;  // Already formatted by backend
    }
}

// WRONG - do not use moment()
{
    data: 'created_at',
    render: function (data) {
        return moment(data).format('DD/MM/YYYY HH:mm:ss');  // ❌ Don't do this
    }
}
```
## Tema Licas (por empresa)

Empresas com `empresas.tema = 'licas'` recebem a classe `theme-licas` no `<html>` e um visual
próprio: marinho sobre papel, Barlow / Barlow Condensed / Cinzel, claro e escuro. A fonte da
verdade é `DESIGN-LICAS.md`; a seção "Design System - SalesControl UI" abaixo
(roxo, glass, Plus Jakarta Sans, JetBrains Mono) **não** se aplica a essas empresas.

- Tudo do tema fica em `resources/assets/vendor/scss/licas/` e é sempre escopado em
  `html.theme-licas`; nada ali pode alterar o visual das outras empresas.
- `_remap.scss` é GERADO por `python3 tools/licas-remap.py`: ele redefine os tokens antigos de
  cada tela (`--dash-*`, `--pv-*`, `--kb-*`...) para os tokens Licas. Token novo numa tela
  antiga → rodar o script de novo; não editar o arquivo à mão.
- `_hardcoded.scss` é GERADO por `node tools/licas-hardcoded.mjs` (rodar no container do
  vite: `docker exec salescontrol-platform-dev-vite-1 sh -c 'cd /var/www/html && node tools/licas-hardcoded.mjs'`):
  compila o SCSS das telas e os `<style>` dos Blades e sobrescreve as cores da identidade
  antiga escritas direto no CSS (roxo, ciano, gradientes, brilhos). Mexeu em CSS de tela
  antiga → rodar os dois geradores.
- Telas redesenhadas ganham um arquivo em `licas/pages/` importado no `licas.scss`.
- Gráficos ApexCharts passam por `resources/assets/js/licas-charts.js`, que troca as cores
  sozinho; `data-licas-series="marca"` no elemento força todas as séries para o marinho.
- Escolha do tema: `Helper::temaEmpresa()` (empresa ativa pelo `TenantContext`). O login usa
  `config('custom.tema_login')` (`TEMA_LOGIN` no `.env`, padrão `licas`).

## Design System - SalesControl UI

### Visão Geral

O projeto utiliza um Design System moderno baseado em **Glass Morphism + Gradient**. Todas as novas telas devem seguir este padrão para manter consistência visual.

**Arquivo de referência:** `resources/assets/vendor/scss/pages/dashboard-analytics.scss`

### Paleta de Cores

```scss
// Primary - Violet/Purple (cor principal do sistema)
--dash-primary: #7C3AED;
--dash-primary-light: #A78BFA;
--dash-primary-rgb: 124, 58, 237;

// Success - Green (implantado, sucesso, confirmado)
--dash-success: #10B981;
--dash-success-light: #34D399;
--dash-success-rgb: 16, 185, 129;

// Info - Cyan (informativo, leads, contatos)
--dash-info: #06B6D4;
--dash-info-light: #22D3EE;
--dash-info-rgb: 6, 182, 212;

// Warning - Amber (pendente, atenção)
--dash-warning: #F59E0B;
--dash-warning-light: #FBBF24;
--dash-warning-rgb: 245, 158, 11;

// Danger - Red (cancelado, erro)
--dash-danger: #EF4444;
--dash-danger-light: #F87171;
--dash-danger-rgb: 239, 68, 68;
```

### Tipografia

```scss
// Importar fontes no início do SCSS
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap');

// Font principal para UI
font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;

// Font para valores monetários e números
font-family: 'JetBrains Mono', monospace;
```

### Estrutura de Cards

```scss
// Card base
--dash-card-bg: rgba(255, 255, 255, 0.95);
--dash-card-border: rgba(255, 255, 255, 0.2);
--dash-glass-bg: rgba(255, 255, 255, 0.7);
--dash-glass-blur: 12px;

// Border radius padrão
--dash-border-radius: 16px;
--dash-border-radius-sm: 12px;
--dash-border-radius-lg: 24px;

// Shadows
--dash-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
--dash-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
--dash-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.12);
--dash-shadow-glow: 0 0 40px rgba(124, 58, 237, 0.15);
```

### Dark Theme

```scss
.dark-style {
    --dash-card-bg: rgba(30, 32, 47, 0.95);
    --dash-card-border: rgba(255, 255, 255, 0.08);
    --dash-glass-bg: rgba(30, 32, 47, 0.8);

    --dash-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.2);
    --dash-shadow-md: 0 4px 20px rgba(0, 0, 0, 0.3);
    --dash-shadow-lg: 0 8px 40px rgba(0, 0, 0, 0.4);
    --dash-shadow-glow: 0 0 60px rgba(124, 58, 237, 0.25);

    --dash-text-primary: #F9FAFB;
    --dash-text-secondary: #D1D5DB;
    --dash-text-muted: #9CA3AF;
}
```

### KPI Cards Pattern

Os KPI cards seguem este padrão visual:

```html
<div class="kpi-card kpi-primary">
    <div class="kpi-icon-wrapper">
        <div class="kpi-icon">
            <svg><!-- Ícone SVG inline --></svg>
        </div>
        <div class="kpi-pulse"></div>
    </div>
    <div class="kpi-content">
        <span class="kpi-label">Label do KPI</span>
        <h2 class="kpi-value">R$ 0,00</h2>
        <div class="kpi-trend trend-up">
            <svg><!-- Ícone de tendência --></svg>
            <span>+12%</span>
        </div>
    </div>
    <div class="kpi-glow"></div>
</div>
```

**Variantes de KPI:**
- `kpi-primary` - Violet (valor principal, contratos)
- `kpi-success` - Green (implantado, concluído)
- `kpi-info` - Cyan (contatos, leads)
- `kpi-warning` - Amber (conversão, pendente)

**Características visuais:**
- Ícone com gradient e cor branca
- Efeito pulse no hover
- Glow effect no hover
- Valores em fonte monospace (JetBrains Mono)
- Trend badges com cores de status

### Ícones SVG

Usar ícones SVG inline para melhor controle de cor. Exemplos:

```html
<!-- Dinheiro/Valor -->
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
</svg>

<!-- Check/Implantado -->
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
    <polyline points="22 4 12 14.01 9 11.01"/>
</svg>

<!-- Usuários/Contatos -->
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
    <circle cx="9" cy="7" r="4"/>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
</svg>

<!-- Gráfico/Conversão -->
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
    <path d="M22 12A10 10 0 0 0 12 2v10z"/>
</svg>

<!-- Tendência Up -->
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
    <polyline points="17 6 23 6 23 12"/>
</svg>
```

### Chart Cards Pattern

```html
<div class="chart-card chart-large">
    <div class="chart-header">
        <div class="chart-title-group">
            <h3 class="chart-title">Título do Gráfico</h3>
            <span class="chart-subtitle">Subtítulo descritivo</span>
        </div>
        <div class="chart-legend">
            <span class="legend-item">
                <span class="legend-dot primary"></span>
                Cadastradas
            </span>
        </div>
    </div>
    <div class="chart-body">
        <div id="chartContainer"></div>
    </div>
</div>
```

### Table Cards Pattern

```html
<div class="table-card">
    <div class="table-header">
        <div class="table-title-group">
            <div class="table-icon cadastrados">
                <svg><!-- Ícone --></svg>
            </div>
            <div>
                <h3 class="table-title">Título da Tabela</h3>
                <span class="table-subtitle">Subtítulo</span>
            </div>
        </div>
        <div class="table-badge cadastrados">
            <span>0</span> itens
        </div>
    </div>
    <div class="table-body">
        <table class="custom-table">...</table>
    </div>
</div>
```

### Animações e Transições

```scss
// Transição padrão
--dash-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
--dash-transition-fast: all 0.15s ease;

// Hover em cards
&:hover {
    transform: translateY(-4px);
    box-shadow: var(--dash-shadow-lg), var(--dash-shadow-glow);
}

// Stagger animation para KPIs
.kpi-grid > *:nth-child(1) { animation-delay: 0ms; }
.kpi-grid > *:nth-child(2) { animation-delay: 100ms; }
.kpi-grid > *:nth-child(3) { animation-delay: 200ms; }
.kpi-grid > *:nth-child(4) { animation-delay: 300ms; }
```

### Regras Importantes

1. **SEMPRE** usar as CSS variables do design system
2. **SEMPRE** suportar dark mode com `.dark-style`
3. **SEMPRE** usar SVG inline para ícones (não Tabler Icons via classe)
4. **SEMPRE** usar `JetBrains Mono` para valores monetários
5. **SEMPRE** usar `Plus Jakarta Sans` para textos
6. **NUNCA** hardcodar cores - usar as variáveis
7. **SEMPRE** incluir efeitos hover nos cards
8. **SEMPRE** usar border-radius de 16px para cards principais

<!-- skills-frontend:start -->
## Skills de interface (obrigatório)

Toda alteração de UI deste repo passa pelas **duas** skills, nesta ordem. Regra completa e
precedência em `~/.claude/CLAUDE.md` (global).

1. **Direção visual — fonte da verdade:** a seção "Design System - SalesControl UI" deste arquivo + `resources/assets/vendor/scss/pages/dashboard-analytics.scss`.
   O design system deste repo **vence** sobre o gosto das skills. Elas existem para caçar
   defaults que ninguém escolheu e manter coerência entre telas, não para trocar a identidade.
2. **`avoid-ai-design`** — catálogo de vícios + scanner. Rodar antes e depois de mexer:
   `node ~/.claude/skills/avoid-ai-design/scripts/detect.mjs <arquivos ou pasta>`
   (exit 2 = tem P0/P1). Meta: zero P0 e nenhum tell SD que o brief não pediu.
3. **`antislop`** — filtro de propósito (toda decisão visual precisa de motivo escrito) +
   Delivery Gate antes de entregar. Modo **DURING** para tela nova ou reescrita; **AFTER**
   só quando o pedido for auditar tela existente. **Não rodar o install wizard** nem criar
   pasta `skills/` aqui: já está instalado como plugin (`antislop@anti-slop`).
4. **Contraste WCAG** (não chutar):
   `python3 "$(ls -d ~/.claude/plugins/cache/anti-slop/antislop/*/skills/antislop-human/contrast-check.py | tail -1)" "#fg" "#bg"`
5. **Perfil de contexto:** `dashboard` — densidade, legibilidade e hierarquia da informação acima de decoração.
6. **Antes de editar:** inspecionar uma tela equivalente já implementada (o Cofre de Acessos é a
   referência operacional). Bootstrap sozinho não representa o design system; não criar identidade
   isolada por rota. Direções específicas de `DESIGN.md` (ex.: Temporada Comercial) valem só para a
   superfície documentada. Empresas Licas seguem `DESIGN-LICAS.md` e a seção "Tema Licas".
7. **Verificar** desktop e celular, temas claro e escuro, controles e estados com dados, sem
   alterar regra de negócio em ajuste visual.

> **Atenção, conflito conhecido:** o design system atual deste projeto é *Glass Morphism + Gradient*
> com primária violeta `#7C3AED`. Isso casa com três tells que as skills acusam por padrão
> (C1 gradiente roxo/indigo, C2 primária violeta, K3 glassmorphism). Pela regra de precedência o
> design system do projeto vence, então **não trocar a paleta por conta própria**: marcar com
> `avoid-ai-design-ignore` e aplicar as skills no resto (hierarquia, espaçamento, estados,
> contraste, foco, copy). Se a decisão for revisar a identidade, isso é conversa com o dono primeiro.

O que o design system deste projeto exige e o scanner acusa deve ser marcado com um comentário
`avoid-ai-design-ignore: <ID>` na linha, para o scanner parar de reclamar do que foi escolhido.
<!-- skills-frontend:end -->

## Produto

Contexto de produto e regras de negócio (migrado do antigo `PRODUCT.md`).


### Platform

web

### Users

Equipes comerciais de corretoras. Vendedores trabalham suas próprias carteiras; supervisores, administrativos e desenvolvedores administram bases, filas e distribuição de leads.

### Product Purpose

O SalesControl centraliza o ciclo comercial de leads, propostas e contratos. O produto deve permitir que a gestão receba bases novas, preserve a origem e o histórico de cada contato e distribua oportunidades para vendedores com controle e rastreabilidade.

### Operating Context

Leads entram por mailings, mídia paga e cadastros internos. A operação usa filas distintas para prospecção, preditiva, remarketing, descarte e pós-venda. O reservatório guarda somente oportunidades novas antes da primeira distribuição e permite liberações graduais por estratégias definidas pelo gestor.

### Capabilities and Constraints

- Todo dado é isolado por empresa.
- Elegibilidade e permissões críticas são validadas no servidor.
- A importação persistente já classifica itens novos, duplicados e inválidos por CPF/CNPJ.
- Um contato entra no reservatório no máximo uma vez; distribuído ou bloqueado não retorna.
- Estratégias filtram os campos existentes do lead. O gestor escolhe vendedores e quantidades em cada execução, e os leads compatíveis são sorteados.
- Remarketing, descarte, preditiva, negócio fechado e vendas válidas não pertencem ao reservatório.
- Uma carga inicial excepcional pode recolher, uma única vez, os leads aptos concentrados em um vendedor.

### Product Principles

- Custódia explícita: cada lead deve ter uma posição operacional inequívoca.
- Segurança antes da conveniência: filtros de tela nunca substituem validações transacionais.
- Decisões persistentes e auditáveis: importações, estratégias e distribuições sobrevivem a refresh e concorrência.
- Clareza operacional: tabelas priorizam leitura, origem, estado e próxima ação.
- Preservação do histórico: transferências de custódia não apagam evidências de trabalho.
