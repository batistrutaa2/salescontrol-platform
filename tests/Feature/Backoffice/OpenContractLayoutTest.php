<?php

namespace Tests\Feature\Backoffice;

use App\Enums\TabulationCode;
use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
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
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id,
            'codigo' => TabulationCode::IMPLANTADO, 'descricao' => 'Cliente ativado',
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
        $response->assertSee('class="pv-status st-ok">Cliente ativado</span>', false);
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

    public function test_resumo_operacional_prioriza_observacoes_e_dados_copiaveis(): void
    {
        $venda = $this->criarContratoLegado('NOVO');
        $venda->update([
            'email' => 'implantacao@cliente.test',
            'telefone1' => '11987654321',
            'telefone2' => '1133445566',
            'obs_contrato' => 'Validar carência antes da implantação.',
        ]);

        $response = $this->actingAs($this->admin)->get(route('backoffice.openContract', $venda->id));

        $response->assertOk()
            ->assertSee('Resumo operacional do contrato')
            ->assertSee('Observações do contrato')
            ->assertSee('Validar carência antes da implantação.')
            ->assertSee('data-contract-copy="11222333000144"', false)
            ->assertSee('data-contract-copy="implantacao@cliente.test"', false)
            ->assertSee('data-contract-copy="11987654321"', false)
            ->assertSee('data-contract-copy="1133445566"', false);

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'Dados da Empresa'), strpos($html, 'Observações do contrato'));
    }

    public function test_documentos_do_contrato_abrem_em_modal_sem_ocupar_o_conteudo_principal(): void
    {
        $venda = $this->criarContratoLegado('NOVO');

        $response = $this->actingAs($this->admin)->get(route('backoffice.openContract', $venda->id));

        $response->assertOk();
        $response->assertSee('class="vd-launcher"', false);
        $response->assertSee('data-bs-target="#vd-modal-venda-'.$venda->id.'"', false);
        $response->assertSee('class="modal fade vd-modal"', false);
        $response->assertSee('modal-dialog-scrollable', false);
        $response->assertSee('Importe novos documentos');

        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'class="pv-screen"'), strpos($html, 'class="vd-launcher"'));
    }

    public function test_componente_de_documentos_permanece_inline_por_padrao(): void
    {
        $html = Blade::render('<x-venda-documentos />');

        $this->assertStringContainsString('class="vd-panel" data-venda-documentos', $html);
        $this->assertStringNotContainsString('data-vd-modal', $html);
        $this->assertStringNotContainsString('class="vd-launcher"', $html);
    }

    public function test_backoffice_cadastra_e_exibe_destino_da_portabilidade(): void
    {
        $venda = $this->criarContratoLegado('NOVO');
        $operadoraAnteriorId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'ORIGEM SAÚDE',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $operadoraDestinoId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'DESTINO SAÚDE',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $planoDestinoId = DB::table('planos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'operadora_id' => $operadoraDestinoId,
            'nome' => 'PLANO DESTINO OURO',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.portabilidades.storePME'), [
                'venda_id' => $venda->id,
                'nome' => 'Cliente Portado',
                'operadora_anterior_id' => $operadoraAnteriorId,
                'operadora_destino_id' => $operadoraDestinoId,
                'plano_destino_id' => $planoDestinoId,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('vendas_portabilidades', [
            'venda_id' => $venda->id,
            'nome' => 'CLIENTE PORTADO',
            'operadora_destino_id' => $operadoraDestinoId,
            'plano_destino_id' => $planoDestinoId,
        ]);
        $this->assertSame('SIM', $venda->fresh()->portabilidade_status);
        $this->assertSame(1, $venda->fresh()->qtd_portabilidade);

        $this->actingAs($this->admin)
            ->get(route('backoffice.openContract', $venda->id))
            ->assertOk()
            ->assertSee('Operadora de destino')
            ->assertSee('DESTINO SAÚDE')
            ->assertSee('Plano de destino')
            ->assertSee('PLANO DESTINO OURO');
    }

    public function test_beneficiario_rejeita_plano_e_coparticipacao_de_outra_regra_comercial(): void
    {
        $venda = $this->criarContratoLegado('NOVO');
        $operadoraVendaId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'OPERADORA CONFIGURADA',
            'status' => 'Y',
            'coparticipacao_formato' => 'PARCIAL_COMPLETA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $outraOperadoraId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'OUTRA OPERADORA',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $planoIncompativelId = DB::table('planos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'operadora_id' => $outraOperadoraId,
            'nome' => 'PLANO INCOMPATÍVEL',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $venda->update([
            'operadora_id' => $operadoraVendaId,
            'operadora' => 'OPERADORA CONFIGURADA',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.titulares.storePME'), [
                'venda_id' => $venda->id,
                'nome' => 'Beneficiário inválido',
                'plano_id' => $planoIncompativelId,
                'coparticipacao' => 'Y',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plano_id', 'coparticipacao']);

        $this->assertDatabaseMissing('vendas_titulares', [
            'venda_id' => $venda->id,
            'nome' => 'BENEFICIÁRIO INVÁLIDO',
        ]);
    }
}
