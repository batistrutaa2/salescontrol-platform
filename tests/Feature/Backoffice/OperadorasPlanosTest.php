<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tela única Operadoras e Planos: dados agregados, cadastro, toggle de status
 * e redirecionamento das telas antigas.
 */
class OperadorasPlanosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
    }

    private function criarOperadora(int $empresaId, string $nome, string $status = 'Y'): int
    {
        return DB::table('operadoras')->insertGetId([
            'empresa_id' => $empresaId, 'nome' => $nome, 'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function criarPlano(int $empresaId, int $operadoraId, string $nome): int
    {
        return DB::table('planos')->insertGetId([
            'empresa_id' => $empresaId, 'operadora_id' => $operadoraId, 'nome' => $nome,
            'status' => 'Y', 'acomodacao' => 'ENFERMARIA', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_data_lista_operadoras_com_planos_e_respeita_empresa(): void
    {
        $op = $this->criarOperadora($this->empresa->id, 'AMIL');
        $this->criarPlano($this->empresa->id, $op, 'BRONZE');
        $this->criarPlano($this->empresa->id, $op, 'PRATA');

        // Outra empresa não pode vazar.
        $outra = Empresa::factory()->create();
        $this->criarOperadora($outra->id, 'UNIMED OUTRA');

        $resp = $this->actingAs($this->admin)->getJson(route('backoffice.operadorasPlanos.data'));

        $resp->assertOk();
        $ops = collect($resp->json('operadoras'));
        $this->assertCount(1, $ops);
        $this->assertSame('AMIL', $ops->first()['nome']);
        $this->assertCount(2, $ops->first()['planos']);
    }

    public function test_cadastra_operadora_e_plano_pela_tela(): void
    {
        // Cadastra operadora (nome vai em maiúsculas).
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.createOperation'), ['nome' => 'sulamerica', 'status' => 'Y'])
            ->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('operadoras', ['empresa_id' => $this->empresa->id, 'nome' => 'SULAMERICA', 'status' => 'Y']);
        $opId = DB::table('operadoras')->where('nome', 'SULAMERICA')->value('id');

        // Cadastra plano na operadora.
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.createPlan'), ['operadora_id' => $opId, 'nome' => 'especial 200', 'status' => 'Y', 'acomodacao' => 'APARTAMENTO'])
            ->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('planos', ['operadora_id' => $opId, 'nome' => 'ESPECIAL 200', 'acomodacao' => 'APARTAMENTO']);

        // Aparece no data agregado.
        $ops = collect($this->actingAs($this->admin)->getJson(route('backoffice.operadorasPlanos.data'))->json('operadoras'));
        $this->assertSame(1, $ops->firstWhere('nome', 'SULAMERICA')['planos'] ? count($ops->firstWhere('nome', 'SULAMERICA')['planos']) : 0);
    }

    public function test_toggle_status_operadora_e_plano(): void
    {
        $op = $this->criarOperadora($this->empresa->id, 'AMIL', 'Y');
        $plano = $this->criarPlano($this->empresa->id, $op, 'BRONZE');

        $this->actingAs($this->admin)
            ->patchJson(route('backoffice.operadoras.toggleStatus', $op))
            ->assertOk()->assertJson(['success' => true, 'status' => 'N']);
        $this->assertDatabaseHas('operadoras', ['id' => $op, 'status' => 'N']);

        $this->actingAs($this->admin)
            ->patchJson(route('backoffice.planos.toggleStatus', $plano))
            ->assertOk()->assertJson(['status' => 'N']);
        $this->assertDatabaseHas('planos', ['id' => $plano, 'status' => 'N']);
    }

    public function test_toggle_multitenant_bloqueia_outra_empresa(): void
    {
        $outra = Empresa::factory()->create();
        $op = $this->criarOperadora($outra->id, 'DE OUTRA');

        $this->actingAs($this->admin)
            ->patchJson(route('backoffice.operadoras.toggleStatus', $op))
            ->assertStatus(404);
        $this->assertDatabaseHas('operadoras', ['id' => $op, 'status' => 'Y']);
    }

    public function test_rotas_antigas_redirecionam_para_tela_unica(): void
    {
        $this->actingAs($this->admin)->get(route('backoffice.operadoras'))->assertRedirect(route('backoffice.operadorasPlanos'));
        $this->actingAs($this->admin)->get(route('backoffice.planos'))->assertRedirect(route('backoffice.operadorasPlanos'));
    }
}
