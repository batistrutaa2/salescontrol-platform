<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FunnelConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_funnel_screen_only_renders_the_active_company_catalog(): void
    {
        [$empresa, $outra, $admin] = $this->tenantScenario();
        Tabulacoes::query()->create($this->customStage($outra->id, 'ETAPA SIGILOSA'));

        $this->actingAs($admin)
            ->get(route('manager.funis.index'))
            ->assertOk()
            ->assertSee($empresa->nome_fantasia)
            ->assertSee('PROSPECÇÃO')
            ->assertDontSee('ETAPA SIGILOSA');
    }

    public function test_custom_stage_is_always_created_in_the_server_tenant(): void
    {
        [$empresa, $outra, $admin] = $this->tenantScenario();

        $this->actingAs($admin)
            ->post(route('manager.funis.store'), [
                'empresa_id' => $outra->id,
                'descricao' => 'Qualificação interna',
                'tipo_tabulacao' => 'C',
                'efetivo' => 'Y',
                'prazo' => '24 horas',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tabulacoes', [
            'empresa_id' => $empresa->id,
            'codigo' => null,
            'descricao' => 'QUALIFICAÇÃO INTERNA',
        ]);
        $this->assertDatabaseMissing('tabulacoes', [
            'empresa_id' => $outra->id,
            'descricao' => 'QUALIFICAÇÃO INTERNA',
        ]);
    }

    public function test_duplicate_stage_name_displays_a_clear_message_on_creation_and_editing(): void
    {
        [$empresa, , $admin] = $this->tenantScenario();
        Tabulacoes::query()->create(array_merge($this->customStage($empresa->id, 'NOME EXISTENTE'), [
            'tipo_tabulacao' => 'A',
            'status' => 'N',
        ]));
        $stage = Tabulacoes::query()->create($this->customStage($empresa->id, 'ETAPA ORIGINAL'));
        $payload = [
            'descricao' => 'NOME EXISTENTE',
            'tipo_tabulacao' => 'C',
            'efetivo' => 'Y',
            'status' => 'Y',
            'prazo' => null,
        ];
        $message = 'Já existe uma etapa com esse nome nesta empresa, no funil comercial ou no pós-venda, inclusive entre as arquivadas. Escolha outro nome.';

        $this->actingAs($admin)->from(route('manager.funis.index'))
            ->post(route('manager.funis.store'), $payload)
            ->assertRedirect(route('manager.funis.index'))
            ->assertSessionHasErrors(['descricao' => $message]);

        $this->get(route('manager.funis.index'))
            ->assertOk()
            ->assertSee($message)
            ->assertDontSee('validation.unique');

        $this->from(route('manager.funis.index'))
            ->put(route('manager.funis.update', $stage->id), $payload)
            ->assertRedirect(route('manager.funis.index'))
            ->assertSessionHasErrors(['descricao' => $message]);

        $this->assertSame('ETAPA ORIGINAL', $stage->fresh()->descricao);
        $this->assertDatabaseMissing('tabulacoes', [
            'empresa_id' => $empresa->id,
            'descricao' => 'NOME EXISTENTE',
            'tipo_tabulacao' => 'C',
        ]);
    }

    public function test_name_from_another_company_and_unchanged_name_are_allowed(): void
    {
        [$empresa, $outra, $admin] = $this->tenantScenario();
        Tabulacoes::query()->create($this->customStage($outra->id, 'NOME COMPARTILHADO'));
        $payload = [
            'descricao' => 'NOME COMPARTILHADO',
            'tipo_tabulacao' => 'C',
            'efetivo' => 'Y',
            'status' => 'Y',
            'prazo' => '24 horas',
        ];

        $this->actingAs($admin)->post(route('manager.funis.store'), $payload)
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $stage = Tabulacoes::query()->where('empresa_id', $empresa->id)
            ->where('descricao', 'NOME COMPARTILHADO')->firstOrFail();

        $this->put(route('manager.funis.update', $stage->id), array_merge($payload, ['prazo' => '48 horas']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('48 horas', $stage->fresh()->prazo);
    }

    public function test_stage_from_another_company_cannot_be_edited_or_reordered(): void
    {
        [$empresa, $outra, $admin] = $this->tenantScenario();
        $foreign = Tabulacoes::query()->create($this->customStage($outra->id, 'ETAPA DA OUTRA EMPRESA'));

        $this->actingAs($admin)
            ->put(route('manager.funis.update', $foreign->id), [
                'descricao' => 'ALTERAÇÃO INDEVIDA',
                'tipo_tabulacao' => 'C',
                'efetivo' => 'N',
                'status' => 'N',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->patch(route('manager.funis.move', $foreign->id), ['direction' => 'up'])
            ->assertNotFound();

        $this->assertDatabaseHas('tabulacoes', [
            'id' => $foreign->id,
            'empresa_id' => $outra->id,
            'descricao' => 'ETAPA DA OUTRA EMPRESA',
            'status' => 'Y',
        ]);
        $this->assertDatabaseMissing('tabulacoes', [
            'empresa_id' => $empresa->id,
            'descricao' => 'ALTERAÇÃO INDEVIDA',
        ]);
    }

    public function test_structural_stage_keeps_technical_rules_when_renamed(): void
    {
        [$empresa, , $admin] = $this->tenantScenario();
        $stage = app(TenantContext::class)->run($empresa->id, fn () => Tabulacoes::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo', TabulationCode::PROSPECCAO)
            ->firstOrFail());

        $this->actingAs($admin)
            ->put(route('manager.funis.update', $stage->id), [
                'descricao' => 'Primeira conversa',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'N',
                'status' => 'N',
                'prazo' => '2 dias',
                'codigo' => 'CODIGO_FORJADO',
            ])
            ->assertRedirect();

        $stage->refresh();
        $this->assertSame('PRIMEIRA CONVERSA', $stage->descricao);
        $this->assertSame(TabulationCode::PROSPECCAO, $stage->codigo);
        $this->assertSame('C', $stage->tipo_tabulacao);
        $this->assertSame('Y', $stage->efetivo);
        $this->assertSame('Y', $stage->status);
        $this->assertSame($stage->id, app(TabulationCatalog::class)->id($empresa->id, TabulationCode::PROSPECCAO));
    }

    public function test_reordering_changes_only_stages_from_the_active_company_and_same_flow(): void
    {
        [$empresa, $outra, $admin] = $this->tenantScenario();
        $first = Tabulacoes::query()->create(array_merge($this->customStage($empresa->id, 'ETAPA UM'), ['ordem_kanban' => '9001']));
        $second = Tabulacoes::query()->create(array_merge($this->customStage($empresa->id, 'ETAPA DOIS'), ['ordem_kanban' => '9002']));
        $foreign = Tabulacoes::query()->create(array_merge($this->customStage($outra->id, 'ETAPA ESTRANGEIRA'), ['ordem_kanban' => 'ABCD']));

        $this->actingAs($admin)
            ->patch(route('manager.funis.move', $second->id), ['direction' => 'up'])
            ->assertRedirect();

        $orderedIds = Tabulacoes::query()
            ->where('empresa_id', $empresa->id)
            ->where('tipo_tabulacao', 'C')
            ->orderBy('ordem_kanban')
            ->orderBy('id')
            ->pluck('id');

        $this->assertLessThan($orderedIds->search($first->id), $orderedIds->search($second->id));
        $this->assertDatabaseHas('tabulacoes', [
            'id' => $foreign->id,
            'empresa_id' => $outra->id,
            'ordem_kanban' => 'ABCD',
        ]);
    }

    public function test_regular_seller_cannot_access_funnel_configuration(): void
    {
        $empresa = Empresa::query()->create(['nome_fantasia' => 'Corretora Alfa']);
        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR,
            'tipo_usuario' => 'VENDEDOR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $seller = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $this->actingAs($seller)->get(route('manager.funis.index'))->assertForbidden();
    }

    private function tenantScenario(): array
    {
        $empresa = Empresa::query()->create(['nome_fantasia' => 'Corretora Alfa']);
        $outra = Empresa::query()->create(['nome_fantasia' => 'Corretora Beta']);
        app(TabulationCatalog::class)->provision($empresa->id);
        app(TabulationCatalog::class)->provision($outra->id);
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'ativo' => 'Y',
        ]);

        return [$empresa, $outra, $admin];
    }

    private function customStage(int $empresaId, string $description): array
    {
        return [
            'empresa_id' => $empresaId,
            'codigo' => null,
            'descricao' => $description,
            'tipo_tabulacao' => 'C',
            'efetivo' => 'Y',
            'ordem_kanban' => '9999',
            'status' => 'Y',
            'sub_tabulacao' => 'N',
        ];
    }
}
