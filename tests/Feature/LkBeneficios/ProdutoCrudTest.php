<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProdutoCrudTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => 4, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->user = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => 4,
        ]);
    }

    // -----------------------------------------------------------------------
    // STORE
    // -----------------------------------------------------------------------

    public function test_admin_pode_criar_produto(): void
    {
        $resp = $this->actingAs($this->user)->postJson(route('lk-beneficios.produtos.store'), [
            'nome' => 'Vida Individual Premium',
            'tipo' => TipoBeneficio::VIDA,
            'subtipo' => 'Individual',
            'descricao' => 'Cobertura ampla',
            'ativo' => true,
        ]);

        $resp->assertStatus(201);
        $this->assertDatabaseHas('lk_beneficios_produtos', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'Vida Individual Premium',
            'tipo' => TipoBeneficio::VIDA,
            'subtipo' => 'Individual',
            'ativo' => 1,
        ]);
    }

    public function test_store_falha_sem_nome(): void
    {
        $resp = $this->actingAs($this->user)->postJson(route('lk-beneficios.produtos.store'), [
            'nome' => '',
            'tipo' => TipoBeneficio::VIDA,
        ]);

        $resp->assertStatus(422)->assertJsonValidationErrors(['nome']);
    }

    public function test_store_falha_com_tipo_invalido(): void
    {
        $resp = $this->actingAs($this->user)->postJson(route('lk-beneficios.produtos.store'), [
            'nome' => 'Produto X',
            'tipo' => 'XPTO',
        ]);

        $resp->assertStatus(422)->assertJsonValidationErrors(['tipo']);
    }

    // -----------------------------------------------------------------------
    // DATATABLE / multi-tenant
    // -----------------------------------------------------------------------

    public function test_datatable_filtra_por_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroUser = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => 4,
        ]);

        Produto::factory()->create(['empresa_id' => $this->empresa->id, 'nome' => 'Meu Produto']);
        Produto::factory()->create(['empresa_id' => $outraEmpresa->id, 'nome' => 'Produto Alheio']);

        $resp = $this->actingAs($this->user)->getJson(route('lk-beneficios.produtos.datatable'));

        $resp->assertOk();
        $nomes = collect($resp->json('data'))->pluck('nome')->all();
        $this->assertContains('Meu Produto', $nomes);
        $this->assertNotContains('Produto Alheio', $nomes);
    }

    // -----------------------------------------------------------------------
    // UPDATE
    // -----------------------------------------------------------------------

    public function test_update_atualiza_campos_permitidos(): void
    {
        $produto = Produto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'Antigo',
            'tipo' => TipoBeneficio::ODONTO,
            'ativo' => true,
        ]);

        $resp = $this->actingAs($this->user)->putJson(route('lk-beneficios.produtos.update', $produto->id), [
            'nome' => 'Novo Nome',
            'subtipo' => 'Empresarial',
            'ativo' => false,
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('lk_beneficios_produtos', [
            'id' => $produto->id,
            'nome' => 'Novo Nome',
            'subtipo' => 'Empresarial',
            'ativo' => 0,
        ]);
    }

    public function test_update_bloqueia_troca_de_tipo_quando_ha_contratos(): void
    {
        $produto = Produto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo' => TipoBeneficio::VIDA,
        ]);

        $this->criarContrato($produto->id);

        $resp = $this->actingAs($this->user)->putJson(route('lk-beneficios.produtos.update', $produto->id), [
            'tipo' => TipoBeneficio::PATRIMONIAL,
        ]);

        $resp->assertStatus(422);
        $this->assertSame(TipoBeneficio::VIDA, $produto->fresh()->tipo);
    }

    public function test_usuario_de_outra_empresa_nao_atualiza_produto(): void
    {
        $produto = Produto::factory()->create(['empresa_id' => $this->empresa->id]);

        $outraEmpresa = Empresa::factory()->create();
        $outroUser = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => 4,
        ]);

        $resp = $this->actingAs($outroUser)->putJson(route('lk-beneficios.produtos.update', $produto->id), [
            'nome' => 'Hacked',
        ]);

        $resp->assertStatus(404);
        $this->assertNotSame('Hacked', $produto->fresh()->nome);
    }

    // -----------------------------------------------------------------------
    // TOGGLE
    // -----------------------------------------------------------------------

    public function test_toggle_inverte_estado_do_produto(): void
    {
        $produto = Produto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'ativo' => true,
        ]);

        $resp = $this->actingAs($this->user)->patchJson(route('lk-beneficios.produtos.toggle', $produto->id), [
            'ativo' => false,
        ]);

        $resp->assertOk()->assertJsonPath('ativo', false);
        $this->assertFalse((bool) $produto->fresh()->ativo);
    }

    // -----------------------------------------------------------------------
    // DESTROY
    // -----------------------------------------------------------------------

    public function test_destroy_remove_produto_sem_contratos(): void
    {
        $produto = Produto::factory()->create(['empresa_id' => $this->empresa->id]);

        $resp = $this->actingAs($this->user)->deleteJson(route('lk-beneficios.produtos.destroy', $produto->id));

        $resp->assertOk();
        $this->assertDatabaseMissing('lk_beneficios_produtos', ['id' => $produto->id]);
    }

    public function test_destroy_bloqueia_quando_ha_contratos_vinculados(): void
    {
        $produto = Produto::factory()->create(['empresa_id' => $this->empresa->id]);
        $this->criarContrato($produto->id);

        $resp = $this->actingAs($this->user)->deleteJson(route('lk-beneficios.produtos.destroy', $produto->id));

        $resp->assertStatus(422);
        $this->assertDatabaseHas('lk_beneficios_produtos', ['id' => $produto->id]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function criarContrato(int $produtoId): void
    {
        DB::table('lk_beneficios_contratos')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->user->id,
            'produto_id' => $produtoId,
            'cliente_tipo' => 'PF',
            'cpf_cnpj' => '12345678900',
            'nome_cliente' => 'Teste Contrato',
            'status' => 'EM_IMPLANTACAO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
