<?php

namespace App\Repositories\Eloquent;


use Illuminate\Support\Facades\DB;
use App\Models\Legace\AllConsultLegace;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;

class BaseLegaceRespository implements BaseLegaceRespositoryInterface
{
  protected $model;

  public function __construct(AllConsultLegace $model)
  {
    $this->model = $model;
  }

  public function getContactsAll()
  {
    return $this->model::select('records.id', 'brokers.name AS nome_corretor', 'records.nameClient AS nome_cliente', 'records.category', 'records.phone1 AS telefone')
      ->leftJoin('recordsandbrokers', 'records.id', '=', 'recordsandbrokers.idRecords')
      ->leftJoin('brokers', 'recordsandbrokers.idBroker', '=', 'brokers.id')
      ->leftJoin('tabulations', 'recordsandbrokers.codTabulation', '=', 'tabulations.id')
      ->whereNotNull('brokers.name')
      ->get();

  }

  public function getContacts($id_mailing)
  {
    return $this->model
      ->where('id', $id_mailing)
      ->first();
  }

  public function getCommentsMailing($id_mailing)
  {
    return DB::connection('mysql2')->table('records AS a')
      ->leftJoin('comments AS b', 'a.id', '=', 'b.idMailing')
      ->leftJoin('brokers AS c', 'b.idBrokers', '=', 'c.id')
      ->select('b.*', 'c.name')
      ->where('a.id', $id_mailing)
      ->get();
  }

}
