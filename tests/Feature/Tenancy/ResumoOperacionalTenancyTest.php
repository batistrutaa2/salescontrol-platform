<?php

namespace Tests\Feature\Tenancy;

use App\Models\Empresa;
use App\Models\User;
use App\Repositories\Eloquent\ResumoOperacionalRepository;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResumoOperacionalTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_venda_com_relacao_adulterada_de_outro_tenant_nao_oculta_agendamento(): void
    {
        $empresaA = Empresa::factory()->create(['nome_fantasia' => 'Empresa A']);
        $empresaB = Empresa::factory()->create(['nome_fantasia' => 'Empresa B']);
        $usuarioA = User::factory()->create(['empresa_id' => $empresaA->id]);
        $usuarioB = User::factory()->create(['empresa_id' => $empresaB->id]);
        $contatoA = DB::table('contatos')->insertGetId([
            'empresa_id' => $empresaA->id,
            'user_import_id' => $usuarioA->id,
            'nome_cliente' => 'Contato da Empresa A',
            'status' => 'Y',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $amanha = Carbon::today()->addDay();

        DB::table('agendamentos')->insert([
            'empresa_id' => $empresaA->id,
            'user_id' => $usuarioA->id,
            'contato_id' => $contatoA,
            'horario_agendamento' => $amanha->copy()->setTime(10, 0),
            'observacao' => 'Agendamento legítimo',
            'notificado' => 'N',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendas')->insert([
            'empresa_id' => $empresaB->id,
            'user_id' => $usuarioB->id,
            'contato_id' => $contatoA,
            'nome_contrato' => 'Relação externa adulterada',
            'data_vigencia' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $repository = app(ResumoOperacionalRepository::class);
        $context = app(TenantContext::class);
        $this->assertSame(1, $context->run(
            $empresaA->id,
            fn () => $repository->agendamentosAmanha($empresaA->id, Carbon::today()),
        ));

        DB::table('vendas')->insert([
            'empresa_id' => $empresaA->id,
            'user_id' => $usuarioA->id,
            'contato_id' => $contatoA,
            'nome_contrato' => 'Venda legítima',
            'data_vigencia' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $context->run(
            $empresaA->id,
            fn () => $repository->agendamentosAmanha($empresaA->id, Carbon::today()),
        ));
    }
}
