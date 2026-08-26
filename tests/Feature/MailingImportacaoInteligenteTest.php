<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Contatos;
use App\Models\Dependentes;
use App\Models\Empresa;
use App\Models\MailingImportacao;
use App\Models\Preditiva;
use App\Models\Tabulacoes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class MailingImportacaoInteligenteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $admin;

    private User $vendedor;

    private Tabulacoes $tabulacao;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
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
        $this->tabulacao = Tabulacoes::create([
            'empresa_id' => $this->empresa->id,
            'descricao' => 'PROSPECÇÃO',
            'tipo_tabulacao' => 'C',
            'status' => 'Y',
        ]);
    }

    public function test_analisa_importa_apenas_novos_e_mantem_duplicado_pendente(): void
    {
        $existente = Contatos::create([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Cliente Existente',
            'cpf' => '11111111111',
            'status' => 'Y',
        ]);
        Preditiva::create([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $existente->id,
            'status' => 'Y',
        ]);

        $response = $this->actingAs($this->admin)->post('/mailing/importaMailing', [
            'base' => 'Base teste',
            'tipo_layout' => 'padrao',
            'id_user' => $this->vendedor->id,
            'tabulacao' => $this->tabulacao->id,
            'file' => $this->planilhaPadrao([
                ['Lead repetido', '01/01/1990', '111.111.111-11'],
                ['Lead novo', '02/02/1992', '222.222.222-22'],
            ]),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.importacao.total_novos', 1)
            ->assertJsonPath('data.importacao.total_duplicados', 1)
            ->assertJsonPath('data.itens.0.situacao', 'PREDITIVA');

        $importacaoId = $response->json('data.importacao.id');

        $this->actingAs($this->admin)
            ->post("/mailing/importacoes/{$importacaoId}/importar-novos")
            ->assertOk()
            ->assertJsonPath('resultado.importados', 1);

        $this->assertDatabaseHas('contatos', [
            'empresa_id' => $this->empresa->id,
            'cpf' => '22222222222',
            'nome_cliente' => 'Lead novo',
        ]);
        $novoId = Contatos::where('empresa_id', $this->empresa->id)->where('cpf', '22222222222')->value('id');
        $this->assertDatabaseHas('lead_reservatorio_itens', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $novoId,
            'origem' => 'IMPORTACAO',
            'status' => 'DISPONIVEL',
        ]);
        $this->assertDatabaseMissing('contatos_corretores', ['contato_id' => $novoId]);
        $this->assertDatabaseHas('mailing_importacoes', [
            'id' => $importacaoId,
            'status' => 'PARCIAL',
            'total_importados' => 1,
            'total_resolvidos' => 0,
        ]);

        $this->actingAs($this->admin)
            ->post("/mailing/importacoes/{$importacaoId}/importar-novos")
            ->assertOk()
            ->assertJsonPath('resultado.importados', 0);
        $this->assertSame(1, Contatos::where('empresa_id', $this->empresa->id)->where('cpf', '22222222222')->count());

        $this->actingAs($this->admin)
            ->get('/mailing/importacoes/pendente')
            ->assertOk()
            ->assertJsonPath('data.importacao.id', $importacaoId);
    }

    public function test_move_duplicado_da_preditiva_para_vendedor_sem_apagar_o_contato(): void
    {
        $contato = Contatos::create([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Lead Preditiva',
            'cpf' => '33333333333',
            'status' => 'Y',
        ]);
        Preditiva::create(['empresa_id' => $this->empresa->id, 'contato_id' => $contato->id, 'status' => 'Y']);
        DB::table('comentarios')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contato->id,
            'anotacao' => 'Negociação antiga',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('ligacoes')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contato->id,
            'tabulacao_id' => $this->tabulacao->id,
            'telefone' => '11999999999',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('lead_atividades')->insert([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contato->id,
            'tabulacao_anterior_id' => $this->tabulacao->id,
            'tabulacao_atual_id' => $this->tabulacao->id,
            'log_descricao' => 'Atividade antiga',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $importacao = MailingImportacao::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->admin->id,
            'vendedor_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacao->id,
            'nome_base' => 'Base duplicada',
            'tipo_layout' => 'padrao',
            'status' => 'EM_ANALISE',
            'total_itens' => 1,
            'total_duplicados' => 1,
        ]);
        $item = $importacao->itens()->create([
            'linha' => 2,
            'cpf' => $contato->cpf,
            'nome' => $contato->nome_cliente,
            'payload' => [],
            'classificacao' => 'DUPLICADO_BASE',
            'contato_existente_id' => $contato->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/mailing/importacoes/{$importacao->id}/resolver-duplicados", [
                'itens' => [$item->id],
                'destino' => 'VENDEDOR',
                'vendedor_id' => $this->vendedor->id,
                'tabulacao_id' => $this->tabulacao->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.importacao.status', 'CONCLUIDA');

        $this->assertDatabaseMissing('preditiva', ['empresa_id' => $this->empresa->id, 'contato_id' => $contato->id]);
        $this->assertDatabaseHas('contatos_corretores', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $contato->id,
            'user_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacao->id,
        ]);
        $this->assertDatabaseHas('contatos', ['id' => $contato->id]);
        $this->assertDatabaseMissing('comentarios', ['contato_id' => $contato->id]);
        $this->assertDatabaseMissing('ligacoes', ['contato_id' => $contato->id]);
        $this->assertDatabaseMissing('lead_atividades', ['contato_id' => $contato->id]);
        $this->assertDatabaseHas('contatos', [
            'id' => $contato->id,
            'nome_base' => 'Base duplicada',
            'valor_negociacao' => 0,
        ]);
    }

    public function test_lead_com_vendedor_nao_pode_ser_movimentado_pela_importacao(): void
    {
        $contato = Contatos::create([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Lead em atendimento',
            'cpf' => '77777777777',
            'status' => 'Y',
        ]);
        DB::table('contatos_corretores')->insert([
            'empresa_id' => $this->empresa->id,
            'contato_id' => $contato->id,
            'user_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacao->id,
            'temperatura' => 'FRIO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $importacao = MailingImportacao::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->admin->id,
            'vendedor_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacao->id,
            'nome_base' => 'Base protegida',
            'tipo_layout' => 'padrao',
            'status' => 'EM_ANALISE',
            'total_itens' => 1,
            'total_duplicados' => 1,
        ]);
        $item = $importacao->itens()->create([
            'linha' => 2,
            'cpf' => $contato->cpf,
            'nome' => $contato->nome_cliente,
            'payload' => [],
            'classificacao' => 'DUPLICADO_BASE',
            'contato_existente_id' => $contato->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/mailing/importacoes/{$importacao->id}/resolver-duplicados", [
                'itens' => [$item->id],
                'destino' => 'PREDITIVA',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Somente leads descartados ou na preditiva, sem vendedor atual, podem ser movimentados.');

        $this->assertDatabaseHas('contatos_corretores', ['contato_id' => $contato->id, 'user_id' => $this->vendedor->id]);
    }

    public function test_layout_com_dependentes_importa_somente_o_grupo_do_titular_novo(): void
    {
        Contatos::create([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->admin->id,
            'nome_cliente' => 'Titular existente',
            'cpf' => '44444444444',
            'status' => 'Y',
        ]);

        $response = $this->actingAs($this->admin)->post('/mailing/importaMailing', [
            'base' => 'Base famílias',
            'tipo_layout' => 'com_dependentes',
            'id_user' => $this->vendedor->id,
            'tabulacao' => $this->tabulacao->id,
            'file' => $this->planilhaComDependentes(),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.importacao.total_novos', 1)
            ->assertJsonPath('data.importacao.total_duplicados', 1);

        $this->actingAs($this->admin)
            ->post('/mailing/importacoes/'.$response->json('data.importacao.id').'/importar-novos')
            ->assertOk()
            ->assertJsonPath('resultado.importados', 1);

        $novo = Contatos::where('empresa_id', $this->empresa->id)->where('cpf', '55555555555')->firstOrFail();
        $this->assertDatabaseHas('dependentes', [
            'empresa_id' => $this->empresa->id,
            'contato_id' => $novo->id,
            'nome' => 'Dependente novo',
        ]);
        $this->assertSame(1, Dependentes::where('contato_id', $novo->id)->count());
    }

    public function test_usuario_de_outra_empresa_nao_acessa_analise(): void
    {
        $importacao = MailingImportacao::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->admin->id,
            'vendedor_id' => $this->vendedor->id,
            'tabulacao_id' => $this->tabulacao->id,
            'nome_base' => 'Privada',
            'tipo_layout' => 'padrao',
            'status' => 'EM_ANALISE',
        ]);
        $outraEmpresa = Empresa::factory()->create();
        $outroUsuario = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $this->actingAs($outroUsuario)
            ->post("/mailing/importacoes/{$importacao->id}/importar-novos")
            ->assertNotFound();
    }

    private function planilhaPadrao(array $linhas): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['nome', 'data_de_nascimento', 'cpf', 'plano', 'cartegoria', 'entidade', 'contato_1', 'contato_2', 'contato_3', 'email', 'idades', 'valor'],
            ...array_map(fn (array $linha) => [$linha[0], $linha[1], $linha[2], 'Plano', 'Categoria', 'Entidade', '11999999999', '', '', '', '30', '100'], $linhas),
        ]);

        $path = tempnam(sys_get_temp_dir(), 'mailing_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'mailing.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function planilhaComDependentes(): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['CATEGORIA', 'NOME', 'CPF', 'IDADE', 'PARENTESCO', 'VALOR', 'ENTIDADE'],
            ['Familiar', 'Titular existente', '444.444.444-44', 40, 'TITULAR', '200,00', 'Entidade'],
            ['Familiar', 'Titular novo', '555.555.555-55', 35, 'TITULAR', '180,00', 'Entidade'],
            ['Familiar', 'Dependente novo', '666.666.666-66', 10, 'FILHO', '80,00', 'Entidade'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'mailing_dep_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, 'familias.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }
}
