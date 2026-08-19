<?php

namespace Tests\Unit\Services;

use App\Modules\LkBeneficios\Services\LemitService;
use App\Services\Comercial\PropostaEnriquecimentoService;
use Mockery;
use Tests\TestCase;

class PropostaEnriquecimentoServiceTest extends TestCase
{
    public function test_retorna_lemit_sem_consultar_assertiva_quando_identidade_foi_encontrada(): void
    {
        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldReceive('consultarCpf')->once()->with('11144477735')->andReturn([
            'pessoa' => [
                'nome' => 'Maria da Silva', 'data_nascimento' => '1990-04-20',
                'emails' => [['email' => 'maria@example.com']],
                'celulares' => [['ddd' => '11', 'numero' => '999998888']],
            ],
        ]);

        $resultado = (new PropostaEnriquecimentoService($lemit))->consultar('111.444.777-35');

        $this->assertTrue($resultado['encontrado']);
        $this->assertSame('Maria da Silva', $resultado['dados']['nome']);
        $this->assertSame('20/04/1990', $resultado['dados']['data_nascimento']);
        $this->assertSame('11999998888', $resultado['dados']['telefone1']);
    }

    public function test_consulta_cnpj_exclusivamente_pela_lemit(): void
    {
        $lemit = Mockery::mock(LemitService::class);
        $lemit->shouldReceive('consultarCnpj')->once()->with('12345678000195')->andReturn([
            'empresa' => ['razao_social' => 'Empresa Encontrada', 'data_abertura' => '2018-01-10'],
        ]);

        $resultado = (new PropostaEnriquecimentoService($lemit))->consultar('12.345.678/0001-95');

        $this->assertTrue($resultado['encontrado']);
        $this->assertSame('Empresa Encontrada', $resultado['dados']['nome']);
        $this->assertSame('10/01/2018', $resultado['dados']['data_abertura']);
    }
}
