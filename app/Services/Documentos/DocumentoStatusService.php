<?php

namespace App\Services\Documentos;

use App\Models\Vendas;

class DocumentoStatusService
{
    public function atualizarVenda(Vendas $venda): void
    {
        $status = $venda->documentos()->whereNull('deleted_at')->pluck('status');
        $resumo = $status->isEmpty() ? 'PENDENTE'
            : ($status->contains(fn ($item) => in_array($item, ['FALHA', 'BLOQUEADO'], true)) ? 'COM_FALHA'
                : ($status->every(fn ($item) => $item === 'DISPONIVEL') ? 'DISPONIVEL' : 'PROCESSANDO'));

        $venda->update(['documentacao_status' => $resumo]);
    }
}
