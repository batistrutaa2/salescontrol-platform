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

### Initial Setup
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed database
php artisan migrate
php artisan db:seed
```

### Development Workflow
```bash
# Start development server
php artisan serve

# Watch and compile frontend assets (development)
npm run dev

# Build assets for production
npm run build

# Run code formatting
./vendor/bin/pint

# Run tests
php artisan test
```

### Database Operations
```bash
# Create a new migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration (drop all tables and re-migrate)
php artisan migrate:fresh

# Seed database
php artisan db:seed
```

### Artisan Commands
```bash
# Clear all caches
php artisan optimize:clear

# Create controller
php artisan make:controller ControllerName

# Create model with migration
php artisan make:model ModelName -m

# Create repository
php artisan make:class Repositories/Eloquent/RepositoryName
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

**estudos** - Quotation studies
- Links to: empresas, contatos, users
- For managing insurance quotations

**estudo_itens** & **estudo_vidas** - Study details
- Breakdown of quotation studies

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
