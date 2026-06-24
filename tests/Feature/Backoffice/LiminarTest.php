<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Models\CancelamentoLiminar;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use App\Models\VendaTitular;
use App\Notifications\LiminarStatusAlterado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LiminarTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private User $backoffice;
    private User $vendedor;
    private User $advogada;
    private int $contatoId;
    private Vendas $venda;
    private VendaTitular $titular;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::DEVELOPER, 'tipo_usuario' => 'DEVELOPER', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADVOGADA, 'tipo_usuario' => 'ADVOGADA', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();

        $this->admin = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADMINISTRATIVO, 'ativo' => 'Y']);
        $this->backoffice = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::BACKOFFICE, 'ativo' => 'Y']);
        $this->vendedor = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::VENDEDOR, 'ativo' => 'Y']);
        $this->advogada = User::factory()->create(['empresa_id' => $this->empresa->id, 'user_role_id' => UserRole::ADVOGADA, 'ativo' => 'Y']);

        $this->contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->vendedor->id,
            'cpf' => '12345678900',
            'nome_cliente' => 'Cliente Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->venda = Vendas::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->vendedor->id,
            'contato_id' => $this->contatoId,
            'cpf_cnpj' => '12345678900',
            'nome_contrato' => 'Cliente Teste',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
        ]);

        $this->titular = VendaTitular::create([
            'venda_id' => $this->venda->id,
            'nome' => 'Titular Teste',
            'cpf' => '12345678900',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'venda_id'                       => $this->venda->id,
            'beneficiario_tipo'              => 'TITULAR',
            'beneficiario_id'                => $this->titular->id,
            'responsavel_id'                 => $this->backoffice->id,
            'nome_empresa'                   => 'MD4 Consultoria LTDA',
            'cnpj'                           => '43.685.447/0001-54',
            'protocolo_cancelamento'         => '998877',
            'email_procuracao'               => 'cliente@example.com',
            'data_fim_plano'                 => '31/12/2024',
            'data_contratacao'               => '01/01/2023',
            'data_solicitacao_cancelamento'  => '15/11/2024',
            'data_ultimo_pagamento_boleto'   => '10/11/2024',
            'cobertura_comprovante_inicio'   => '01/11/2024',
            'cobertura_comprovante_fim'      => '30/11/2024',
            'data_vencimento_boleto_1'       => '10/12/2024',
            'data_vencimento_boleto_2'       => '10/01/2025',
            'doc_contrato_social'            => UploadedFile::fake()->create('contrato_social.pdf', 100, 'application/pdf'),
            'doc_cartao_cnpj'                => UploadedFile::fake()->create('cartao_cnpj.pdf', 100, 'application/pdf'),
            'doc_rg_cliente'                 => UploadedFile::fake()->create('rg.pdf', 100, 'application/pdf'),
            'doc_comprovante_pagamento'      => UploadedFile::fake()->create('comprovante.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    public function test_backoffice_abre_processo_com_documentacao_completa(): void
    {
        Storage::fake('s3');

        $resp = $this->actingAs($this->admin)
            ->post(route('backoffice.liminar.store'), $this->payload());

        $resp->assertOk()->assertJson(['success' => true]);

        $liminar = CancelamentoLiminar::first();
        $this->assertDatabaseHas('cancelamentos_liminares', [
            'id'                     => $liminar->id,
            'empresa_id'             => $this->empresa->id,
            'venda_id'               => $this->venda->id,
            'fase'                   => 'CANCELAMENTO_ABERTO',
            'nome_empresa'           => 'MD4 Consultoria LTDA',
            'protocolo_cancelamento' => '998877',
            'data_fim_plano'         => '2024-12-31',
        ]);

        // Os 4 documentos obrigatórios foram persistidos no S3 e no banco.
        $this->assertSame(4, $liminar->documentos()->count());
        Storage::disk('s3')->assertExists("liminares/{$this->empresa->id}/{$liminar->id}/contrato_social.pdf");

        // Histórico de abertura.
        $this->assertDatabaseHas('cancelamentos_liminares_historico', [
            'cancelamento_liminar_id' => $liminar->id,
            'campo_alterado'          => 'fase',
            'valor_novo'              => 'Cancelamento Aberto',
        ]);
    }

    public function test_abre_processo_com_documentos_opcionais(): void
    {
        Storage::fake('s3');

        $resp = $this->actingAs($this->admin)
            ->post(route('backoffice.liminar.store'), $this->payload([
                'doc_print_protocolo' => UploadedFile::fake()->create('print.png', 80, 'image/png'),
                'doc_audio_hapvida'   => UploadedFile::fake()->create('ligacao.mp3', 300, 'audio/mpeg'),
            ]));

        $resp->assertOk()->assertJson(['success' => true]);

        $liminar = CancelamentoLiminar::first();
        // 4 obrigatórios + 2 opcionais.
        $this->assertSame(6, $liminar->documentos()->count());
        Storage::disk('s3')->assertExists("liminares/{$this->empresa->id}/{$liminar->id}/print_protocolo.png");
        Storage::disk('s3')->assertExists("liminares/{$this->empresa->id}/{$liminar->id}/audio_hapvida.mp3");
        $this->assertDatabaseHas('cancelamentos_liminares_documentos', [
            'cancelamento_liminar_id' => $liminar->id,
            'tipo_documento'          => 'AUDIO_HAPVIDA',
        ]);
    }

    public function test_upload_avulso_aceita_audio_no_tipo_hapvida(): void
    {
        Storage::fake('s3');
        $liminar = $this->criarLiminar();

        $this->actingAs($this->admin)
            ->post(route('backoffice.liminar.uploadDocumento', $liminar->id), [
                'tipo_documento' => 'AUDIO_HAPVIDA',
                'arquivo'        => UploadedFile::fake()->create('audio.mp3', 200, 'audio/mpeg'),
            ])
            ->assertOk();

        Storage::disk('s3')->assertExists("liminares/{$this->empresa->id}/{$liminar->id}/audio_hapvida.mp3");
    }

    public function test_upload_avulso_rejeita_audio_em_tipo_de_imagem(): void
    {
        Storage::fake('s3');
        $liminar = $this->criarLiminar();

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.liminar.uploadDocumento', $liminar->id), [
                'tipo_documento' => 'CONTRATO_SOCIAL',
                'arquivo'        => UploadedFile::fake()->create('audio.mp3', 200, 'audio/mpeg'),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['arquivo']);
    }

    public function test_store_valida_campos_obrigatorios(): void
    {
        Storage::fake('s3');

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.liminar.store'), $this->payload([
                'nome_empresa'             => '',
                'protocolo_cancelamento'   => '',
                'data_fim_plano'           => '',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome_empresa', 'protocolo_cancelamento', 'data_fim_plano']);
    }

    public function test_store_exige_documentos(): void
    {
        Storage::fake('s3');

        $payload = $this->payload();
        unset($payload['doc_contrato_social']);

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.liminar.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['doc_contrato_social']);
    }

    public function test_advogada_nao_pode_abrir_processo(): void
    {
        Storage::fake('s3');

        $this->actingAs($this->advogada)
            ->postJson(route('backoffice.liminar.store'), $this->payload())
            ->assertForbidden();
    }

    public function test_vendedor_nao_acessa_kanban(): void
    {
        $this->actingAs($this->vendedor)
            ->get(route('backoffice.liminar.index'))
            ->assertForbidden();
    }

    public function test_advogada_acessa_kanban(): void
    {
        $this->actingAs($this->advogada)
            ->get(route('backoffice.liminar.index'))
            ->assertOk();
    }

    public function test_advogada_fica_restrita_ao_kanban(): void
    {
        // Rota fora do escopo da liminar é redirecionada de volta ao kanban.
        $this->actingAs($this->advogada)
            ->get(route('home.dashboard'))
            ->assertRedirect(route('backoffice.liminar.index'));
    }

    public function test_mover_altera_fase_grava_historico_e_notifica(): void
    {
        Notification::fake();

        $liminar = $this->criarLiminar();

        // Admin (nem responsável nem corretor) move → notifica os dois.
        $this->actingAs($this->admin)
            ->postJson(route('backoffice.liminar.mover', $liminar->id), ['fase' => 'AGUARDANDO_ASSINATURA'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('cancelamentos_liminares', [
            'id'   => $liminar->id,
            'fase' => 'AGUARDANDO_ASSINATURA',
        ]);
        $this->assertDatabaseHas('cancelamentos_liminares_historico', [
            'cancelamento_liminar_id' => $liminar->id,
            'campo_alterado'          => 'fase',
            'valor_novo'              => 'Procuração / Aguardando Assinatura',
        ]);

        Notification::assertSentTo($this->backoffice, LiminarStatusAlterado::class);
        Notification::assertSentTo($this->vendedor, LiminarStatusAlterado::class);
    }

    public function test_advogada_pode_mover(): void
    {
        Notification::fake();
        $liminar = $this->criarLiminar();

        $this->actingAs($this->advogada)
            ->postJson(route('backoffice.liminar.mover', $liminar->id), ['fase' => 'LIMINAR_CONCEDIDA'])
            ->assertOk();

        $this->assertDatabaseHas('cancelamentos_liminares', [
            'id'     => $liminar->id,
            'fase'   => 'LIMINAR_CONCEDIDA',
            'status' => 'CONCLUIDA',
        ]);
    }

    public function test_mover_valida_fase_invalida(): void
    {
        $liminar = $this->criarLiminar();

        $this->actingAs($this->admin)
            ->postJson(route('backoffice.liminar.mover', $liminar->id), ['fase' => 'FASE_INEXISTENTE'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fase']);
    }

    public function test_multitenant_nao_move_processo_de_outra_empresa(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $adminOutra = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $liminar = $this->criarLiminar();

        $this->actingAs($adminOutra)
            ->postJson(route('backoffice.liminar.mover', $liminar->id), ['fase' => 'AGUARDANDO_ASSINATURA'])
            ->assertNotFound();

        $this->actingAs($adminOutra)
            ->getJson(route('backoffice.liminar.show', $liminar->id))
            ->assertNotFound();
    }

    public function test_busca_contratos_por_nome_e_cpf(): void
    {
        // Por nome
        $this->actingAs($this->admin)
            ->getJson(route('backoffice.liminar.buscarContratos', ['q' => 'Cliente']))
            ->assertOk()
            ->assertJsonFragment(['id' => $this->venda->id, 'nome_contrato' => 'Cliente Teste']);

        // Por CPF (com máscara — o backend normaliza dígitos)
        $this->actingAs($this->admin)
            ->getJson(route('backoffice.liminar.buscarContratos', ['q' => '123.456.789-00']))
            ->assertOk()
            ->assertJsonFragment(['id' => $this->venda->id]);
    }

    public function test_busca_contratos_exige_minimo_de_caracteres(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('backoffice.liminar.buscarContratos', ['q' => 'a']))
            ->assertOk()
            ->assertJsonPath('contratos', []);
    }

    public function test_busca_contratos_respeita_multitenancy(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $adminOutra = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $this->actingAs($adminOutra)
            ->getJson(route('backoffice.liminar.buscarContratos', ['q' => 'Cliente']))
            ->assertOk()
            ->assertJsonPath('contratos', []);
    }

    private function criarLiminar(): CancelamentoLiminar
    {
        return CancelamentoLiminar::create([
            'empresa_id'        => $this->empresa->id,
            'venda_id'          => $this->venda->id,
            'beneficiario_tipo' => 'TITULAR',
            'beneficiario_id'   => $this->titular->id,
            'nome_contrato'     => $this->venda->nome_contrato,
            'responsavel_id'    => $this->backoffice->id,
            'status'            => 'EM_EXECUCAO',
            'fase'              => 'CANCELAMENTO_ABERTO',
        ]);
    }
}
