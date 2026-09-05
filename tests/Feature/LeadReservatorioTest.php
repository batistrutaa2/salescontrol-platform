<?php

namespace Tests\Feature;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Empresa;
use App\Models\LeadReservatorioEstrategia;
use App\Models\LeadReservatorioItem;
use App\Models\PreditivaConfiguracao;
use App\Models\User;
use App\Services\LeadReservatorioService;
use App\Services\TabulationCatalog;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LeadReservatorioTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $vendedorA;

    private User $vendedorB;

    private int $prospeccaoId;

    private int $novosClientesId;

    private int $remarketingId;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->empresa = Empresa::factory()->create();
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedorA = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->vendedorB = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($this->empresa->id);
        $this->prospeccaoId = $catalog->id($this->empresa->id, TabulationCode::PROSPECCAO);
        $this->novosClientesId = $catalog->id($this->empresa->id, TabulationCode::NOVOS_CLIENTES);
        $this->remarketingId = $catalog->id($this->empresa->id, TabulationCode::REMARKETING);
    }

    public function test_distribui_quantidades_exatas_dentro_dos_filtros_e_nao_permita_reentrada(): void
    {
        $service = app(LeadReservatorioService::class);
        $elegiveis = collect(range(1, 5))->map(fn (int $indice) => $this->contato([
            'nome_cliente' => "Lead PME {$indice}",
            'nome_base' => 'Base PME',
            'vidas' => '4',
        ]));
        $foraDoFiltro = $this->contato(['nome_cliente' => 'Lead individual', 'nome_base' => 'Base PME', 'vidas' => '1']);

        $elegiveis->push($foraDoFiltro)->each(fn (Contatos $contato) => $service->adicionarNovo(
            $contato,
            LeadReservatorioItem::ORIGEM_IMPORTACAO,
            $this->admin->id,
        ));

        $estrategia = LeadReservatorioEstrategia::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'PME com quatro vidas',
            'condicoes' => [
                ['campo' => 'nome_base', 'operador' => 'igual', 'valor' => 'Base PME'],
                ['campo' => 'vidas', 'operador' => 'maior_ou_igual', 'valor' => 4],
            ],
            'ativo' => true,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/mailing/reservatorio/estrategias/{$estrategia->id}/executar", [
                'distribuicoes' => [
                    ['vendedor_id' => $this->vendedorA->id, 'quantidade' => 2],
                    ['vendedor_id' => $this->vendedorB->id, 'quantidade' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.total_executado', 3);

        $this->assertSame(2, ContatosCorretores::where('user_id', $this->vendedorA->id)->count());
        $this->assertSame(1, ContatosCorretores::where('user_id', $this->vendedorB->id)->count());
        $this->assertSame(3, ContatosCorretores::where('tabulacao_id', $this->novosClientesId)->count());
        $this->assertSame(0, ContatosCorretores::where('tabulacao_id', '!=', $this->novosClientesId)->count());
        $this->assertSame(3, LeadReservatorioItem::where('status', LeadReservatorioItem::STATUS_DISTRIBUIDO)->count());
        $this->assertSame(3, LeadReservatorioItem::where('status', LeadReservatorioItem::STATUS_DISPONIVEL)->count());
        $this->assertDatabaseHas('lead_reservatorio_itens', [
            'contato_id' => $foraDoFiltro->id,
            'status' => LeadReservatorioItem::STATUS_DISPONIVEL,
        ]);

        $distribuido = LeadReservatorioItem::where('status', LeadReservatorioItem::STATUS_DISTRIBUIDO)->firstOrFail();
        $mesmoRegistro = $service->adicionarNovo($distribuido->contato, LeadReservatorioItem::ORIGEM_IMPORTACAO, $this->admin->id);
        $this->assertSame($distribuido->id, $mesmoRegistro->id);
        $this->assertSame(LeadReservatorioItem::STATUS_DISTRIBUIDO, $mesmoRegistro->status);
    }

    public function test_bloqueia_acesso_de_vendedor_e_isola_estrategia_de_outra_empresa(): void
    {
        $this->actingAs($this->vendedorA)->getJson('/mailing/reservatorio/dados')->assertForbidden();

        $outraEmpresa = Empresa::factory()->create();
        $estrategia = app(TenantContext::class)->run($outraEmpresa->id, function () use ($outraEmpresa) {
            $outroAdmin = User::factory()->create([
                'empresa_id' => $outraEmpresa->id,
                'user_role_id' => UserRole::ADMINISTRATIVO,
                'ativo' => 'Y',
            ]);

            return LeadReservatorioEstrategia::create([
                'empresa_id' => $outraEmpresa->id,
                'nome' => 'Estratégia externa',
                'condicoes' => [['campo' => 'origem', 'operador' => 'igual', 'valor' => 'MARKETING']],
                'ativo' => true,
                'created_by' => $outroAdmin->id,
            ]);
        });

        $this->actingAs($this->admin)
            ->postJson("/mailing/reservatorio/estrategias/{$estrategia->id}/preview")
            ->assertNotFound();
    }

    public function test_master_sem_empresa_opera_somente_o_reservatorio_do_tenant_ativo(): void
    {
        $master = User::factory()->create([
            'empresa_id' => null,
            'user_role_id' => UserRole::DEVELOPER,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $contatoLocal = $this->contato(['nome_cliente' => 'Lead do tenant ativo']);
        app(LeadReservatorioService::class)->adicionarNovo(
            $contatoLocal,
            LeadReservatorioItem::ORIGEM_MARKETING,
            $master->id,
        );

        $outraEmpresa = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($outraEmpresa->id);
        app(TenantContext::class)->run($outraEmpresa->id, function () use ($outraEmpresa): void {
            $autorExterno = User::factory()->create([
                'empresa_id' => $outraEmpresa->id,
                'user_role_id' => UserRole::ADMINISTRATIVO,
                'ativo' => 'Y',
            ]);
            $contatoExterno = Contatos::create([
                'empresa_id' => $outraEmpresa->id,
                'user_import_id' => $autorExterno->id,
                'nome_cliente' => 'Lead confidencial externo',
                'status' => 'Y',
            ]);
            app(LeadReservatorioService::class)->adicionarNovo(
                $contatoExterno,
                LeadReservatorioItem::ORIGEM_MARKETING,
                $autorExterno->id,
            );
        });

        $this->actingAs($master)
            ->withSession([TenantContext::SESSION_KEY => $this->empresa->id])
            ->getJson(route('mailing.reservatorio.dados'))
            ->assertOk()
            ->assertJsonPath('metricas.disponiveis', 1)
            ->assertSee('Lead do tenant ativo')
            ->assertDontSee('Lead confidencial externo');

        $this->postJson(route('mailing.reservatorio.estrategias.store'), [
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Estratégia criada pelo master',
            'condicoes' => [[
                'campo' => 'origem',
                'operador' => 'igual',
                'valor' => LeadReservatorioItem::ORIGEM_MARKETING,
            ]],
        ])->assertCreated();

        $this->assertDatabaseHas('lead_reservatorio_estrategias', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'Estratégia criada pelo master',
            'created_by' => $master->id,
        ]);
        $this->assertDatabaseMissing('lead_reservatorio_estrategias', [
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Estratégia criada pelo master',
        ]);
    }

    public function test_distribuicao_rapida_sorteia_todos_os_elegiveis_sem_exigir_estrategia(): void
    {
        $service = app(LeadReservatorioService::class);
        collect(range(1, 6))->each(function (int $indice) use ($service) {
            $service->adicionarNovo(
                $this->contato(['nome_cliente' => "Lead aleatório {$indice}", 'nome_base' => $indice % 2 ? 'Base A' : 'Base B']),
                LeadReservatorioItem::ORIGEM_IMPORTACAO,
                $this->admin->id,
            );
        });

        $this->actingAs($this->admin)
            ->postJson('/mailing/reservatorio/distribuicao-aleatoria/preview')
            ->assertOk()
            ->assertJsonPath('total_elegivel', 6)
            ->assertJsonPath('condicoes', []);

        $this->actingAs($this->admin)
            ->postJson('/mailing/reservatorio/distribuicao-aleatoria', [
                'distribuicoes' => [
                    ['vendedor_id' => $this->vendedorA->id, 'quantidade' => 3],
                    ['vendedor_id' => $this->vendedorB->id, 'quantidade' => 1],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.tipo', 'DISTRIBUICAO_ALEATORIA')
            ->assertJsonPath('data.estrategia_id', null)
            ->assertJsonPath('data.total_executado', 4);

        $this->assertSame(3, ContatosCorretores::where('user_id', $this->vendedorA->id)->count());
        $this->assertSame(1, ContatosCorretores::where('user_id', $this->vendedorB->id)->count());
        $this->assertSame(4, ContatosCorretores::where('tabulacao_id', $this->novosClientesId)->count());
        $this->assertSame(0, ContatosCorretores::where('tabulacao_id', '!=', $this->novosClientesId)->count());
        $this->assertSame(4, LeadReservatorioItem::where('status', LeadReservatorioItem::STATUS_DISTRIBUIDO)->count());
        $this->assertSame(2, LeadReservatorioItem::where('status', LeadReservatorioItem::STATUS_DISPONIVEL)->count());
        $this->assertDatabaseHas('lead_reservatorio_execucoes', [
            'tipo' => 'DISTRIBUICAO_ALEATORIA',
            'estrategia_id' => null,
            'total_solicitado' => 4,
            'total_executado' => 4,
        ]);
    }

    public function test_item_disponivel_que_recebe_vinculo_externo_e_bloqueado_antes_da_listagem(): void
    {
        $contato = $this->contato(['nome_cliente' => 'Lead atribuído fora do reservatório']);
        app(LeadReservatorioService::class)->adicionarNovo(
            $contato,
            LeadReservatorioItem::ORIGEM_MARKETING,
            null,
        );
        $this->vincular($contato, $this->prospeccaoId);

        $this->actingAs($this->admin)
            ->getJson('/mailing/reservatorio/dados')
            ->assertOk()
            ->assertJsonPath('metricas.disponiveis', 0)
            ->assertJsonPath('metricas.bloqueados', 1);

        $this->assertDatabaseHas('lead_reservatorio_itens', [
            'contato_id' => $contato->id,
            'status' => LeadReservatorioItem::STATUS_BLOQUEADO,
            'bloqueado_motivo' => 'JA_ATRIBUIDO',
        ]);
    }

    public function test_historico_nao_resolve_usuarios_de_outra_empresa_em_relacoes_corrompidas(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $usuarioExterno = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'name' => 'Autor externo confidencial',
            'ativo' => 'Y',
        ]);

        DB::table('lead_reservatorio_execucoes')->insert([
            'empresa_id' => $this->empresa->id,
            'estrategia_id' => null,
            'tipo' => 'DISTRIBUICAO_ALEATORIA',
            'status' => 'CONCLUIDA',
            'total_solicitado' => 0,
            'total_executado' => 0,
            'total_ignorado' => 0,
            'vendedor_origem_id' => $usuarioExterno->id,
            'created_by' => $usuarioExterno->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('mailing.reservatorio.historico'))
            ->assertOk()
            ->assertJsonPath('data.data.0.autor_nome', null)
            ->assertJsonPath('data.data.0.vendedor_origem_nome', null)
            ->assertDontSee('Autor externo confidencial');
    }

    public function test_carga_inicial_move_somente_aptos_preserva_historico_e_e_idempotente(): void
    {
        $apto = $this->contato(['nome_cliente' => 'Lead novo concentrado']);
        $remarketing = $this->contato(['nome_cliente' => 'Lead de remarketing']);
        $descartado = $this->contato(['nome_cliente' => 'Lead descartado', 'status' => 'N']);
        $this->vincular($apto, $this->prospeccaoId);
        $this->vincular($remarketing, $this->remarketingId);
        $this->vincular($descartado, $this->prospeccaoId);
        DB::table('comentarios')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedorA->id,
            'contato_id' => $apto->id,
            'anotacao' => 'Histórico que deve permanecer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->postJson('/mailing/reservatorio/migracao-inicial', ['vendedor_id' => $this->vendedorA->id])
            ->assertOk()
            ->assertJsonPath('data.total_executado', 1);

        $this->assertDatabaseMissing('contatos_corretores', ['contato_id' => $apto->id]);
        $this->assertDatabaseHas('contatos_corretores', ['contato_id' => $remarketing->id]);
        $this->assertDatabaseHas('contatos_corretores', ['contato_id' => $descartado->id]);
        $this->assertDatabaseHas('comentarios', ['contato_id' => $apto->id, 'anotacao' => 'Histórico que deve permanecer']);
        $this->assertDatabaseHas('lead_reservatorio_itens', [
            'contato_id' => $apto->id,
            'origem' => LeadReservatorioItem::ORIGEM_MIGRACAO,
            'status' => LeadReservatorioItem::STATUS_DISPONIVEL,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/mailing/reservatorio/migracao-inicial', ['vendedor_id' => $this->vendedorA->id])
            ->assertUnprocessable();
    }

    public function test_indicador_de_entradas_usa_janela_da_empresa_ativa(): void
    {
        PreditivaConfiguracao::query()->create([
            'empresa_id' => $this->empresa->id,
            'indicadores_janela_dias' => 10,
        ]);
        $recente = $this->contato(['nome_cliente' => 'Entrada recente']);
        $antigo = $this->contato(['nome_cliente' => 'Entrada antiga']);

        foreach ([[$recente, now()], [$antigo, now()->subDays(11)]] as [$contato, $entrouEm]) {
            DB::table('lead_reservatorio_itens')->insert([
                'empresa_id' => $this->empresa->id,
                'contato_id' => $contato->id,
                'origem' => LeadReservatorioItem::ORIGEM_MARKETING,
                'status' => LeadReservatorioItem::STATUS_DISPONIVEL,
                'entrou_em' => $entrouEm,
                'created_at' => $entrouEm,
                'updated_at' => $entrouEm,
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('mailing.reservatorio.index'))
            ->assertOk()
            ->assertSee('Entradas em 10 dias');

        $this->getJson(route('mailing.reservatorio.dados'))
            ->assertOk()
            ->assertJsonPath('metricas.entradas_na_janela', 1)
            ->assertJsonPath('metricas.indicadores_janela_dias', 10);
    }

    private function contato(array $attributes = []): Contatos
    {
        return Contatos::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Lead',
            'status' => 'Y',
        ], $attributes));
    }

    private function vincular(Contatos $contato, int $tabulacaoId): void
    {
        ContatosCorretores::create([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $contato->id,
            'user_id' => $this->vendedorA->id,
            'tabulacao_id' => $tabulacaoId,
            'temperatura' => 'FRIO',
        ]);
    }
}
