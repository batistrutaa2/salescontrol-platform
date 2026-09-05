<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FaqTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresaA;

    private Empresa $empresaB;

    private User $adminA;

    private User $vendedorA;

    private int $operadoraA;

    private int $operadoraB;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresaA = Empresa::factory()->create();
        $this->empresaB = Empresa::factory()->create();
        $this->adminA = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedorA = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->operadoraA = $this->criarOperadora($this->empresaA, 'Operadora A');
        $this->operadoraB = $this->criarOperadora($this->empresaB, 'Operadora B');
    }

    private function criarOperadora(Empresa $empresa, string $nome): int
    {
        return DB::table('operadoras')->insertGetId([
            'empresa_id' => $empresa->id,
            'nome' => $nome,
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function criarFaq(Empresa $empresa, int $operadoraId, string $titulo, string $status = 'Y'): int
    {
        return DB::table('faqs')->insertGetId([
            'empresa_id' => $empresa->id,
            'operadora_id' => $operadoraId,
            'titulo' => $titulo,
            'resposta' => "Resposta de {$titulo}",
            'status' => $status,
            'ordem' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_listagem_e_visualizacao_do_vendedor_nao_vazam_outra_empresa(): void
    {
        $this->criarFaq($this->empresaA, $this->operadoraA, 'FAQ visível A');
        $this->criarFaq($this->empresaA, $this->operadoraA, 'FAQ inativo A', 'N');
        $this->criarFaq($this->empresaB, $this->operadoraB, 'FAQ secreto B');

        $response = $this->actingAs($this->adminA)->getJson(route('backoffice.getFaqs'));

        $response->assertOk();
        $this->assertSame(
            ['FAQ inativo A', 'FAQ visível A'],
            collect($response->json())->pluck('titulo')->sort()->values()->all()
        );

        $this->actingAs($this->adminA)
            ->getJson(route('backoffice.getFaqs', ['operadora_id' => $this->operadoraB]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['operadora_id']);

        $this->actingAs($this->vendedorA)
            ->get(route('comercial.faqs'))
            ->assertOk()
            ->assertSee('FAQ visível A')
            ->assertDontSee('FAQ inativo A')
            ->assertDontSee('FAQ secreto B');
    }

    public function test_criacao_usa_empresa_do_servidor_e_rejeita_operadora_externa(): void
    {
        $this->actingAs($this->adminA)
            ->postJson(route('backoffice.createFaq'), [
                'empresa_id' => $this->empresaB->id,
                'operadora_id' => $this->operadoraA,
                'titulo' => 'FAQ criada em A',
                'resposta' => 'Resposta segura',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('faqs', [
            'empresa_id' => $this->empresaA->id,
            'operadora_id' => $this->operadoraA,
            'titulo' => 'FAQ criada em A',
        ]);

        $this->actingAs($this->adminA)
            ->postJson(route('backoffice.createFaq'), [
                'operadora_id' => $this->operadoraB,
                'titulo' => 'FAQ inválida',
                'resposta' => 'Não deve existir',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['operadora_id']);
    }

    public function test_edicao_e_exclusao_rejeitam_faq_de_outra_empresa_com_404(): void
    {
        $faqLocal = $this->criarFaq($this->empresaA, $this->operadoraA, 'FAQ local');
        $faqExterna = $this->criarFaq($this->empresaB, $this->operadoraB, 'FAQ externa');

        $this->actingAs($this->adminA)
            ->postJson(route('backoffice.updateFaq', $faqExterna), [
                'operadora_id' => $this->operadoraA,
                'titulo' => 'Tentativa externa',
                'resposta' => 'Não altera',
            ])
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->deleteJson(route('backoffice.deleteFaq', $faqExterna))
            ->assertNotFound();

        $this->actingAs($this->adminA)
            ->postJson(route('backoffice.updateFaq', $faqLocal), [
                'operadora_id' => $this->operadoraB,
                'titulo' => 'Operadora externa',
                'resposta' => 'Não altera',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['operadora_id']);

        $this->assertDatabaseHas('faqs', ['id' => $faqLocal, 'titulo' => 'FAQ local']);
        $this->assertDatabaseHas('faqs', ['id' => $faqExterna, 'titulo' => 'FAQ externa']);
    }

    public function test_crud_local_funciona_e_backoffice_nao_recebe_permissao_de_mutacao(): void
    {
        $faq = $this->criarFaq($this->empresaA, $this->operadoraA, 'FAQ original');

        $this->actingAs($this->adminA)
            ->postJson(route('backoffice.updateFaq', $faq), [
                'operadora_id' => $this->operadoraA,
                'titulo' => 'FAQ atualizada',
                'resposta' => 'Resposta atualizada',
                'status' => 'N',
                'ordem' => 3,
            ])
            ->assertOk();

        $this->assertDatabaseHas('faqs', [
            'id' => $faq,
            'empresa_id' => $this->empresaA->id,
            'titulo' => 'FAQ atualizada',
            'status' => 'N',
            'ordem' => 3,
        ]);

        $backoffice = User::factory()->create([
            'empresa_id' => $this->empresaA->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $this->actingAs($backoffice)
            ->postJson(route('backoffice.createFaq'), [
                'operadora_id' => $this->operadoraA,
                'titulo' => 'Sem autorização',
                'resposta' => 'Não deve existir',
            ])
            ->assertForbidden();

        $this->actingAs($this->adminA)
            ->deleteJson(route('backoffice.deleteFaq', $faq))
            ->assertOk();

        $this->assertDatabaseMissing('faqs', ['id' => $faq]);
    }

    public function test_master_sem_empresa_de_origem_opera_faqs_somente_no_tenant_ativo(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $this->criarFaq($this->empresaA, $this->operadoraA, 'FAQ A');
        $this->criarFaq($this->empresaB, $this->operadoraB, 'FAQ B');
        $session = [TenantContext::SESSION_KEY => $this->empresaB->id];

        $response = $this->actingAs($master)
            ->withSession($session)
            ->getJson(route('backoffice.getFaqs'));

        $response->assertOk();
        $this->assertSame(['FAQ B'], collect($response->json())->pluck('titulo')->all());

        $this->actingAs($master)
            ->withSession($session)
            ->postJson(route('backoffice.createFaq'), [
                'operadora_id' => $this->operadoraB,
                'titulo' => 'FAQ criada pelo master',
                'resposta' => 'Somente na empresa ativa',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('faqs', [
            'empresa_id' => $this->empresaB->id,
            'titulo' => 'FAQ criada pelo master',
        ]);
        $this->assertNull($master->fresh()->empresa_id);
    }
}
