<?php

namespace App\Repositories\Eloquent;

use App\Models\TransferenciaContato;
use App\Repositories\Contracts\TransferenciaContatoRepositoryInterface;

class TransferenciaContatoRepository implements TransferenciaContatoRepositoryInterface
{
    protected $model;

    public function __construct(TransferenciaContato $model)
    {
        $this->model = $model;
    }

    public function saveTransfer($empresa_id, $contato_id, $fromUser, $toUser, $reponsableSend): bool
    {
        return (bool) $this->model::create([
            'empresa_id' => $empresa_id,
            'contato_id' => $contato_id,
            'para_user_id' => $toUser,
            'de_users_id' => $fromUser,
            'responsavel_transferencia' => $reponsableSend,
        ]);
    }

    public function monthlyTransferCount($month, $year, $empresaId)
    {
        return $this->model
            ->selectRaw('users.name, COUNT(transferencia_contatos.id) as quantidade')
            ->join('users', function ($join) use ($empresaId) {
                $join->on('users.id', '=', 'transferencia_contatos.para_user_id')
                    ->where('users.empresa_id', '=', $empresaId)
                    ->where('users.is_platform_admin', false);
            })
            ->where('transferencia_contatos.empresa_id', $empresaId)
            ->whereMonth('transferencia_contatos.created_at', $month)
            ->whereYear('transferencia_contatos.created_at', $year)
            ->groupBy('users.name')
            ->get();
    }
}
