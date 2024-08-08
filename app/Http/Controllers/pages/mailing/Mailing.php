<?php

namespace App\Http\Controllers\pages\mailing;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\UseCases\MailingUseCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class Mailing extends Controller
{

  protected UsuariosRepositoryInterface $usuarioRepository;
  protected MailingUseCase $mailingUseCase;
  private $rulesUpload = [
    'file' => 'required|string',
    'base' => 'required|string',
    'file' => 'required|file',
  ];


  public function __construct(
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface
  ) {

    $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);

    $this->usuarioRepository = $usuariosRepositoryInterface;
  }


  public function index()
  {
    $users = Auth::user()->role->id == UserRole::DEVELOPER ? $this->usuarioRepository->all() : $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    return view('content.pages.mailing.importMailing', [
      'users' => $users
    ]);
  }

  public function importaMailing(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), $this->rulesUpload);
      if ($validator->fails()) {
        $firstError = $validator->errors()->first();

        return response()->json([
          'error' => true,
          'message' => $firstError
        ], 422);
      }

      $this->validateFileUploadExcel($request);

      return $this->mailingUseCase->importaMailing($request);
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => $th->getMessage()
      ]);
    }
  }
}
