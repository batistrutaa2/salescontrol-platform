<?php

namespace App\UseCases;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\PreditivaRepository;
use App\Repositories\Eloquent\LogPreditivaRepository;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;

class PreditivaUseCase
{
  protected PreditivaRepository $previtivaRepository;
  protected LogPreditivaRepository $logPreditivaRepository;
  protected ContatosCorretoresRepository $contatosCorretoresRepository;

  public function __construct(
    PreditivaRepositoryInterface $preditivaRepositoryInterface,
    LogPreditivaRepositoryInterface $logPreditivaRepositoryInterface,
    ContatosCorretoresRepositoryInterface $contatosCorretoresRepositoryInterface)
  {
    $this->previtivaRepository = $preditivaRepositoryInterface;
    $this->logPreditivaRepository = $logPreditivaRepositoryInterface;
    $this->contatosCorretoresRepository = $contatosCorretoresRepositoryInterface;
  }

  public function sendMailingPredictive($id_mailing) {
      try {

        DB::beginTransaction();
        $deleteContactBrokers = $this->contatosCorretoresRepository->deleteMailing($id_mailing);
        $createMailingPreditiva = $this->previtivaRepository->create([
          'empresa_id' => Auth::user()->empresa_id,
          'contato_id' => $id_mailing,
          'status' => "Y"
        ]);

        if ($deleteContactBrokers && $createMailingPreditiva) {
          DB::commit();
          return true;
        }
        DB::rollBack();
        return false;
      } catch (\Throwable $th) {
        DB::rollBack();
        return false;
      }
  }
}
