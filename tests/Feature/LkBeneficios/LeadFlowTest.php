<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadFlowTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $corretor;
    private Produto $produto;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => 4, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'tipo_usuario' => 'BENEFICIOS', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->corretor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => 4,
        ]);
        $this->produto = Produto::factory()->create([
            'empresa_id' => $this->empresa->id,
            'tipo' => 'VIDA',
        ]);
    }

    public function test_cadastro_manual_cria_lead_em_novo_cliente(): void
    {
        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.store'), [
            'produto_interesse_id' => $this->produto->id,
            'cliente_tipo' => 'PF',
            'cpf_cnpj' => '12345678900',
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
        ]);

        $resp->assertStatus(201)->assertJsonStructure(['lead_id']);
        $this->assertDatabaseHas('lk_beneficios_leads', [
            'empresa_id' => $this->empresa->id,
            'cpf_cnpj' => '12345678900',
            'status' => 'NOVO_CLIENTE',
            'origem' => 'MANUAL',
        ]);
        $this->assertDatabaseHas('lk_beneficios_lead_historico', [
            'status_novo' => 'NOVO_CLIENTE',
            'status_anterior' => null,
        ]);
    }

    public function test_cadastro_manual_retorna_422_com_payload_invalido(): void
    {
        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.store'), [
            'cliente_tipo' => 'INVALIDO',
            'cpf_cnpj' => '123',
            'nome' => '',
        ]);

        $resp->assertStatus(422)
            ->assertJsonValidationErrors(['produto_interesse_id', 'cliente_tipo', 'nome']);
    }

    public function test_cadastro_manual_bloqueia_duplicata_do_mesmo_produto(): void
    {
        Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $this->produto->id,
            'cpf_cnpj' => '12345678900',
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.store'), [
            'produto_interesse_id' => $this->produto->id,
            'cliente_tipo' => 'PF',
            'cpf_cnpj' => '12345678900',
            'nome' => 'Duplicado',
        ]);

        $resp->assertStatus(422);
    }

    public function test_kanban_mover_altera_status_e_grava_historico(): void
    {
        $lead = Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $this->produto->id,
            'status' => 'NOVO_CLIENTE',
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.mover'), [
            'lead_id' => $lead->id,
            'novo_status' => 'NEGOCIANDO',
            'observacao' => 'cliente interessado',
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('lk_beneficios_leads', [
            'id' => $lead->id,
            'status' => 'NEGOCIANDO',
        ]);
        $this->assertDatabaseHas('lk_beneficios_lead_historico', [
            'lead_id' => $lead->id,
            'status_anterior' => 'NOVO_CLIENTE',
            'status_novo' => 'NEGOCIANDO',
        ]);
    }

    public function test_kanban_mover_retorna_422_para_status_invalido(): void
    {
        $lead = Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $this->produto->id,
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.mover'), [
            'lead_id' => $lead->id,
            'novo_status' => 'STATUS_INEXISTENTE',
        ]);

        $resp->assertStatus(422);
    }

    public function test_multi_tenant_usuario_nao_enxerga_lead_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroProduto = Produto::factory()->create(['empresa_id' => $outraEmpresa->id]);
        $leadAlheio = Lead::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $outroProduto->id,
            'status' => 'NOVO_CLIENTE',
        ]);

        $resp = $this->actingAs($this->corretor)->getJson(route('lk-beneficios.leads.dados'));

        $resp->assertOk();
        $dadosJson = $resp->json('colunas.NOVO_CLIENTE');
        $this->assertEmpty($dadosJson, 'Lead de outra empresa vazou na listagem.');
    }

    public function test_multi_tenant_mover_lead_de_outra_empresa_retorna_404(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outroProduto = Produto::factory()->create(['empresa_id' => $outraEmpresa->id]);
        $leadAlheio = Lead::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $outroProduto->id,
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.leads.mover'), [
            'lead_id' => $leadAlheio->id,
            'novo_status' => 'NEGOCIANDO',
        ]);

        $resp->assertStatus(404);
    }

    public function test_conversao_cria_contrato_ligado_ao_lead_e_marca_convertido(): void
    {
        $lead = Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $this->produto->id,
            'cpf_cnpj' => '12345678900',
            'nome' => 'Maria',
            'status' => 'DOCUMENTACAO',
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(
            route('lk-beneficios.leads.converter.submit', ['id' => $lead->id]),
            [
                'valor_mensal' => 200.0,
                'forma_pagamento' => 'MENSAL',
                'vidas_total' => 1,
                'vidas_titulares' => 1,
            ]
        );

        $resp->assertStatus(201)->assertJsonStructure(['contrato_id', 'redirect']);
        $this->assertDatabaseHas('lk_beneficios_contratos', [
            'lead_id' => $lead->id,
            'empresa_id' => $this->empresa->id,
            'cpf_cnpj' => '12345678900',
            'nome_cliente' => 'Maria',
        ]);
        $this->assertNotNull(Lead::find($lead->id)->convertido_em);
    }

    public function test_conversao_bloqueia_lead_ja_convertido(): void
    {
        $lead = Lead::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->corretor->id,
            'produto_interesse_id' => $this->produto->id,
            'convertido_em' => now(),
        ]);

        $resp = $this->actingAs($this->corretor)->postJson(
            route('lk-beneficios.leads.converter.submit', ['id' => $lead->id]),
            ['valor_mensal' => 100.0]
        );

        $resp->assertStatus(422);
    }
}
