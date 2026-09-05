<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Imports\RawSheetImport;
use App\Models\CredencialAcesso;
use App\Models\Empresa;
use App\Models\Operadora;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Tests\TestCase;

class CredenciaisImportCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insert([
            'id' => UserRole::ADMINISTRATIVO,
            'tipo_usuario' => 'ADMINISTRATIVO',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_importa_layout_generico_e_aceita_master_como_autor_auditavel(): void
    {
        $empresa = Empresa::factory()->create();
        $empresaOrigem = Empresa::factory()->create();
        $master = User::factory()->create([
            'empresa_id' => $empresaOrigem->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'is_platform_admin' => true,
            'ativo' => 'Y',
        ]);
        $operadora = Operadora::create([
            'empresa_id' => $empresa->id,
            'nome' => 'OPERADORA CONFIGURÁVEL',
            'status' => 'Y',
        ]);
        [$planilha, $layout] = $this->arquivosTemporarios([
            'header_rows' => 1,
            'blocos' => [[
                'operadora_id' => $operadora->id,
                'colunas' => ['tipo' => 4, 'nome' => 1, 'login' => 3, 'senha' => 0, 'observacao' => [2]],
            ]],
        ]);

        Excel::shouldReceive('toArray')
            ->once()
            ->with(Mockery::type(RawSheetImport::class), $planilha)
            ->andReturn([[
                ['senha', 'nome', 'observação', 'login', 'tipo'],
                ['segredo', 'Cliente Exemplo', 'Conta principal', 'cliente@example.test', 'PORTAL'],
            ]]);

        try {
            $this->artisan('credenciais:importar', [
                'arquivo' => $planilha,
                'empresa_id' => $empresa->id,
                '--layout' => $layout,
                '--user' => $master->id,
            ])->assertSuccessful();
        } finally {
            @unlink($planilha);
            @unlink($layout);
        }

        $credencial = CredencialAcesso::withoutGlobalScopes()->sole();
        $this->assertSame($empresa->id, $credencial->empresa_id);
        $this->assertSame($operadora->id, $credencial->operadora_id);
        $this->assertSame('CLIENTE EXEMPLO', $credencial->nome);
        $this->assertSame('cliente@example.test', $credencial->login);
        $this->assertSame('segredo', $credencial->senha);
        $this->assertSame('Conta principal', $credencial->observacao);
        $this->assertDatabaseHas('credenciais_acesso_historico', [
            'empresa_id' => $empresa->id,
            'credencial_id' => $credencial->id,
            'user_id' => $master->id,
        ]);
    }

    public function test_rejeita_operadora_de_outra_empresa_antes_de_ler_planilha(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        User::factory()->create([
            'empresa_id' => $empresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $operadoraExterna = Operadora::create([
            'empresa_id' => $outraEmpresa->id,
            'nome' => 'OPERADORA EXTERNA',
            'status' => 'Y',
        ]);
        [$planilha, $layout] = $this->arquivosTemporarios([
            'header_rows' => 0,
            'blocos' => [[
                'operadora_id' => $operadoraExterna->id,
                'colunas' => ['tipo' => 0, 'nome' => 1, 'login' => 2, 'senha' => 3],
            ]],
        ]);

        Excel::shouldReceive('toArray')->never();

        try {
            $this->artisan('credenciais:importar', [
                'arquivo' => $planilha,
                'empresa_id' => $empresa->id,
                '--layout' => $layout,
            ])->assertFailed();
        } finally {
            @unlink($planilha);
            @unlink($layout);
        }

        $this->assertDatabaseCount('credenciais_acesso', 0);
    }

    public function test_rejeita_autor_comum_de_outra_empresa(): void
    {
        $empresa = Empresa::factory()->create();
        $outraEmpresa = Empresa::factory()->create();
        $autorExterno = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::ADMINISTRATIVO,
            'ativo' => 'Y',
        ]);
        $operadora = Operadora::create([
            'empresa_id' => $empresa->id,
            'nome' => 'OPERADORA LOCAL',
            'status' => 'Y',
        ]);
        [$planilha, $layout] = $this->arquivosTemporarios([
            'blocos' => [[
                'operadora_id' => $operadora->id,
                'colunas' => ['tipo' => 0, 'nome' => 1, 'login' => 2, 'senha' => 3],
            ]],
        ]);

        Excel::shouldReceive('toArray')->never();

        try {
            $this->artisan('credenciais:importar', [
                'arquivo' => $planilha,
                'empresa_id' => $empresa->id,
                '--layout' => $layout,
                '--user' => $autorExterno->id,
            ])->assertFailed();
        } finally {
            @unlink($planilha);
            @unlink($layout);
        }

        $this->assertDatabaseCount('credenciais_acesso', 0);
    }

    private function arquivosTemporarios(array $layout): array
    {
        $planilha = tempnam(sys_get_temp_dir(), 'credenciais-planilha-');
        $layoutPath = tempnam(sys_get_temp_dir(), 'credenciais-layout-');
        file_put_contents($planilha, 'arquivo simulado');
        file_put_contents($layoutPath, json_encode($layout, JSON_THROW_ON_ERROR));

        return [$planilha, $layoutPath];
    }
}
