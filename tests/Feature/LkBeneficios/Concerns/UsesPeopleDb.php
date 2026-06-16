<?php

namespace Tests\Feature\LkBeneficios\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O `people_db` é um MySQL externo sem migrations versionadas neste repositório.
 * Nos testes apontamos `people_db` para a MESMA conexão de teste padrão e criamos
 * sob demanda as tabelas do Lemit (`pessoas`/`celulares`/`fixos`/`emails`), já que
 * as `assertiva_*` são criadas pelas migrations versionadas.
 *
 * Use em conjunto com RefreshDatabase. Chame {@see preparePeopleDb()} no setUp,
 * e inclua 'people_db' em connectionsToTransact() para rollback entre testes.
 */
trait UsesPeopleDb
{
    /**
     * Cria as tabelas legadas do Lemit que não possuem migration no repo.
     */
    protected function preparePeopleDb(): void
    {
        $schema = Schema::connection('people_db');

        if (! $schema->hasTable('pessoas')) {
            $schema->create('pessoas', function (Blueprint $table) {
                $table->id();
                $table->string('cpf', 11)->nullable()->index();
                $table->string('nome')->nullable();
                $table->string('data_nascimento')->nullable();
                $table->string('sexo')->nullable();
                $table->string('nome_mae')->nullable();
                $table->boolean('falecido')->default(false);
                $table->string('situacao_cpf')->nullable();
                $table->string('renda')->nullable();
                $table->string('ocupacao')->nullable();
                $table->timestamp('data_consulta')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('celulares')) {
            $schema->create('celulares', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pessoa_id')->nullable()->index();
                $table->string('ddd')->nullable();
                $table->string('numero')->nullable();
                $table->boolean('plus')->default(false);
                $table->integer('ranking')->nullable();
                $table->boolean('whatsapp')->default(false);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('fixos')) {
            $schema->create('fixos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pessoa_id')->nullable()->index();
                $table->string('ddd')->nullable();
                $table->string('numero')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('enderecos')) {
            $schema->create('enderecos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pessoa_id')->nullable()->index();
                $table->string('endereco')->nullable();
                $table->string('bairro')->nullable();
                $table->string('cidade')->nullable();
                $table->string('uf')->nullable();
                $table->string('cep')->nullable();
                $table->string('tipo')->nullable();
                $table->integer('ranking')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('emails')) {
            $schema->create('emails', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pessoa_id')->nullable()->index();
                $table->string('email')->nullable();
                $table->integer('ranking')->nullable();
                $table->boolean('possui_cookie')->default(false);
                $table->timestamps();
            });
        }
    }
}
