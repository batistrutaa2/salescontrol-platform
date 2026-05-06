<?php

namespace Tests\Feature\Comercial;

use App\Enums\UserRole;
use App\Jobs\EnviarReuniaoAgendadaWhatsappJob;
use App\Models\ComercialReunioes;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgendarReuniaoWhatsappTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private User $vendedor;

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            ['id' => UserRole::VENDEDOR, 'tipo_usuario' => 'VENDEDOR', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::ADMINISTRATIVO, 'tipo_usuario' => 'ADMINISTRATIVO', 'created_at' => now(), 'updated_at' => now()],
            ['id' => UserRole::SUPERVISOR, 'tipo_usuario' => 'SUPERVISOR', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();

        $this->vendedor = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::VENDEDOR,
            'ativo' => 'Y',
        ]);

        $this->manager = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::SUPERVISOR,
            'ativo' => 'Y',
            'whatsapp' => '(11) 99999-8888',
        ]);
    }

    public function test_agendar_reuniao_dispara_job_de_whatsapp_para_o_manager_selecionado(): void
    {
        Bus::fake();

        $payload = [
            'titulo' => 'Reunião com lead premium',
            'manager_id' => $this->manager->id,
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'https://meet.google.com/abc-defg-hij',
            'observacao' => 'Cliente quer plano empresarial',
        ];

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', $payload);

        $response->assertStatus(200)->assertJson(['status' => 'success']);

        $reuniao = ComercialReunioes::where('empresa_id', $this->empresa->id)->latest('id')->first();
        $this->assertNotNull($reuniao);
        $this->assertSame($this->vendedor->id, $reuniao->user_id);
        $this->assertSame($this->manager->id, $reuniao->manager_id);

        Bus::assertDispatched(
            EnviarReuniaoAgendadaWhatsappJob::class,
            fn (EnviarReuniaoAgendadaWhatsappJob $job) => $job->reuniaoId === $reuniao->id,
        );
    }

    public function test_request_invalido_nao_dispara_job(): void
    {
        Bus::fake();

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', [
            // titulo ausente, manager_id ausente
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        ]);

        $this->assertNotEquals(200, $response->status(), 'request inválido não deveria retornar 200');
        $this->assertSame(0, ComercialReunioes::count(), 'nenhuma reunião deveria ter sido persistida');
        Bus::assertNotDispatched(EnviarReuniaoAgendadaWhatsappJob::class);
    }

    public function test_manager_de_outra_empresa_nao_dispara_job(): void
    {
        Bus::fake();

        $outraEmpresa = Empresa::factory()->create();
        $managerOutraEmpresa = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
            'whatsapp' => '(11) 88888-7777',
        ]);

        $response = $this->actingAs($this->vendedor)->postJson('/reunioes', [
            'titulo' => 'Tentativa cross-empresa',
            'manager_id' => $managerOutraEmpresa->id,
            'data_inicio' => now()->addDay()->format('Y-m-d H:i:s'),
            'data_final' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'location' => 'Sala 1',
        ]);

        $response->assertStatus(422);
        Bus::assertNotDispatched(EnviarReuniaoAgendadaWhatsappJob::class);
    }
}
