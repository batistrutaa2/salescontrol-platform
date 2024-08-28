<?php

namespace App\UseCases;

use App\Enums\Tabulations;
use App\Helpers\Helpers;
use App\Imports\ContatosImport;
use App\Models\ContatosCorretores;
use App\Repositories\Eloquent\ContatosRepository;
use Database\Seeders\Tabulacoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MailingUseCase
{
  protected $contatosRepository;

  public function __construct(ContatosRepository $contatosRepositoryInterface)
  {
    $this->contatosRepository =  $contatosRepositoryInterface;
  }


  public function importaMailing(Request $request)
  {
    try {
      $uniqueIdBase = Helpers::generateUniqueId();

      $rows = Excel::toArray(new ContatosImport(Auth::user()->id, Auth::user()->empresa_id, $request->base, $uniqueIdBase), $request->file('file'));
      foreach ($rows[0] as $row) {
        $cpfs[] = Helpers::cleanSpecialCharacters($row['cpf']);
      }

      $cpfsFound = $this->contatosRepository->searchForCpfsFound($cpfs);

      if (count($cpfsFound) > 0) {
        return response()->json([
          'message' => count($cpfsFound) . " CPFs já se encontram na sua base de dados.",
          'cpfs' => $cpfsFound,
          'error' => true,
        ]);
      } else {

        Excel::import(new ContatosImport(Auth::user()->id, Auth::user()->empresa_id, $request->base, $uniqueIdBase),  $request->file('file'));

        $importedContacts = $this->contatosRepository->getNewlyImportedBase($uniqueIdBase);

        $newRelationship = collect();

        foreach ($importedContacts as $contato) {
          $newRelationship->push([
            'empresa_id' => $contato->empresa_id,
            'contato_id' => $contato->id,
            'user_id' => $request->id_user,
            'tabulacao_id' => $request->tabulacao,
            'temperatura' => "FRIO",
            'created_at' => now(),
            'updated_at' => now(),
          ]);
        }

        $newRelationship->each(function ($contatoCorretor) {
          ContatosCorretores::create($contatoCorretor);
        });

        return response()->json([
          'error' => false,
          'message' => "Mailing importado com sucesso.",
          'cpfs' => $cpfsFound,
        ], 201);
      }
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => $th->getMessage()
      ]);
    }
  }
}
