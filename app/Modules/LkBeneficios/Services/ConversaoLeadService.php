<?php

namespace App\Modules\LkBeneficios\Services;

use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Repositories\Contracts\ContratoRepositoryInterface;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConversaoLeadService
{
    public function __construct(
        private LeadRepositoryInterface $leads,
        private ContratoRepositoryInterface $contratos,
    ) {}

    public function converterEmContrato(int $leadId, array $dadosContrato, int $empresaId, int $userId): Contrato
    {
        return DB::transaction(function () use ($leadId, $dadosContrato, $empresaId, $userId) {
            $lead = $this->leads->findByEmpresa($leadId, $empresaId);
            if (! $lead) {
                throw new RuntimeException('Lead não encontrado.');
            }
            if ($lead->convertido_em !== null) {
                throw new RuntimeException('Lead já foi convertido em contrato.');
            }

            $payload = array_merge([
                'empresa_id' => $empresaId,
                'user_id' => $userId,
                'produto_id' => $lead->produto_interesse_id,
                'lead_id' => $lead->id,
                'pessoa_id' => $lead->pessoa_id,
                'empresa_lemit_id' => $lead->empresa_lemit_id,
                'cliente_tipo' => $lead->cliente_tipo,
                'cpf_cnpj' => $lead->cpf_cnpj,
                'nome_cliente' => $lead->nome,
                'status' => 'EM_IMPLANTACAO',
            ], $dadosContrato);

            $contrato = $this->contratos->create($payload);

            $this->leads->marcarConvertido($lead->id, $empresaId);

            return $contrato;
        });
    }
}
