<?php

namespace App\Services;

use App\Enums\TabulationCode;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Dependentes;
use App\Models\LeadAtividade;
use App\Models\Ligacoes;
use App\Models\LogPreditiva;
use App\Models\Preditiva;
use App\Models\PreditivaEnvio;
use App\Models\TransferenciaContato;
use App\Repositories\Eloquent\VendasRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadManagementService
{
    private VendasRepository $vendasRepository;

    public function __construct(VendasRepository $vendasRepository, private readonly TabulationCatalog $tabulations)
    {
        $this->vendasRepository = $vendasRepository;
    }

    public function getLeadKPIs(int $empresaId, $request = null): array
    {
        $remarketingId = $this->tabulations->id($empresaId, TabulationCode::REMARKETING);
        $query = DB::table('contatos as a')
            ->leftJoin('contatos_corretores as b', function ($join) use ($empresaId) {
                $join->on('b.contato_id', '=', 'a.id')
                    ->on('b.empresa_id', '=', 'a.empresa_id')
                    ->where('b.empresa_id', '=', $empresaId);
            })
            ->leftJoin('tabulacoes as c', function ($join) use ($empresaId) {
                $join->on('c.id', '=', 'b.tabulacao_id')
                    ->on('c.empresa_id', '=', 'b.empresa_id')
                    ->where('c.empresa_id', '=', $empresaId);
            })
            ->leftJoin('preditiva as p', function ($join) use ($empresaId) {
                $join->on('p.contato_id', '=', 'a.id')
                    ->on('p.empresa_id', '=', 'a.empresa_id')
                    ->where('p.empresa_id', '=', $empresaId)
                    ->where('p.status', '=', 'Y');
            })
            ->leftJoin('users as d', function ($join) use ($empresaId) {
                $join->on('d.id', '=', 'b.user_id')
                    ->on('d.empresa_id', '=', 'b.empresa_id')
                    ->where('d.empresa_id', '=', $empresaId)
                    ->where('d.is_platform_admin', false);
            })
            ->where('a.empresa_id', $empresaId);

        // Aplicar filtros se existirem
        if ($request) {
            if ($request->filled('corretor')) {
                $query->where('b.user_id', $request->corretor);
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
            if ($request->filled('ultimo_contato_inicio') && $request->filled('ultimo_contato_fim')) {
                $inicParts = explode('/', $request->ultimo_contato_inicio);
                $fimParts = explode('/', $request->ultimo_contato_fim);
                if (count($inicParts) === 3 && count($fimParts) === 3) {
                    $dataInicio = "{$inicParts[2]}-{$inicParts[1]}-{$inicParts[0]}";
                    $dataFim = "{$fimParts[2]}-{$fimParts[1]}-{$fimParts[0]}";
                    $query->whereRaw('
                        (
                            SELECT MAX(uc_max) FROM ('.\App\Repositories\Eloquent\ContatosRepository::ultimoContatoUnionSql($empresaId).') _uc_f
                        ) BETWEEN ? AND ?
                    ', [$dataInicio.' 00:00:00', $dataFim.' 23:59:59']);
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
            ", [$remarketingId, $remarketingId])
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
            PreditivaEnvio::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            TransferenciaContato::where('contato_id', $contatoId)->where('empresa_id', $empresaId)->delete();
            Contatos::where('id', $contatoId)->where('empresa_id', $empresaId)->delete();
            DB::commit();

            return ['success' => true, 'message' => 'Lead excluido com sucesso.'];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error("Erro ao excluir lead {$contatoId}: ".$th->getMessage());

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
            Contatos::where('id', $contatoId)->where('empresa_id', $empresaId)->update(['status' => 'N', 'updated_at' => now()]);
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
