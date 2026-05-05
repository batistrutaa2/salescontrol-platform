<?php

namespace App\Modules\LkBeneficios\Repositories\Contracts;

use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Models\LeadComentario;

interface LeadRepositoryInterface
{
    public function queryKanban(int $empresaId, array $filtros = []): array;

    public function findByEmpresa(int $id, int $empresaId): ?Lead;

    public function create(array $data, int $userId): Lead;

    public function moverStatus(int $id, int $empresaId, string $novoStatus, int $userId, ?string $observacao = null): Lead;

    public function reordenar(int $empresaId, string $status, array $idsOrdenados): void;

    public function existeLeadAtivo(int $empresaId, string $cpfCnpj, int $produtoId): bool;

    public function marcarConvertido(int $id, int $empresaId): Lead;

    public function soft(int $id, int $empresaId): bool;

    public function adicionarComentario(int $leadId, int $empresaId, int $userId, string $anotacao): LeadComentario;

    public function removerComentario(int $comentarioId, int $leadId, int $empresaId): bool;

    public function atualizarInformacaoFixada(int $leadId, int $empresaId, ?string $texto): Lead;
}
