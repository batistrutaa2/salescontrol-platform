<?php

namespace App\UseCases;

use App\Enums\Tabulations;
use App\Helpers\Helpers;
use App\Imports\ContatosImport;
use App\Models\ContatosCorretores;
use App\Repositories\Eloquent\ContatosRepository;
use Carbon\Carbon;
use Database\Seeders\Tabulacoes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class MailingUseCase
{
  protected $contatosRepository;

  public function __construct(ContatosRepository $contatosRepositoryInterface)
  {
    $this->contatosRepository = $contatosRepositoryInterface;
  }


  public function importaMailing(Request $request)
  {
    try {
      $uniqueIdBase = Helpers::generateUniqueId();

      $rows = Excel::toArray(new ContatosImport(Auth::user()->id, Auth::user()->empresa_id, $request->base, $uniqueIdBase), $request->file('file'));
      foreach ($rows[0] as $row) {
        if (!is_null($row['cpf'])) {
          $cpfs[] = Helpers::cleanSpecialCharacters($row['cpf']);
        }
      }

      $cpfsFound = $this->contatosRepository->searchForCpfsFound($cpfs);

      if (count($cpfsFound) > 0) {
        return response()->json([
          'message' => count($cpfsFound) . " CPFs já se encontram na sua base de dados.",
          'cpfs' => $cpfsFound,
          'error' => true,
        ]);
      } else {

        Excel::import(new ContatosImport(Auth::user()->id, Auth::user()->empresa_id, $request->base, $uniqueIdBase), $request->file('file'));

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



  public function createLead(array $data)
  {
    try {
      $uniqueIdBase = Helpers::generateUniqueId();
      $searchCpf = $this->contatosRepository->searchForCpfFound(Helpers::cleanSpecialCharacters($data['cpf']));

      if (!is_null($searchCpf)) {
        return [
          'status' => 'error',
          'message' => 'O CPF/CNPJ Cadastrado já existe na base de dados',
          'error' => true
        ];
      }

      $createBase = $this->contatosRepository->create([
        'id_operacao' => $uniqueIdBase,
        'empresa_id' => Auth::user()->empresa_id,
        'user_import_id' => Auth::user()->id,
        'nome_base' => !empty($data['nome_base']) ? $data['nome_base'] : 'BASE_IMPORTACAO_' . Auth::user()->name . '_' . Carbon::now(),
        'nome_cliente' => $data['nome_cliente'],
        'data_nascimento' => $data['nome_cliente'],
        'cpf' => Helpers::cleanSpecialCharacters($data['cpf']),
        'plano' => $data['plano'] ?? "",
        'categoria' => $data['categoria'] ?? "",
        'entidade' => Helpers::cleanSpecialCharacters($data['entidade']) ?? "",
        'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']) ?? "",
        'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']) ?? "",
        'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3']) ?? "",
        'email' => $data['email'] ?? "",
        'idades' => $data['idades'] ?? "",
        'valor_plano_atual' => Helpers::formatCurrencyToDecimal($data['valor_plano_atual']),
      ]);

      ContatosCorretores::create(
        [
          'empresa_id' => Auth::user()->empresa_id,
          'contato_id' => $createBase->id,
          'user_id' => Auth::user()->id,
          'tabulacao_id' => Tabulations::PROSPECCAO,
          'temperatura' => "FRIO",
          'created_at' => now(),
          'updated_at' => now(),
        ]
      );

      return [
        'status' => 'sucess',
        'message' => 'Cliente Cadastrado com sucesso',
        'error' => false
      ];
    } catch (\Throwable $th) {
      return [
        'status' => 'error',
        'message' => $th->getMessage(),
        'error' => true
      ];
    }
  }

  public function sendRemaketing(array $data)
  {

  }
}
