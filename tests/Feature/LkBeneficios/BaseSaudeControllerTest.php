<?php

namespace Tests\Feature\LkBeneficios;

use App\Models\Empresa;
use App\Models\User;
use App\Modules\LkBeneficios\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BaseSaudeControllerTest extends TestCase
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

        // Cria um contato mínimo para satisfazer a FK de vendas.contato_id
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->corretor->id,
            'cpf' => '12345678900',
            'nome_cliente' => 'João Silva',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insere 2 contratos do mesmo CPF na base do saúde (para agrupar por cpf_cnpj)
        DB::table('vendas')->insert([
            [
                'empresa_id' => $this->empresa->id,
                'user_id' => $this->corretor->id,
                'contato_id' => $contatoId,
                'cpf_cnpj' => '12345678900',
                'nome_contrato' => 'João Silva',
                'email' => 'joao@example.com',
                'telefone1' => '11999999999',
                'operadora' => 'AMIL',
                'nome_plano' => 'Plano A',
                'valor_contrato' => 500.00,
                'data_vigencia' => now(),
                'data_implantacao' => '2022-01-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'empresa_id' => $this->empresa->id,
                'user_id' => $this->corretor->id,
                'contato_id' => $contatoId,
                'cpf_cnpj' => '12345678900',
                'nome_contrato' => 'João Silva',
                'email' => 'joao@example.com',
                'telefone1' => '11999999999',
                'operadora' => 'SULAMERICA',
                'nome_plano' => 'Plano B',
                'valor_contrato' => 700.00,
                'data_vigencia' => now(),
                'data_implantacao' => '2024-06-20',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function test_datatable_agrupa_por_cpf_cnpj_e_contabiliza_contratos(): void
    {
        $resp = $this->actingAs($this->corretor)->getJson(route('lk-beneficios.base-saude.datatable'));

        $resp->assertOk();
        $dados = $resp->json('data');
        $this->assertCount(1, $dados, 'Deveria haver 1 linha agrupada por CPF/CNPJ.');
        $this->assertSame('12345678900', $dados[0]['cpf_cnpj']);
        $this->assertSame(2, (int) $dados[0]['qtd_contratos']);
    }

    public function test_pegar_lead_da_base_saude_cria_registro_em_novo_cliente(): void
    {
        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.base-saude.pegar'), [
            'cpf_cnpj' => '12345678900',
            'produto_id' => $this->produto->id,
        ]);

        $resp->assertStatus(201);
        $this->assertDatabaseHas('lk_beneficios_leads', [
            'empresa_id' => $this->empresa->id,
            'cpf_cnpj' => '12345678900',
            'status' => 'NOVO_CLIENTE',
            'origem' => 'BASE_SAUDE',
            'produto_interesse_id' => $this->produto->id,
        ]);
    }

    public function test_pegar_lead_bloqueia_duplicata_para_mesmo_produto(): void
    {
        $this->actingAs($this->corretor)->postJson(route('lk-beneficios.base-saude.pegar'), [
            'cpf_cnpj' => '12345678900',
            'produto_id' => $this->produto->id,
        ])->assertStatus(201);

        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.base-saude.pegar'), [
            'cpf_cnpj' => '12345678900',
            'produto_id' => $this->produto->id,
        ]);

        $resp->assertStatus(422);
    }

    public function test_pegar_lead_falha_para_cpf_fora_da_empresa(): void
    {
        $resp = $this->actingAs($this->corretor)->postJson(route('lk-beneficios.base-saude.pegar'), [
            'cpf_cnpj' => '99999999999',
            'produto_id' => $this->produto->id,
        ]);

        $resp->assertStatus(422);
    }
}
