<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\People\Assertiva\AssertivaPessoa;
use App\Models\People\Celular;
use App\Models\People\Pessoa;
use App\Modules\LkBeneficios\Services\Assertiva\AssertivaService;
use App\Modules\LkBeneficios\Services\Assertiva\AssertivaTokenManager;
use App\Modules\LkBeneficios\Services\ConsultaService;
use App\Modules\LkBeneficios\Services\LemitService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;

class ConsultaServiceTest extends AssertivaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.assertiva.client_id' => 'cli',
            'services.assertiva.client_secret' => 'sec',
            'services.assertiva.base_url' => 'https://api.assertivasolucoes.com.br',
            'services.assertiva.id_finalidade' => 5,
            'services.assertiva.cache_months' => 3,
            'services.lemit.cache_months' => 3,
        ]);
        Cache::flush();
    }

    private function consultaService(?LemitService $lemit = null): ConsultaService
    {
        $lemit ??= Mockery::mock(LemitService::class);

        return new ConsultaService($lemit, new AssertivaService(new AssertivaTokenManager()));
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

    public function test_telefone_servido_do_cache_lemit(): void
    {
        Http::fake();

        $pessoa = Pessoa::create(['cpf' => '99999999999', 'nome' => 'MARIA', 'data_consulta' => now()]);
        Celular::create(['pessoa_id' => $pessoa->id, 'ddd' => '11', 'numero' => '988887777']);

        $res = $this->consultaService()->consultarTelefone('11988887777');

        $this->assertSame('local_db_lemit', $res['fonte']);
        $this->assertSame('MARIA', $res['pessoa']->nome);
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
}
