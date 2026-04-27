<?php

namespace App\Http\Controllers\pages\lk_beneficios;

use App\Http\Controllers\Controller;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use App\Modules\LkBeneficios\Services\ConversaoLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConversaoController extends Controller
{
    public function __construct(
        private LeadRepositoryInterface $leads,
        private ConversaoLeadService $conversao,
    ) {
    }

    public function form(int $leadId)
    {
        $empresaId = Auth::user()->empresa_id;
        $lead = $this->leads->findByEmpresa($leadId, $empresaId);
        abort_unless($lead, 404);
        abort_if($lead->convertido_em !== null, 422);

        return view('content.pages.lk-beneficios.leads.converter', compact('lead'));
    }

    public function submit(int $leadId, Request $request): JsonResponse
    {
        $data = $request->validate([
            'numero_apolice' => 'nullable|string|max:60',
            'numero_proposta' => 'nullable|string|max:60',
            'data_proposta' => 'nullable|date',
            'data_vigencia_inicio' => 'nullable|date',
            'data_vigencia_fim' => 'nullable|date|after_or_equal:data_vigencia_inicio',
            'data_aniversario_reajuste' => 'nullable|date',
            'valor_mensal' => 'nullable|numeric|min:0',
            'valor_anual' => 'nullable|numeric|min:0',
            'forma_pagamento' => 'nullable|in:MENSAL,TRIMESTRAL,SEMESTRAL,ANUAL',
            'dia_vencimento' => 'nullable|integer|min:1|max:31',
            'vidas_total' => 'nullable|integer|min:0',
            'vidas_titulares' => 'nullable|integer|min:0',
            'vidas_dependentes' => 'nullable|integer|min:0',
            'observacoes' => 'nullable|string',
        ]);

        try {
            $contrato = $this->conversao->converterEmContrato(
                $leadId,
                $data,
                Auth::user()->empresa_id,
                Auth::id(),
            );
            return response()->json([
                'message' => 'Contrato criado.',
                'contrato_id' => $contrato->id,
                'redirect' => route('lk-beneficios.contratos.show', $contrato->id),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
