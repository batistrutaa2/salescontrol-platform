<?php

namespace Tests\Feature\Enrichment;

use App\Models\Empresa;
use App\Models\People\Assertiva\AssertivaPessoa;
use App\Services\Enrichment\Assertiva\AssertivaService;
use App\Services\Enrichment\Assertiva\AssertivaTokenManager;
use App\Services\Enrichment\ConsultaService;
use App\Services\Enrichment\LemitService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LogicException;
use Mockery;

class ConsultaServiceTest extends AssertivaTestCase
{
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.assertiva.client_id' => 'cli',
            'services.assertiva.client_secret' => 'sec',
            'services.assertiva.base_url' => 'https://api.assertivasolucoes.com.br',
            'services.assertiva.id_finalidade' => 5,
            'services.assertiva.cache_months' => 3,
        ]);
        Cache::flush();
        $this->empresa = Empresa::factory()->create();
        app(TenantContext::class)->set($this->empresa->id);
    }

    private function consultaService(?LemitService $lemit = null): ConsultaService
    {
        $lemit ??= Mockery::mock(LemitService::class);

        return new ConsultaService($lemit, new AssertivaService(new AssertivaTokenManager));
    }

    public function test_telefone_servido_do_cache_assertiva_sem_chamar_api(): void
    {
        Http::fake(); // qualquer request seria registrado

        $pessoa = AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'JOAO', 'data_consulta' => now()]);
        $pessoa->telefones()->create(['numero_normalizado' => '11999998888', 'numero' => '11999998888', 'tipo' => 'MOVEL']);

        $res = $this->consultaService()->consultarTelefone('11999998888');

        $this->assertSame('local_db_assertiva', $res['fonte']);
        $this->assertSame('JOAO', $res['pessoa']->nome);
        Http::assertNothingSent();
    }

    public function test_telefone_chama_assertiva_quando_sem_cache(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/telefone*' => Http::response([
                'cabecalho' => ['protocolo' => 'p'],
                'resposta' => ['pessoaFisica' => ['cpf' => '12345678901', 'nome' => 'NOVO']],
            ]),
        ]);

        $res = $this->consultaService()->consultarTelefone('11912345678');

        $this->assertSame('api_assertiva', $res['fonte']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/localize/v3/telefone'));
    }

    public function test_documento_fonte_lemit_roteia_para_lemit(): void
    {
        // Mesmo com cache Assertiva, fonte=lemit vai ao Lemit (fonte explícita).
        AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'CACHE ASSERTIVA', 'data_consulta' => now()]);

        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldReceive('consultarCpf')->once()->with('12345678901')
            ->andReturn(['fonte' => 'api_lemit', 'pessoa' => ['nome' => 'DO LEMIT']]);

        $res = $this->consultaService($lemit)->consultarDocumento('123.456.789-01', 'lemit');

        $this->assertSame('api_lemit', $res['fonte']);
    }

    public function test_documento_fonte_assertiva_servido_do_cache(): void
    {
        AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'CACHE', 'data_consulta' => now()]);

        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldNotReceive('consultarCpf');

        $res = $this->consultaService($lemit)->consultarDocumento('12345678901', 'assertiva');

        $this->assertSame('local_db_assertiva', $res['fonte']);
        $this->assertSame('CACHE', $res['pessoa']->nome);
    }

    public function test_documento_fonte_assertiva_sem_cache_chama_api(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/cpf*' => Http::response([
                'cabecalho' => ['protocolo' => 'p-cpf'],
                'resposta' => ['dadosCadastrais' => ['cpf' => '12345678901', 'nome' => 'DA ASSERTIVA', 'signo' => 'Áries']],
            ]),
        ]);

        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldNotReceive('consultarCpf');

        $res = $this->consultaService($lemit)->consultarDocumento('12345678901', 'assertiva');

        $this->assertSame('api_assertiva', $res['fonte']);
        // payload completo salvo (nada se perde)
        $this->assertSame('Áries', $res['pessoa']->payload['dadosCadastrais']['signo']);
        $this->assertDatabaseHas('assertiva_pessoas', ['cpf' => '12345678901', 'nome' => 'DA ASSERTIVA'], 'people_db');
    }

    public function test_cache_expirado_volta_a_chamar_api(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/telefone*' => Http::response([
                'cabecalho' => ['protocolo' => 'p'],
                'resposta' => ['pessoaFisica' => ['cpf' => '12345678901', 'nome' => 'FRESH']],
            ]),
        ]);

        $pessoa = AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'VELHO', 'data_consulta' => now()->subMonths(4)]);
        $pessoa->telefones()->create(['numero_normalizado' => '11999998888', 'numero' => '11999998888', 'tipo' => 'MOVEL']);

        $res = $this->consultaService()->consultarTelefone('11999998888');

        $this->assertSame('api_assertiva', $res['fonte']);
    }

    public function test_cache_assertiva_nao_e_reutilizado_por_outra_empresa(): void
    {
        AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'EMPRESA A', 'data_consulta' => now()]);

        $empresaB = Empresa::factory()->create();
        app(TenantContext::class)->set($empresaB->id);
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/cpf*' => Http::response([
                'cabecalho' => ['protocolo' => 'p-cpf-b'],
                'resposta' => ['dadosCadastrais' => ['cpf' => '12345678901', 'nome' => 'EMPRESA B']],
            ]),
        ]);

        $res = $this->consultaService()->consultarDocumento('12345678901', 'assertiva');

        $this->assertSame('api_assertiva', $res['fonte']);
        $this->assertSame($empresaB->id, $res['pessoa']->empresa_id);
        $this->assertSame('EMPRESA B', $res['pessoa']->nome);

        app(TenantContext::class)->set($this->empresa->id);
        $this->assertSame('EMPRESA A', AssertivaPessoa::where('cpf', '12345678901')->sole()->nome);
    }

    public function test_cache_assertiva_rejeita_gravacao_sem_empresa_ativa(): void
    {
        app(TenantContext::class)->clear();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('sem uma empresa ativa');

        AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'SEM TENANT']);
    }

    public function test_cache_assertiva_falha_fechado_em_leitura_sem_empresa_ativa(): void
    {
        AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'EMPRESA A']);
        app(TenantContext::class)->clear();

        $this->assertNull(AssertivaPessoa::first());
        $this->assertSame(0, AssertivaPessoa::query()->delete());
        $this->assertDatabaseHas('assertiva_pessoas', [
            'empresa_id' => $this->empresa->id,
            'cpf' => '12345678901',
        ], 'people_db');
    }
}
