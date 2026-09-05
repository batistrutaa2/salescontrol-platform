<?php

namespace App\Repositories\Eloquent;

use App\Models\Ligacoes;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LigacoesRepository implements LigacoesRepositoryInterface
{
    protected $model;

    public function __construct(Ligacoes $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $contatoId = (int) ($data['contato_id'] ?? 0);
            $tabulacaoId = (int) ($data['status'] ?? 0);

            if (! DB::table('contatos')->where('empresa_id', $empresaId)->where('id', $contatoId)->exists()) {
                return false;
            }

            if (! DB::table('tabulacoes')->where('empresa_id', $empresaId)->where('id', $tabulacaoId)->exists()) {
                return false;
            }

            return $this->model::create([
                'empresa_id' => $empresaId,
                'user_id' => Auth::id(),
                'contato_id' => $contatoId,
                'telefone' => $data['telefone'],
                'tabulacao_id' => $tabulacaoId,
                'id_call' => $data['id_call'],
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getLigacoes($id_user, $data_inicial, $data_final)
    {
        $empresaId = (int) app(\App\Support\TenantContext::class)->id();
        $startDate = Carbon::parse($data_inicial);
        $endDate = Carbon::parse($data_final);

        $result = DB::table('ligacoes as a')
            ->select('b.descricao as status', DB::raw('COUNT(*) as total_ligacoes'))
            ->leftJoin('tabulacoes as b', function ($join) {
                $join->on('b.id', '=', 'a.tabulacao_id')
                    ->on('b.empresa_id', '=', 'a.empresa_id');
            })
            ->leftJoin('users as c', function ($join) {
                $join->on('c.id', '=', 'a.user_id')
                    ->on('c.empresa_id', '=', 'a.empresa_id')
                    ->where('c.is_platform_admin', false);
            })
            ->where('c.id', $id_user)
            ->where('a.empresa_id', $empresaId)
            ->where('b.empresa_id', $empresaId)
            ->where('c.empresa_id', $empresaId)
            ->whereBetween('a.created_at', [$startDate, $endDate])
            ->groupBy('b.descricao')
            ->orderByDesc('total_ligacoes')
            ->get();

        return $result;
    }
}
