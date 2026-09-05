<?php

namespace Tests\Feature\Backoffice;

use App\Enums\RenovacaoStatus;
use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\RenovacaoOportunidade;
use App\Models\User;
use App\Models\Vendas;
use App\Services\RenovacaoService;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RenovacoesTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $admin;

    private TabulationCatalog $tabulationCatalog;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([UserRole::VENDEDOR => 'VENDEDOR', UserRole::ADMINISTRATIVO => 'ADMINISTRATIVO', UserRole::BACKOFFICE => 'BACKOFFICE', UserRole::DEVELOPER => 'DEVELOPER', UserRole::SUPERVISOR => 'SUPERVISOR'] as $id => $nome) {
            DB::table('user_roles')->insert(['id' => $id, 'tipo_usuario' => $nome, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->tabulationCatalog = app(TabulationCatalog::class);
        $empresaAnterior = Empresa::factory()->create();
        $this->tabulationCatalog->provision($empresaAnterior->id);
        $this->empresa = Empresa::factory()->create();
        $this->tabulationCatalog->provision($this->empresa->id);
        $this->vendedor = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $this->admin = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        app(TenantContext::class)->set($this->empresa->id);
    }

    private function venda(string $documento, ?string $implantacao, string $status = TabulationCode::IMPLANTADO): Vendas
    {
        $contato = DB::table('contatos')->insertGetId(['empresa_id' => $this->empresa->id, 'user_import_id' => $this->vendedor->id, 'nome_cliente' => 'Cliente Teste', 'cpf' => preg_replace('/\D/', '', $documento), 'telefone1' => '11999999999', 'created_at' => now(), 'updated_at' => now()]);

        return Vendas::create(['empresa_id' => $this->empresa->id, 'user_id' => $this->vendedor->id, 'contato_id' => $contato, 'tabulacao_id' => $this->tabulationCatalog->id($this->empresa->id, $status), 'nome_contrato' => 'Cliente Teste', 'cpf_cnpj' => $documento, 'operadora' => 'AMIL', 'data_vigencia' => now(), 'data_implantacao' => $implantacao, 'created_at' => now()->subYears(3)]);
    }

    public function test_cria_oportunidade_apos_24_meses_e_normaliza_documento(): void
    {
        $venda = $this->venda('12.345.678/0001-90', now()->subMonths(25)->toDateString());
        app(RenovacaoService::class)->sincronizar();
        $this->assertDatabaseHas('renovacao_oportunidades', ['empresa_id' => $this->empresa->id, 'documento' => '12345678000190', 'venda_referencia_id' => $venda->id, 'status' => RenovacaoStatus::ELEGIVEL]);
    }

    public function test_venda_implantada_recente_impede_contato_do_contrato_antigo(): void
    {
        $this->venda('123.456.789-09', now()->subYears(3)->toDateString());
        $this->venda('12345678909', now()->subMonths(3)->toDateString());
        app(RenovacaoService::class)->sincronizar();
        $this->assertDatabaseCount('renovacao_oportunidades', 0);
    }

    public function test_usa_data_de_cadastro_quando_implantacao_nao_existe(): void
    {
        $venda = $this->venda('98765432100', null);
        DB::table('vendas')->where('id', $venda->id)->update(['created_at' => now()->subMonths(25)]);
        app(RenovacaoService::class)->sincronizar();
        $this->assertDatabaseHas('renovacao_oportunidades', ['documento' => '98765432100', 'status' => RenovacaoStatus::ELEGIVEL]);
    }

    public function test_registra_tratativa_e_exige_data_para_reagendamento(): void
    {
        $this->venda('12345678909', now()->subYears(3)->toDateString());
        app(RenovacaoService::class)->sincronizar();
        $o = RenovacaoOportunidade::firstOrFail();
        $this->actingAs($this->admin)->postJson(route('backoffice.renovacoes.tratar', $o), ['status' => RenovacaoStatus::REAGENDADO])->assertUnprocessable();
        $this->actingAs($this->admin)->postJson(route('backoffice.renovacoes.tratar', $o), ['status' => RenovacaoStatus::REAGENDADO, 'recontato_em' => now()->addWeek()->toDateString(), 'observacao' => 'Cliente pediu retorno.'])->assertOk();
        $this->assertDatabaseHas('renovacao_interacoes', ['oportunidade_id' => $o->id, 'tipo' => RenovacaoStatus::REAGENDADO]);
    }

    public function test_nova_venda_cadastrada_marca_conversao_sem_depender_do_status(): void
    {
        $this->venda('12345678909', now()->subYears(3)->toDateString());
        app(RenovacaoService::class)->sincronizar();
        $nova = $this->venda('123.456.789-09', null, TabulationCode::VENDA);
        $this->assertDatabaseHas('renovacao_oportunidades', ['status' => RenovacaoStatus::CONVERTIDO, 'nova_venda_id' => $nova->id]);
    }

    public function test_vendedor_nao_acessa_e_gestao_acessa(): void
    {
        $this->actingAs($this->vendedor)->get(route('backoffice.renovacoes.index'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('backoffice.renovacoes.index'))->assertOk();
    }

    public function test_relacoes_inconsistentes_nao_expoem_usuarios_de_outra_empresa(): void
    {
        $venda = $this->venda('12345678909', now()->subYears(3)->toDateString());
        app(RenovacaoService::class)->sincronizar(false, $this->empresa->id);
        $oportunidade = RenovacaoOportunidade::firstOrFail();
        $outraEmpresa = Empresa::factory()->create();
        $usuarioExterno = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        DB::table('vendas')->where('id', $venda->id)->update(['user_id' => $usuarioExterno->id]);
        DB::table('renovacao_oportunidades')->where('id', $oportunidade->id)->update([
            'vendedor_original_id' => $usuarioExterno->id,
            'responsavel_id' => $usuarioExterno->id,
        ]);
        DB::table('renovacao_interacoes')->insert([
            'oportunidade_id' => $oportunidade->id,
            'user_id' => $usuarioExterno->id,
            'tipo' => RenovacaoStatus::EM_CONVERSA,
            'observacao' => 'Relação externa inconsistente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $lista = $this->actingAs($this->admin)
            ->getJson(route('backoffice.renovacoes.dados'));

        $lista->assertOk()
            ->assertJsonPath('data.0.vendedor_original', null)
            ->assertJsonPath('data.0.responsavel', null)
            ->assertDontSee($usuarioExterno->name);

        $detalhe = $this->getJson(route('backoffice.renovacoes.show', $oportunidade->id));
        $detalhe->assertOk()
            ->assertJsonPath('venda_referencia.user', null)
            ->assertJsonPath('vendedor_original', null)
            ->assertJsonPath('responsavel', null)
            ->assertJsonPath('interacoes.0.usuario', null)
            ->assertDontSee($usuarioExterno->name);

        $this->getJson(route('backoffice.renovacoes.dados', ['responsavel_id' => $usuarioExterno->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['responsavel_id']);
    }
}
