<?php

namespace Tests\Feature\Backoffice;

use App\Enums\TabulationCode;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\User;
use App\Services\TabulationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Carteira de Clientes contém valores/faturamento: só ADMINISTRATIVO, BACKOFFICE
 * e DEVELOPER acessam — e o bloqueio é na rota (não só no menu).
 */
class CarteiraClientesAcessoTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    private int $implantadoId;

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
        $catalog = app(TabulationCatalog::class);
        $catalog->provision($this->empresa->id);
        $this->implantadoId = $catalog->id($this->empresa->id, TabulationCode::IMPLANTADO);
    }

    private function user(int $roleId): User
    {
        return User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => $roleId,
            'ativo' => 'Y',
        ]);
    }

    public function test_administrador_backoffice_e_developer_acessam(): void
    {
        foreach ([UserRole::ADMINISTRATIVO, UserRole::BACKOFFICE, UserRole::DEVELOPER] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('backoffice.carteiraClientes'))
                ->assertOk();
        }
    }

    public function test_tela_prioriza_implantados_recentes_e_mantem_consulta_da_carteira(): void
    {
        $response = $this->actingAs($this->user(UserRole::BACKOFFICE))
            ->get(route('backoffice.carteiraClientes'));

        $response->assertOk()
            ->assertSee('Contratos implantados recentemente')
            ->assertSee('data-cc-view="recentes"', false)
            ->assertSee('data-cc-view="carteira"', false)
            ->assertSee('data-period="30"', false)
            ->assertSee('data-period="60"', false)
            ->assertSee('data-period="365"', false);
    }

    public function test_endpoint_mapeia_implantados_por_periodo_backoffice_e_acoes_pendentes(): void
    {
        $admin = $this->user(UserRole::ADMINISTRATIVO);
        $backoffice = $this->user(UserRole::BACKOFFICE);
        $vendedor = $this->user(UserRole::VENDEDOR);

        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $vendedor->id,
            'nome_cliente' => 'Cliente Implantado',
            'cpf' => '12345678900',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vendaRecente = $this->criarVendaImplantada($vendedor, $backoffice, $contatoId, now()->subDays(10), '11.222.333/0001-44');
        $this->criarVendaImplantada($vendedor, $backoffice, $contatoId, now()->subDays(90), '55.666.777/0001-88');

        DB::table('vendas_portabilidades')->insert([
            'venda_id' => $vendaRecente,
            'nome' => 'Titular Portabilidade',
            'sequencial' => 1,
            'status' => 'PENDENTE',
            'fase' => 'REUNINDO_DOCUMENTOS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('venda_demandas')->insert([
            'venda_id' => $vendaRecente,
            'empresa_id' => $this->empresa->id,
            'created_by' => $vendedor->id,
            'origem' => 'VENDEDOR',
            'tipo' => TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
            'titulo' => 'Cancelar plano anterior',
            'status' => 'PENDENTE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('backoffice.getCarteiraClientesData', [
            'visao' => 'recentes',
            'periodo' => 30,
            'backoffice' => $backoffice->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('visao', 'recentes')
            ->assertJsonCount(1, 'contratos')
            ->assertJsonPath('contratos.0.id', $vendaRecente)
            ->assertJsonPath('contratos.0.backoffice', $backoffice->name)
            ->assertJsonPath('contratos.0.portabilidades_pendentes', 1)
            ->assertJsonPath('contratos.0.cancelamentos_pendentes', 1)
            ->assertJsonPath('contratos.0.precisa_atencao', true)
            ->assertJsonPath('kpis.implantados', 1)
            ->assertJsonPath('kpis.atencao', 1)
            ->assertJsonPath('kpis.portabilidades', 1)
            ->assertJsonPath('kpis.cancelamentos', 1);

        $this->actingAs($admin)->getJson(route('backoffice.getCarteiraClientesData', [
            'visao' => 'recentes',
            'periodo' => 365,
        ]))->assertOk()->assertJsonPath('kpis.implantados', 2);

        $this->actingAs($admin)->getJson(route('backoffice.getCarteiraClientesData', [
            'visao' => 'carteira',
        ]))->assertOk()->assertJsonCount(2, 'clientes');
    }

    public function test_relacionamentos_inconsistentes_de_outro_tenant_nao_vazam_na_carteira(): void
    {
        $admin = $this->user(UserRole::ADMINISTRATIVO);
        $vendedor = $this->user(UserRole::VENDEDOR);
        $outraEmpresa = Empresa::factory()->create();
        $backofficeExterno = User::factory()->create([
            'empresa_id' => $outraEmpresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $vendedor->id,
            'nome_cliente' => 'Cliente com relação inconsistente',
            'cpf' => '98765432100',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $vendaId = $this->criarVendaImplantada(
            $vendedor,
            $backofficeExterno,
            $contatoId,
            now()->subDay(),
            '77.888.999/0001-00'
        );
        DB::table('venda_demandas')->insert([
            'venda_id' => $vendaId,
            'empresa_id' => $outraEmpresa->id,
            'created_by' => $backofficeExterno->id,
            'origem' => 'BACKOFFICE',
            'tipo' => TipoDemandaContrato::CANCELAMENTO->value,
            'titulo' => 'Demanda externa inconsistente',
            'status' => 'PENDENTE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson(route('backoffice.getCarteiraClientesData', [
            'visao' => 'recentes',
            'periodo' => 30,
        ]));

        $response->assertOk()
            ->assertJsonPath('contratos.0.id', $vendaId)
            ->assertJsonPath('contratos.0.backoffice', 'Sem responsável')
            ->assertJsonPath('contratos.0.cancelamentos_pendentes', 0)
            ->assertJsonPath('kpis.cancelamentos', 0);
        $response->assertDontSee($backofficeExterno->name);
        $response->assertDontSee('Demanda externa inconsistente');
    }

    public function test_demais_papeis_recebem_403(): void
    {
        foreach ([UserRole::VENDEDOR, UserRole::SUPERVISOR] as $role) {
            $this->actingAs($this->user($role))
                ->get(route('backoffice.carteiraClientes'))
                ->assertStatus(403);

            // Endpoints de dados (com os valores) também bloqueados.
            $this->actingAs($this->user($role))
                ->getJson(route('backoffice.getCarteiraClientesData'))
                ->assertStatus(403);
        }
    }

    private function criarVendaImplantada(User $vendedor, User $backoffice, int $contatoId, $dataImplantacao, string $cnpj): int
    {
        return DB::table('vendas')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_id' => $vendedor->id,
            'backoffice_id' => $backoffice->id,
            'contato_id' => $contatoId,
            'tabulacao_id' => $this->implantadoId,
            'nome_contrato' => 'Empresa '.$cnpj,
            'cpf_cnpj' => $cnpj,
            'numero_proposta' => 'PROP-'.$cnpj,
            'data_vigencia' => $dataImplantacao,
            'data_implantacao' => $dataImplantacao,
            'operadora' => 'AMIL',
            'nome_plano' => 'Plano Empresarial',
            'valor_contrato' => 1500,
            'vidas' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
