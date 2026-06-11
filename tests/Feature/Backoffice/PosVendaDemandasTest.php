<?php

namespace Tests\Feature\Backoffice;

use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosVendaDemandasTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $backoffice;

    private User $outroBackoffice;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();

        $this->backoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $this->outroBackoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO,
            'empresa_id' => $this->empresa->id,
            'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A',
            'efetivo' => 'Y',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Cria um contrato IMPLANTADO com uma demanda PENDENTE, atribuído a um backoffice.
     */
    private function criarContratoImplantado(?int $backofficeId, ?Empresa $empresa = null, bool $comDemanda = true): Vendas
    {
        $empresa = $empresa ?? $this->empresa;

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $this->backoffice->id,
            'nome_cliente' => 'Cliente '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('contatos_corretores')->insert([
            'empresa_id' => $empresa->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => Tabulations::IMPLANTADO,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $venda = Vendas::create([
            'empresa_id' => $empresa->id,
            'user_id' => $this->backoffice->id,
            'backoffice_id' => $backofficeId,
            'contato_id' => $contatoId,
            'tabulacao_id' => Tabulations::IMPLANTADO,
            'nome_contrato' => 'Contrato '.uniqid(),
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999),
            'operadora' => 'AMIL',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
            'data_implantacao' => now(),
        ]);

        if ($comDemanda) {
            DB::table('venda_demandas')->insert([
                'venda_id' => $venda->id,
                'empresa_id' => $empresa->id,
                'created_by' => $this->backoffice->id,
                'tipo' => TipoDemandaContrato::ACESSO_EMPRESA->value,
                'titulo' => 'Criar acesso da empresa',
                'status' => 'PENDENTE',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $venda;
    }

    private function vendaIds(array $json): array
    {
        return array_column($json['contratos'], 'venda_id');
    }

    public function test_backoffice_ve_apenas_contratos_que_implantou(): void
    {
        $minha = $this->criarContratoImplantado($this->backoffice->id);
        $deOutro = $this->criarContratoImplantado($this->outroBackoffice->id);
        $semDono = $this->criarContratoImplantado(null);

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.posVendaDemandas.data'));

        $response->assertOk();
        $ids = $this->vendaIds($response->json());

        $this->assertContains($minha->id, $ids);
        $this->assertContains($semDono->id, $ids, 'Contrato sem responsável deve ser visível ao backoffice.');
        $this->assertNotContains($deOutro->id, $ids, 'Backoffice não deve ver contrato de outro responsável.');
    }

    public function test_admin_ve_todos_os_contratos(): void
    {
        $a = $this->criarContratoImplantado($this->backoffice->id);
        $b = $this->criarContratoImplantado($this->outroBackoffice->id);

        $response = $this->actingAs($this->admin)->getJson(route('backoffice.posVendaDemandas.data'));

        $response->assertOk();
        $ids = $this->vendaIds($response->json());
        $this->assertContains($a->id, $ids);
        $this->assertContains($b->id, $ids);
    }

    public function test_concluir_todas_finaliza_e_remove_da_lista(): void
    {
        $venda = $this->criarContratoImplantado($this->backoffice->id);

        $response = $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.posVendaDemandas.concluirTodas', $venda->id));

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('venda_demandas', [
            'venda_id' => $venda->id,
            'status' => 'CONCLUIDA',
            'concluida_por' => $this->backoffice->id,
        ]);
        $this->assertNotNull($venda->fresh()->pos_venda_concluida_em);

        // Sem pendentes -> some da lista ativa.
        $lista = $this->actingAs($this->backoffice)->getJson(route('backoffice.posVendaDemandas.data'));
        $this->assertNotContains($venda->id, $this->vendaIds($lista->json()));
    }

    public function test_backoffice_nao_conclui_contrato_de_outro_responsavel(): void
    {
        $venda = $this->criarContratoImplantado($this->outroBackoffice->id);

        $response = $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.posVendaDemandas.concluirTodas', $venda->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('venda_demandas', [
            'venda_id' => $venda->id,
            'status' => 'PENDENTE',
        ]);
    }

    public function test_multitenant_nao_ve_nem_conclui_contrato_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO + 1000, // id distinto, mesma semântica não importa aqui
            'empresa_id' => $outraEmpresa->id,
            'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A',
            'efetivo' => 'Y',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $boOutra = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $vendaEmpresaA = $this->criarContratoImplantado($this->backoffice->id);

        // Backoffice da empresa B não vê o contrato da empresa A.
        $lista = $this->actingAs($boOutra)->getJson(route('backoffice.posVendaDemandas.data'));
        $this->assertNotContains($vendaEmpresaA->id, $this->vendaIds($lista->json()));

        // Nem consegue concluir (escopo por empresa -> 404).
        $response = $this->actingAs($boOutra)
            ->postJson(route('backoffice.posVendaDemandas.concluirTodas', $vendaEmpresaA->id));
        $response->assertStatus(404);

        $this->assertDatabaseHas('venda_demandas', [
            'venda_id' => $vendaEmpresaA->id,
            'status' => 'PENDENTE',
        ]);
    }

    /**
     * Insere uma demanda CONCLUIDA no contrato, criada há X horas e concluída agora.
     */
    private function concluirDemanda(int $vendaId, int $empresaId, int $concluidaPor, int $horasAtras = 2): void
    {
        DB::table('venda_demandas')->insert([
            'venda_id' => $vendaId,
            'empresa_id' => $empresaId,
            'created_by' => $concluidaPor,
            'concluida_por' => $concluidaPor,
            'tipo' => TipoDemandaContrato::REEMBOLSO->value,
            'titulo' => 'Reembolso',
            'status' => 'CONCLUIDA',
            'created_at' => now()->subHours($horasAtras),
            'concluida_em' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_metricas_contam_concluidas_do_mes_e_pendentes(): void
    {
        $venda = $this->criarContratoImplantado($this->backoffice->id); // já cria 1 PENDENTE
        $this->concluirDemanda($venda->id, $this->empresa->id, $this->backoffice->id, 2);

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.posVendaDemandas.metricas'));

        $response->assertOk();
        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertSame(1, $json['metricas']['concluidas_mes']);
        $this->assertSame(1, $json['metricas']['pendentes']);
        $this->assertNotSame('—', $json['metricas']['tempo_medio'], 'Deve haver tempo médio para a demanda concluída.');
        $this->assertFalse($json['is_admin']);
    }

    public function test_metricas_backoffice_nao_conta_de_outro_responsavel(): void
    {
        $minha = $this->criarContratoImplantado($this->backoffice->id);
        $this->concluirDemanda($minha->id, $this->empresa->id, $this->backoffice->id);

        $deOutro = $this->criarContratoImplantado($this->outroBackoffice->id);
        $this->concluirDemanda($deOutro->id, $this->empresa->id, $this->outroBackoffice->id);

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.posVendaDemandas.metricas'));

        // Só conta a concluída do próprio backoffice.
        $this->assertSame(1, $response->json('metricas.concluidas_mes'));
    }

    public function test_metricas_admin_ve_todos_e_ranking_por_responsavel(): void
    {
        $a = $this->criarContratoImplantado($this->backoffice->id);
        $this->concluirDemanda($a->id, $this->empresa->id, $this->backoffice->id);
        $b = $this->criarContratoImplantado($this->outroBackoffice->id);
        $this->concluirDemanda($b->id, $this->empresa->id, $this->outroBackoffice->id);

        $response = $this->actingAs($this->admin)->getJson(route('backoffice.posVendaDemandas.metricas'));

        $response->assertOk();
        $this->assertTrue($response->json('is_admin'));
        $this->assertSame(2, $response->json('metricas.concluidas_mes'));
        $this->assertCount(2, $response->json('por_backoffice'));
    }

    public function test_metricas_multitenant_nao_conta_outra_empresa(): void
    {
        $venda = $this->criarContratoImplantado($this->backoffice->id);
        $this->concluirDemanda($venda->id, $this->empresa->id, $this->backoffice->id);

        $outraEmpresa = Empresa::factory()->create();
        $adminOutra = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $response = $this->actingAs($adminOutra)->getJson(route('backoffice.posVendaDemandas.metricas'));

        $this->assertSame(0, $response->json('metricas.concluidas_mes'));
    }
}
