<?php

namespace Tests\Feature;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use App\Notifications\StatusPropostaAlterada;
use App\Notifications\VendaEstornadaComComissaoPaga;
use App\Notifications\VendaReenviadaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

        // IDs fixos correspondem ao Enum Tabulations (ESTORNO=17, VENDA=16).
        DB::table('tabulacoes')->insert([
            [
                'id' => Tabulations::ESTORNO,
                'empresa_id' => $this->empresa->id,
                'descricao' => 'ESTORNO',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'N',
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Tabulations::VENDA,
                'empresa_id' => $this->empresa->id,
                'descricao' => 'VENDA',
                'tipo_tabulacao' => 'A',
                'efetivo' => 'Y',
                'status' => 'Y',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        $this->tabulacaoEstornoId = Tabulations::ESTORNO;
        $this->tabulacaoVendaId = Tabulations::VENDA;

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

        $this->assertDatabaseHas('contatos_corretores', [
            'id' => $this->contatoCorretorId,
            'tabulacao_id' => $this->tabulacaoEstornoId,
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
        $this->assertDatabaseMissing('contatos_corretores', [
            'id' => $this->contatoCorretorId,
            'tabulacao_id' => $this->tabulacaoEstornoId,
        ]);
    }

    public function test_estorno_de_outra_empresa_e_bloqueado(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $boOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);

        $venda = $this->criarVenda();

        $response = $this->actingAs($boOutraEmpresa)->post(route('backoffice.alterStatusContract'), [
            'idSale' => $venda->id,
            'tabulacao_id' => $this->tabulacaoEstornoId,
            'motivo_pendencia' => 'Tentativa indevida.',
        ]);

        // canEditContract retorna false → redirect com erro de permissão
        $response->assertRedirect(route('backoffice.index'));
        $response->assertSessionHas('status', 'error');

        $this->assertDatabaseMissing('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_nova_id' => $this->tabulacaoEstornoId,
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
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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

    public function test_vendedor_dono_acessa_tela_de_edicao_quando_em_estorno(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId, 'plano_id' => $this->planoId]);
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->get(route('sale.editEstorno', $venda->id));
        $response->assertStatus(200);
    }

    public function test_vendedor_nao_pode_editar_venda_de_outro_vendedor(): void
    {
        $venda = $this->criarVenda();
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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

        $this->assertDatabaseHas('contatos_corretores', [
            'id' => $this->contatoCorretorId,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);

        $this->assertDatabaseHas('vendas', [
            'id' => $venda->id,
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
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
            ->update(['tabulacao_id' => $this->tabulacaoEstornoId]);

        $response = $this->actingAs($this->vendedor)->post(
            route('sale.reenviarEstorno', $venda->id),
            $this->payloadReenvio(['observacao_reenvio' => '']),
        );

        $response->assertRedirect(route('sale.meusEstornos'));
        $response->assertSessionHas('status', 'success');

        $this->assertDatabaseHas('contatos_corretores', [
            'id' => $this->contatoCorretorId,
            'tabulacao_id' => $this->tabulacaoVendaId,
        ]);

        $this->assertDatabaseHas('vendas_historico', [
            'venda_id' => $venda->id,
            'tabulacao_anterior_id' => $this->tabulacaoEstornoId,
            'tabulacao_nova_id' => $this->tabulacaoVendaId,
            'observacao' => null,
        ]);
    }

    public function test_reenvio_substitui_titulares_e_dependentes(): void
    {
        $venda = $this->criarVenda(['operadora_id' => $this->operadoraId]);
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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
        DB::table('contatos_corretores')->where('id', $this->contatoCorretorId)
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
}
