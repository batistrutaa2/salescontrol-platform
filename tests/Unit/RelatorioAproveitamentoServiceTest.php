<?php

namespace Tests\Unit;

use App\Services\RelatorioAproveitamentoService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RelatorioAproveitamentoServiceTest extends TestCase
{
    public function test_provedor_e_configuravel_e_erro_tecnico_nao_e_exposto(): void
    {
        config([
            'services.anthropic.api_key' => 'chave-teste',
            'services.anthropic.base_url' => 'https://ia.exemplo.test/v2',
            'services.anthropic.model' => 'modelo-configurado',
            'services.anthropic.version' => 'versao-configurada',
            'services.anthropic.max_tokens' => 321,
        ]);
        Http::fake([
            'https://ia.exemplo.test/*' => Http::response([
                'error' => ['message' => 'detalhe interno do provedor'],
            ], 500, ['request-id' => 'req-teste']),
        ]);

        $resultado = app(RelatorioAproveitamentoService::class)->gerarAnaliseIA($this->dados());

        $this->assertSame([
            'success' => false,
            'error' => 'O serviço de análise está temporariamente indisponível.',
        ], $resultado);
        $this->assertStringNotContainsString('detalhe interno', $resultado['error']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://ia.exemplo.test/v2/messages'
            && $request->hasHeader('anthropic-version', 'versao-configurada')
            && $request['model'] === 'modelo-configurado'
            && $request['max_tokens'] === 321);
    }

    /** @return array<string, mixed> */
    private function dados(): array
    {
        return [
            'periodo' => ['data_inicio' => '01/01/2026', 'data_fim' => '31/01/2026'],
            'kpis' => [
                'total_leads' => 0,
                'vendas_cadastradas' => 0,
                'valor_cadastradas' => 0,
                'vendas_implantadas' => 0,
                'valor_implantadas' => 0,
                'ticket_medio' => 0,
                'vidas_total' => 0,
                'taxa_conversao' => 0,
            ],
            'funil_conversao' => [
                'importados' => 0,
                'atribuidos' => 0,
                'taxa_atribuicao' => 0,
                'em_negociacao' => 0,
                'taxa_negociacao' => 0,
                'convertidos' => 0,
                'taxa_conversao' => 0,
                'perdidos' => 0,
            ],
            'leads_por_mes' => [],
            'vendas_por_mes' => [],
            'distribuicao_status' => [],
            'fontes_importacao' => [],
            'distribuicao_temperatura' => ['quente' => 0, 'morno' => 0, 'frio' => 0],
        ];
    }
}
