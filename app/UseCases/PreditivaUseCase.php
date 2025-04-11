<?php

namespace App\UseCases;

use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Repositories\Contracts\EmpresaRepositoryInterface;
use App\Repositories\Contracts\LogPreditivaRepositoryInterface;
use App\Repositories\Contracts\PreditivaRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Eloquent\LogPreditivaRepository;
use App\Repositories\Eloquent\PreditivaRepository;
use Illuminate\Http\Request;

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
    // deletar o lead da contatosCorretores
    // adicionar na fila de preditiva
    // usar o transaction para garatir que nada seja alterado caso de erro
  }

}
