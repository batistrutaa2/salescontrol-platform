<?php

namespace Tests\Feature\LkBeneficios;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\Feature\LkBeneficios\Concerns\UsesPeopleDb;
use Tests\TestCase;

/**
 * Base para os testes da integração Assertiva.
 *
 * Aponta a conexão `people_db` para a conexão de teste padrão ANTES das migrations
 * rodarem (assim as `assertiva_*` são criadas no banco de teste, não no MySQL real).
 *
 * As tabelas legadas do Lemit (`pessoas`/`celulares`/...) não têm migration no repo;
 * são criadas UMA vez, entre o migrate:fresh e o início da transação de teste, para
 * que o DDL (que no MySQL faz COMMIT implícito) não quebre o isolamento transacional.
 */
abstract class AssertivaTestCase extends TestCase
{
    use RefreshDatabase;
    use UsesPeopleDb;

    private static bool $peopleTablesReady = false;

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
            self::$peopleTablesReady = false;
        }

        if (! self::$peopleTablesReady) {
            $this->preparePeopleDb();
            self::$peopleTablesReady = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function connectionsToTransact()
    {
        return [config('database.default'), 'people_db'];
    }
}
