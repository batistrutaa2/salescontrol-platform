<?php

namespace App\Modules\LkBeneficios\Repositories\Eloquent;

use App\Modules\LkBeneficios\Enums\StatusLead;
use App\Modules\LkBeneficios\Models\Lead;
use App\Modules\LkBeneficios\Models\LeadComentario;
use App\Modules\LkBeneficios\Models\LeadHistorico;
use App\Modules\LkBeneficios\Repositories\Contracts\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadRepository implements LeadRepositoryInterface
{
    public function queryKanban(int $empresaId, array $filtros = []): array
    {
        $query = Lead::query()
            ->with(['produtoInteresse:id,nome,tipo', 'user:id,name'])
            ->ativos()
            ->daEmpresa($empresaId);

        if (! empty($filtros['user_id'])) {
            $query->where('user_id', $filtros['user_id']);
        }
        if (! empty($filtros['produto_id'])) {
            $query->where('produto_interesse_id', $filtros['produto_id']);
        }
        if (! empty($filtros['busca'])) {
            $busca = '%' . $filtros['busca'] . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)->orWhere('cpf_cnpj', 'like', $busca);
            });
        }

        $leads = $query->orderBy('status')->orderBy('ordem_kanban')->orderByDesc('id')->get();

        $agrupado = array_fill_keys(StatusLead::all(), []);
        foreach ($leads as $lead) {
            $agrupado[$lead->status][] = $lead;
        }

        return $agrupado;
    }

    public function findByEmpresa(int $id, int $empresaId): ?Lead
    {
        return Lead::with([
                'produtoInteresse',
                'user:id,name',
                'comentarios.user:id,name',
                'contratos',
            ])
            ->where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();
    }

    public function create(array $data, int $userId): Lead
    {
        return DB::transaction(function () use ($data, $userId) {
            $proximaOrdem = (int) Lead::where('empresa_id', $data['empresa_id'])
                ->where('status', StatusLead::NOVO_CLIENTE)
                ->max('ordem_kanban') + 1;

            $lead = Lead::create([
                'empresa_id' => $data['empresa_id'],
                'user_id' => $data['user_id'] ?? $userId,
                'produto_interesse_id' => $data['produto_interesse_id'],
                'pessoa_id' => $data['pessoa_id'] ?? null,
                'empresa_lemit_id' => $data['empresa_lemit_id'] ?? null,
                'cliente_tipo' => $data['cliente_tipo'],
                'cpf_cnpj' => preg_replace('/\D+/', '', $data['cpf_cnpj']),
                'nome' => $data['nome'],
                'email' => $data['email'] ?? null,
                'telefone' => $data['telefone'] ?? null,
                'status' => StatusLead::NOVO_CLIENTE,
                'origem' => $data['origem'],
                'ordem_kanban' => $proximaOrdem,
                'data_ultima_movimentacao' => now(),
                'observacoes' => $data['observacoes'] ?? null,
            ]);

            LeadHistorico::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'status_anterior' => null,
                'status_novo' => StatusLead::NOVO_CLIENTE,
                'observacao' => 'Lead criado via ' . ($data['origem'] === 'BASE_SAUDE' ? 'Base Saúde' : 'cadastro manual'),
                'created_at' => now(),
            ]);

            return $lead->fresh(['produtoInteresse', 'user:id,name']);
        });
    }

    public function moverStatus(int $id, int $empresaId, string $novoStatus, int $userId, ?string $observacao = null): Lead
    {
        if (! in_array($novoStatus, StatusLead::all(), true)) {
            throw new InvalidArgumentException('Status inválido: ' . $novoStatus);
        }

        return DB::transaction(function () use ($id, $empresaId, $novoStatus, $userId, $observacao) {
            $lead = Lead::where('id', $id)->where('empresa_id', $empresaId)->firstOrFail();

            if ($lead->convertido_em !== null) {
                throw new InvalidArgumentException('Lead já convertido não pode ser movido.');
            }

            $statusAnterior = $lead->status;
            if ($statusAnterior === $novoStatus) {
                return $lead;
            }

            $proximaOrdem = (int) Lead::where('empresa_id', $empresaId)
                ->where('status', $novoStatus)
                ->max('ordem_kanban') + 1;

            $lead->update([
                'status' => $novoStatus,
                'ordem_kanban' => $proximaOrdem,
                'data_ultima_movimentacao' => now(),
            ]);

            LeadHistorico::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'status_anterior' => $statusAnterior,
                'status_novo' => $novoStatus,
                'observacao' => $observacao,
                'created_at' => now(),
            ]);

            return $lead->fresh(['produtoInteresse', 'user:id,name']);
        });
    }

    public function reordenar(int $empresaId, string $status, array $idsOrdenados): void
    {
        if (! in_array($status, StatusLead::all(), true)) {
            throw new InvalidArgumentException('Status inválido.');
        }

        DB::transaction(function () use ($empresaId, $status, $idsOrdenados) {
            foreach ($idsOrdenados as $ordem => $id) {
                Lead::where('id', (int) $id)
                    ->where('empresa_id', $empresaId)
                    ->where('status', $status)
                    ->update(['ordem_kanban' => $ordem + 1]);
            }
        });
    }

    public function existeLeadAtivo(int $empresaId, string $cpfCnpj, int $produtoId): bool
    {
        $cpfCnpj = preg_replace('/\D+/', '', $cpfCnpj);
        return Lead::ativos()
            ->daEmpresa($empresaId)
            ->where('cpf_cnpj', $cpfCnpj)
            ->where('produto_interesse_id', $produtoId)
            ->exists();
    }

    public function marcarConvertido(int $id, int $empresaId): Lead
    {
        $lead = Lead::where('id', $id)->where('empresa_id', $empresaId)->firstOrFail();
        $lead->update(['convertido_em' => now()]);
        return $lead->fresh();
    }

    public function soft(int $id, int $empresaId): bool
    {
        $lead = Lead::where('id', $id)->where('empresa_id', $empresaId)->firstOrFail();
        return (bool) $lead->delete();
    }

    public function adicionarComentario(int $leadId, int $empresaId, int $userId, string $anotacao): LeadComentario
    {
        $lead = Lead::where('id', $leadId)->where('empresa_id', $empresaId)->firstOrFail();

        $comentario = LeadComentario::create([
            'empresa_id' => $lead->empresa_id,
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'anotacao' => $anotacao,
        ]);

        return $comentario->load('user:id,name');
    }

    public function removerComentario(int $comentarioId, int $leadId, int $empresaId): bool
    {
        $comentario = LeadComentario::where('id', $comentarioId)
            ->where('lead_id', $leadId)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        return (bool) $comentario->delete();
    }

    public function atualizarInformacaoFixada(int $leadId, int $empresaId, ?string $texto): Lead
    {
        $lead = Lead::where('id', $leadId)->where('empresa_id', $empresaId)->firstOrFail();
        $lead->update(['informacao_fixada' => $texto !== null && trim($texto) === '' ? null : $texto]);

        return $lead->fresh();
    }
}
