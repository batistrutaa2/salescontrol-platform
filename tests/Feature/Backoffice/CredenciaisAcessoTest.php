<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\CredencialAcesso;
use App\Models\CredencialAcessoHistorico;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CredenciaisAcessoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $vendedor;

    private Operadora $operadora;

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

        $this->operadora = Operadora::create([
            'empresa_id' => $this->empresa->id,
            'nome' => 'BRADESCO',
            'status' => 'Y',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'operadora_id' => $this->operadora->id,
            'tipo' => 'Empresa',
            'nome' => 'MD4 Consultoria',
            'login' => '43.685.447/0001-54',
            'senha' => '7075263f',
            'observacao' => 'Dia 08',
            'status' => 'Y',
        ], $overrides);
    }

    public function test_admin_cria_credencial_e_gera_historico_de_criacao(): void
    {
        $resp = $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.store'), $this->payload());

        $resp->assertCreated()->assertJson(['success' => true]);

        // Nome é padronizado em MAIÚSCULAS na gravação.
        $this->assertDatabaseHas('credenciais_acesso', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'MD4 CONSULTORIA',
            'senha' => '7075263f',
            'created_by' => $this->admin->id,
        ]);

        $credencial = CredencialAcesso::first();
        $this->assertDatabaseHas('credenciais_acesso_historico', [
            'credencial_id' => $credencial->id,
            'acao' => 'CRIACAO',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_index_renderiza_para_papel_autorizado(): void
    {
        CredencialAcesso::create($this->payload() + [
            'empresa_id' => $this->empresa->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('backoffice.credenciais.index'))
            ->assertOk()
            ->assertSee('Cofre de Acessos');
    }

    public function test_nome_salvo_sempre_em_maiusculas(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.store'), $this->payload([
                'nome' => '  clínica são josé  ',
            ]))
            ->assertCreated();

        $this->assertDatabaseHas('credenciais_acesso', [
            'empresa_id' => $this->empresa->id,
            'nome' => 'CLÍNICA SÃO JOSÉ',
        ]);
    }

    public function test_store_multiplo_cria_varios_acessos_com_contexto_compartilhado(): void
    {
        $resp = $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.storeMultiplo'), [
                'tipo' => 'Empresa',
                'status' => 'Y',
                'observacao' => 'mesmo portal',
                'acessos' => [
                    ['nome' => 'rofer master', 'login' => '15.285.829/0001-55', 'senha' => 'a1'],
                    ['nome' => 'rofer submaster', 'login' => '15.285.829/0001-55', 'senha' => 'a2'],
                ],
            ]);

        $resp->assertStatus(201)->assertJson(['success' => true, 'quantidade' => 2]);
        // Nome em maiúsculas (mutator), contexto compartilhado replicado em cada acesso.
        $this->assertDatabaseHas('credenciais_acesso', ['empresa_id' => $this->empresa->id, 'nome' => 'ROFER MASTER', 'senha' => 'a1', 'tipo' => 'Empresa', 'observacao' => 'mesmo portal']);
        $this->assertDatabaseHas('credenciais_acesso', ['nome' => 'ROFER SUBMASTER', 'senha' => 'a2', 'observacao' => 'mesmo portal']);
        // Histórico de criação para cada uma.
        $this->assertDatabaseCount('credenciais_acesso_historico', 2);
    }

    public function test_store_multiplo_valida_nome_de_cada_acesso(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.storeMultiplo'), [
                'status' => 'Y',
                'acessos' => [['login' => 'x', 'senha' => 'y']], // sem nome
            ])
            ->assertStatus(422);
    }

    public function test_store_multiplo_bloqueia_vendedor(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('backoffice.credenciais.storeMultiplo'), [
                'status' => 'Y', 'acessos' => [['nome' => 'X']],
            ])
            ->assertStatus(403);
    }

    public function test_store_valida_nome_obrigatorio(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.store'), $this->payload(['nome' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    public function test_vendedor_recebe_403(): void
    {
        $this->actingAs($this->vendedor)
            ->postJson(route('backoffice.credenciais.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_edicao_gera_historico_por_campo_alterado(): void
    {
        $credencial = CredencialAcesso::create($this->payload() + [
            'empresa_id' => $this->empresa->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('backoffice.credenciais.update', $credencial->id), $this->payload([
                'senha' => 'NovaSenha@1',
                'nome' => 'MD4 Consultoria', // inalterado
            ]))
            ->assertOk();

        // Apenas a senha mudou → exatamente 1 registro de EDICAO.
        $this->assertSame(1, CredencialAcessoHistorico::where('acao', 'EDICAO')->count());
        $this->assertDatabaseHas('credenciais_acesso_historico', [
            'credencial_id' => $credencial->id,
            'acao' => 'EDICAO',
            'campo' => 'Senha',
            'valor_anterior' => '7075263f',
            'valor_novo' => 'NovaSenha@1',
        ]);
        $this->assertDatabaseHas('credenciais_acesso', [
            'id' => $credencial->id,
            'senha' => 'NovaSenha@1',
            'updated_by' => $this->admin->id,
        ]);
    }

    public function test_destroy_gera_historico_de_exclusao(): void
    {
        $credencial = CredencialAcesso::create($this->payload() + [
            'empresa_id' => $this->empresa->id,
            'created_by' => $this->admin->id,
            'updated_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('backoffice.credenciais.destroy', $credencial->id))
            ->assertOk();

        $this->assertDatabaseMissing('credenciais_acesso', ['id' => $credencial->id]);

        // A trilha de auditoria sobrevive à exclusão (credencial_id vira null via
        // onDelete set null), preservando quem excluiu e o nome do registro.
        $this->assertDatabaseHas('credenciais_acesso_historico', [
            'credencial_id' => null,
            'acao' => 'EXCLUSAO',
            'campo' => 'Nome',
            'valor_anterior' => 'MD4 CONSULTORIA',
            'user_id' => $this->admin->id,
        ]);
    }

    private function csvAcessos(): UploadedFile
    {
        $conteudo = "Empresa,CPF/CNPJ,Senha,Acesso\n"
            ."MD4 Consultoria,43.685.447/0001-54,7075263f,Dia 08\n"
            ."Rofer,15.285.829/0001-55,Famili2@,\n";

        return UploadedFile::fake()->createWithContent('acessos.csv', $conteudo);
    }

    public function test_preview_retorna_colunas_e_palpite_de_mapeamento(): void
    {
        $resp = $this->actingAs($this->admin)
            ->post(route('backoffice.credenciais.import.preview'), [
                'arquivo' => $this->csvAcessos(),
                'tem_cabecalho' => '1',
            ]);

        $resp->assertOk()
            ->assertJsonCount(4, 'colunas')
            ->assertJsonPath('total_linhas', 2)
            ->assertJsonPath('palpite.nome', 0)
            ->assertJsonPath('palpite.login', 1)
            ->assertJsonPath('palpite.senha', 2)
            ->assertJsonPath('palpite.observacao', 3);
    }

    public function test_import_cria_credenciais_mapeadas_e_historico(): void
    {
        $resp = $this->actingAs($this->admin)
            ->post(route('backoffice.credenciais.import'), [
                'operadora_id' => $this->operadora->id,
                'arquivo' => $this->csvAcessos(),
                'tem_cabecalho' => '1',
                'mapping' => ['nome' => 0, 'login' => 1, 'senha' => 2, 'observacao' => 3],
            ]);

        $resp->assertOk()->assertJson(['success' => true, 'importados' => 2]);

        $this->assertSame(2, CredencialAcesso::where('empresa_id', $this->empresa->id)->count());
        $this->assertDatabaseHas('credenciais_acesso', [
            'empresa_id' => $this->empresa->id,
            'operadora_id' => $this->operadora->id,
            'nome' => 'MD4 CONSULTORIA',
            'login' => '43.685.447/0001-54',
            'senha' => '7075263f',
            'observacao' => 'Dia 08',
            'created_by' => $this->admin->id,
        ]);
        $this->assertSame(2, CredencialAcessoHistorico::where('acao', 'CRIACAO')->count());
    }

    public function test_import_exige_mapeamento_de_nome(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.credenciais.import'), [
                'operadora_id' => $this->operadora->id,
                'arquivo' => $this->csvAcessos(),
                'mapping' => ['login' => 1],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mapping.nome']);
    }

    public function test_import_bloqueia_operadora_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $operadoraOutra = Operadora::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'AMIL',
            'status' => 'Y',
        ]);

        $this->actingAs($this->admin)
            ->post(route('backoffice.credenciais.import'), [
                'operadora_id' => $operadoraOutra->id,
                'arquivo' => $this->csvAcessos(),
                'mapping' => ['nome' => 0],
            ])
            ->assertNotFound();

        $this->assertSame(0, CredencialAcesso::count());
    }

    public function test_multitenant_nao_acessa_credencial_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $outraOperadora = Operadora::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'AMIL',
            'status' => 'Y',
        ]);
        $credencialOutra = CredencialAcesso::create([
            'empresa_id' => $outraEmpresa->id,
            'operadora_id' => $outraOperadora->id,
            'nome' => 'Empresa de Outra',
            'senha' => 'secreta',
            'status' => 'Y',
        ]);

        // admin da empresa A não enxerga credencial da empresa B
        $this->actingAs($this->admin)
            ->getJson(route('backoffice.credenciais.show', $credencialOutra->id))
            ->assertNotFound();

        // nem consegue editar
        $this->actingAs($this->admin)
            ->putJson(route('backoffice.credenciais.update', $credencialOutra->id), $this->payload())
            ->assertNotFound();
    }
}
