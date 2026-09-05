<?php

namespace Tests\Feature\Tenancy;

use App\Models\Empresa;
use App\Services\Enrichment\LemitService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LemitIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_consulta_cnpj_nao_le_nem_altera_tabela_de_corretoras(): void
    {
        config([
            'services.lemit.api_key' => 'token-teste',
            'services.lemit.base_url' => 'https://lemit.example.test',
        ]);
        $empresa = Empresa::factory()->create([
            'cpf_cnpj' => '12345678000190',
            'nome_fantasia' => 'Corretora preservada',
        ]);
        app(TenantContext::class)->set($empresa->id);
        Http::fake([
            'https://lemit.example.test/empresa' => Http::response([
                'empresa' => [
                    'cnpj' => '12345678000190',
                    'nome_fantasia' => 'Nome retornado pelo provedor',
                ],
            ]),
        ]);

        $resultado = (new LemitService)->consultarCnpj('12345678000190');

        $this->assertSame('api_lemit', $resultado['fonte']);
        $this->assertSame('Nome retornado pelo provedor', $resultado['empresa']['nome_fantasia']);
        $this->assertSame('Corretora preservada', $empresa->fresh()->nome_fantasia);
        $this->assertDatabaseCount('empresas', 1);
    }
}
