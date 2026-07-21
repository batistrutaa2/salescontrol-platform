<?php

namespace Tests\Feature\Backoffice;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Todo contrato abre no layout novo (abas), independente do layout_venda de
 * origem — os campos que o layout antigo não tinha ficam em branco.
 */
class OpenContractLayoutTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Contrato mínimo, como os antigos: sem tipo_contrato/data_abertura/tipo_empresa e sem filhos. */
    private function criarContratoLegado(string $layout): Vendas
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id, 'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Cliente Legado', 'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $venda = Vendas::create([
            'empresa_id' => $this->empresa->id, 'user_id' => $this->admin->id, 'contato_id' => $contatoId,
            'tabulacao_id' => Tabulations::IMPLANTADO, 'nome_contrato' => 'CONTRATO LEGADO',
            'cpf_cnpj' => '11222333000144', 'operadora' => 'AMIL', 'valor_contrato' => 350.00,
            'vidas' => 2, 'data_vigencia' => now(),
        ]);

        // Titular como os antigos: sem data_nascimento/cargo/plano.
        DB::table('vendas_titulares')->insert([
            'venda_id' => $venda->id, 'nome' => 'TITULAR LEGADO', 'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('vendas')->where('id', $venda->id)->update([
            'layout_venda' => $layout, 'tipo_contrato' => null, 'tipo_empresa' => null, 'data_abertura' => null,
        ]);

        return $venda->fresh();
    }

    public function test_contrato_antigo_abre_no_layout_novo(): void
    {
        $venda = $this->criarContratoLegado('ANTIGO');

        $response = $this->actingAs($this->admin)->get(route('backoffice.openContract', $venda->id));

        $response->assertOk();
        // Marca do layout novo (abas) presente; dados legados renderizados.
        $response->assertSee('pv-tabnav', false);
        $response->assertSee('CONTRATO LEGADO');
        $response->assertSee('TITULAR LEGADO');
        // layout_venda preservado no hidden (não vira NOVO ao abrir).
        $response->assertSee('value="ANTIGO"', false);
    }

    public function test_contrato_importacao_sys_abre_no_layout_novo(): void
    {
        $venda = $this->criarContratoLegado('IMPORTACAO_SYS');

        $response = $this->actingAs($this->admin)->get(route('backoffice.openContract', $venda->id));

        $response->assertOk();
        $response->assertSee('pv-tabnav', false);
        $response->assertSee('value="IMPORTACAO_SYS"', false);
    }

    public function test_contrato_novo_continua_no_layout_novo(): void
    {
        $venda = $this->criarContratoLegado('NOVO');

        $response = $this->actingAs($this->admin)->get(route('backoffice.openContract', $venda->id));

        $response->assertOk();
        $response->assertSee('pv-tabnav', false);
    }
}
