<?php

namespace Tests\Feature;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Empresa;
use App\Models\LeadReservatorioEstrategia;
use App\Models\LeadReservatorioItem;
use App\Models\Tabulacoes;
use App\Models\User;
use App\Services\LeadReservatorioService;
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

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
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
        Tabulacoes::create([
            'id' => Tabulations::PROSPECCAO,
            'empresa_id' => $this->empresa->id,
            'descricao' => 'PROSPECÇÃO',
            'tipo_tabulacao' => 'C',
            'status' => 'Y',
        ]);
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
        $outroAdmin = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $estrategia = LeadReservatorioEstrategia::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'Estratégia externa',
            'condicoes' => [['campo' => 'origem', 'operador' => 'igual', 'valor' => 'MARKETING']],
            'ativo' => true,
            'created_by' => $outroAdmin->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/mailing/reservatorio/estrategias/{$estrategia->id}/preview")
            ->assertNotFound();
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
        $this->vincular($contato, Tabulations::PROSPECCAO);

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

    public function test_carga_inicial_move_somente_aptos_preserva_historico_e_e_idempotente(): void
    {
        Tabulacoes::create([
            'id' => Tabulations::REMARKETING,
            'empresa_id' => $this->empresa->id,
            'descricao' => 'REMARKETING',
            'tipo_tabulacao' => 'C',
            'status' => 'Y',
        ]);
        $apto = $this->contato(['nome_cliente' => 'Lead novo concentrado']);
        $remarketing = $this->contato(['nome_cliente' => 'Lead de remarketing']);
        $descartado = $this->contato(['nome_cliente' => 'Lead descartado', 'status' => 'N']);
        $this->vincular($apto, Tabulations::PROSPECCAO);
        $this->vincular($remarketing, Tabulations::REMARKETING);
        $this->vincular($descartado, Tabulations::PROSPECCAO);
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
