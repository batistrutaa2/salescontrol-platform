<?php

namespace Tests\Unit\Services;

use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Models\Empresa;
use App\Models\PosVendaDemandaTemplate;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\Vendas;
use App\Services\PosVendaDemandaService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PosVendaDemandaServiceTest extends TestCase
{
    use RefreshDatabase;

    private PosVendaDemandaService $service;

    private Empresa $empresa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PosVendaDemandaService();

        DB::table('user_roles')->insert([
            ['id' => UserRole::BACKOFFICE, 'tipo_usuario' => 'BACKOFFICE', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->empresa = Empresa::factory()->create();
        $this->user = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'user_role_id' => UserRole::BACKOFFICE,
            'ativo' => 'Y',
        ]);
        app(TenantContext::class)->set($this->empresa->id);
    }

    private function criarVenda(): Vendas
    {
        $contatoId = DB::table('contatos')->insertGetId([
            'empresa_id' => $this->empresa->id,
            'user_import_id' => $this->user->id,
            'nome_cliente' => 'Cliente Teste',
            'cpf' => (string) random_int(10000000000, 99999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Vendas::create([
            'empresa_id' => $this->empresa->id,
            'user_id' => $this->user->id,
            'backoffice_id' => $this->user->id,
            'contato_id' => $contatoId,
            'nome_contrato' => 'Contrato Teste',
            'cpf_cnpj' => '12345678000199',
            'operadora' => 'AMIL',
            'valor_contrato' => 500.00,
            'vidas' => 1,
            'data_vigencia' => now(),
        ]);
    }

    public function test_gera_apenas_os_templates_marcados_como_automaticos(): void
    {
        $venda = $this->criarVenda();

        // Sem seleção explícita (null) -> usa o padrão (gerar_automatico).
        $criadas = $this->service->gerarParaVenda($venda, null, $this->user->id);

        $automaticos = collect(PosVendaDemandaTemplate::defaults())
            ->where('gerar_automatico', true)
            ->count();

        $this->assertSame($automaticos, $criadas);
        $this->assertSame($automaticos, VendaDemanda::where('venda_id', $venda->id)->count());

        // Um tipo situacional (gerar_automatico=false) não deve ser gerado.
        $this->assertDatabaseMissing('venda_demandas', [
            'venda_id' => $venda->id,
            'tipo' => TipoDemandaContrato::ENVIO_BOLETO->value,
        ]);

        // Um tipo do núcleo deve existir.
        $this->assertDatabaseHas('venda_demandas', [
            'venda_id' => $venda->id,
            'tipo' => TipoDemandaContrato::ACESSO_EMPRESA->value,
            'status' => 'PENDENTE',
        ]);
    }

    public function test_e_idempotente_nao_duplica_em_reimplantacao(): void
    {
        $venda = $this->criarVenda();

        $primeira = $this->service->gerarParaVenda($venda, null, $this->user->id);
        $segunda = $this->service->gerarParaVenda($venda, null, $this->user->id);

        $this->assertGreaterThan(0, $primeira);
        $this->assertSame(0, $segunda, 'Re-implantar não deve gerar demandas novamente.');
        $this->assertSame($primeira, VendaDemanda::where('venda_id', $venda->id)->count());
    }

    public function test_respeita_templates_personalizados_da_empresa(): void
    {
        // Empresa já tem templates: apenas 1 ativo+automatico.
        PosVendaDemandaTemplate::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => TipoDemandaContrato::ACESSO_EMPRESA->value,
            'titulo' => 'Único item gerado',
            'gerar_automatico' => true,
            'ativo' => true,
            'ordem' => 0,
        ]);
        PosVendaDemandaTemplate::create([
            'empresa_id' => $this->empresa->id,
            'tipo' => TipoDemandaContrato::BOAS_VINDAS->value,
            'titulo' => 'Desativado',
            'gerar_automatico' => true,
            'ativo' => false,
            'ordem' => 1,
        ]);

        $venda = $this->criarVenda();
        $criadas = $this->service->gerarParaVenda($venda, null, $this->user->id);

        // seedDefaults NÃO roda (empresa já tem templates); só o ativo+automatico gera.
        $this->assertSame(1, $criadas);
        $this->assertSame(1, VendaDemanda::where('venda_id', $venda->id)->count());
    }

    public function test_gera_somente_os_templates_escolhidos_na_implantacao(): void
    {
        // Empresa sem templates -> seedDefaults roda dentro do service.
        $venda = $this->criarVenda();
        PosVendaDemandaTemplate::seedDefaults($this->empresa->id);

        $escolhidos = PosVendaDemandaTemplate::where('empresa_id', $this->empresa->id)
            ->whereIn('tipo', [
                TipoDemandaContrato::ACESSO_EMPRESA->value,
                TipoDemandaContrato::ENVIO_BOLETO->value, // situacional, normalmente não-automático
            ])
            ->pluck('id')
            ->all();

        $criadas = $this->service->gerarParaVenda($venda, $escolhidos, $this->user->id);

        $this->assertSame(2, $criadas);
        $this->assertSame(2, VendaDemanda::where('venda_id', $venda->id)->count());
        $this->assertDatabaseHas('venda_demandas', [
            'venda_id' => $venda->id,
            'tipo' => TipoDemandaContrato::ENVIO_BOLETO->value,
        ]);
    }

    public function test_selecao_vazia_nao_gera_nenhuma_demanda(): void
    {
        $venda = $this->criarVenda();

        $criadas = $this->service->gerarParaVenda($venda, [], $this->user->id);

        $this->assertSame(0, $criadas);
        $this->assertSame(0, VendaDemanda::where('venda_id', $venda->id)->count());
    }
}
