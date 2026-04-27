<?php

namespace Tests\Unit\LkBeneficios;

use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\LkBeneficios\Services\ConversaoLeadService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ConversaoLeadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_converter_falha_se_lead_nao_existe(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $contratos = Mockery::mock(ContratoRepositoryInterface::class);

        $leads->shouldReceive('findByEmpresa')->once()->andReturn(null);
        $contratos->shouldNotReceive('create');

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $this->expectException(\RuntimeException::class);

        (new ConversaoLeadService($leads, $contratos))
            ->converterEmContrato(99, [], 10, 5);
    }

    public function test_converter_falha_se_lead_ja_convertido(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $contratos = Mockery::mock(ContratoRepositoryInterface::class);

        $lead = new Lead(['convertido_em' => now()]);
        $lead->convertido_em = now();

        $leads->shouldReceive('findByEmpresa')->once()->andReturn($lead);
        $contratos->shouldNotReceive('create');

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('já foi convertido');

        (new ConversaoLeadService($leads, $contratos))
            ->converterEmContrato(1, [], 10, 5);
    }

    public function test_converter_cria_contrato_com_lead_id_e_marca_convertido(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $contratos = Mockery::mock(ContratoRepositoryInterface::class);

        $lead = new Lead([
            'produto_interesse_id' => 2,
            'cliente_tipo' => 'PF',
            'cpf_cnpj' => '12345678900',
            'nome' => 'Maria',
        ]);
        $lead->id = 7;
        $lead->convertido_em = null;

        $leads->shouldReceive('findByEmpresa')->once()->andReturn($lead);
        $contratos->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['lead_id'] === 7
                    && $data['produto_id'] === 2
                    && $data['nome_cliente'] === 'Maria';
            })
            ->andReturn(new Contrato(['id' => 42]));
        $leads->shouldReceive('marcarConvertido')->once()->with(7, 10)->andReturn($lead);

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn ($cb) => $cb());

        $contrato = (new ConversaoLeadService($leads, $contratos))
            ->converterEmContrato(7, ['valor_mensal' => 200], 10, 5);

        $this->assertInstanceOf(Contrato::class, $contrato);
    }
}
