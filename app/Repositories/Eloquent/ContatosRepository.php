<?php

namespace App\Repositories\Eloquent;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use Helper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class ContatosRepository implements ContatosRepositoryInterface
{

  protected $model;

  public function __construct(Contatos $model)
  {
    $this->model = $model;
  }


  public function create(array $data)
  {
    return $this->model::create($data);
  }

  public function getNewlyImportedBase($idBase)
  {
    return $this->model->where('id_operacao', $idBase)->get();
  }


  public function all()
  {
    return $this->model->all();
  }

  public function find($id)
  {
    return $this->model->where('id', $id)->first();
  }

  public function searchForCpfsFound(array $cpfs)
  {
    $contatos = $this->model->whereIn('cpf', $cpfs)->where('empresa_id', Auth::user()->empresa_id)->get();

    $result = $contatos->map(function ($contato) {
      return [
        'id' => $contato->id,
        'nome' => $contato->nome_cliente,
        'telefone1' => $contato->telefone1,
        'telefone2' => $contato->telefone2,
        'telefone3' => $contato->telefone3,
        'cpf' => $contato->cpf,
      ];
    });

    return $result->toArray();
  }

  public function searchForCpfFound($cpf)
  {
    $contatos = $this->model->where('cpf', $cpf)->where('empresa_id', Auth::user()->empresa_id)->first();

    return $contatos;
  }



  public function updateContact($idMailing, $telefone1, $telefone2, $telefone3, $negotiationValue)
  {
    try {
      $contact = $this->find($idMailing);
      $contact->telefone1 = Helpers::cleanSpecialCharacters($telefone1) ?? "";
      $contact->telefone2 = Helpers::cleanSpecialCharacters($telefone2) ?? "";
      $contact->telefone3 = Helpers::cleanSpecialCharacters($telefone3) ?? "";
      $contact->valor_negociacao = Helpers::formatCurrencyToDecimal($negotiationValue);
      $contact->save();
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function updateOrCreate(array $data)
  {
    try {
      $serchClient = ["id" => $data['id']];
      if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER) {
        $dataClient = [
          'nome_cliente' => $data['nome_cliente'],
          'email' => $data['email'],
          'cpf' => Helpers::cleanSpecialCharacters($data['cpf']),
          'data_nascimento' => $data['data_nascimento'],
          'plano' => $data['plano'],
          'categoria' => $data['cartegoria'],
          'entidade' => $data['entidade'],
          'idades' => $data['idades'],
          'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
          'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']),
          'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3']),
          'valor_plano_atual' => Helpers::formatCurrencyToDecimal($data['valor_plano_atual']),
          'valor_negociacao' => Helpers::formatCurrencyToDecimal($data['valor_negociacao'])
        ];
      } else {
        $dataClient = [
          'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1']),
          'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2']),
          'telefone3' => Helpers::cleanSpecialCharacters($data['telefone3']),
          'valor_negociacao' => Helpers::formatCurrencyToDecimal($data['valor_negociacao'])
        ];
      }


      $this->model::updateOrCreate($serchClient, $dataClient);
      return true;
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function getLeads($empresa_id)
  {
    return DB::table('contatos as a')
      ->select(
        'a.id',
        'a.nome_base',
        'd.name as nome_corretor',
        'a.nome_cliente',
        'a.cpf',
        'a.telefone1 as telefone',
        'a.valor_plano_atual',
        'c.descricao as status',
        'a.created_at',
        DB::raw("
          CASE
              WHEN b.tabulacao_id = 1 AND DATEDIFF(CURDATE(), DATE(b.created_at)) > 5 THEN 'Fora do Prazo'
              WHEN b.tabulacao_id = 2 AND DATEDIFF(CURDATE(), DATE(b.created_at)) > 10 THEN 'Fora do Prazo'
              WHEN b.tabulacao_id = 3 AND DATEDIFF(CURDATE(), DATE(b.updated_at)) > 15 THEN 'Fora do Prazo'
              ELSE 'Dentro do Prazo'
          END AS Prazo
        ")
      )
      ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.id')
      ->leftJoin('tabulacoes as c', 'b.tabulacao_id', '=', 'c.id')
      ->leftJoin('users as d', 'b.user_id', '=', 'd.id')
      ->where('b.empresa_id', $empresa_id)
      ->get();
  }


  public function quantidadeContatosImportadosMes($month, $year, $empresa_id)
  {
    return $this->model::whereYear('created_at', 2024)
      ->where('empresa_id', $empresa_id)
      ->whereYear('created_at', $year)
      ->whereMonth('created_at', $month)
      ->count();
  }

  public function quantidadeContatosImportadosMesPorVendedor($month, $year, $empresa_id)
  {
    return DB::table('contatos as a')
      ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
      ->leftJoin('users as c', 'c.id', '=', 'b.user_id')
      ->select('c.name', DB::raw('COUNT(*) as quantidade'))
      ->whereYear('a.created_at', $year)
      ->whereMonth('a.created_at', $month)
      ->where('a.empresa_id', $empresa_id)
      ->whereNotNull('c.name')
      ->groupBy('c.name')
      ->get();
  }

  public function importarLeadsAds(array $data)
  {
      try {
          $lead = $this->model::create([
              'is_ads'        => 'Y',
              'tipo_criativo' => $data['tipo_criativo'] ?? null,
              'nome_cliente'  => $data['nome_cliente'] ?? null,
              'telefone1'     => $data['telefone1'] ?? null,
              'email'         => $data['email'] ?? null,
              'plano_ativo'   => $data['plano_ativo'] ?? 'N',
              'vidas'         => $data['vidas'] ?? null,
              'idades'        => $data['idades'] ?? null,
              'user_import_id'=> 1,
              'id_operacao' => Helpers::generateUniqueId(),
              'empresa_id' => 2,
              'created_at'    => now(),
              'updated_at'    => now(),
          ]);
          return response()->json(['message' => 'Lead importado com sucesso!', 'lead' => $lead], 201);
      } catch (\Throwable $th) {
          return response()->json(['error' => 'Erro ao importar lead', 'message' => $th->getMessage()], 500);
      }
  }


  public function getLeadsmarketing($empresa_id) {
    return $this->model::leftJoin('contatos_corretores as b', function ($join) use($empresa_id) {
        // Junção entre contatos e contatos_corretores
        $join->on('b.contato_id', '=', 'contatos.id')
             ->where('b.empresa_id', '=', $empresa_id); // Filtra a empresa no relacionamento com contatos_corretores
    })
    ->where('contatos.is_ads', 'Y')  // Verifica se o contato é um lead
    ->whereNull('b.contato_id')  // Filtra os contatos que não tem corretores associados
    ->where('contatos.empresa_id', '=', $empresa_id)  // Filtra a empresa diretamente na tabela contatos
    ->select('contatos.id', 'contatos.nome_cliente', 'contatos.email', 'contatos.telefone1', 'contatos.created_at')
    ->get();
  }

}

