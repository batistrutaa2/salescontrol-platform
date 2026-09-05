<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\Estudos;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstudoTenancyTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private Empresa $outraEmpresa;

    private User $admin;

    private User $vendedor;

    private User $outroVendedor;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->outraEmpresa = Empresa::factory()->create();
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
        $this->outroVendedor = User::factory()->create([
            'empresa_id' => $this->outraEmpresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);
    }

    public function test_listagem_e_detalhe_nao_vazam_estudos_de_outra_empresa(): void
    {
        $meu = $this->estudo($this->empresa, $this->vendedor, 'Estudo da empresa A');
        $outro = $this->estudo($this->outraEmpresa, $this->outroVendedor, 'Estudo da empresa B');

        $this->actingAs($this->admin)
            ->getJson(route('estudo.getListStudies'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $meu->id)
            ->assertJsonCount(1, 'data');

        $this->assertNotContains($outro->id, collect($this->getJson(route('estudo.getListStudies'))->json('data'))->pluck('id'));
        $this->getJson(route('estudo.data', $outro->id))->assertNotFound();
    }

    public function test_vendedor_so_edita_e_exclui_os_proprios_estudos(): void
    {
        $doAdmin = $this->estudo($this->empresa, $this->admin, 'Estudo da gestão');

        $this->actingAs($this->vendedor)
            ->get(route('estudo.edit', $doAdmin->id))
            ->assertNotFound();
        $this->deleteJson(route('estudo.delete', $doAdmin->id))->assertNotFound();

        $this->assertDatabaseHas('estudos', ['id' => $doAdmin->id]);
    }

    public function test_operadora_de_outro_tenant_nao_pode_ser_consultada(): void
    {
        $operadora = Operadora::create([
            'empresa_id' => $this->outraEmpresa->id,
            'nome' => 'Operadora externa',
            'status' => 'Y',
        ]);

        $this->actingAs($this->admin)
            ->getJson('/planos/'.$operadora->id)
            ->assertNotFound();
    }

    public function test_link_publico_opaco_continua_disponivel_sem_expor_listagem(): void
    {
        $estudo = $this->estudo($this->outraEmpresa, $this->outroVendedor, 'Compartilhado');

        $this->get(route('estudo.data', $estudo->id))->assertRedirect(route('login'));
        $this->get(route('estudo.show', $estudo->link_unico))->assertOk();
    }

    public function test_criacao_vincula_operadora_e_plano_do_tenant_e_ignora_titulo_forjado(): void
    {
        [$operadora, $plano] = $this->opcao($this->empresa, 'OPERADORA LIVRE', 'PLANO OURO');

        $this->actingAs($this->admin)
            ->postJson(route('estudo.store'), $this->payload($operadora->id, $plano->id, [
                'titulo' => 'OUTRA OPERADORA - PLANO INVASOR',
            ]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('estudo_itens', [
            'operadora_id' => $operadora->id,
            'plano_id' => $plano->id,
            'operadora_plano' => 'OPERADORA LIVRE - PLANO OURO',
            'reembolso_consulta' => 0,
        ]);
        $this->assertDatabaseHas('estudo_vidas', [
            'qtde' => 2,
            'valor_unitario' => 100,
            'total' => 200,
        ]);
    }

    public function test_criacao_rejeita_plano_de_outro_tenant_e_plano_de_outra_operadora(): void
    {
        [$operadora, $plano] = $this->opcao($this->empresa, 'OPERADORA A', 'PLANO A');
        [, $planoOutraOperadora] = $this->opcao($this->empresa, 'OPERADORA B', 'PLANO B');
        [, $planoExterno] = $this->opcao($this->outraEmpresa, 'OPERADORA EXTERNA', 'PLANO EXTERNO');

        $this->actingAs($this->admin)
            ->postJson(route('estudo.store'), $this->payload($operadora->id, $planoOutraOperadora->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estudos.0.plano_id']);

        $this->postJson(route('estudo.store'), $this->payload($operadora->id, $planoExterno->id))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['estudos.0.plano_id']);

        $this->assertDatabaseCount('estudos', 0);
        $this->assertDatabaseMissing('estudo_itens', ['plano_id' => $plano->id]);
    }

    public function test_estudo_publico_aplica_iof_pela_configuracao_e_nao_pelo_nome(): void
    {
        [$configurada, $planoConfigurado] = $this->opcao($this->empresa, 'OPERADORA SEM MARCA', 'PLANO UM');
        $configurada->update(['iof_percentual' => 1.50, 'cor_marca' => '#123456']);
        [$nomeHistorico, $planoSemIof] = $this->opcao($this->empresa, 'BRADESCO SEM REGRA', 'PLANO DOIS');
        $nomeHistorico->update(['iof_percentual' => 0]);

        $estudo = $this->estudo($this->empresa, $this->admin, 'Comparativo configurável');
        foreach ([[$configurada, $planoConfigurado], [$nomeHistorico, $planoSemIof]] as [$operadora, $plano]) {
            $itemId = DB::table('estudo_itens')->insertGetId([
                'estudo_id' => $estudo->id,
                'operadora_id' => $operadora->id,
                'plano_id' => $plano->id,
                'operadora_plano' => $operadora->nome.' - '.$plano->nome,
                'coparticipacao' => 'NÃO',
                'categoria' => '',
                'reembolso_consulta' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('estudo_vidas')->insert([
                'estudo_item_id' => $itemId,
                'faixa' => '0 a 18',
                'qtde' => 1,
                'valor_unitario' => 100,
                'total' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $response = $this->get(route('estudo.show', $estudo->link_unico))->assertOk();
        $response->assertSee('IOF (1,50%)')->assertSee('#123456', false);
        $this->assertSame(1, substr_count($response->getContent(), 'Total com IOF'));
    }

    public function test_estudo_publico_nao_carrega_configuracao_de_operadora_de_outro_tenant(): void
    {
        [$operadoraExterna, $planoExterno] = $this->opcao($this->outraEmpresa, 'MARCA SECRETA EXTERNA', 'PLANO EXTERNO');
        $operadoraExterna->update(['iof_percentual' => 9.99, 'cor_marca' => '#ABCDEF']);
        $estudo = $this->estudo($this->empresa, $this->admin, 'Estudo com vínculo histórico inválido');
        $itemId = DB::table('estudo_itens')->insertGetId([
            'estudo_id' => $estudo->id,
            'operadora_id' => $operadoraExterna->id,
            'plano_id' => $planoExterno->id,
            'operadora_plano' => 'Referência legada',
            'coparticipacao' => 'NÃO',
            'categoria' => '',
            'reembolso_consulta' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('estudo_vidas')->insert([
            'estudo_item_id' => $itemId,
            'faixa' => '0 a 18',
            'qtde' => 1,
            'valor_unitario' => 100,
            'total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('estudo.show', $estudo->link_unico))->assertOk();
        $response->assertDontSee('MARCA SECRETA EXTERNA')->assertDontSee('IOF (9,99%)');
    }

    private function estudo(Empresa $empresa, User $user, string $titulo): Estudos
    {
        return Estudos::create([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'titulo' => $titulo,
            'link_unico' => (string) Str::uuid(),
        ]);
    }

    private function opcao(Empresa $empresa, string $nomeOperadora, string $nomePlano): array
    {
        $operadora = Operadora::create([
            'empresa_id' => $empresa->id,
            'nome' => $nomeOperadora,
            'status' => 'Y',
        ]);
        $plano = Plano::create([
            'empresa_id' => $empresa->id,
            'operadora_id' => $operadora->id,
            'nome' => $nomePlano,
            'status' => 'Y',
            'acomodacao' => 'ENFERMARIA',
        ]);

        return [$operadora, $plano];
    }

    private function payload(int $operadoraId, int $planoId, array $sobrescrever = []): array
    {
        return [
            'nome_empresa' => 'Cliente de teste',
            'estudos' => [[
                ...$sobrescrever,
                'operadora_id' => $operadoraId,
                'plano_id' => $planoId,
                'coparticipacao' => 'NÃO',
                'categoria' => 'PME',
                'reembolso' => 0,
                'faixas' => [[
                    'faixa' => '0 a 18',
                    'qtde' => 2,
                    'valor_unitario' => 100,
                    'total' => 999999,
                ]],
            ]],
        ];
    }
}
