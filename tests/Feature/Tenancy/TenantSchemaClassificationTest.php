<?php

namespace Tests\Feature\Tenancy;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\BelongsToTenantThrough;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantSchemaClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_todas_as_tabelas_estao_classificadas_sem_sobreposicao(): void
    {
        $groups = [
            config('tenancy.direct', []),
            array_keys(config('tenancy.inherited', [])),
            config('tenancy.shared_reference', []),
            config('tenancy.infrastructure', []),
            config('tenancy.deprecated', []),
        ];
        $classified = collect($groups)->flatten()->values();

        $this->assertSame(
            $classified->count(),
            $classified->unique()->count(),
            'Uma tabela foi classificada em mais de uma categoria de tenancy.'
        );

        $actual = collect(DB::select(
            'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME'
        ))->pluck('TABLE_NAME')
            ->diff(config('tenancy.external_reference', []))
            ->sort()
            ->values()
            ->all();

        $this->assertSame($actual, $classified->sort()->values()->all());
    }

    public function test_tabelas_de_tenant_direto_possuem_empresa_id(): void
    {
        foreach (config('tenancy.direct', []) as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'empresa_id'),
                "A tabela {$table} foi classificada como tenant direto, mas não possui empresa_id."
            );
        }
    }

    public function test_tabelas_de_tenant_direto_possuem_indice_iniciado_por_empresa_id(): void
    {
        $indexed = collect(DB::select(<<<'SQL'
            SELECT DISTINCT TABLE_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND COLUMN_NAME = 'empresa_id'
              AND SEQ_IN_INDEX = 1
            SQL))->pluck('TABLE_NAME');

        $missing = collect(config('tenancy.direct', []))->diff($indexed)->values()->all();

        $this->assertSame([], $missing, 'Tabelas de tenant sem índice iniciado por empresa_id: '.implode(', ', $missing));
    }

    public function test_consultas_operacionais_criticas_possuem_indices_compostos_por_tenant(): void
    {
        $required = [
            'contatos' => [['empresa_id', 'status'], ['empresa_id', 'cpf']],
            'contatos_corretores' => [['empresa_id', 'contato_id', 'user_id'], ['empresa_id', 'user_id', 'tabulacao_id', 'updated_at']],
            'vendas' => [['empresa_id', 'contato_id'], ['empresa_id', 'user_id', 'created_at'], ['empresa_id', 'backoffice_id', 'tabulacao_id']],
            'agendamentos' => [['empresa_id', 'contato_id'], ['empresa_id', 'user_id', 'horario_agendamento']],
            'preditiva' => [['empresa_id', 'contato_id'], ['empresa_id', 'status', 'user_id', 'data_atribuicao']],
            'ligacoes' => [['empresa_id', 'user_id', 'created_at']],
        ];

        foreach ($required as $table => $expectedIndexes) {
            $actual = collect(DB::select(<<<'SQL'
                SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                GROUP BY INDEX_NAME
                SQL, [$table]))->pluck('columns_list')->all();

            foreach ($expectedIndexes as $columns) {
                $this->assertContains(implode(',', $columns), $actual, "Índice operacional ausente em {$table}: ".implode(',', $columns));
            }
        }
    }

    public function test_unicidades_globais_em_tabelas_de_tenant_estao_justificadas(): void
    {
        $globalUniqueIndexes = collect(DB::select(<<<'SQL'
            SELECT TABLE_NAME, INDEX_NAME,
                   GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columns_list
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND NON_UNIQUE = 0
              AND INDEX_NAME <> 'PRIMARY'
            GROUP BY TABLE_NAME, INDEX_NAME
            ORDER BY TABLE_NAME, INDEX_NAME
            SQL))
            ->filter(fn (object $index) => in_array($index->TABLE_NAME, config('tenancy.direct', []), true))
            ->reject(fn (object $index) => in_array('empresa_id', explode(',', $index->columns_list), true))
            ->map(fn (object $index) => "{$index->TABLE_NAME}.{$index->INDEX_NAME}")
            ->values()
            ->all();

        $documented = array_keys(config('tenancy.global_unique_indexes', []));
        sort($documented);

        $this->assertSame($globalUniqueIndexes, $documented);
    }

    public function test_tabelas_de_tenant_herdado_possuem_o_vinculo_declarado(): void
    {
        foreach (config('tenancy.inherited', []) as $table => $ownership) {
            $this->assertTrue(Schema::hasTable($ownership['parent']), "Pai {$ownership['parent']} não existe para {$table}.");
            $this->assertTrue(
                Schema::hasColumn($table, $ownership['foreign_key']),
                "A tabela {$table} não possui a chave {$ownership['foreign_key']} para resolver seu tenant."
            );
        }
    }

    public function test_tabela_legada_nao_volta_ao_runtime_da_aplicacao(): void
    {
        $runtimeFiles = collect([
            ...File::allFiles(app_path()),
            ...File::allFiles(base_path('routes')),
        ]);

        foreach (config('tenancy.deprecated', []) as $table) {
            $references = $runtimeFiles->filter(
                fn (\SplFileInfo $file) => str_contains(File::get($file->getPathname()), $table)
            );

            $this->assertTrue($references->isEmpty(), "A tabela desativada {$table} voltou ao runtime.");
        }
    }

    public function test_models_de_tenant_direto_usam_escopo_automatico_ou_excecao_justificada(): void
    {
        $exceptions = array_keys(config('tenancy.model_scope_exceptions', []));
        $missing = collect(File::allFiles(app_path()))
            ->filter(fn (\SplFileInfo $file) => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR))
            ->map(fn (\SplFileInfo $file) => 'App\\'.str_replace(
                [app_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
                ['', '\\', ''],
                $file->getPathname()
            ))
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class))
            ->map(fn (string $class) => new $class)
            ->filter(fn (Model $model) => in_array($model->getTable(), config('tenancy.direct', []), true))
            ->reject(fn (Model $model) => in_array($model->getTable(), $exceptions, true))
            ->reject(fn (Model $model) => in_array(BelongsToTenant::class, class_uses_recursive($model), true))
            ->map(fn (Model $model) => $model::class.' ('.$model->getTable().')')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Models diretos sem BelongsToTenant: '.implode(', ', $missing));
    }

    public function test_models_de_tenant_herdado_usam_escopo_automatico_pelo_pai(): void
    {
        $missing = collect(File::allFiles(app_path()))
            ->filter(fn (\SplFileInfo $file) => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR))
            ->map(fn (\SplFileInfo $file) => 'App\\'.str_replace(
                [app_path().DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, '.php'],
                ['', '\\', ''],
                $file->getPathname()
            ))
            ->filter(fn (string $class) => class_exists($class) && is_subclass_of($class, Model::class))
            ->map(fn (string $class) => new $class)
            ->filter(fn (Model $model) => array_key_exists($model->getTable(), config('tenancy.inherited', [])))
            ->reject(fn (Model $model) => in_array(BelongsToTenantThrough::class, class_uses_recursive($model), true))
            ->map(fn (Model $model) => $model::class.' ('.$model->getTable().')')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([], $missing, 'Models herdados sem BelongsToTenantThrough: '.implode(', ', $missing));
    }
}
