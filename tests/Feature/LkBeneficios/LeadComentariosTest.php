<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Models\LeadComentario;
use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadComentariosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $user;
    private Lead $lead;

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

        $produto = Produto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'VIDA',
        ]);

        $this->lead = Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->user->id,
            'produto_interesse_id' => $produto->id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Comentários
    // -----------------------------------------------------------------------

    public function test_pode_criar_comentario(): void
    {
        $resp = $this->actingAs($this->user)->postJson(
            route('lk-beneficios.leads.comentarios.store', $this->lead->id),
            ['anotacao' => 'Cliente confirmou retorno na próxima quinta.']
        );

        $resp->assertStatus(201)->assertJsonPath('comentario.anotacao', 'Cliente confirmou retorno na próxima quinta.');
        $this->assertDatabaseHas('lk_beneficios_lead_comentarios', [
            'lead_id' => $this->lead->id,
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->user->id,
            'anotacao' => 'Cliente confirmou retorno na próxima quinta.',
        ]);
    }

    public function test_comentario_falha_sem_anotacao(): void
    {
        $resp = $this->actingAs($this->user)->postJson(
            route('lk-beneficios.leads.comentarios.store', $this->lead->id),
            ['anotacao' => '']
        );

        $resp->assertStatus(422)->assertJsonValidationErrors(['anotacao']);
    }

    public function test_usuario_de_outra_empresa_nao_comenta(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroUser = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => 4,
        ]);

        $resp = $this->actingAs($outroUser)->postJson(
            route('lk-beneficios.leads.comentarios.store', $this->lead->id),
            ['anotacao' => 'Tentativa cross-tenant']
        );

        // ModelNotFoundException → 404
        $resp->assertStatus(404);
        $this->assertDatabaseMissing('lk_beneficios_lead_comentarios', [
            'anotacao' => 'Tentativa cross-tenant',
        ]);
    }

    public function test_pode_excluir_comentario(): void
    {
        $comentario = LeadComentario::create([
            'empresa_id' => $this->empresa->id,
            'lead_id' => $this->lead->id,
            'user_id' => $this->user->id,
            'anotacao' => 'apagar',
        ]);

        $resp = $this->actingAs($this->user)->deleteJson(
            route('lk-beneficios.leads.comentarios.destroy', [
                'id' => $this->lead->id,
                'comentarioId' => $comentario->id,
            ])
        );

        $resp->assertOk();
        $this->assertDatabaseMissing('lk_beneficios_lead_comentarios', ['id' => $comentario->id]);
    }

    public function test_excluir_comentario_de_outra_empresa_falha(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroUser = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => 4,
        ]);

        $comentario = LeadComentario::create([
            'empresa_id' => $this->empresa->id,
            'lead_id' => $this->lead->id,
            'user_id' => $this->user->id,
            'anotacao' => 'protegido',
        ]);

        $resp = $this->actingAs($outroUser)->deleteJson(
            route('lk-beneficios.leads.comentarios.destroy', [
                'id' => $this->lead->id,
                'comentarioId' => $comentario->id,
            ])
        );

        $resp->assertStatus(404);
        $this->assertDatabaseHas('lk_beneficios_lead_comentarios', ['id' => $comentario->id]);
    }

    // -----------------------------------------------------------------------
    // Informação fixada
    // -----------------------------------------------------------------------

    public function test_pode_salvar_informacao_fixada(): void
    {
        $resp = $this->actingAs($this->user)->putJson(
            route('lk-beneficios.leads.informacao-fixada.update', $this->lead->id),
            ['informacao_fixada' => 'Cliente prefere contato após 18h.']
        );

        $resp->assertOk()->assertJsonPath('informacao_fixada', 'Cliente prefere contato após 18h.');
        $this->assertSame('Cliente prefere contato após 18h.', $this->lead->fresh()->informacao_fixada);
    }

    public function test_pode_remover_informacao_fixada_passando_null(): void
    {
        $this->lead->update(['informacao_fixada' => 'algo']);

        $resp = $this->actingAs($this->user)->putJson(
            route('lk-beneficios.leads.informacao-fixada.update', $this->lead->id),
            ['informacao_fixada' => null]
        );

        $resp->assertOk()->assertJsonPath('informacao_fixada', null);
        $this->assertNull($this->lead->fresh()->informacao_fixada);
    }

    public function test_informacao_fixada_falha_acima_de_1000_caracteres(): void
    {
        $resp = $this->actingAs($this->user)->putJson(
            route('lk-beneficios.leads.informacao-fixada.update', $this->lead->id),
            ['informacao_fixada' => str_repeat('a', 1001)]
        );

        $resp->assertStatus(422)->assertJsonValidationErrors(['informacao_fixada']);
    }

    public function test_outra_empresa_nao_atualiza_informacao_fixada(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroUser = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => 4,
        ]);

        $resp = $this->actingAs($outroUser)->putJson(
            route('lk-beneficios.leads.informacao-fixada.update', $this->lead->id),
            ['informacao_fixada' => 'invasão']
        );

        $resp->assertStatus(404);
        $this->assertNull($this->lead->fresh()->informacao_fixada);
    }
}
