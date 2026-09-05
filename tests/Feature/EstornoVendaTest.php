<?php

namespace Tests\Feature;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use App\Notifications\StatusPropostaAlterada;
use App\Notifications\VendaEstornadaComComissaoPaga;
use App\Notifications\VendaReenviadaNotification;
use App\Notifications\VendaRetomadaPeloBackoffice;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Eloquent\VendasRepository;
use App\Services\TabulationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EstornoVendaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $backoffice;

    private User $admin;

    private int $contatoId;

    private int $contatoCorretorId;

    private int $tabulacaoEstornoId;

    private int $tabulacaoVendaId;

    private int $tabulacaoImplantadoId;

    private int $operadoraId;

    private int $planoId;

    private int $planoIdAlternativo;

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

        $catalog = app(TabulationCatalog::class);
        $catalog->provision($this->empresa->id);
        $this->tabulacaoEstornoId = $catalog->id($this->empresa->id, TabulationCode::ESTORNO);
        $this->tabulacaoVendaId = $catalog->id($this->empresa->id, TabulationCode::VENDA);
        $this->tabulacaoImplantadoId = $catalog->id($this->empresa->id, TabulationCode::IMPLANTADO);

        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $this->backoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $this->contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'cpf' => '12345678900',
            'nome_cliente' => 'Cliente Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->contatoCorretorId = DB::table('contatos_corretores')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $this->contatoId,
            'user_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->operadoraId = DB::table('operadoras')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'nome' => 'AMIL',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->planoId = DB::table('planos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'operadora_id' => $this->operadoraId,
            'nome' => 'Plano X',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->planoIdAlternativo = DB::table('planos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'operadora_id' => $this->operadoraId,
            'nome' => 'Plano Premium',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function payloadReenvio(array $overrides = []): array
    {
        return array_merge([
            'observacao_reenvio' => 'Correções aplicadas conforme pedido do backoffice.',
            'nome_contrato' => 'Cliente Teste Atualizado',
            'cpf_cnpj' => '12345678900',
            'operadora_id' => $this->operadoraId,
            'tipo_contrato' => 'PME',
            'tipo_empresa' => 'LTDA',
            'valor_contrato' => '1.500,00',
            'taxa_angariacao' => '0',
            'angariacao_status' => 'NAO',
            'plano_dental' => 'SIM',
            'portabilidade_status' => 'NAO',
            'qtd_portabilidade' => 0,
            'vidas' => 1,
            'titulares' => [
                [
                    'nome' => 'Titular Corrigido',
                    'cpf' => '12345678900',
                    'plano_id' => $this->planoId,
                    'coparticipacao' => 'N',
                    'plano_anterior' => 'NAO',
                    'cargo' => 'SOCIO',
                    'dependentes' => [],
                ],
            ],
            'portabilidades' => [],
        ], $overrides);
    }

    private function criarVenda(array $overrides = []): Vendas
    {
        return Vendas::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'backoffice_id' => $this->backoffice->id,
            'contato_id' => $this->contatoId,
            'tabulacao_id' => $this->tabulacaoVendaId,
            'cpf_cnpj' => '12345678900',
            'nome_contrato' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'telefone1' => '11999999999',
            'operadora' => 'AMIL',
            'nome_plano' => 'Plano X',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
            'comissao_paga' => false,
        ], $overrides));
    }

    public function test_backoffice_dono_estorna_venda_e_grava_motivo_no_historico(): void
    {
        Notification::fake();
        $venda = $this->criarVenda();

        $response = $this->actingAs($this->backoffice)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
            'motivo_pendencia' => 'Plano errado, cliente queria coparticipação.',
        ]);

        $response->assertRedirect(route('backoffice.index'));
        $response->assertSessionHas('status', 'success');

        // Status é da VENDA — o registro do contato (lead) não é tocado.
        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);
        $this->assertDatabaseHas('contatos_corretores', [
            'id' => $this->contatoCorretorId,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);

        $this->assertDatabaseHas('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_nova_id' => $this->tabulacaoEstornoId,
            'motivo_pendencia' => 'Plano errado, cliente queria coparticipação.',
        ]);

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'motivo_pendencia' => 'Plano errado, cliente queria coparticipação.',
        ]);

        Notification::assertSentTo($this->vendedor, StatusPropostaAlterada::class);
    }

    public function test_estorno_sem_motivo_retorna_erro_de_validacao(): void
    {
        $venda = $this->criarVenda();

        $response = $this->actingAs($this->backoffice)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);

        $response->assertSessionHasErrors('motivo_pendencia');
        $this->assertDatabaseMissing('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);
    }

    public function test_estorno_de_outra_empresa_e_bloqueado(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($outraEmpresa->id);
        $boOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $venda = $this->criarVenda();

        $response = $this->actingAs($boOutraEmpresa)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $catalog->id($outraEmpresa->id, TabulationCode::ESTORNO),
            'motivo_pendencia' => 'Tentativa indevida.',
        ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_nova_id' => $this->tabulacaoEstornoId,
        ]);
    }

    public function test_admin_nao_edita_nem_exclui_contrato_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $adminOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVenda();

        $this->actingAs($adminOutraEmpresa)
            ->post(route('backoffice.updateSale'), ['id' => $venda->id])
            ->assertNotFound();

        $this->actingAs($adminOutraEmpresa)
            ->delete(route('backoffice.deleteContract', $venda->id))
            ->assertNotFound();

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_status_de_outra_empresa_nao_pode_ser_aplicado(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($outraEmpresa->id);
        $statusEstrangeiro = $catalog->id($outraEmpresa->id, TabulationCode::ESTORNO);
        $venda = $this->criarVenda();

        $this->actingAs($this->admin)
            ->post(route('backoffice.alterStatusContract'), [
                'idSale' => $venda->id,
                'tabulacao_id' => $statusEstrangeiro,
                'motivo_pendencia' => 'Tentativa de cruzar empresas.',
            ])
            ->assertSessionHasErrors('tabulacao_id');

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);
    }

    public function test_supervisor_nao_pode_estornar(): void
    {
        $supervisor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVenda();

        $response = $this->actingAs($supervisor)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
            'motivo_pendencia' => 'Tentativa de estorno por supervisor.',
        ]);

        $response->assertRedirect(route('backoffice.index'));
        $response->assertSessionHas('status', 'error');
    }

    public function test_quick_status_change_para_estorno_exige_modal(): void
    {
        $venda = $this->criarVenda();

        $response = $this->actingAs($this->backoffice)->post(route('backoffice.quickStatusChange'), [
            'venda_id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
            'requires_modal' => true,
        ]);
    }

    public function test_alerta_financeiro_quando_venda_com_comissao_paga(): void
    {
        Notification::fake();
        $venda = $this->criarVenda(['comissao_paga' => true]);

        $this->actingAs($this->backoffice)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
            'motivo_pendencia' => 'Estorno em venda com comissão paga.',
        ]);

        // Comissão NÃO é estornada automaticamente.
        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'comissao_paga' => 1,
            'comissao_estornada' => 0,
        ]);

        Notification::assertSentTo($this->admin, VendaEstornadaComComissaoPaga::class);
    }

    public function test_vendedor_acessa_meus_estornos_e_ve_apenas_suas_vendas(): void
    {
        $venda = $this->criarVenda();

        // Move para ESTORNO direto na pivot
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->get(route('sale.meusEstornosDados'));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['success']);
        $this->assertCount(1, $json['data']);
        $this->assertSame($venda->id, $json['data'][0]['id']);

        // Outro vendedor não vê
        $outroVendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $response2 = $this->actingAs($outroVendedor)->get(route('sale.meusEstornosDados'));
        $this->assertCount(0, $response2->json('data'));
    }

    public function test_vendedor_nao_acessa_fila_gerencial_do_backoffice_por_url_direta(): void
    {
        $this->actingAs($this->vendedor);

        foreach ([
            route('backoffice.index'),
            route('backoffice.listContracts'),
            route('backoffice.operadorasPlanos'),
            route('backoffice.getFaqs'),
        ] as $rota) {
            $this->getJson($rota)->assertForbidden();
        }

        $this->deleteJson(route('backoffice.deleteContract', 1))->assertForbidden();
    }

    public function test_vendedor_dono_acessa_tela_de_edicao_quando_em_estorno(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId, 'plano_id' => $this->planoId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->get(route('sale.editEstorno', $venda->id));
        $response->assertStatus(200);
        $response->assertSee('id="dep-modal-overlay"', false);
        $response->assertSee('id="btn-dep-modal-save"', false);
    }

    public function test_vendedor_nao_pode_editar_venda_de_outro_vendedor(): void
    {
        $venda = $this->criarVenda();
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $outroVendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $response = $this->actingAs($outroVendedor)->get(route('sale.editEstorno', $venda->id));
        $response->assertStatus(403);
    }

    public function test_vendedor_nao_edita_venda_fora_de_estorno(): void
    {
        $venda = $this->criarVenda();
        // Pivot continua em VENDA(tabulacaoVendaId), não ESTORNO

        $response = $this->actingAs($this->vendedor)->get(route('sale.editEstorno', $venda->id));
        $response->assertStatus(403);
    }

    public function test_reenviar_estorno_volta_para_venda_e_notifica_backoffice(): void
    {
        Notification::fake();
        $venda = $this->criarVenda(['motivo_pendencia' => 'Plano errado.', 'operadora_id' => $this->operadoraId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $payload = $this->payloadReenvio([
            'observacao_reenvio' => 'Plano corrigido para o Premium conforme combinado.',
            'titulares' => [[
                'nome' => 'Titular Corrigido',
                'cpf' => '12345678900',
                'plano_id' => $this->planoIdAlternativo,
                'coparticipacao' => 'Y',
                'plano_anterior' => 'NAO',
                'cargo' => 'SOCIO',
                'dependentes' => [],
            ]],
        ]);

        $response = $this->actingAs($this->vendedor)->post(route('sale.reenviarEstorno', $venda->id), $payload);

        $response->assertRedirect(route('sale.meusEstornos'));
        $response->assertSessionHas('status', 'success');

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
            'motivo_pendencia' => null,
            'plano_id' => $this->planoIdAlternativo,
            'backoffice_id' => $this->backoffice->id, // mantido
        ]);

        $this->assertDatabaseHas('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_anterior_id' => $this->tabulacaoEstornoId,
            'tabulacao_nova_id' => $this->tabulacaoVendaId,
            'observacao' => 'Plano corrigido para o Premium conforme combinado.',
        ]);

        $this->assertDatabaseHas('vendas_titulares', [
            'venda_id' => $venda->id,
            'plano_id' => $this->planoIdAlternativo,
        ]);

        Notification::assertSentTo($this->backoffice, VendaReenviadaNotification::class);
    }

    public function test_reenvio_sem_observacao_e_aceito(): void
    {
        // O vendedor pode não preencher observação — alinhamento já feito por outro canal.
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->post(
            route('sale.reenviarEstorno', $venda->id),
            $this->payloadReenvio(['observacao_reenvio' => '']),
        );

        $response->assertRedirect(route('sale.meusEstornos'));
        $response->assertSessionHas('status', 'success');

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);

        $this->assertDatabaseHas('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_anterior_id' => $this->tabulacaoEstornoId,
            'tabulacao_nova_id' => $this->tabulacaoVendaId,
            'observacao' => null,
        ]);
    }

    public function test_falha_no_reenvio_nao_expoe_excecao_interna(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);
        $repository = Mockery::mock(VendasRepository::class);
        $repository->shouldReceive('updateContractFull')
            ->once()
            ->andThrow(new RuntimeException('SQLSTATE reenvio_tenant_secreto'));
        $this->app->instance(VendasRepositoryInterface::class, $repository);

        $response = $this->actingAs($this->vendedor)->post(
            route('sale.reenviarEstorno', $venda->id),
            $this->payloadReenvio(),
        );

        $response->assertRedirect()
            ->assertSessionHas('status', 'error')
            ->assertSessionHas('message', 'Não foi possível reenviar a venda neste momento.')
            ->assertDontSee('SQLSTATE')
            ->assertDontSee('reenvio_tenant_secreto');
    }

    public function test_reenvio_substitui_titulares_e_dependentes(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        // Cria titular antigo que deve ser substituído
        $titularAntigoId = DB::table('vendas_titulares')->insertGetId([
            'venda_id' => $venda->id,
            'nome' => 'TITULAR ANTIGO',
            'cargo' => 'SOCIO',
            'plano_id' => $this->planoId,
            'coparticipacao' => 'N',
            'plano_anterior' => 'NAO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendas_dependentes')->insert([
            'venda_id' => $venda->id,
            'titular_id' => $titularAntigoId,
            'nome' => 'DEPENDENTE ANTIGO',
            'parentesco' => 'FILHO',
            'plano_anterior' => 'NAO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = $this->payloadReenvio([
            'titulares' => [[
                'nome' => 'Titular Novo',
                'cpf' => '11122233344',
                'plano_id' => $this->planoIdAlternativo,
                'coparticipacao' => 'Y',
                'plano_anterior' => 'NAO',
                'cargo' => 'DIRETOR',
                'dependentes' => [
                    [
                        'nome' => 'Dependente Novo',
                        'cpf' => '99988877766',
                        'parentesco' => 'CONJUGE',
                        'plano_anterior' => 'NAO',
                    ],
                ],
            ]],
            'vidas' => 2,
        ]);

        $this->actingAs($this->vendedor)->post(route('sale.reenviarEstorno', $venda->id), $payload)
            ->assertRedirect(route('sale.meusEstornos'));

        // Antigo apagado
        $this->assertDatabaseMissing('vendas_titulares', ['nome' => 'TITULAR ANTIGO']);
        $this->assertDatabaseMissing('vendas_dependentes', ['nome' => 'DEPENDENTE ANTIGO']);

        // Novo gravado
        $this->assertDatabaseHas('vendas_titulares', [
            'venda_id' => $venda->id,
            'nome' => 'TITULAR NOVO',
            'cargo' => 'DIRETOR',
            'plano_id' => $this->planoIdAlternativo,
        ]);
        $this->assertDatabaseHas('vendas_dependentes', [
            'venda_id' => $venda->id,
            'nome' => 'DEPENDENTE NOVO',
            'parentesco' => 'CONJUGE',
        ]);
    }

    public function test_segundo_reenvio_consecutivo_e_bloqueado_pelo_gate(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId]);
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $primeiro = $this->actingAs($this->vendedor)->post(
            route('sale.reenviarEstorno', $venda->id),
            $this->payloadReenvio(['observacao_reenvio' => 'Primeira correção concluída com sucesso.']),
        );
        $primeiro->assertRedirect(route('sale.meusEstornos'));

        $segundo = $this->actingAs($this->vendedor)->post(
            route('sale.reenviarEstorno', $venda->id),
            $this->payloadReenvio(['observacao_reenvio' => 'Tentando reenviar de novo, mas não está em estorno.']),
        );
        $segundo->assertStatus(403);
    }

    public function test_open_contract_redireciona_vendedor_para_edicao_quando_em_estorno(): void
    {
        $venda = $this->criarVenda();
        DB::table('vendas')->where('id', $venda->id)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->get(route('backoffice.openContract', $venda->id));
        $response->assertRedirect(route('sale.editEstorno', $venda->id));
    }

    public function test_open_contract_bloqueia_vendedor_quando_venda_nao_esta_em_estorno(): void
    {
        $venda = $this->criarVenda();
        // pivot continua em VENDA

        $response = $this->actingAs($this->vendedor)->get(route('backoffice.openContract', $venda->id));
        $response->assertStatus(403);
    }

    // =====================================================================
    // RETOMADA — backoffice puxa a venda estornada de volta para a fila
    // =====================================================================

    private function criarVendaEstornada(array $overrides = []): Vendas
    {
        $venda = $this->criarVenda(array_merge(['motivo_pendencia' => 'Faltou documento.'], $overrides));
        DB::table('vendas')->where('id', $venda->id)->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        return $venda->refresh();
    }

    public function test_backoffice_dono_retoma_estorno_e_venda_volta_para_a_fila(): void
    {
        Notification::fake();
        $venda = $this->criarVendaEstornada();

        $response = $this->actingAs($this->backoffice)->postJson(route('backoffice.retomarEstorno'), [
            'venda_id' => $venda->id,
            'observacao' => 'Cliente enviou o documento direto para o backoffice.',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
            'motivo_pendencia' => null,
            'backoffice_id' => $this->backoffice->id,
        ]);

        $this->assertDatabaseHas('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_anterior_id' => $this->tabulacaoEstornoId,
            'tabulacao_nova_id' => $this->tabulacaoVendaId,
            'observacao' => 'Cliente enviou o documento direto para o backoffice.',
        ]);

        // O vendedor precisa saber que não deve mais reenviar.
        Notification::assertSentTo($this->vendedor, VendaRetomadaPeloBackoffice::class);
    }

    public function test_retomada_assume_custodia_quando_contrato_esta_sem_responsavel(): void
    {
        $venda = $this->criarVendaEstornada(['backoffice_id' => null]);

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertOk();

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'backoffice_id' => $this->backoffice->id,
        ]);
    }

    public function test_backoffice_nao_retoma_contrato_de_outro_responsavel(): void
    {
        $outroBackoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVendaEstornada();

        $this->actingAs($outroBackoffice)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertStatus(403);

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);
    }

    public function test_admin_retoma_estorno_de_qualquer_responsavel(): void
    {
        $venda = $this->criarVendaEstornada();

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertOk();

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);
    }

    public function test_supervisor_nao_pode_retomar_estorno(): void
    {
        $supervisor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVendaEstornada();

        $this->actingAs($supervisor)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertStatus(403);
    }

    public function test_retomada_de_venda_fora_de_estorno_e_rejeitada(): void
    {
        $venda = $this->criarVenda(); // permanece em VENDA

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertStatus(409);
    }

    public function test_retomada_de_outra_empresa_e_bloqueada(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $boOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $venda = $this->criarVendaEstornada();

        $this->actingAs($boOutraEmpresa)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertStatus(404);
    }

    public function test_retomada_para_status_que_exige_dados_extras_e_rejeitada(): void
    {
        $venda = $this->criarVendaEstornada();

        // IMPLANTADO exige comprovante/data — não pode entrar pela retomada.
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.retomarEstorno'), [
                'venda_id' => $venda->id,
                'tabulacao_id' => $this->tabulacaoImplantadoId,
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);
    }

    public function test_lista_de_estornos_ordena_do_estorno_mais_recente_para_o_mais_antigo(): void
    {
        $antiga = $this->criarVendaEstornada(['nome_contrato' => 'Cliente Antigo']);
        $recente = $this->criarVendaEstornada(['nome_contrato' => 'Cliente Recente']);

        DB::table('vendas')->where('id', $antiga->id)->update(['tabulacao_updated_at' => now()->subDays(40)]);
        DB::table('vendas')->where('id', $recente->id)->update(['tabulacao_updated_at' => now()->subDays(2)]);

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.listaEstornos'));

        $response->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('estornos.0.id', $recente->id)
            ->assertJsonPath('estornos.0.dias_parado', 2)
            ->assertJsonPath('estornos.1.id', $antiga->id)
            ->assertJsonPath('estornos.1.dias_parado', 40);
    }

    public function test_lista_de_estornos_marca_o_que_o_backoffice_pode_retomar(): void
    {
        $outroBackoffice = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $doOutro = $this->criarVendaEstornada(['backoffice_id' => $outroBackoffice->id]);
        $livre = $this->criarVendaEstornada(['backoffice_id' => null]);

        $estornos = collect(
            $this->actingAs($this->backoffice)
                ->getJson(route('backoffice.listaEstornos'))
                ->assertOk()
                ->json('estornos')
        )->keyBy('id');

        $this->assertFalse($estornos[$doOutro->id]['pode_retomar']);
        $this->assertTrue($estornos[$livre->id]['pode_retomar']);

        // Admin retoma qualquer um.
        $estornosAdmin = collect(
            $this->actingAs($this->admin)->getJson(route('backoffice.listaEstornos'))->json('estornos')
        )->keyBy('id');
        $this->assertTrue($estornosAdmin[$doOutro->id]['pode_retomar']);
    }

    public function test_lista_de_estornos_nao_vaza_entre_empresas(): void
    {
        $this->criarVendaEstornada();

        $outraEmpresa = Empresa::factory()->create();
        app(TabulationCatalog::class)->provision($outraEmpresa->id);
        $boOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $this->actingAs($boOutraEmpresa)
            ->getJson(route('backoffice.listaEstornos'))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_lista_de_estornos_ignora_vendas_fora_de_estorno(): void
    {
        $this->criarVenda(); // segue em VENDA
        $estornada = $this->criarVendaEstornada();

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.listaEstornos'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('estornos.0.id', $estornada->id);
    }

    public function test_venda_retomada_sai_da_lista_de_estornos(): void
    {
        $venda = $this->criarVendaEstornada();

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertOk();

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.listaEstornos'))
            ->assertOk()
            ->assertJsonPath('total', 0);
    }

    public function test_venda_retomada_sai_da_lista_de_meus_estornos_do_vendedor(): void
    {
        $venda = $this->criarVendaEstornada();

        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.retomarEstorno'), ['venda_id' => $venda->id])
            ->assertOk();

        $response = $this->actingAs($this->vendedor)->getJson(route('sale.meusEstornosDados'));
        $response->assertOk();
        $this->assertSame(0, $response->json('total'));
    }
}
