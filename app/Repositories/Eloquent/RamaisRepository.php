<?php

namespace App\Repositories\Eloquent;

use App\Models\Ramais;
use App\Enums\UserRole;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use Carbon\Carbon;


class RamaisRepository implements RamaisRepositoryInterface
{

  protected $model;

  public function __construct(Ramais $model)
  {
    $this->model = $model;
  }

  public function create(array $data)
  {
    try {
      return $this->model->updateOrCreate([
        'user_id' => $data['usuario_id']
      ], $data);
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  public function getRamais($typeUser, $empresa_id)
  {
    if ($typeUser === UserRole::DEVELOPER) {
      $resultados = DB::table('ramais as a')
        ->select('a.id', 'b.name', 'a.ramal', 'a.created_at')
        ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
        ->get();

      // Formatar as datas manualmente
      $resultados = $resultados->map(function ($registro) {
        $registro->created_at = $registro->created_at
          ? Carbon::parse($registro->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
          : null;
        return $registro;
      });
      return $resultados;
    } elseif ($typeUser === UserRole::ADMINISTRATIVO || $typeUser === UserRole::BACKOFFICE) {
      $resultados = DB::table('ramais as a')
        ->select('a.id', 'b.name', 'a.ramal', 'a.created_at')
        ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
        ->where('a.empresa_id', $empresa_id)
        ->get();

      // Formatar as datas manualmente
      $resultados = $resultados->map(function ($registro) {
        $registro->created_at = $registro->created_at
          ? Carbon::parse($registro->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
          : null;
        return $registro;
      });
      return $resultados;
    }
  }


  public function getRamal($idUser)
  {
    return $this->model->where('user_id', $idUser)->first();
  }
}
