<?php

namespace App\Repositories\Eloquent;

use App\Models\Ligacoes;
use App\Enums\AtividadesLeads;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Contracts\LigacoesRepositoryInterface;

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
      $this->model::create([
        'empresa_id' => $data['empresa_id'],
        'user_id' => $data['user_id'],
        'contato_id' => $data['contato_id'],
        'telefone' => $data['telefone'],
        'tabulacao_id' => $data['status'],
        'id_call' => $data['id_call']
      ]);
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  public function getLigacoes($id_user, $data_inicial, $data_final)
  {
    $startDate = Carbon::parse($data_inicial);
    $endDate = Carbon::parse($data_final);

    $result = DB::table('ligacoes as a')
      ->select('b.descricao as status', DB::raw('COUNT(*) as total_ligacoes'))
      ->leftJoin('tabulacoes as b', 'b.id', '=', 'a.tabulacao_id')
      ->leftJoin('users as c', 'c.id', '=', 'a.user_id')
      ->where('c.id', $id_user)
      ->whereBetween('a.created_at', [$startDate, $endDate])
      ->groupBy('b.descricao')
      ->orderByDesc('total_ligacoes')
      ->get();

    return $result;
  }

}
