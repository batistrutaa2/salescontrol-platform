<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Eloquent\ContatosRepository;
use Illuminate\Http\Request;

class Contatos extends Controller
{
  protected ContatosRepository $contatosRepository;

  public function __construct(ContatosRepositoryInterface $contatosRepositoryInterface)
   {
    $this->contatosRepository = $contatosRepositoryInterface;
   }


    public function collectCustomer(Request $request) {

      $token = isset($request->token) ? $request->token : "";
      if ($token != env('TOKEN_MARKETING')) {
        return response()->json([
          'error' => true,
          'message' => "Acesso negado"
        ], 401);
      }

     return $this->contatosRepository->importarLeadsAds($request->all());
    }
}
