<?php

namespace App\Repositories\Eloquent;

use App\Models\Agendamento;
use App\Models\ComercialReunioes;
use App\Models\ContatosCorretores;
use App\Models\Vendas;
use App\Repositories\Contracts\ResumoOperacionalRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResumoOperacionalRepository implements ResumoOperacionalRepositoryInterface
{
    public function vendasCadastradas(int $empresaId, Carbon $data): array
    {
        $row = Vendas::where('empresa_id', $empresaId)
            ->whereDate('created_at', $data->toDateString())
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(valor_contrato), 0) as valor_total, COALESCE(AVG(valor_contrato), 0) as ticket_medio')
            ->first();

        return [
            'count'        => (int) ($row->cnt ?? 0),
            'valor_total'  => (float) ($row->valor_total ?? 0),
            'ticket_medio' => (float) ($row->ticket_medio ?? 0),
        ];
    }

    public function vendasImplantadas(int $empresaId, Carbon $data): array
    {
        $row = Vendas::where('empresa_id', $empresaId)
            ->whereDate('data_implantacao', $data->toDateString())
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(valor_contrato), 0) as valor_total, COALESCE(SUM(vidas), 0) as vidas')
            ->first();

        return [
            'count'       => (int) ($row->cnt ?? 0),
            'valor_total' => (float) ($row->valor_total ?? 0),
            'vidas'       => (int) ($row->vidas ?? 0),
        ];
    }

    public function boletosLiberadosHoje(int $empresaId, Carbon $data): int
    {
        return Vendas::where('empresa_id', $empresaId)
            ->whereNotNull('path_boleto_disponivel')
            ->whereDate('updated_at', $data->toDateString())
            ->count();
    }

    public function topVendedoresDoDia(int $empresaId, Carbon $data, int $limit = 3): array
    {
        return Vendas::where('vendas.empresa_id', $empresaId)
            ->whereDate('vendas.created_at', $data->toDateString())
            ->whereNotNull('vendas.user_id')
            ->join('users', 'users.id', '=', 'vendas.user_id')
            ->groupBy('vendas.user_id', 'users.name')
            ->selectRaw('vendas.user_id as user_id, users.name as nome, COALESCE(SUM(vendas.valor_contrato), 0) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'user_id' => (int) $row->user_id,
                'nome'    => $row->nome,
                'total'   => (float) $row->total,
            ])
            ->all();
    }

    public function leadsTrabalhados(int $empresaId, Carbon $data): int
    {
        return ContatosCorretores::where('empresa_id', $empresaId)
            ->whereDate('updated_at', $data->toDateString())
            ->distinct('contato_id')
            ->count('contato_id');
    }

    public function agendamentosAmanha(int $empresaId, Carbon $data): int
    {
        $amanha = $data->copy()->addDay()->toDateString();

        return Agendamento::where('empresa_id', $empresaId)
            ->whereDate('horario_agendamento', $amanha)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('vendas')
                    ->whereColumn('vendas.contato_id', 'agendamentos.contato_id');
            })
            ->count();
    }

    public function reunioesAmanhaPorGestor(int $empresaId, Carbon $data): array
    {
        $amanha = $data->copy()->addDay()->toDateString();

        return ComercialReunioes::where('comercial_reunioes.empresa_id', $empresaId)
            ->whereDate('comercial_reunioes.data_inicio', $amanha)
            ->where('comercial_reunioes.status', 'scheduled')
            ->join('users', 'users.id', '=', 'comercial_reunioes.manager_id')
            ->groupBy('comercial_reunioes.manager_id', 'users.name')
            ->selectRaw('comercial_reunioes.manager_id as manager_id, users.name as nome, COUNT(*) as total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'manager_id' => (int) $row->manager_id,
                'nome'       => $row->nome,
                'total'      => (int) $row->total,
            ])
            ->all();
    }
}
