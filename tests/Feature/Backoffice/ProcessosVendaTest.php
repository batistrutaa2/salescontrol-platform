<?php

namespace Tests\Feature\Backoffice;

use App\Enums\FaseCancelamento;
use App\Enums\ModalidadeCancelamento;
use App\Enums\Tabulations;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Models\CredencialAcesso;
use App\Models\Empresa;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\Vendas;
use App\Repositories\Contracts\VendasRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tela de acompanhamento do contrato: aba Cancelamento (por titular, modalidade +
 * fase) e geração do processo a partir do sinal do vendedor no cadastro.
 */
class ProcessosVendaTest extends TestCase
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
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->backoffice = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::BACKOFFICE, 'ativo' => 'Y']);
        $this->outroBackoffice = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::BACKOFFICE, 'ativo' => 'Y']);
        $this->admin = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);

        DB::table('tabulacoes')->insert([
            'id' => Tabulations::IMPLANTADO, 'empresa_id' => $this->empresa->id, 'descricao' => 'IMPLANTADO',
            'tipo_tabulacao' => 'A', 'efetivo' => 'Y', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function criarContrato(?int $backofficeId, ?Empresa $empresa = null, ?string $cnpj = null): Vendas
    {
        $empresa = $empresa ?? $this->empresa;

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id, 'user_import_id' => $this->backoffice->id,
            'nome_cliente' => 'Cliente '.uniqid(), 'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $empresa->id, 'user_id' => $this->backoffice->id, 'backoffice_id' => $backofficeId,
            'contato_id' => $contatoId, 'tabulacao_id' => Tabulations::IMPLANTADO, 'nome_contrato' => 'Contrato '.uniqid(),
            'cpf_cnpj' => $cnpj ?? (string) random_int(10000000000000, 99999999999999), 'operadora' => 'AMIL',
            'valor_contrato' => 500.00, 'vidas' => 1, 'data_vigencia' => now(), 'data_implantacao' => now(),
        ]);
    }

    private function criarTitular(Vendas $venda, string $nome = 'Titular'): int
    {
        return DB::table('vendas_titulares')->insertGetId([
            'venda_id' => $venda->id, 'nome' => $nome, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function criarCancelamento(Vendas $venda, int $titularId, string $fase = 'SOLICITADO', string $status = 'PENDENTE'): VendaDemanda
    {
        return VendaDemanda::create([
            'venda_id' => $venda->id, 'titular_id' => $titularId, 'empresa_id' => $venda->empresa_id,
            'created_by' => $this->backoffice->id, 'origem' => 'VENDEDOR',
            'tipo' => TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
            'titulo' => 'Cancelamento', 'meta' => ['fase' => $fase, 'modalidade' => null], 'status' => $status,
        ]);
    }

    public function test_dados_lista_cancelamentos_titulares_e_enums(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $titularId = $this->criarTitular($venda);
        $this->criarCancelamento($venda, $titularId, 'SOLICITADO');

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.processos.dados', $venda->id));

        $response->assertOk()->assertJson([
            'success' => true,
            'can_edit' => true,
            'venda' => ['status' => 'IMPLANTADO'],
        ]);
        $this->assertCount(1, $response->json('cancelamentos'));
        $this->assertSame('SOLICITADO', $response->json('cancelamentos.0.fase'));
        $this->assertSame('Solicitado', $response->json('cancelamentos.0.fase_label'));
        $this->assertSame($titularId, $response->json('cancelamentos.0.titular.id'));
        $this->assertArrayHasKey(ModalidadeCancelamento::VIA_LIMITAR->value, $response->json('modalidades'));
        $this->assertCount(4, $response->json('fases'));
    }

    public function test_atualizar_cancelamento_avanca_fase_e_conclui(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $titularId = $this->criarTitular($venda);
        $cancel = $this->criarCancelamento($venda, $titularId);

        // Avança para EM_ANALISE com modalidade e protocolo — segue PENDENTE.
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.cancelamento', $cancel->id), [
                'modalidade' => ModalidadeCancelamento::VIA_LIMITAR->value,
                'fase' => FaseCancelamento::EM_ANALISE->value,
                'protocolo' => 'PROTO-123',
            ])
            ->assertOk()->assertJson(['success' => true]);

        $fresh = $cancel->fresh();
        $this->assertSame('PENDENTE', $fresh->status);
        $this->assertSame('EM_ANALISE', $fresh->meta['fase']);
        $this->assertSame('VIA_LIMITAR', $fresh->meta['modalidade']);
        $this->assertSame('PROTO-123', $fresh->meta['protocolo']);

        // Conclui.
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.cancelamento', $cancel->id), [
                'fase' => FaseCancelamento::CONCLUIDO->value,
            ])
            ->assertOk();

        $fresh = $cancel->fresh();
        $this->assertSame('CONCLUIDA', $fresh->status);
        $this->assertSame($this->backoffice->id, $fresh->concluida_por);
        $this->assertNotNull($fresh->concluida_em);
    }

    public function test_atualizar_cancelamento_valida_fase_invalida(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $cancel = $this->criarCancelamento($venda, $this->criarTitular($venda));

        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.cancelamento', $cancel->id), ['fase' => 'INEXISTENTE'])
            ->assertStatus(422);
    }

    public function test_dados_traz_contratos_do_mesmo_cnpj_e_acessos(): void
    {
        $cnpj = '15285829000155';
        $atual = $this->criarContrato($this->backoffice->id, cnpj: $cnpj);
        $anterior = $this->criarContrato($this->backoffice->id, cnpj: $cnpj); // mesmo CNPJ
        $this->criarContrato($this->backoffice->id, cnpj: '99999999999999'); // outro CNPJ, não deve aparecer

        // Credencial com CNPJ formatado no login (casa por dígitos).
        CredencialAcesso::create([
            'empresa_id' => $this->empresa->id, 'tipo' => 'Empresa', 'nome' => 'ROFER',
            'login' => '15.285.829/0001-55', 'senha' => 'segredo', 'status' => 'Y', 'created_by' => $this->backoffice->id,
        ]);

        $response = $this->actingAs($this->backoffice)->getJson(route('backoffice.processos.dados', $atual->id));

        $response->assertOk();
        $ids = collect($response->json('contratos_anteriores'))->pluck('id');
        $this->assertTrue($ids->contains($anterior->id), 'Deve listar o outro contrato do mesmo CNPJ.');
        $this->assertFalse($ids->contains($atual->id), 'Não lista o próprio contrato.');
        $this->assertCount(1, $ids);

        $this->assertCount(1, $response->json('acessos'));
        $this->assertSame('ROFER', $response->json('acessos.0.nome'));
    }

    public function test_busca_contratos_por_nome_cnpj_e_proposta(): void
    {
        $a = $this->criarContrato($this->backoffice->id, cnpj: '11222333000199');
        $a->update(['nome_contrato' => 'PADARIA CENTRAL LTDA', 'numero_proposta' => 'PROP-777']);
        $b = $this->criarContrato($this->backoffice->id, cnpj: '55666777000188');
        $b->update(['nome_contrato' => 'MERCADO SUL']);
        // Contrato de outra empresa — não pode vazar.
        $outra = Empresa::factory()->create();
        $this->criarContrato($this->backoffice->id, empresa: $outra)->update(['nome_contrato' => 'PADARIA DA ESQUINA']);

        $buscar = fn (string $t) => collect(
            $this->actingAs($this->backoffice)->getJson(route('backoffice.processos.buscar', ['termo' => $t]))->json('resultados')
        )->pluck('id');

        $this->assertTrue($buscar('PADARIA')->contains($a->id));
        $this->assertSame(1, $buscar('PADARIA')->count(), 'Não vaza contrato de outra empresa.');
        $this->assertTrue($buscar('11.222.333/0001-99')->contains($a->id), 'Casa por CNPJ formatado (dígitos).');
        $this->assertTrue($buscar('PROP-777')->contains($a->id), 'Casa por nº de proposta.');
        $this->assertTrue($buscar('MERCADO')->contains($b->id));
    }

    public function test_busca_casa_palavras_soltas_fora_de_ordem(): void
    {
        // Caso real: procurar "empresa x10" precisa achar "X10 COMERCIO ...".
        // O LIKE da frase inteira falhava porque exigia o trecho contíguo.
        $x10 = $this->criarContrato($this->backoffice->id, cnpj: '52138844000105');
        $x10->update(['nome_contrato' => 'X10 COMERCIO E AUTOMACAO LTDA', 'numero_proposta' => '4565']);

        $buscar = fn (string $t) => collect(
            $this->actingAs($this->backoffice)->getJson(route('backoffice.processos.buscar', ['termo' => $t]))->json('resultados')
        )->pluck('id');

        $this->assertTrue($buscar('empresa x10')->contains($x10->id), 'Palavra extra não pode derrubar o resultado.');
        $this->assertTrue($buscar('x10')->contains($x10->id));
        $this->assertTrue($buscar('automacao comercio')->contains($x10->id), 'Ordem das palavras não importa.');
        $this->assertTrue($buscar('  X10   COMERCIO  ')->contains($x10->id), 'Espaços extras são normalizados.');
    }

    public function test_busca_ordena_por_relevancia(): void
    {
        $exato = $this->criarContrato($this->backoffice->id, cnpj: '11111111000111');
        $exato->update(['nome_contrato' => 'X10 COMERCIO E AUTOMACAO LTDA']);
        $parcial = $this->criarContrato($this->backoffice->id, cnpj: '22222222000122');
        $parcial->update(['nome_contrato' => 'COMERCIO DE ALIMENTOS SA']);

        $ids = collect(
            $this->actingAs($this->backoffice)
                ->getJson(route('backoffice.processos.buscar', ['termo' => 'x10 comercio']))
                ->json('resultados')
        )->pluck('id');

        $this->assertTrue($ids->contains($parcial->id), 'Quem casa só uma palavra ainda aparece.');
        $this->assertSame($exato->id, $ids->first(), 'Quem casa a frase inteira vem primeiro.');
    }

    public function test_busca_nao_trata_curinga_do_like_como_wildcard(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $venda->update(['nome_contrato' => 'CLINICA POPULAR']);

        $resultados = $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.processos.buscar', ['termo' => '%%']))
            ->json('resultados');

        $this->assertSame([], $resultados, 'Curinga digitado não pode devolver a base inteira.');
    }

    public function test_busca_ignora_termo_curto(): void
    {
        $this->criarContrato($this->backoffice->id);

        $this->actingAs($this->backoffice)
            ->getJson(route('backoffice.processos.buscar', ['termo' => 'a']))
            ->assertOk()->assertJson(['resultados' => []]);
    }

    public function test_dados_multitenant_404(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $outra = Empresa::factory()->create();
        $userOutra = User::factory()->create(['empresa_id' => $outra->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);

        $this->actingAs($userOutra)->getJson(route('backoffice.processos.dados', $venda->id))->assertStatus(404);
    }

    public function test_atualizar_cancelamento_403_para_backoffice_nao_dono(): void
    {
        $venda = $this->criarContrato($this->outroBackoffice->id);
        $cancel = $this->criarCancelamento($venda, $this->criarTitular($venda));

        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.cancelamento', $cancel->id), ['fase' => FaseCancelamento::CONCLUIDO->value])
            ->assertStatus(403);

        $this->assertSame('PENDENTE', $cancel->fresh()->status);
    }

    public function test_fase_portabilidade_pela_tela_do_contrato(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $port = \App\Models\VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'MARIA', 'sequencial' => 1]);

        // Dono avança a fase — continua aberta.
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.fasePortabilidade', $port->id), ['fase' => 'ENVIADA_ANALISE'])
            ->assertOk()->assertJson(['success' => true, 'fase' => ['value' => 'ENVIADA_ANALISE', 'final' => false]]);
        $this->assertSame('PENDENTE', $port->fresh()->status);

        // Fase final encerra.
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.fasePortabilidade', $port->id), ['fase' => 'CONCLUIDA'])
            ->assertOk();
        $this->assertSame('CONCLUIDA', $port->fresh()->status);

        // Fase inválida -> 422.
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.fasePortabilidade', $port->id), ['fase' => 'X'])
            ->assertStatus(422);
    }

    public function test_fase_portabilidade_403_para_backoffice_nao_dono(): void
    {
        $venda = $this->criarContrato($this->outroBackoffice->id);
        $port = \App\Models\VendaPortabilidade::create(['venda_id' => $venda->id, 'nome' => 'ZE', 'sequencial' => 1]);

        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.fasePortabilidade', $port->id), ['fase' => 'ENVIADA_ANALISE'])
            ->assertStatus(403);

        $this->assertSame('REUNINDO_DOCUMENTOS', $port->fresh()->fase);
    }

    public function test_emails_criados_crud_completo(): void
    {
        $venda = $this->criarContrato($this->backoffice->id);
        $titularId = $this->criarTitular($venda, 'MARIA');

        // Criar
        $resp = $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.processos.emails.store', $venda->id), [
                'email' => 'maria.cliente@gmail.com',
                'senha' => 'Senha@123',
                'titular_id' => $titularId,
                'observacao' => 'Conta criada para implantação AMIL',
            ]);
        $resp->assertOk()->assertJson(['success' => true, 'email' => ['email' => 'maria.cliente@gmail.com', 'titular' => 'MARIA']]);
        $emailId = $resp->json('email.id');

        // Aparece no read agregado da tela.
        $dados = $this->actingAs($this->backoffice)->getJson(route('backoffice.processos.dados', $venda->id));
        $this->assertCount(1, $dados->json('emails_criados'));

        // Atualizar
        $this->actingAs($this->backoffice)
            ->patchJson(route('backoffice.processos.emails.update', $emailId), [
                'email' => 'maria.nova@gmail.com', 'senha' => 'Outra@456',
            ])
            ->assertOk()->assertJson(['email' => ['email' => 'maria.nova@gmail.com']]);

        // Validação: e-mail inválido -> 422.
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.processos.emails.store', $venda->id), ['email' => 'nao-eh-email', 'senha' => 'x'])
            ->assertStatus(422);

        // Titular de OUTRA venda -> 422.
        $outraVenda = $this->criarContrato($this->backoffice->id);
        $titularOutra = $this->criarTitular($outraVenda, 'ZE');
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.processos.emails.store', $venda->id), [
                'email' => 'ze@gmail.com', 'senha' => 'x', 'titular_id' => $titularOutra,
            ])
            ->assertStatus(422);

        // Excluir
        $this->actingAs($this->backoffice)
            ->deleteJson(route('backoffice.processos.emails.destroy', $emailId))
            ->assertOk();
        $this->assertDatabaseMissing('venda_emails_criados', ['id' => $emailId]);
    }

    public function test_emails_criados_permissoes(): void
    {
        $venda = $this->criarContrato($this->outroBackoffice->id);

        // Backoffice não-dono: 403.
        $this->actingAs($this->backoffice)
            ->postJson(route('backoffice.processos.emails.store', $venda->id), ['email' => 'a@b.com', 'senha' => 'x'])
            ->assertStatus(403);

        // Outra empresa: 404 (nem enxerga).
        $registro = \App\Models\VendaEmailCriado::create([
            'venda_id' => $venda->id, 'empresa_id' => $venda->empresa_id,
            'email' => 'a@b.com', 'senha' => 'x', 'created_by' => $this->outroBackoffice->id,
        ]);
        $outraEmpresa = Empresa::factory()->create();
        $userOutra = User::factory()->create(['empresa_id' => $outraEmpresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $this->actingAs($userOutra)
            ->deleteJson(route('backoffice.processos.emails.destroy', $registro->id))
            ->assertStatus(404);
        $this->assertDatabaseHas('venda_emails_criados', ['id' => $registro->id]);
    }

    public function test_cadastro_do_vendedor_gera_cancelamento_por_titular(): void
    {
        DB::table('tabulacoes')->insert([
            'id' => Tabulations::VENDA, 'empresa_id' => $this->empresa->id, 'descricao' => 'VENDA',
            'tipo_tabulacao' => 'C', 'efetivo' => 'N', 'status' => 'Y', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $operadoraId = DB::table('operadoras')->insertGetId(['empresa_id' => $this->empresa->id, 'nome' => 'AMIL', 'created_at' => now(), 'updated_at' => now()]);
        $opAnteriorId = DB::table('operadoras')->insertGetId(['empresa_id' => $this->empresa->id, 'nome' => 'UNIMED', 'created_at' => now(), 'updated_at' => now()]);
        $planoId = DB::table('planos')->insertGetId(['empresa_id' => $this->empresa->id, 'operadora_id' => $operadoraId, 'nome' => 'Plano X', 'created_at' => now(), 'updated_at' => now()]);
        $contatoId = DB::table('contatos')->insertGetId(['empresa_id' => $this->empresa->id, 'user_import_id' => $this->backoffice->id, 'nome_cliente' => 'C', 'cpf' => '12345678901', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->backoffice);

        $venda = app(VendasRepositoryInterface::class)->create([
            'contato_id' => $contatoId,
            'nome_contrato' => 'ACME LTDA',
            'cpf_cnpj' => '11222333000144',
            'operadora_id' => $operadoraId,
            'titulares' => [
                ['nome' => 'Fulano', 'plano_id' => $planoId, 'plano_anterior' => 'SIM', 'operadora_anterior_id' => $opAnteriorId, 'precisa_cancelamento' => '1'],
                ['nome' => 'Beltrano', 'plano_id' => $planoId, 'plano_anterior' => 'NAO'], // sem cancelamento
            ],
        ]);

        $this->assertInstanceOf(Vendas::class, $venda);

        // Titular 1 gera cancelamento (origem VENDEDOR, fase SOLICITADO).
        $cancel = VendaDemanda::where('venda_id', $venda->id)
            ->where('tipo', TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value)
            ->get();
        $this->assertCount(1, $cancel, 'Só o titular que precisa cancelar gera processo.');
        $this->assertSame('VENDEDOR', $cancel->first()->origem);
        $this->assertSame('SOLICITADO', $cancel->first()->meta['fase']);
        $this->assertSame($opAnteriorId, $cancel->first()->operadora_anterior_id);

        // Sinal persistido no titular.
        $this->assertDatabaseHas('vendas_titulares', ['venda_id' => $venda->id, 'nome' => 'FULANO', 'precisa_cancelamento' => 1]);
    }
}
