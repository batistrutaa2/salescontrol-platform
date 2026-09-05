<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Http\Controllers\Controller;
use App\Services\RelatorioAproveitamentoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RelatorioAproveitamento extends Controller
{
    private RelatorioAproveitamentoService $service;

    public function __construct(RelatorioAproveitamentoService $service)
    {
        $this->service = $service;
    }

    /**
     * Exibe a página do relatório
     */
    public function index()
    {
        $anoAtual = now()->year;
        $anos = range($anoAtual, $anoAtual - 5);

        return view('content.pages.relatorios.aproveitamento-ia', [
            'anos' => $anos,
            'anoAtual' => $anoAtual,
            'temApiKey' => ! empty(config('services.anthropic.api_key')),
        ]);
    }

    /**
     * Retorna os dados agregados para o relatório
     */
    public function getDados(Request $request)
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:'.(now()->year + 1),
            'mes_inicio' => 'nullable|integer|min:1|max:12',
            'mes_fim' => 'nullable|integer|min:1|max:12',
        ]);

        $ano = $request->ano;
        $mesInicio = $request->mes_inicio ?? 1;
        $mesFim = $request->mes_fim ?? 12;

        // Garantir que mes_fim >= mes_inicio
        if ($mesFim < $mesInicio) {
            $mesFim = $mesInicio;
        }

        try {
            $dados = $this->service->coletarDadosAgregados($ano, $mesInicio, $mesFim);

            return response()->json([
                'success' => true,
                'dados' => $dados,
            ]);
        } catch (Throwable $e) {
            Log::error('Falha ao coletar dados do relatório de aproveitamento.', [
                'empresa_id' => $this->tenantId(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Não foi possível coletar os dados do relatório.',
            ], 500);
        }
    }

    /**
     * Gera a análise da IA
     */
    public function gerarAnalise(Request $request)
    {
        $request->validate([
            'ano' => 'required|integer|min:2020|max:'.(now()->year + 1),
            'mes_inicio' => 'nullable|integer|min:1|max:12',
            'mes_fim' => 'nullable|integer|min:1|max:12',
        ]);

        $ano = $request->ano;
        $mesInicio = $request->mes_inicio ?? 1;
        $mesFim = $request->mes_fim ?? 12;

        if ($mesFim < $mesInicio) {
            $mesFim = $mesInicio;
        }

        try {
            // Primeiro coleta os dados
            $dados = $this->service->coletarDadosAgregados($ano, $mesInicio, $mesFim);

            // Depois gera a análise
            $analise = $this->service->gerarAnaliseIA($dados);

            return response()->json($analise);
        } catch (Throwable $e) {
            Log::error('Falha ao gerar análise do relatório de aproveitamento.', [
                'empresa_id' => $this->tenantId(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Não foi possível gerar a análise do relatório.',
            ], 500);
        }
    }
}
