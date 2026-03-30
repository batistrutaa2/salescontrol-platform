<?php

namespace App\Services;

use App\Enums\Tabulations;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Dependentes;
use App\Models\LeadAtividade;
use App\Models\Ligacoes;
use App\Models\LogPreditiva;
use App\Models\Preditiva;
use App\Models\TransferenciaContato;
use App\Repositories\Eloquent\VendasRepository;
use Illuminate\Support\Facades\DB;

class LeadManagementService
{
    private VendasRepository $vendasRepository;

    public function __construct(VendasRepository $vendasRepository)
    {
        $this->vendasRepository = $vendasRepository;
    }

    public function getLeadKPIs(int $empresaId, $request = null): array
    {
        $query = DB::table('contatos as a')
            ->leftJoin('contatos_corretores as b', function ($join) use ($empresaId) {
                $join->on('b.contato_id', '=', 'a.id')
                    ->where('b.empresa_id', '=', $empresaId);
            })
            ->leftJoin('tabulacoes as c', 'b.tabulacao_id', '=', 'c.id')
            ->leftJoin('preditiva as p', function ($join) use ($empresaId) {
                $join->on('p.contato_id', '=', 'a.id')
                    ->where('p.empresa_id', '=', $empresaId)
                    ->where('p.status', '=', 'Y');
            })
            ->leftJoin('users as d', 'b.user_id', '=', 'd.id')
            ->where('a.empresa_id', $empresaId);

        // Aplicar filtros se existirem
        if ($request) {
            if ($request->filled('corretor')) {
                $query->where('b.user_id', $request->corretor);
            }
            if ($request->filled('nome_base')) {
                $query->where('a.nome_base', $request->nome_base);
            }
            if ($request->filled('data_importacao')) {
                $dateParts = explode('/', $request->data_importacao);
                if (count($dateParts) === 3) {
                    $dateFormatted = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";
                    $query->whereDate('a.created_at', $dateFormatted);
                }
            }
            if ($request->filled('data_inicio') && $request->filled('data_fim')) {
                $inicParts = explode('/', $request->data_inicio);
                $fimParts = explode('/', $request->data_fim);
                if (count($inicParts) === 3 && count($fimParts) === 3) {
                    $dataInicio = "{$inicParts[2]}-{$inicParts[1]}-{$inicParts[0]}";
                    $dataFim = "{$fimParts[2]}-{$fimParts[1]}-{$fimParts[0]}";
                    $query->whereDate('a.created_at', '>=', $dataInicio)
                        ->whereDate('a.created_at', '<=', $dataFim);
                }
            }
        }

        $result = $query->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN a.status = 'Y' AND b.contato_id IS NOT NULL AND (c.id IS NULL OR c.id != ?) THEN 1 ELSE 0 END) as com_vendedor,
                SUM(CASE WHEN p.contato_id IS NOT NULL AND b.contato_id IS NULL THEN 1 ELSE 0 END) as preditiva,
                SUM(CASE WHEN a.status = 'N' THEN 1 ELSE 0 END) as descartados,
                SUM(CASE WHEN a.status = 'Y' AND b.contato_id IS NOT NULL AND c.id = ? THEN 1 ELSE 0 END) as remarketing,
                SUM(CASE WHEN a.status = 'Y' AND b.contato_id IS NULL AND p.contato_id IS NULL THEN 1 ELSE 0 END) as sem_atribuicao
            ", [Tabulations::REMARKETING, Tabulations::REMARKETING])
            ->first();

        return [
            'total' => (int) ($result->total ?? 0),
            'com_vendedor' => (int) ($result->com_vendedor ?? 0),
            'preditiva' => (int) ($result->preditiva ?? 0),
            'descartados' => (int) ($result->descartados ?? 0),
            'remarketing' => (int) ($result->remarketing ?? 0),
            'sem_atribuicao' => (int) ($result->sem_atribuicao ?? 0),
        ];
    }

    public function getImportDatesByMonth(int $empresaId, int $month, int $year): array
    {
        $imports = DB::table('contatos')
            ->where('empresa_id', $empresaId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw("
                DATE(created_at) as data_importacao,
                nome_base,
                COUNT(*) as quantidade
            ")
            ->groupBy('data_importacao', 'nome_base')
            ->orderBy('data_importacao', 'asc')
            ->get();

        return $imports->toArray();
    }

    public function reactivateLead(int $contatoId, int $empresaId): bool
    {
        return Contatos::where('id', $contatoId)
            ->where('empresa_id', $empresaId)
            ->where('status', 'N')
            ->update(['status' => 'Y', 'updated_at' => now()]) > 0;
    }

    public function bulkReactivateLeads(array $ids, int $empresaId): array
    {
        $successCount = 0;
        $errorCount = 0;

        foreach ($ids as $id) {
            if ($this->reactivateLead($id, $empresaId)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return ['success' => $successCount, 'errors' => $errorCount];
    }

    public function deleteLead(int $contatoId, int $empresaId): array
    {
        $hasSale = $this->vendasRepository->checkExistenceSale($contatoId);
        if ($hasSale) {
            return ['success' => false, 'message' => 'Lead possui venda cadastrada, exclusao cancelada.'];
        }

        DB::beginTransaction();
        try {
            Comentarios::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Agendamento::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Dependentes::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Ligacoes::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            LeadAtividade::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            ContatosCorretores::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            LogPreditiva::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Preditiva::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            TransferenciaContato::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Contatos::where('id', $contatoId)->where('empresa_id', $empresaId)->delete();
            DB::commit();
            return ['success' => true, 'message' => 'Lead excluido com sucesso.'];
        } catch (\Throwable $th) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Erro ao excluir lead.'];
        }
    }

    public function bulkDeleteLeads(array $ids, int $empresaId): array
    {
        $successCount = 0;
        $errorCount = 0;
        $skipped = [];

        foreach ($ids as $id) {
            $result = $this->deleteLead($id, $empresaId);
            if ($result['success']) {
                $successCount++;
            } else {
                $errorCount++;
                if (str_contains($result['message'], 'venda')) {
                    $skipped[] = $id;
                }
            }
        }

        return [
            'success' => $successCount,
            'errors' => $errorCount,
            'skipped_with_sales' => $skipped,
        ];
    }

    public function discardLead(int $contatoId, int $empresaId): bool
    {
        DB::beginTransaction();
        try {
            Contatos::where('id', $contatoId)->where('empresa_id', $empresaId)->update(['status' => 'N']);
            ContatosCorretores::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Preditiva::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            DB::rollBack();
            return false;
        }
    }

    public function bulkDiscardLeads(array $ids, int $empresaId): array
    {
        $successCount = 0;
        $errorCount = 0;

        foreach ($ids as $id) {
            if ($this->discardLead($id, $empresaId)) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        return ['success' => $successCount, 'errors' => $errorCount];
    }
}
