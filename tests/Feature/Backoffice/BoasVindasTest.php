<?php

namespace Tests\Feature\Backoffice;

use App\Enums\UserRole;
use App\Mail\BoasVindasMail;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use App\Models\Vendas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Boas-vindas: a mensagem/e-mail agora aceita VÁRIOS acessos ao aplicativo
 * (login/senha), com fallback para o par único legado (login_app/senha_app).
 */
class BoasVindasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $empresa = Empresa::factory()->create();
        $this->user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
    }

    public function test_preview_email_renderiza_varios_acessos_do_app(): void
    {
        $resp = $this->actingAs($this->user)->post(route('backoffice.previewEmailBoasVindas'), [
            'tipo_envio' => 'padrao',
            'operadora' => 'AMIL',
            'nome_contrato' => 'ACME LTDA',
            'acessos_app' => [
                ['rotulo' => 'Titular', 'login' => 'maria@ex.com', 'senha' => 'Saude2026'],
                ['rotulo' => 'Dependente', 'login' => 'joao@ex.com', 'senha' => 'Saude2027'],
            ],
        ]);

        $resp->assertOk()
            ->assertSee('maria@ex.com')->assertSee('Saude2026')
            ->assertSee('joao@ex.com')->assertSee('Saude2027')
            ->assertSee('TITULAR')->assertSee('DEPENDENTE'); // rótulo em maiúsculas
    }

    public function test_preview_email_fallback_para_login_unico_legado(): void
    {
        $resp = $this->actingAs($this->user)->post(route('backoffice.previewEmailBoasVindas'), [
            'tipo_envio' => 'padrao',
            'operadora' => 'AMIL',
            'login_app' => 'unico@ex.com',
            'senha_app' => 'Senha123',
        ]);

        $resp->assertOk()->assertSee('unico@ex.com')->assertSee('Senha123');
    }

    // =====================================================================
    // Registro do envio — o cabeçalho do contrato mostra data/autor e o
    // reenvio continua permitido, entrando no histórico como reenvio.
    // =====================================================================

    private function criarVenda(array $overrides = []): Vendas
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->user->empresa_id,
            'user_import_id' => $this->user->id,
            'cpf' => '12345678900',
            'nome_cliente' => 'Cliente Teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Vendas::create(array_merge([
            'empresa_id' => $this->user->empresa_id,
            'user_id' => $this->user->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'ACME LTDA',
            'cpf_cnpj' => '12345678000190',
            'operadora' => 'AMIL',
            'nome_plano' => 'Plano X',
            'valor_contrato' => 1500.00,
            'vidas' => 2,
            'data_vigencia' => now(),
        ], $overrides));
    }

    private function payloadRegistro(int $vendaId): array
    {
        // 'sem_whatsapp' = apenas registrar, sem disparar canal externo.
        return [
            'venda_id' => $vendaId,
            'tipo_envio' => 'sem_whatsapp',
            'canais' => [],
            'beneficiarios' => [['nome' => 'Maria', 'codigo' => '123']],
        ];
    }

    public function test_primeiro_envio_registra_data_e_autor(): void
    {
        $venda = $this->criarVenda();

        $resp = $this->actingAs($this->user)
            ->postJson(route('backoffice.marcarBoasVindas'), $this->payloadRegistro($venda->id));

        $resp->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reenvio', false);

        $venda->refresh();
        $this->assertNotNull($venda->boas_vindas_enviado_em);
        $this->assertSame($this->user->id, $venda->boas_vindas_enviado_por);

        $this->assertDatabaseHas('pos_venda_anotacoes', [
            'venda_id' => $venda->id,
            'descricao' => 'Boas Vindas registrado (sem envio).',
        ]);
    }

    public function test_emails_de_boas_vindas_colocam_somente_o_vendedor_em_copia(): void
    {
        Mail::fake();

        $vendedor = User::factory()->create([
            'empresa_id' => $this->user->empresa_id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
            'email' => 'vendedor@corretora.test',
        ]);
        $venda = $this->criarVenda(['user_id' => $vendedor->id]);

        $payload = $this->payloadRegistro($venda->id);
        $payload['canais'] = ['email'];
        $payload['destinatarios_email'] = [
            ['nome' => 'Cliente Teste', 'email' => 'cliente@example.com'],
            ['nome' => 'Dependente Teste', 'email' => 'dependente@example.com'],
        ];

        $this->actingAs($this->user)
            ->postJson(route('backoffice.marcarBoasVindas'), $payload)
            ->assertOk()
            ->assertJsonPath('success', true);

        Mail::assertSent(BoasVindasMail::class, 2);

        foreach (['cliente@example.com', 'dependente@example.com'] as $destinatario) {
            Mail::assertSent(BoasVindasMail::class, function (BoasVindasMail $mail) use ($destinatario) {
                return $mail->hasTo($destinatario)
                    && $mail->hasCc('vendedor@corretora.test')
                    && ! $mail->hasCc('implantacao@lkbrokers.com');
            });
        }
    }

    public function test_reenvio_atualiza_o_registro_e_preserva_o_historico(): void
    {
        $outroUsuario = User::factory()->create([
            'empresa_id' => $this->user->empresa_id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);

        $envioAnterior = now()->subDays(5);
        $venda = $this->criarVenda([
            'boas_vindas_enviado_em' => $envioAnterior,
            'boas_vindas_enviado_por' => $outroUsuario->id,
        ]);

        $resp = $this->actingAs($this->user)
            ->postJson(route('backoffice.marcarBoasVindas'), $this->payloadRegistro($venda->id));

        $resp->assertOk()->assertJsonPath('reenvio', true);

        $venda->refresh();
        $this->assertTrue($venda->boas_vindas_enviado_em->greaterThan($envioAnterior));
        $this->assertSame($this->user->id, $venda->boas_vindas_enviado_por);

        // O envio anterior continua no histórico — a anotação do reenvio é um registro novo.
        $this->assertDatabaseHas('pos_venda_anotacoes', [
            'venda_id' => $venda->id,
            'descricao' => 'Boas Vindas registrado novamente (sem envio).',
        ]);
    }

    public function test_endpoint_de_beneficiarios_informa_status_das_boas_vindas(): void
    {
        $semEnvio = $this->criarVenda();
        $this->actingAs($this->user)
            ->getJson(route('backoffice.getBeneficiariosParaBoasVindas', $semEnvio->id))
            ->assertOk()
            ->assertJsonPath('venda.boas_vindas_enviado', false)
            ->assertJsonPath('venda.boas_vindas_enviado_em', null);

        $comEnvio = $this->criarVenda([
            'boas_vindas_enviado_em' => now()->setTime(14, 32),
            'boas_vindas_enviado_por' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.getBeneficiariosParaBoasVindas', $comEnvio->id))
            ->assertOk()
            ->assertJsonPath('venda.boas_vindas_enviado', true)
            ->assertJsonPath('venda.boas_vindas_enviado_em', now()->setTime(14, 32)->format('d/m/Y H:i'))
            ->assertJsonPath('venda.boas_vindas_enviado_por', $this->user->name);
    }

    public function test_endpoint_retorna_links_configurados_sem_inferir_nome_da_operadora(): void
    {
        $operadora = Operadora::create([
            'empresa_id' => $this->user->empresa_id,
            'nome' => 'OPERADORA PERSONALIZADA',
            'status' => 'Y',
            'app_ios_url' => 'https://apps.apple.com/app/id987654',
            'app_android_url' => 'https://play.google.com/store/apps/details?id=tenant.app',
        ]);
        $venda = $this->criarVenda([
            'operadora_id' => $operadora->id,
            'operadora' => 'TEXTO ANTIGO DIFERENTE',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.getBeneficiariosParaBoasVindas', $venda->id))
            ->assertOk()
            ->assertJsonPath('venda.app_links.ios', $operadora->app_ios_url)
            ->assertJsonPath('venda.app_links.android', $operadora->app_android_url);
    }

    public function test_endpoint_nao_expoe_links_de_operadora_de_outro_tenant_em_vinculo_adulterado(): void
    {
        $outraEmpresa = Empresa::factory()->create();
        $operadoraExterna = Operadora::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'OPERADORA EXTERNA',
            'status' => 'Y',
            'app_ios_url' => 'https://secret.example.test/ios',
            'app_android_url' => 'https://secret.example.test/android',
        ]);
        $venda = $this->criarVenda(['operadora_id' => $operadoraExterna->id]);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.getBeneficiariosParaBoasVindas', $venda->id))
            ->assertOk()
            ->assertJsonPath('venda.app_links.ios', '')
            ->assertJsonPath('venda.app_links.android', '');
    }
}
