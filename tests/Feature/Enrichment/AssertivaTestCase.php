<?php

namespace Tests\Feature\Enrichment;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

/**
 * Base para os testes da integração Assertiva.
 *
 * Aponta a conexão `people_db` para a conexão de teste padrão ANTES das migrations
 * rodarem (assim as `assertiva_*` são criadas no banco de teste, não no MySQL real).
 *
 * O cache legado da Lemit não é recriado: a plataforma consulta essa fonte
 * diretamente e mantém apenas o cache Assertiva isolado por empresa.
 */
abstract class AssertivaTestCase extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        $app['config']->set(
            'database.connections.people_db',
            $app['config']->get('database.connections.'.$app['config']->get('database.default'))
        );

        return $app;
    }

    protected function refreshTestDatabase()
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            $this->app[Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function connectionsToTransact()
    {
        return [config('database.default'), 'people_db'];
    }
}
