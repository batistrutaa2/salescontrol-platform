<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\Recebivel;
use App\Models\RegrasComissionamento;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class FinanceiroAccessTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $financeiro;

    private User $admin;

    private User $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->financeiro = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::FINANCEIRO,
            'ativo' => 'Y',
        ]);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
    }

    public function test_login_do_financeiro_redireciona_para_recebiveis(): void
    {
        $this->post(route('login.autentication'), [
            'email' => $this->financeiro->email,
            'password' => 'password',
        ])->assertRedirect(route('financeiro.recebiveis.index'));
    }

    public function test_financeiro_acessa_modulo_e_ve_somente_menu_financeiro(): void
    {
        $this->actingAs($this->financeiro)
            ->get(route('financeiro.recebiveis.index'))
            ->assertOk()
            ->assertSee('Regras de Comissão')
            ->assertSee('Recebíveis')
            ->assertDontSee('Dashboard')
            ->assertDontSee('Escola LK Brokers');
    }

    public function test_financeiro_nao_acessa_rotas_fora_do_modulo(): void
    {
        $this->actingAs($this->financeiro)
            ->get(route('home.dashboard'))
            ->assertRedirect(route('financeiro.recebiveis.index'));

        $this->actingAs($this->financeiro)
            ->getJson(route('home.dashboard'))
            ->assertForbidden();
    }

    public function test_outros_perfis_sem_permissao_nao_acessam_financeiro(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('financeiro.recebiveis.index'))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('financeiro.recebiveis.index'))
            ->assertOk();
    }

    public function test_cadastro_de_usuario_oferece_e_aceita_perfil_financeiro(): void
    {
        $this->actingAs($this->admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('<option value="8">FINANCEIRO</option>', false);

        $this->postJson(route('usuarios.createUser'), [
            'name' => 'Equipe Financeira',
            'email' => 'financeiro@example.com',
            'whatsapp' => null,
            'user_role_id' => UserRole::FINANCEIRO,
            'empresa_id' => (string) $this->empresa->id,
            'password' => 'senha-segura',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'financeiro@example.com',
            'user_role_id' => UserRole::FINANCEIRO,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_ids_financeiros_de_outra_empresa_nao_podem_ser_lidos_ou_alterados(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroVendedor = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $operadoraOutra = Operadora::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Operadora isolada',
            'status' => 'Y',
        ]);
        $regraOutra = RegrasComissionamento::create([
            'empresa_id' => $outraEmpresa->id,
            'operadora_id' => $operadoraOutra->id,
            'categoria' => 'PME',
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $outraEmpresa->id,
            'user_import_id' => $outroVendedor->id,
            'nome_cliente' => 'Cliente isolado',
            'cpf' => '12345678901',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaOutra = Vendas::create([
            'empresa_id' => $outraEmpresa->id,
            'user_id' => $outroVendedor->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato isolado',
            'data_vigencia' => today(),
        ]);
        $recebivelOutro = Recebivel::create([
            'empresa_id' => $outraEmpresa->id,
            'venda_id' => $vendaOutra->id,
            'vendedor_id' => $outroVendedor->id,
            'operadora' => 'Operadora isolada',
            'plano' => 'Plano isolado',
            'parcela' => 1,
            'valor' => 100,
            'data_prevista' => today(),
            'status' => 'PENDENTE',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('financeiro.regras.update', $regraOutra->id), [
                'operadora_id' => $operadoraOutra->id,
                'categoria' => 'PME',
            ])->assertNotFound();

        $this->postJson(route('financeiro.recebiveis.pagar', $recebivelOutro->id))
            ->assertNotFound();
        $this->get(route('financeiro.recebiveis.contrato', $vendaOutra->id))
            ->assertNotFound();

        $this->assertDatabaseHas('recebiveis', [
            'id' => $recebivelOutro->id,
            'status' => 'PENDENTE',
        ]);
    }

    public function test_falha_ao_gerar_parcelas_nao_expoe_excecao_interna(): void
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Cliente local',
            'cpf' => '12345678901',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $venda = Vendas::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato local',
            'data_vigencia' => today(),
        ]);
        Recebivel::saving(fn () => throw new RuntimeException('SQLSTATE segredo_financeiro'));

        $this->actingAs($this->admin)
            ->postJson(route('financeiro.recebiveis.gerarManual', $venda->id), [
                'quantidade_parcelas' => 1,
                'data_inicial' => today()->format('Y-m-d'),
                'valor' => 100,
            ])
            ->assertStatus(500)
            ->assertJsonPath('message', 'Não foi possível gerar as parcelas neste momento.')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('segredo_financeiro');
    }
}
