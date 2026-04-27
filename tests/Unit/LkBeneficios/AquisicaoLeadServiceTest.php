<?php

namespace Tests\Unit\LkBeneficios;

use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Repositories\Contracts\BaseSaudeRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\LkBeneficios\Services\AquisicaoLeadService;
use Mockery;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AquisicaoLeadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_pegar_da_base_saude_bloqueia_se_ja_existe_lead_ativo(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $baseSaude = Mockery::mock(BaseSaudeRepositoryInterface::class);

        $leads->shouldReceive('existeLeadAtivo')->once()->andReturn(true);
        $baseSaude->shouldNotReceive('buscarDadosConsolidadosPorCpfCnpj');
        $leads->shouldNotReceive('create');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('lead ativo');

        (new AquisicaoLeadService($leads, $baseSaude))
            ->pegarDaBaseSaude('12345678900', 1, 10, 5);
    }

    public function test_pegar_da_base_saude_erra_quando_cpf_nao_existe_na_base(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $baseSaude = Mockery::mock(BaseSaudeRepositoryInterface::class);

        $leads->shouldReceive('existeLeadAtivo')->once()->andReturn(false);
        $baseSaude->shouldReceive('buscarDadosConsolidadosPorCpfCnpj')->once()->andReturn(null);
        $leads->shouldNotReceive('create');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não encontrado');

        (new AquisicaoLeadService($leads, $baseSaude))
            ->pegarDaBaseSaude('12345678900', 1, 10, 5);
    }

    public function test_pegar_da_base_saude_cria_lead_com_origem_base_saude(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $baseSaude = Mockery::mock(BaseSaudeRepositoryInterface::class);

        $leads->shouldReceive('existeLeadAtivo')->once()->andReturn(false);
        $baseSaude->shouldReceive('buscarDadosConsolidadosPorCpfCnpj')->once()->andReturn([
            'cpf_cnpj' => '12345678900',
            'cliente_tipo' => 'PF',
            'nome' => 'João Silva',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'primeira_implantacao' => '2023-05-10',
            'qtd_contratos' => 2,
        ]);
        $leads->shouldReceive('create')
            ->once()
            ->withArgs(function ($data) {
                return $data['origem'] === 'BASE_SAUDE'
                    && $data['cpf_cnpj'] === '12345678900'
                    && $data['nome'] === 'João Silva';
            })
            ->andReturn(new Lead());

        $retorno = (new AquisicaoLeadService($leads, $baseSaude))
            ->pegarDaBaseSaude('123.456.789-00', 1, 10, 5);

        $this->assertInstanceOf(Lead::class, $retorno);
    }

    public function test_criar_manual_bloqueia_lead_duplicado(): void
    {
        $leads = Mockery::mock(LeadRepositoryInterface::class);
        $baseSaude = Mockery::mock(BaseSaudeRepositoryInterface::class);

        $leads->shouldReceive('existeLeadAtivo')->once()->andReturn(true);
        $leads->shouldNotReceive('create');

        $this->expectException(RuntimeException::class);

        (new AquisicaoLeadService($leads, $baseSaude))->criarManual([
            'cpf_cnpj' => '12345678900',
            'produto_interesse_id' => 1,
            'cliente_tipo' => 'PF',
            'nome' => 'Ana',
        ], 10, 5);
    }
}
