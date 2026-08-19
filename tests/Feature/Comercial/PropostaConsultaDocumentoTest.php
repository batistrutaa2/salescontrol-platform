<?php

namespace Tests\Feature\Comercial;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\LkBeneficios\Services\LemitService;
use App\Services\Comercial\PropostaEnriquecimentoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PropostaConsultaDocumentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_exige_autenticacao(): void
    {
        $this->postJson(route('comercial.proposta.consultaDocumento'), ['documento' => '11144477735'])
            ->assertUnauthorized();
    }

    public function test_documento_invalido_nao_executa_consulta(): void
    {
        $service = Mockery::mock(PropostaEnriquecimentoService::class);
        $service->shouldNotReceive('consultar');
        $this->app->instance(PropostaEnriquecimentoService::class, $service);

        $user = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($user)->postJson(route('comercial.proposta.consultaDocumento'), ['documento' => '11111111111'])
            ->assertUnprocessable()->assertJsonValidationErrors('documento');
    }

    public function test_retorna_somente_contrato_normalizado(): void
    {
        $service = Mockery::mock(PropostaEnriquecimentoService::class);
        $service->shouldReceive('consultar')->once()->with('11144477735')->andReturn([
            'encontrado' => true,
            'dados' => ['nome' => 'Maria', 'data_nascimento' => '01/01/1990', 'data_abertura' => null, 'email' => null, 'telefone1' => null, 'telefone2' => null],
        ]);
        $this->app->instance(PropostaEnriquecimentoService::class, $service);

        $user = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($user)->postJson(route('comercial.proposta.consultaDocumento'), ['documento' => '11144477735'])
            ->assertOk()->assertExactJson([
                'encontrado' => true,
                'dados' => ['nome' => 'Maria', 'data_nascimento' => '01/01/1990', 'data_abertura' => null, 'email' => null, 'telefone1' => null, 'telefone2' => null],
            ]);
    }

    public function test_consulta_lemit_funciona_sem_credenciais_da_assertiva(): void
    {
        config([
            'services.assertiva.client_id' => '',
            'services.assertiva.client_secret' => '',
            'services.assertiva.base_url' => '',
        ]);

        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldReceive('consultarCpf')->once()->with('11144477735')->andReturn([
            'pessoa' => [
                'nome' => 'Maria pela Lemit',
                'data_nascimento' => '1990-04-20',
                'emails' => [],
                'celulares' => [],
            ],
        ]);
        $this->app->instance(LemitService::class, $lemit);

        $user = User::factory()->create(['empresa_id' => Empresa::factory()->create()->id]);
        $this->actingAs($user)
            ->postJson(route('comercial.proposta.consultaDocumento'), ['documento' => '11144477735'])
            ->assertOk()
            ->assertJsonPath('encontrado', true)
            ->assertJsonPath('dados.nome', 'Maria pela Lemit');
    }
}
