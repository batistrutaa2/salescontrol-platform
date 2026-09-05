<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\ComissaoPagamento;
use App\Models\ContaPagamento;
use App\Models\Empresa;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ComissionamentoTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $admin;

    private User $vendedor;

    private User $outroVendedor;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();
        $this->admin = $this->usuario($this->empresa, UserRole::ADMINISTRATIVO);
        $this->vendedor = $this->usuario($this->empresa, UserRole::VENDEDOR);
        $this->outroVendedor = $this->usuario($this->outraEmpresa, UserRole::VENDEDOR);
    }

    public function test_nao_cria_configuracao_para_usuario_de_outra_empresa(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('comissionamento.store'), [
                'user_id' => $this->outroVendedor->id,
                'percentual' => 10,
                'periodicidade' => 'mensal',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseMissing('comissionamento_configuracao', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->outroVendedor->id,
        ]);
    }

    public function test_master_global_nao_pode_ser_tratado_como_comissionado_do_tenant(): void
    {
        $master = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);

        $this->actingAs($master)
            ->postJson(route('comissionamento.store'), [
                'user_id' => $master->id,
                'percentual' => 10,
                'percentual_angariacao' => 20,
                'imposto' => 0,
                'grade' => 'ADMIN',
                'periodicidade' => 'mensal',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseMissing('comissionamento_configuracao', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $master->id,
        ]);
    }

    public function test_conta_bancaria_de_outro_tenant_nao_pode_ser_consultada(): void
    {
        $conta = ContaPagamento::create([
            'user_id' => $this->outroVendedor->id,
            'banco' => 'Banco externo',
            'chave_pix' => 'segredo@externo.test',
            'is_default' => true,
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('contas.byUser', ['user_id' => $this->outroVendedor->id]))
            ->assertNotFound()
            ->assertJsonMissing(['id' => $conta->id]);
    }

    public function test_vendedor_nao_consulta_conta_de_colega_nem_marca_pagamento(): void
    {
        $colega = $this->usuario($this->empresa, UserRole::VENDEDOR);
        ContaPagamento::create(['user_id' => $colega->id, 'banco' => 'Privado', 'is_default' => true]);
        $pagamento = $this->pagamento($this->empresa, $colega, $this->admin);

        $this->actingAs($this->vendedor)
            ->getJson(route('contas.byUser', ['user_id' => $colega->id]))
            ->assertForbidden();
        $this->postJson(route('comissao.pagar', $pagamento->id))->assertForbidden();

        $this->assertNull($pagamento->fresh()->pago_em);
    }

    public function test_pagamento_de_outro_tenant_nao_pode_ser_alterado(): void
    {
        $adminOutro = $this->usuario($this->outraEmpresa, UserRole::ADMINISTRATIVO);
        $pagamento = $this->pagamento($this->outraEmpresa, $this->outroVendedor, $adminOutro);

        $this->actingAs($this->admin)
            ->postJson(route('comissao.pagar', $pagamento->id))
            ->assertNotFound();

        $this->assertNull($pagamento->fresh()->pago_em);
    }

    public function test_vendedor_nao_acessa_configuracao_faturamento_ou_mutacoes_de_gestao(): void
    {
        $rotasGet = [
            route('ranking.index'),
            route('ranking.config'),
            route('comissionamento.index'),
            route('comissionamento.getCommissioning'),
            route('comissionamento.invoiceCommission'),
            route('comissionamento.faturamento'),
            route('comissionamento.vendedores'),
        ];

        $this->actingAs($this->vendedor);

        foreach ($rotasGet as $rota) {
            $this->getJson($rota)->assertForbidden();
        }

        $this->postJson(route('comissionamento.store'))->assertForbidden();
        $this->postJson(route('comissionamento.pagar'))->assertForbidden();
        $this->postJson(route('comissionamento.ajuste.store'))->assertForbidden();
    }

    public function test_vendedor_visualiza_somente_os_proprios_pagamentos(): void
    {
        $colega = $this->usuario($this->empresa, UserRole::VENDEDOR);
        $meuPagamento = $this->pagamento($this->empresa, $this->vendedor, $this->admin);
        $this->pagamento($this->empresa, $colega, $this->admin);
        $adminOutro = $this->usuario($this->outraEmpresa, UserRole::ADMINISTRATIVO);
        $this->pagamento($this->outraEmpresa, $this->outroVendedor, $adminOutro);

        $this->actingAs($this->vendedor)
            ->getJson(route('comissionamento.pagamentos.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $meuPagamento->id)
            ->assertJsonPath('data.0.vendedor_id', $this->vendedor->id);

        $supervisor = $this->usuario($this->empresa, UserRole::SUPERVISOR);
        $this->actingAs($supervisor)
            ->getJson(route('comissionamento.pagamentos.data'))
            ->assertForbidden();
    }

    public function test_tela_expoe_acoes_de_pagamento_por_capacidade_sem_id_fixo_de_perfil(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('comissionamento.pagamentos'))
            ->assertOk()
            ->assertSee('data-can-manage-payments="0"', false)
            ->assertSee('data-estornar-url=""', false)
            ->assertSee('data-pagar-base=""', false);

        $this->actingAs($this->admin)
            ->get(route('comissionamento.pagamentos'))
            ->assertOk()
            ->assertSee('data-can-manage-payments="1"', false)
            ->assertSee(route('comissionamento.pagamentos.estornar', ['id' => 'PAYMENT_ID']), false)
            ->assertSee(route('comissao.pagar', ['id' => 'PAYMENT_ID']), false);
    }

    public function test_master_enxerga_pagamento_da_empresa_ativa_mesmo_sendo_o_autor_global(): void
    {
        $master = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
            'name' => 'Master da plataforma',
        ]);
        $pagamento = $this->pagamento($this->outraEmpresa, $this->outroVendedor, $master);

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->outraEmpresa->id])
            ->getJson(route('comissionamento.pagamentos.data'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pagamento->id)
            ->assertJsonPath('data.0.criado_por', 'Master da plataforma');
    }

    public function test_faturamento_rejeita_filtro_de_vendedor_de_outra_empresa(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('comissionamento.faturamento', [
                'vendedor_id' => $this->outroVendedor->id,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('vendedor_id');
    }

    public function test_angariacao_usa_percentual_configurado_e_grade_filtrada_no_servidor(): void
    {
        DB::table('comissionamento_configuracao')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'percentual' => 12,
            'percentual_angariacao' => 37.5,
            'imposto' => 0,
            'grade' => 'SENIOR',
            'periodicidade' => 'mensal',
            'salario' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Cliente da angariação',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendas')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato da angariação',
            'valor_contrato' => 1000,
            'angariacao_status' => 'SIM',
            'angariacao_valor' => 200,
            'comissao_paga' => 1,
            'angariacao_paga' => 0,
            'data_vigencia' => today(),
            'data_implantacao' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('comissionamento.faturamento', ['grade' => 'senior']))
            ->assertOk()
            ->assertJsonCount(1, 'vendedores')
            ->assertJsonPath('vendedores.0.grade', 'senior')
            ->assertJsonPath('vendedores.0.contratos.0.tipo_item', 'ANGARIACAO')
            ->assertJsonPath('vendedores.0.contratos.0.percentual_aplicado', 37.5)
            ->assertJsonPath('vendedores.0.contratos.0.valor_comissao', 75);

        $this->getJson(route('comissionamento.faturamento', ['grade' => 'junior']))
            ->assertOk()
            ->assertJsonCount(0, 'vendedores');
        $this->getJson(route('comissionamento.faturamento', ['grade' => 'inventada']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('grade');
    }

    public function test_configuracao_valida_e_persiste_percentual_de_angariacao_no_tenant(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('comissionamento.store'), [
                'user_id' => $this->vendedor->id,
                'percentual' => 8.5,
                'percentual_angariacao' => 22.75,
                'imposto' => 4,
                'grade' => 'COMERCIAL',
                'salario' => '3.500,00',
                'periodicidade' => 'mensal',
            ])
            ->assertOk();

        $this->assertDatabaseHas('comissionamento_configuracao', [
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'percentual' => 8.5,
            'percentual_angariacao' => 22.75,
            'grade' => 'COMERCIAL',
        ]);

        $this->postJson(route('comissionamento.store'), [
            'user_id' => $this->vendedor->id,
            'percentual' => 8.5,
            'percentual_angariacao' => 120,
            'imposto' => 4,
            'grade' => 'COMERCIAL',
            'periodicidade' => 'mensal',
        ])->assertUnprocessable()->assertJsonValidationErrors('percentual_angariacao');
    }

    public function test_pagamento_de_angariacao_grava_snapshot_do_percentual_configurado(): void
    {
        DB::table('comissionamento_configuracao')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'percentual' => 12,
            'percentual_angariacao' => 37.5,
            'imposto' => 0,
            'grade' => 'SENIOR',
            'periodicidade' => 'mensal',
            'salario' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Cliente da angariação paga',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaId = DB::table('vendas')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato da angariação paga',
            'valor_contrato' => 1000,
            'angariacao_status' => 'SIM',
            'angariacao_valor' => 200,
            'comissao_paga' => 1,
            'angariacao_paga' => 0,
            'data_vigencia' => today(),
            'data_implantacao' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('comissionamento.pagar'), [
                'mes' => now()->format('Y-m'),
                'vendedor_id' => $this->vendedor->id,
                'data_pagamento' => today()->toDateString(),
                'itens' => [
                    ['venda_id' => $vendaId, 'tipo_item' => 'ANGARIACAO'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('totais.liquido', 75);

        $pagamentoId = $response->json('pagamento_id');
        $this->assertDatabaseHas('comissao_pagamento_itens', [
            'comissao_pagamento_id' => $pagamentoId,
            'venda_id' => $vendaId,
            'tipo_lancamento' => 'ANGARIACAO',
            'percentual' => 37.5,
            'valor_contrato' => 200,
            'liquido' => 75,
        ]);
        $this->assertDatabaseHas('vendas', [
            'id' => $vendaId,
            'empresa_id' => $this->empresa->id,
            'angariacao_paga' => 1,
        ]);
    }

    public function test_pagamento_rejeita_reprocessamento_de_comissao_ja_paga(): void
    {
        DB::table('comissionamento_configuracao')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'percentual' => 10,
            'imposto' => 0,
            'periodicidade' => 'mensal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Cliente com comissão',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaId = DB::table('vendas')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato com comissão',
            'valor_contrato' => 1000,
            'data_vigencia' => today(),
            'data_implantacao' => today(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload = [
            'mes' => now()->format('Y-m'),
            'vendedor_id' => $this->vendedor->id,
            'data_pagamento' => today()->toDateString(),
            'itens' => [
                ['venda_id' => $vendaId, 'tipo_item' => 'COMISSAO'],
            ],
        ];

        $this->actingAs($this->admin)
            ->postJson(route('comissionamento.pagar'), $payload)
            ->assertOk();

        $this->postJson(route('comissionamento.pagar'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('itens');

        $this->assertDatabaseCount('comissao_pagamentos', 1);
        $this->assertDatabaseHas('vendas', [
            'id' => $vendaId,
            'empresa_id' => $this->empresa->id,
            'comissao_paga' => 1,
        ]);
    }

    public function test_pagamento_nao_aceita_conta_de_outro_vendedor(): void
    {
        $colega = $this->usuario($this->empresa, UserRole::VENDEDOR);
        $contaDoColega = ContaPagamento::create([
            'user_id' => $colega->id,
            'banco' => 'Banco do colega',
            'is_default' => true,
        ]);
        $pagamento = $this->pagamento($this->empresa, $this->vendedor, $this->admin);

        $this->actingAs($this->admin)
            ->postJson(route('comissao.pagar', $pagamento->id), [
                'conta_pagamento_id' => $contaDoColega->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('conta_pagamento_id');

        $this->assertNull($pagamento->fresh()->conta_pagamento_id);
    }

    private function usuario(Empresa $empresa, int $role): User
    {
        return User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => $role,
            'ativo' => 'Y',
        ]);
    }

    private function pagamento(Empresa $empresa, User $vendedor, User $criador): ComissaoPagamento
    {
        return ComissaoPagamento::create([
            'empresa_id' => $empresa->id,
            'vendedor_id' => $vendedor->id,
            'mes' => now()->format('Y-m'),
            'data_pagamento' => today(),
            'created_by' => $criador->id,
        ]);
    }
}
