<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Jobs\TransferirDocumentosVenda;
use App\Jobs\VerificarVendaDocumento;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use App\Models\VendaDocumento;
use App\Models\Vendas;
use App\Services\Documentos\ClamAvService;
use App\Services\Documentos\DocumentoStatusService;
use App\Services\Documentos\RegistrarVendaDocumentoService;
use App\Services\Documentos\VendaDocumentoPermissionPolicy;
use Illuminate\Database\QueryException;
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

    public function test_vendedor_reenvia_documento_com_falha_e_job_e_enfileirado(): void
    {
        $caminhoTemporario = "venda-documentos/{$this->venda->empresa_id}/{$this->venda->id}/falha.pdf";
        Storage::disk('local')->put($caminhoTemporario, 'conteudo');
        $documento = VendaDocumento::create([
            'venda_id' => $this->venda->id,
            'empresa_id' => $this->venda->empresa_id,
            'uploaded_by' => $this->vendedor->id,
            'client_upload_id' => 'reenvio-1',
            'nome_original' => 'falha.pdf',
            'nome_remoto' => 'falha.pdf',
            'mime_type' => 'application/pdf',
            'tamanho' => 8,
            'sha256' => hash('sha256', 'conteudo'),
            'caminho_temporario' => $caminhoTemporario,
            'diretorio_remoto' => 'EmAnalise/Amil/Caragi Participacoes',
            'caminho_remoto' => 'EmAnalise/Amil/Caragi Participacoes/falha.pdf',
            'status' => 'FALHA',
            'erro' => 'Falha transitória',
        ]);

        $this->actingAs($this->vendedor)
            ->postJson(route('vendas.documentos.retry', [$this->venda, $documento]))
            ->assertOk()
            ->assertJsonPath('status', 'RECEBIDO')
            ->assertJsonPath('erro', null);

        $this->assertSame('PROCESSANDO', $this->venda->fresh()->documentacao_status);
        Queue::assertPushed(VerificarVendaDocumento::class, fn ($job) => $job->documentoId === $documento->id);
    }

    public function test_rejeita_nome_repetido_normalizado_sem_criar_efeitos_colaterais(): void
    {
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('Contrato Ágil.png'),
                'client_upload_id' => 'nome-normalizado-1',
            ]
        )->assertCreated();

        $response = $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image("CONTRATO   A\u{0301}GIL.png"),
                'client_upload_id' => 'nome-normalizado-2',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('arquivo')
            ->assertJsonPath(
                'errors.arquivo.0',
                'Já existe um documento chamado “CONTRATO   ÁGIL.png” nesta venda. Renomeie o arquivo ou exclua o existente antes de enviar novamente.'
            );
        $this->assertSame(1, VendaDocumento::where('venda_id', $this->venda->id)->count());
        $this->assertCount(1, Storage::disk('local')->allFiles("venda-documentos/{$this->venda->empresa_id}/{$this->venda->id}"));
        Queue::assertPushed(VerificarVendaDocumento::class, 1);
    }

    public function test_mesmo_client_upload_id_e_arquivo_retorna_o_registro_sem_repetir_job(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $primeira = $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->createWithContent('Documento.png', $png),
                'client_upload_id' => 'idempotente-1',
            ]
        )->assertCreated();

        $segunda = $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->createWithContent('documento.PNG', $png),
                'client_upload_id' => 'idempotente-1',
            ]
        )->assertOk();

        $this->assertSame($primeira->json('id'), $segunda->json('id'));
        $this->assertSame(1, VendaDocumento::where('venda_id', $this->venda->id)->count());
        $this->assertCount(1, Storage::disk('local')->allFiles("venda-documentos/{$this->venda->empresa_id}/{$this->venda->id}"));
        Queue::assertPushed(VerificarVendaDocumento::class, 1);
    }

    public function test_rejeita_reuso_do_client_upload_id_por_outro_arquivo_antes_da_duplicidade(): void
    {
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('documento.png', 1, 1),
                'client_upload_id' => 'idempotente-conflitante',
            ]
        )->assertCreated();

        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('documento.png', 2, 2),
                'client_upload_id' => 'idempotente-conflitante',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors('client_upload_id');

        $this->assertSame(1, VendaDocumento::where('venda_id', $this->venda->id)->count());
        Queue::assertPushed(VerificarVendaDocumento::class, 1);
    }

    public function test_colisoes_distintas_apos_sanitizacao_recebem_sufixo_remoto(): void
    {
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('contrato:final.png'),
                'client_upload_id' => 'sanitizacao-1',
            ]
        )->assertCreated();
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('contrato?final.png'),
                'client_upload_id' => 'sanitizacao-2',
            ]
        )->assertCreated();

        $this->assertDatabaseHas('venda_documentos', [
            'venda_id' => $this->venda->id,
            'nome_remoto' => 'contrato final.png',
        ]);
        $this->assertDatabaseHas('venda_documentos', [
            'venda_id' => $this->venda->id,
            'nome_remoto' => 'contrato final - 2.png',
        ]);
    }

    public function test_nome_so_e_liberado_quando_a_exclusao_termina(): void
    {
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('contrato.png'),
                'client_upload_id' => 'exclusao-1',
            ]
        )->assertCreated();
        $documento = VendaDocumento::firstOrFail();
        $documento->update(['status' => 'EXCLUSAO_PENDENTE']);

        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('CONTRATO.png'),
                'client_upload_id' => 'exclusao-2',
            ]
        )->assertUnprocessable()->assertJsonValidationErrors('arquivo');

        $documento->update(['status' => 'EXCLUIDO', 'deleted_at' => now()]);
        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('CONTRATO.png'),
                'client_upload_id' => 'exclusao-3',
            ]
        )->assertCreated();

        $this->assertSame(2, VendaDocumento::where('venda_id', $this->venda->id)->count());
    }

    public function test_remove_temporario_quando_a_persistencia_falha_e_faz_rollback(): void
    {
        $arquivo = UploadedFile::fake()->image('rollback.png');

        try {
            app(RegistrarVendaDocumentoService::class)->registrar(
                $this->venda,
                $arquivo,
                'rollback-1',
                PHP_INT_MAX
            );
            $this->fail('A chave estrangeira de uploaded_by deveria impedir o registro.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('venda_documentos', ['client_upload_id' => 'rollback-1']);
            $this->assertSame([], Storage::disk('local')->allFiles("venda-documentos/{$this->venda->empresa_id}/{$this->venda->id}"));
            $this->assertNull($this->venda->fresh()->documentacao_diretorio);
            Queue::assertNothingPushed();
        }
    }

    public function test_revalida_limite_de_documentos_ativos_sem_criar_temporario(): void
    {
        foreach (range(1, 30) as $numero) {
            VendaDocumento::create([
                'venda_id' => $this->venda->id,
                'empresa_id' => $this->venda->empresa_id,
                'uploaded_by' => $this->vendedor->id,
                'client_upload_id' => "limite-{$numero}",
                'nome_original' => "documento-{$numero}.pdf",
                'nome_remoto' => "documento-{$numero}.pdf",
                'mime_type' => 'application/pdf',
                'tamanho' => 1,
                'sha256' => hash('sha256', (string) $numero),
                'caminho_temporario' => "temporarios/{$numero}",
                'diretorio_remoto' => 'EmAnalise/Amil/Caragi Participacoes',
                'caminho_remoto' => "EmAnalise/Amil/Caragi Participacoes/documento-{$numero}.pdf",
                'status' => 'RECEBIDO',
            ]);
        }

        $this->actingAs($this->vendedor)->postJson(
            route('vendas.documentos.store', $this->venda),
            [
                'arquivo' => UploadedFile::fake()->image('documento-31.png'),
                'client_upload_id' => 'limite-31',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors('arquivo');

        $this->assertSame(30, VendaDocumento::where('venda_id', $this->venda->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
        Queue::assertNothingPushed();
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
        $this->configureCollaborativeDocumentDisk();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
        $this->actingAs($this->vendedor)->postJson(route('vendas.documentos.store', $this->venda), [
            'arquivo' => UploadedFile::fake()->createWithContent('seguro.png', $png),
            'client_upload_id' => 'pipeline-1',
        ])->assertCreated();

        $doc = VendaDocumento::firstOrFail();
        $clamAv = new class extends ClamAvService
        {
            public function scan(string $absolutePath): void {}
        };
        (new VerificarVendaDocumento($doc->id))->handle($clamAv, app(DocumentoStatusService::class));
        $this->assertSame('AGUARDANDO_ENVIO', $doc->fresh()->status);

        (new TransferirDocumentosVenda($this->venda->id))->handle(
            app(DocumentoStatusService::class),
            app(VendaDocumentoPermissionPolicy::class)
        );
        $doc->refresh();
        $this->assertSame('DISPONIVEL', $doc->status);
        Storage::disk('documentos_test')->assertExists($doc->caminho_remoto);
        $absolutePath = Storage::disk('documentos_test')->path($doc->caminho_remoto);
        clearstatcache(true, $absolutePath);
        $this->assertSame(0660, fileperms($absolutePath) & 0777);
        $this->assertNotSame(0, fileperms(dirname($absolutePath)) & 0110, 'O reparo de arquivo não pode remover a travessia do diretório.');
        $this->assertSame(02770, config('filesystems.disks.documentos_test.permissions.dir.private'));
    }

    public function test_transferencia_bloqueia_identidade_vazia_antes_de_abrir_o_disco_sftp(): void
    {
        config([
            'documentos.disk' => 'documentos_test',
            'filesystems.disks.documentos_test' => [
                'driver' => 'sftp',
                'username' => '',
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DOCUMENTOS_SFTP_USERNAME não está configurado');

        (new TransferirDocumentosVenda($this->venda->id))->handle(
            app(DocumentoStatusService::class),
            app(VendaDocumentoPermissionPolicy::class)
        );
    }

    public function test_reparo_reaplica_modo_colaborativo_apenas_em_documentos_catalogados(): void
    {
        $this->configureCollaborativeDocumentDisk();
        $path = 'EmAnalise/Amil/Caragi Participacoes/contrato.pdf';
        Storage::disk('documentos_test')->put($path, 'conteudo', ['visibility' => 'private']);
        $absolutePath = Storage::disk('documentos_test')->path($path);
        chmod($absolutePath, 0600);

        VendaDocumento::create([
            'venda_id' => $this->venda->id,
            'empresa_id' => $this->venda->empresa_id,
            'uploaded_by' => $this->vendedor->id,
            'client_upload_id' => 'permissoes-existente',
            'nome_original' => 'contrato.pdf',
            'nome_remoto' => 'contrato.pdf',
            'mime_type' => 'application/pdf',
            'tamanho' => 8,
            'sha256' => hash('sha256', 'conteudo'),
            'caminho_temporario' => 'venda-documentos/temporario.upload',
            'diretorio_remoto' => dirname($path),
            'caminho_remoto' => $path,
            'status' => 'DISPONIVEL',
        ]);

        $this->artisan('documentos:reparar-permissoes', ['--apply' => true])
            ->expectsOutput('Reparo concluído: 1 ajustado(s), 0 ausente(s), 0 falha(s).')
            ->assertSuccessful();

        clearstatcache(true, $absolutePath);
        $this->assertSame(0660, fileperms($absolutePath) & 0777);
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

    private function configureCollaborativeDocumentDisk(): void
    {
        config([
            'documentos.disk' => 'documentos_test',
            'cache.default' => 'array',
            'filesystems.disks.documentos_test' => [
                'driver' => 'local',
                'root' => storage_path('framework/testing/disks/documentos-permissions'),
                'visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
                'directory_visibility' => VendaDocumentoPermissionPolicy::VISIBILITY,
                'permissions' => VendaDocumentoPermissionPolicy::permissionMap(),
                'throw' => true,
            ],
        ]);
        Storage::purge('documentos_test');
        Storage::disk('documentos_test')->deleteDirectory('EmAnalise');
    }
}
