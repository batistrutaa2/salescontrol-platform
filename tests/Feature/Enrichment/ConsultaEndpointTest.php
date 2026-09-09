<?php

namespace Tests\Feature\Enrichment;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\People\Assertiva\AssertivaPessoa;
use App\Models\User;
use App\Services\Enrichment\LemitService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Mockery;

class ConsultaEndpointTest extends AssertivaTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.assertiva.enabled' => true,
            'services.assertiva.client_id' => 'cli',
            'services.assertiva.client_secret' => 'sec',
            'services.assertiva.base_url' => 'https://api.assertivasolucoes.com.br',
            'services.assertiva.id_finalidade' => 5,
        ]);
        Cache::flush();

        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR,
            'tipo_usuario' => 'VENDEDOR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $empresa = Empresa::factory()->create();
        app(TenantContext::class)->set($empresa->id);
        $this->actingAs(User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]));
    }

    public function test_consulta_exige_autenticacao(): void
    {
        auth()->logout();

        $this->postJson(route('consulta.telefone'), ['telefone' => '11999998888'])
            ->assertUnauthorized();
    }

    public function test_assertiva_desabilitada_rejeita_a_fonte_no_endpoint(): void
    {
        config(['services.assertiva.enabled' => false]);
        Http::fake();

        $this->postJson(route('consulta.pessoa'), [
            'cpf' => '12345678901',
            'fonte' => 'assertiva',
        ])->assertUnprocessable()->assertJsonValidationErrors('fonte');

        Http::assertNothingSent();
    }

    public function test_consulta_lemit_nao_resolve_credenciais_da_assertiva(): void
    {
        config([
            'services.assertiva.enabled' => false,
            'services.assertiva.client_id' => '',
            'services.assertiva.client_secret' => '',
        ]);
        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldReceive('consultarCpf')->once()->with('12345678901')->andReturn([
            'fonte' => 'api_lemit',
            'pessoa' => ['nome' => 'LEMIT OK'],
        ]);
        $this->app->instance(LemitService::class, $lemit);

        $this->postJson(route('consulta.pessoa'), [
            'cpf' => '12345678901',
            'fonte' => 'lemit',
        ])->assertOk()
            ->assertJsonPath('fonte', 'api_lemit')
            ->assertJsonPath('pessoa.nome', 'LEMIT OK');
    }

    public function test_consultar_telefone_retorna_dados_da_api(): void
    {
        Http::fake([
            '*/oauth2/v3/token' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            '*/localize/v3/telefone*' => Http::response([
                'cabecalho' => ['protocolo' => 'p'],
                'resposta' => ['pessoaFisica' => ['cpf' => '12345678901', 'nome' => 'JOAO']],
            ]),
        ]);

        $resp = $this->postJson(route('consulta.telefone'), ['telefone' => '11999998888']);

        $resp->assertOk()->assertJsonPath('fonte', 'api_assertiva');
    }

    public function test_telefone_obrigatorio_retorna_422(): void
    {
        $this->postJson(route('consulta.telefone'), [])->assertStatus(422);
    }

    public function test_email_invalido_retorna_422(): void
    {
        $this->postJson(route('consulta.email'), ['email' => 'nao-email'])->assertStatus(422);
    }

    public function test_segunda_consulta_vem_do_cache_sem_chamar_api(): void
    {
        $pessoa = AssertivaPessoa::create(['cpf' => '12345678901', 'nome' => 'CACHE', 'data_consulta' => now()]);
        $pessoa->telefones()->create(['numero_normalizado' => '11999998888', 'numero' => '11999998888', 'tipo' => 'MOVEL']);

        Http::fake();

        $resp = $this->postJson(route('consulta.telefone'), ['telefone' => '11999998888']);

        $resp->assertOk()->assertJsonPath('fonte', 'local_db_assertiva');
        Http::assertNothingSent();
    }
}
