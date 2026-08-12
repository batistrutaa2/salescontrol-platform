<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\VerificarVendaDocumento;
use App\Jobs\TransferirDocumentosVenda;
use App\Models\VendaDocumento;
use App\Services\Documentos\ClamAvService;
use App\Services\Documentos\DocumentoStatusService;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendaDocumentoTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;
    private Vendas $venda;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Queue::fake();
        config(['documentos.processamento_ativo' => true]);

        DB::table('user_roles')->insert([
            'id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $empresa = Empresa::factory()->create();
        $this->vendedor = User::factory()->create([
            'empresa_id' => $empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y',
        ]);
        $contato = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $this->vendedor->id,
            'nome_cliente' => 'Caragi Participacoes',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $operadora = Operadora::create([
            'empresa_id' => $empresa->id,
            'nome' => 'AMIL',
            'diretorio_documentos' => 'Amil',
            'status' => 'Y',
        ]);
        $this->venda = Vendas::create([
            'empresa_id' => $empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $contato,
            'nome_contrato' => 'Caragi Participacoes',
            'operadora' => 'Amil',
            'operadora_id' => $operadora->id,
            'data_vigencia' => now(),
        ]);
    }

    public function test_vendedor_envia_imagem_e_job_e_enfileirado(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $arquivo = UploadedFile::fake()->createWithContent('documento.png', $png);

        $response = $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            ['arquivo' => $arquivo, 'client_upload_id' => 'upload-1']
        );

        $response->assertCreated()->assertJsonPath('status', 'RECEBIDO');
        $this->assertDatabaseHas('venda_documentos', [
            'venda_id' => $this->venda->id,
            'nome_original' => 'documento.png',
            'diretorio_remoto' => 'EmAnalise/Amil/Caragi Participacoes',
        ]);
        Queue::assertPushed(VerificarVendaDocumento::class);
    }

    public function test_rejeita_upload_quando_operadora_nao_tem_pasta_mapeada(): void
    {
        $this->venda->operadoraRelation()->update(['diretorio_documentos' => null]);
        $arquivo = UploadedFile::fake()->image('documento.png');

        $this->actingAs($this->vendedor)
            ->postJson(route('vendas.documentos.store', $this->venda), ['arquivo' => $arquivo])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('arquivo');

        Queue::assertNothingPushed();
    }

    public function test_rejeita_svg_e_arquivo_maior_que_limite(): void
    {
        $svg = UploadedFile::fake()->createWithContent('risco.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->actingAs($this->vendedor)
            ->postJson(route('vendas.documentos.store', $this->venda), ['arquivo' => $svg])
            ->assertUnprocessable()->assertJsonValidationErrors('arquivo');

        $grande = UploadedFile::fake()->create('grande.pdf', 25 * 1024 + 1, 'application/pdf');
        $this->actingAs($this->vendedor)
            ->postJson(route('vendas.documentos.store', $this->venda), ['arquivo' => $grande])
            ->assertUnprocessable()->assertJsonValidationErrors('arquivo');
    }

    public function test_modo_preparacao_guarda_documento_sem_enfileirar(): void
    {
        config(['documentos.processamento_ativo' => false]);
        Queue::fake();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            ['arquivo' => UploadedFile::fake()->createWithContent('aguardando.png', $png)]
        )->assertCreated()->assertJsonPath('status', 'RECEBIDO');

        Queue::assertNothingPushed();
        $this->assertSame('PENDENTE', $this->venda->fresh()->documentacao_status);
    }

    public function test_vendedor_nao_acessa_venda_de_outro_usuario(): void
    {
        $outro = User::factory()->create([
            'empresa_id' => $this->venda->empresa_id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y',
        ]);

        $this->actingAs($outro)->getJson(route('vendas.documentos.index', $this->venda))->assertForbidden();
    }

    public function test_scan_e_transferencia_concluem_sem_reler_o_arquivo_remoto(): void
    {
        Storage::fake('documentos_test');
        config(['documentos.disk' => 'documentos_test', 'cache.default' => 'array']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $this->actingAs($this->vendedor)->postJson(route('vendas.documentos.store', $this->venda), [
            'arquivo' => UploadedFile::fake()->createWithContent('seguro.png', $png),
            'client_upload_id' => 'pipeline-1',
        ])->assertCreated();

        $doc = VendaDocumento::firstOrFail();
        $clamAv = new class extends ClamAvService {
            public function scan(string $absolutePath): void {}
        };
        (new VerificarVendaDocumento($doc->id))->handle($clamAv, app(DocumentoStatusService::class));
        $this->assertSame('AGUARDANDO_ENVIO', $doc->fresh()->status);

        (new TransferirDocumentosVenda($this->venda->id))->handle(app(DocumentoStatusService::class));
        $doc->refresh();
        $this->assertSame('DISPONIVEL', $doc->status);
        Storage::disk('documentos_test')->assertExists($doc->caminho_remoto);
    }

    public function test_vendas_duplicadas_recebem_diretorios_deterministicos_distintos(): void
    {
        $segunda = $this->venda->replicate();
        $segunda->save();
        $arquivo = UploadedFile::fake()->image('documento.png');

        $this->actingAs($this->vendedor)->postJson(route('vendas.documentos.store', $this->venda), ['arquivo' => $arquivo])->assertCreated();
        $this->actingAs($this->vendedor)->postJson(route('vendas.documentos.store', $segunda), ['arquivo' => UploadedFile::fake()->image('outro.png')])->assertCreated();

        $this->assertSame('EmAnalise/Amil/Caragi Participacoes', $this->venda->fresh()->documentacao_diretorio);
        $this->assertSame("EmAnalise/Amil/Caragi Participacoes - Venda {$segunda->id}", $segunda->fresh()->documentacao_diretorio);
    }

    public function test_upload_realinha_automaticamente_diretorio_legado_da_operadora(): void
    {
        $this->venda->update(['documentacao_diretorio' => 'EmAnalise/AMIL/Caragi Participacoes']);

        $this->actingAs($this->vendedor)->postJson(route('vendas.documentos.store', $this->venda), [
            'arquivo' => UploadedFile::fake()->image('novo.png'),
        ])->assertCreated();

        $this->assertSame('EmAnalise/Amil/Caragi Participacoes', $this->venda->fresh()->documentacao_diretorio);
        $this->assertDatabaseHas('venda_documentos', [
            'venda_id' => $this->venda->id,
            'diretorio_remoto' => 'EmAnalise/Amil/Caragi Participacoes',
            'status' => 'RECEBIDO',
        ]);
    }
}
