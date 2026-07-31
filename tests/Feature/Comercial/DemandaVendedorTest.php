<?php

namespace Tests\Feature\Comercial;

use App\Enums\NaturezaEtapaSolicitacao;
use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\TipoSolicitacaoPosVenda;
use App\Enums\UserRole;
use App\Models\Demanda;
use App\Models\Empresa;
use App\Models\PosVendaFluxoEtapa;
use App\Models\PosVendaSolicitacao;
use App\Models\User;
use App\Models\Vendas;
use App\Notifications\DemandaVendedorConcluida;
use App\Notifications\DemandaVendedorCriada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemandaVendedorTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $outroVendedor;

    private User $admin;

    private User $backoffice;

    /** Tabulação existente diferente de IMPLANTADO (para testar contratos não implantados). */
    private const TAB_NAO_IMPLANTADO = 1;

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

        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->outroVendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->backoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        DB::table('tabulacoes')->insert([
            [
                'id' => Tabulations::IMPLANTADO,
                'empresa_id' => $this->empresa->id,
                'descricao' => 'IMPLANTADO',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'Y',
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => self::TAB_NAO_IMPLANTADO,
                'empresa_id' => $this->empresa->id,
                'descricao' => 'EM ANALISE',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'N',
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function criarVenda(User $dono, int $tabulacaoId = Tabulations::IMPLANTADO, ?Empresa $empresa = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $dono->id,
            'nome_cliente' => 'Cliente '.uniqid(),
            'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id,
            'user_id' => $dono->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => $tabulacaoId,
            'nome_contrato' => 'Contrato '.uniqid(),
            'cpf_cnpj' => (string) random_int(10000000000000, 99999999999999),
            'numero_proposta' => (string) random_int(1000, 9999),
            'operadora' => 'AMIL',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
            'data_implantacao' => now(),
        ]);
    }

    private function payload(Vendas $venda, array $override = []): array
    {
        return array_merge([
            'venda_id' => $venda->id,
            'tipo' => TipoSolicitacaoPosVenda::ENVIO_BOLETO->value,
            'titulo' => 'Enviar boleto de junho',
            'descricao' => 'Cliente pediu o boleto atualizado.',
        ], $override);
    }

    public function test_vendedor_cria_demanda_e_administrativo_e_notificado(): void
    {
        Notification::fake();

        $venda = $this->criarVenda($this->vendedor);

        $response = $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda));

        $response->assertOk()->assertJson(['ok' => true]);

        // Grava direto na Central de Solicitações do pós-venda, na etapa inicial.
        $this->assertDatabaseHas('pos_venda_solicitacoes', [
            'empresa_id' => $this->empresa->id,
            'origem' => 'VENDEDOR',
            'venda_id' => $venda->id,
            'tipo' => TipoSolicitacaoPosVenda::ENVIO_BOLETO->value,
            'created_by' => $this->vendedor->id,
            'status' => 'ABERTA',
            'data_limite' => null,
        ]);

        $solicitacao = PosVendaSolicitacao::findOrFail($response->json('id'));
        $this->assertSame(NaturezaEtapaSolicitacao::EM_ANDAMENTO->value, $solicitacao->etapa->natureza);
        $this->assertDatabaseHas('pos_venda_solicitacao_historico', [
            'solicitacao_id' => $solicitacao->id,
            'campo_alterado' => 'abertura',
        ]);

        // Administrativo e back-office são avisados; o vendedor não.
        Notification::assertSentTo([$this->admin, $this->backoffice], DemandaVendedorCriada::class);
        Notification::assertNotSentTo($this->vendedor, DemandaVendedorCriada::class);
    }

    public function test_central_concluir_solicitacao_do_vendedor_notifica_o_vendedor(): void
    {
        Notification::fake();

        $venda = $this->criarVenda($this->vendedor);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda))
            ->assertOk();

        $solicitacao = PosVendaSolicitacao::where('venda_id', $venda->id)->firstOrFail();
        $etapaConcluida = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)
            ->where('tipo', $solicitacao->tipo)
            ->where('natureza', NaturezaEtapaSolicitacao::CONCLUIDA->value)
            ->firstOrFail();

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $etapaConcluida->id])
            ->assertOk();

        Notification::assertSentTo($this->vendedor, DemandaVendedorConcluida::class);
    }

    public function test_vendedor_acompanha_a_etapa_da_solicitacao(): void
    {
        $venda = $this->criarVenda($this->vendedor);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda))
            ->assertOk();

        $solicitacao = PosVendaSolicitacao::where('venda_id', $venda->id)->firstOrFail();
        $emAndamento = PosVendaFluxoEtapa::where('empresa_id', $this->empresa->id)
            ->where('tipo', $solicitacao->tipo)
            ->where('natureza', NaturezaEtapaSolicitacao::EM_ANDAMENTO->value)
            ->orderByDesc('ordem')
            ->firstOrFail();

        // A central avança a etapa; a lista do vendedor reflete o progresso.
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.mover', $solicitacao->id), ['etapa_id' => $emAndamento->id])
            ->assertOk();

        $data = $this->actingAs($this->vendedor)
            ->getJson(route('comercial.minhasDemandas.list'))
            ->assertOk()
            ->json('data');

        $this->assertSame('ABERTA', $data[0]['status']);
        $this->assertSame($emAndamento->nome, $data[0]['etapa']);
    }

    public function test_nao_cria_para_venda_nao_implantada(): void
    {
        $venda = $this->criarVenda($this->vendedor, self::TAB_NAO_IMPLANTADO);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda))
            ->assertStatus(422);

        $this->assertDatabaseMissing('pos_venda_solicitacoes', ['venda_id' => $venda->id]);
    }

    public function test_nao_cria_para_venda_de_outro_vendedor(): void
    {
        $venda = $this->criarVenda($this->outroVendedor);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda))
            ->assertStatus(422);

        $this->assertDatabaseMissing('pos_venda_solicitacoes', ['venda_id' => $venda->id]);
    }

    public function test_nao_cria_para_venda_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $vendedorOutra = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVenda($vendedorOutra, Tabulations::IMPLANTADO, $outraEmpresa);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda))
            ->assertStatus(422);

        $this->assertDatabaseMissing('pos_venda_solicitacoes', ['venda_id' => $venda->id]);
    }

    public function test_tipo_invalido_retorna_422(): void
    {
        $venda = $this->criarVenda($this->vendedor);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda, ['tipo' => 'INEXISTENTE']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tipo');
    }

    public function test_concluir_demanda_de_vendedor_notifica_o_vendedor(): void
    {
        Notification::fake();

        $venda = $this->criarVenda($this->vendedor);
        $demanda = Demanda::create([
            'empresa_id' => $this->empresa->id,
            'origem' => 'VENDEDOR',
            'venda_id' => $venda->id,
            'tipo' => TipoDemandaContrato::PORTABILIDADE->value,
            'created_by' => $this->vendedor->id,
            'titulo' => 'Tratar portabilidade',
            'prioridade' => 'MEDIA',
            'status' => 'ABERTA',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('demandas.status', $demanda->id), ['status' => 'CONCLUIDA'])
            ->assertOk();

        $this->assertDatabaseHas('demandas', ['id' => $demanda->id, 'status' => 'CONCLUIDA']);
        $this->assertNotNull($demanda->fresh()->concluida_em);

        Notification::assertSentTo($this->vendedor, DemandaVendedorConcluida::class);
    }

    public function test_concluir_demanda_interna_nao_notifica(): void
    {
        Notification::fake();

        $demanda = Demanda::create([
            'empresa_id' => $this->empresa->id,
            'origem' => 'INTERNA',
            'created_by' => $this->admin->id,
            'titulo' => 'Tarefa interna do setor',
            'prioridade' => 'MEDIA',
            'status' => 'ABERTA',
        ]);

        $this->actingAs($this->admin)
            ->patchJson(route('demandas.status', $demanda->id), ['status' => 'CONCLUIDA'])
            ->assertOk();

        Notification::assertNothingSent();
    }

    public function test_admin_lista_demandas_de_vendedor_com_campos_extras(): void
    {
        $venda = $this->criarVenda($this->vendedor);
        Demanda::create([
            'empresa_id' => $this->empresa->id,
            'origem' => 'VENDEDOR',
            'venda_id' => $venda->id,
            'tipo' => TipoDemandaContrato::CANCELAMENTO->value,
            'created_by' => $this->vendedor->id,
            'titulo' => 'Cancelar contrato',
            'prioridade' => 'MEDIA',
            'status' => 'ABERTA',
        ]);
        // Uma interna, que não deve aparecer no filtro VENDEDOR.
        Demanda::create([
            'empresa_id' => $this->empresa->id,
            'origem' => 'INTERNA',
            'created_by' => $this->admin->id,
            'titulo' => 'Interna',
            'prioridade' => 'MEDIA',
            'status' => 'ABERTA',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('demandas.list', ['origem' => 'VENDEDOR']));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('VENDEDOR', $data[0]['origem']);
        $this->assertSame('Cancelamento', $data[0]['tipo_label']);
        $this->assertSame($venda->nome_contrato, $data[0]['cliente']);
    }

    public function test_listar_minhas_demandas_retorna_apenas_do_vendedor(): void
    {
        $venda = $this->criarVenda($this->vendedor);
        $vendaOutro = $this->criarVenda($this->outroVendedor);

        $this->actingAs($this->vendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($venda, ['titulo' => 'Minha']))
            ->assertOk();
        $this->actingAs($this->outroVendedor)
            ->postJson(route('comercial.minhasDemandas.store'), $this->payload($vendaOutro, ['titulo' => 'Do outro']))
            ->assertOk();

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('comercial.minhasDemandas.list'));

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertSame('Minha', $data[0]['titulo']);
        $this->assertSame('Envio de Boleto', $data[0]['tipo_label']);
        $this->assertSame($venda->nome_contrato, $data[0]['cliente']);
    }

    public function test_vendedor_acompanha_solicitacao_aberta_pelo_backoffice_em_contrato_seu_com_atualizacoes(): void
    {
        $venda = $this->criarVenda($this->vendedor);

        // O backoffice abre o chamado direto na central — sem o vendedor pedir.
        $solicitacaoId = $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.store'), [
                'venda_id' => $venda->id,
                'tipo' => TipoSolicitacaoPosVenda::PORTABILIDADE->value,
            ])
            ->assertOk()->json('id');

        // E registra uma atualização de andamento.
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.solicitacoes.atualizacoes.store', $solicitacaoId), [
                'texto' => 'Acessei hoje e a portabilidade ainda não saiu.',
            ])
            ->assertOk();

        // O dono do contrato vê o chamado e as atualizações, mesmo sem tê-lo aberto.
        $data = $this->actingAs($this->vendedor)
            ->getJson(route('comercial.minhasDemandas.list'))
            ->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame($solicitacaoId, $data[0]['id']);
        $this->assertSame('BACKOFFICE', $data[0]['origem']);
        $this->assertCount(1, $data[0]['atualizacoes']);
        $this->assertSame('Acessei hoje e a portabilidade ainda não saiu.', $data[0]['atualizacoes'][0]['texto']);
        $this->assertSame($this->backoffice->name, $data[0]['atualizacoes'][0]['autor']);
        $this->assertNotEmpty($data[0]['atualizacoes'][0]['data']);

        // Outro vendedor não enxerga nada: o contrato não é dele.
        $this->assertCount(0, $this->actingAs($this->outroVendedor)
            ->getJson(route('comercial.minhasDemandas.list'))
            ->assertOk()->json('data'));
    }

    public function test_buscar_contratos_implantados_so_traz_os_do_vendedor_implantados(): void
    {
        $minhaImplantada = $this->criarVenda($this->vendedor);
        $minhaNaoImplantada = $this->criarVenda($this->vendedor, self::TAB_NAO_IMPLANTADO);
        $deOutro = $this->criarVenda($this->outroVendedor);

        $response = $this->actingAs($this->vendedor)
            ->getJson(route('comercial.minhasDemandas.contratos'));

        $response->assertOk();
        $ids = array_column($response->json('data'), 'venda_id');

        $this->assertContains($minhaImplantada->id, $ids);
        $this->assertNotContains($minhaNaoImplantada->id, $ids);
        $this->assertNotContains($deOutro->id, $ids);
    }
}
