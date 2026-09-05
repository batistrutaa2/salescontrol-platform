<?php

namespace Tests\Feature\Tenancy;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\TenantServiceCredential;
use App\Models\User;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PabxTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_master_lista_somente_ramais_da_empresa_ativa(): void
    {
        $cenario = $this->cenario();
        $this->ramal($cenario['empresaA']->id, $cenario['vendedorA']->id, '1001');
        $ramalB = $this->ramal($cenario['empresaB']->id, $cenario['vendedorB']->id, '2002');

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->getJson(route('pabx.getRamais'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ramalB)
            ->assertJsonPath('data.0.ramal', '2002');

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->get(route('index.createRamal'))
            ->assertOk()
            ->assertViewHas('companies', fn ($empresa) => (int) $empresa->id === (int) $cenario['empresaB']->id);
    }

    public function test_cadastro_ignora_empresa_do_payload_e_rejeita_usuario_externo(): void
    {
        $cenario = $this->cenario();

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->post(route('pabx.createramal'), [
                'empresa_id' => $cenario['empresaA']->id,
                'usuario_id' => $cenario['vendedorB']->id,
                'ramal' => '2002',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ramais', [
            'empresa_id' => $cenario['empresaB']->id,
            'user_id' => $cenario['vendedorB']->id,
            'ramal' => '2002',
        ]);
        $this->assertDatabaseMissing('ramais', [
            'empresa_id' => $cenario['empresaA']->id,
            'user_id' => $cenario['vendedorB']->id,
        ]);

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->post(route('pabx.createramal'), [
                'usuario_id' => $cenario['vendedorA']->id,
                'ramal' => '9999',
            ])
            ->assertSessionHasErrors('usuario_id');

        $this->assertDatabaseMissing('ramais', ['ramal' => '9999']);
    }

    public function test_ramal_nao_pode_ser_atribuido_ao_master_global(): void
    {
        $cenario = $this->cenario();
        $masterComEmpresaDeOrigem = User::factory()->create([
            'empresa_id' => $cenario['empresaB']->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaB']->id])
            ->post(route('pabx.createramal'), [
                'usuario_id' => $masterComEmpresaDeOrigem->id,
                'ramal' => '9090',
            ])
            ->assertSessionHasErrors('usuario_id');

        $this->assertDatabaseMissing('ramais', [
            'user_id' => $masterComEmpresaDeOrigem->id,
            'ramal' => '9090',
        ]);
    }

    public function test_master_global_nao_pode_originar_ligacao_como_usuario_operacional(): void
    {
        $cenario = $this->cenario();
        Http::fake();

        $this->actingAs($cenario['master'])
            ->withSession([TenantContext::SESSION_KEY => $cenario['empresaA']->id])
            ->postJson(route('pabx.clickToCall'), [
                'contato_id' => $cenario['contatoA'],
                'telefone' => '11911111111',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseCount('ligacoes', 0);
    }

    public function test_vendedor_nao_acessa_gestao_e_nao_liga_para_contato_ou_numero_indevido(): void
    {
        $cenario = $this->cenario();
        $outroVendedorA = User::factory()->create([
            'empresa_id' => $cenario['empresaA']->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $contatoOutro = $this->contato($cenario['empresaA']->id, $outroVendedorA->id, 'Lead de colega', '11933333333');
        $this->atribuicao($cenario['empresaA']->id, $outroVendedorA->id, $contatoOutro, $cenario['tabulacaoA']);
        $this->ramal($cenario['empresaA']->id, $cenario['vendedorA']->id, '1001');
        Http::fake();

        $this->actingAs($cenario['vendedorA'])->getJson(route('pabx.getRamais'))->assertForbidden();
        $this->postJson(route('pabx.clickToCall'), [
            'contato_id' => $cenario['contatoB'],
            'telefone' => '11922222222',
        ])->assertUnprocessable();
        $this->postJson(route('pabx.clickToCall'), [
            'contato_id' => $contatoOutro,
            'telefone' => '11933333333',
        ])->assertForbidden();
        $this->postJson(route('pabx.clickToCall'), [
            'contato_id' => $cenario['contatoA'],
            'telefone' => '11999999999',
        ])->assertUnprocessable();

        Http::assertNothingSent();
        $this->assertDatabaseCount('ligacoes', 0);
    }

    public function test_ligacao_valida_tenant_antes_do_provedor_e_registra_na_empresa_correta(): void
    {
        $cenario = $this->cenario();
        $this->ramal($cenario['empresaA']->id, $cenario['vendedorA']->id, '1001');
        TenantServiceCredential::query()->create([
            'empresa_id' => $cenario['empresaA']->id,
            'service' => 'voip_mais',
            'endpoint' => 'https://voip-a.example.test/click-to-call',
            'credentials' => ['token' => 'token-empresa-a'],
            'active' => true,
        ]);
        TenantServiceCredential::query()->create([
            'empresa_id' => $cenario['empresaB']->id,
            'service' => 'voip_mais',
            'endpoint' => 'https://voip-b.example.test/click-to-call',
            'credentials' => ['token' => 'token-empresa-b'],
            'active' => true,
        ]);
        Http::fake([
            '*' => Http::response(['success' => true, 'data' => 'Ligação iniciada', 'idCall' => 'call-a'], 200),
        ]);

        $this->actingAs($cenario['vendedorA'])
            ->postJson(route('pabx.clickToCall'), [
                'contato_id' => $cenario['contatoA'],
                'telefone' => '(11) 91111-1111',
            ])
            ->assertCreated()
            ->assertJson(['error' => false, 'message' => 'Ligação iniciada']);

        Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://voip-a.example.test/click-to-call')
            && $request['api_token'] === 'token-empresa-a'
            && $request['ramal'] === '1001'
            && $request['destino'] === '11911111111');
        $this->assertDatabaseHas('ligacoes', [
            'empresa_id' => $cenario['empresaA']->id,
            'user_id' => $cenario['vendedorA']->id,
            'contato_id' => $cenario['contatoA'],
            'tabulacao_id' => $cenario['tabulacaoA'],
            'id_call' => 'call-a',
        ]);
        $this->assertDatabaseMissing('ligacoes', ['empresa_id' => $cenario['empresaB']->id]);
    }

    public function test_ligacao_sem_credencial_da_empresa_falha_fechado(): void
    {
        $cenario = $this->cenario();
        $this->ramal($cenario['empresaA']->id, $cenario['vendedorA']->id, '1001');
        Http::fake();

        $this->actingAs($cenario['vendedorA'])
            ->postJson(route('pabx.clickToCall'), [
                'contato_id' => $cenario['contatoA'],
                'telefone' => '11911111111',
            ])
            ->assertServiceUnavailable()
            ->assertJsonPath('message', 'Integração de telefonia não configurada para esta empresa.');

        Http::assertNothingSent();
        $this->assertDatabaseCount('ligacoes', 0);
    }

    /** @return array<string, mixed> */
    private function cenario(): array
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Corretora A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Corretora B']);
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($empresaA->id);
        $catalog->provision($empresaB->id);
        $tabulacaoA = $catalog->id($empresaA->id, TabulationCode::PROSPECCAO);
        $tabulacaoB = $catalog->id($empresaB->id, TabulationCode::PROSPECCAO);
        $master = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $vendedorA = User::factory()->create(['empresa_id' => $empresaA->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $vendedorB = User::factory()->create(['empresa_id' => $empresaB->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $contatoA = $this->contato($empresaA->id, $vendedorA->id, 'Lead A', '11911111111');
        $contatoB = $this->contato($empresaB->id, $vendedorB->id, 'Lead B', '11922222222');
        $this->atribuicao($empresaA->id, $vendedorA->id, $contatoA, $tabulacaoA);
        $this->atribuicao($empresaB->id, $vendedorB->id, $contatoB, $tabulacaoB);

        return compact(
            'empresaA', 'empresaB', 'master', 'vendedorA', 'vendedorB',
            'contatoA', 'contatoB', 'tabulacaoA', 'tabulacaoB'
        );
    }

    private function contato(int $empresaId, int $userId, string $nome, string $telefone): int
    {
        return DB::table('contatos')->insertGetId([
            'empresa_id' => $empresaId,
            'user_import_id' => $userId,
            'nome_cliente' => $nome,
            'telefone1' => $telefone,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function atribuicao(int $empresaId, int $userId, int $contatoId, int $tabulacaoId): void
    {
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ramal(int $empresaId, int $userId, string $ramal): int
    {
        return DB::table('ramais')->insertGetId([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'ramal' => $ramal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
