<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\Concerns\RunsTenantFailureCallback;
use App\Jobs\Concerns\UsesTenantContext;
use App\Jobs\EnviarNotificacaoStatusContratoWhatsappJob;
use App\Jobs\EnviarResumoDiarioWhatsappJob;
use App\Jobs\EnviarReuniaoAgendadaWhatsappJob;
use App\Jobs\ExcluirVendaDocumentoRemoto;
use App\Jobs\GerarRecebiveisJob;
use App\Jobs\Middleware\UseTenantContext;
use App\Jobs\ProcessarVendaDocumento;
use App\Jobs\TransferirDocumentosVenda;
use App\Jobs\VerificarVendaDocumento;
use App\Jobs\Whatsapp\EnviarMensagemWhatsapp;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Vendas;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantJobContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_worker_resolve_tenant_from_sale_and_clears_context_after_job(): void
    {
        $empresa = Empresa::factory()->create();
        $outra = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $user->id,
            'nome_cliente' => 'Contato isolado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaId = DB::table('vendas')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato isolado',
            'data_vigencia' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $context = app(TenantContext::class);
        $context->set($outra->id);

        $executed = false;
        (new UseTenantContext)->handle((object) ['vendaId' => $vendaId], function () use (&$executed, $empresa, $vendaId, $context): void {
            $executed = true;
            $this->assertSame($empresa->id, $context->id());
            $this->assertSame($vendaId, Vendas::query()->sole()->id);
        });

        $this->assertTrue($executed);
        $this->assertFalse($context->isResolved());
    }

    public function test_worker_does_not_execute_job_when_tenant_reference_no_longer_exists(): void
    {
        $executed = false;

        (new UseTenantContext)->handle((object) ['vendaId' => 999999], function () use (&$executed): void {
            $executed = true;
        });

        $this->assertFalse($executed);
        $this->assertFalse(app(TenantContext::class)->isResolved());
    }

    public function test_receivables_job_declares_tenant_context_middleware(): void
    {
        $middleware = (new GerarRecebiveisJob(1))->middleware();

        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(UseTenantContext::class, $middleware[0]);
    }

    public function test_all_business_jobs_with_direct_tenant_reference_declare_context_middleware(): void
    {
        foreach ([
            EnviarNotificacaoStatusContratoWhatsappJob::class,
            EnviarResumoDiarioWhatsappJob::class,
            EnviarReuniaoAgendadaWhatsappJob::class,
            ExcluirVendaDocumentoRemoto::class,
            GerarRecebiveisJob::class,
            ProcessarVendaDocumento::class,
            TransferirDocumentosVenda::class,
            VerificarVendaDocumento::class,
        ] as $jobClass) {
            $this->assertContains(
                UsesTenantContext::class,
                class_uses_recursive($jobClass),
                "{$jobClass} precisa reinstalar o tenant no worker.",
            );
        }
    }

    public function test_callbacks_de_falha_reinstalam_e_limpam_o_tenant(): void
    {
        $empresa = Empresa::factory()->create();
        $outra = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresa->id,
            'user_import_id' => $user->id,
            'nome_cliente' => 'Contato da falha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $venda = Vendas::create([
            'empresa_id' => $empresa->id,
            'user_id' => $user->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato da falha',
            'data_vigencia' => today(),
        ]);

        $runner = new class
        {
            use RunsTenantFailureCallback;

            public function execute(int $vendaId, callable $callback): void
            {
                $this->runTenantFailureCallback(Vendas::class, $vendaId, $callback);
            }
        };
        app(TenantContext::class)->set($outra->id);
        $runner->execute($venda->id, function () use ($empresa, $venda): void {
            $this->assertSame($empresa->id, app(TenantContext::class)->id());
            $this->assertSame($venda->id, Vendas::query()->sole()->id);
        });

        $this->assertFalse(app(TenantContext::class)->isResolved());

        foreach ([
            VerificarVendaDocumento::class,
            ProcessarVendaDocumento::class,
            TransferirDocumentosVenda::class,
            EnviarMensagemWhatsapp::class,
        ] as $jobClass) {
            $this->assertContains(
                RunsTenantFailureCallback::class,
                class_uses_recursive($jobClass),
                "{$jobClass} precisa resolver o tenant também no callback failed().",
            );
        }
    }
}
