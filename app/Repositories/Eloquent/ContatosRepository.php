<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use Helper;
use Illuminate\Http\Request;
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
      if (Auth::user()->role->id === UserRole::ADMINISTRATIVO || Auth::user()->role->id === UserRole::DEVELOPER || Auth::user()->role->id === UserRole::VENDEDOR) {
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
        DB::raw("
          CASE
              WHEN b.contato_id IS NULL THEN 'PREDITIVA'
              ELSE c.descricao
          END as status
        "),
        'a.created_at'
      )
      ->leftJoin('contatos_corretores as b', function($join) use ($empresa_id) {
          $join->on('b.contato_id', '=', 'a.id')
               ->where('b.empresa_id', '=', $empresa_id);
      })
      ->leftJoin('tabulacoes as c', 'b.tabulacao_id', '=', 'c.id')
      ->leftJoin('users as d', 'b.user_id', '=', 'd.id')
      ->where('a.status', 'Y')
      ->where(function($query) use ($empresa_id) {
          // Contatos que estão relacionados a esta empresa
          $query->where(function($subquery) use ($empresa_id) {
              $subquery->whereNotNull('b.contato_id')
                       ->where('b.empresa_id', $empresa_id);
          })
          // OU contatos que não estão em nenhum relacionamento
          ->orWhereNotExists(function($subquery) {
              $subquery->select(DB::raw(1))
                       ->from('contatos_corretores')
                       ->whereRaw('contatos_corretores.contato_id = a.id');
          });
      })
      ->get();
  }


  public function quantidadeContatosImportadosMes($month, $year, $empresa_id)
  {
    return $this->model::whereYear('created_at', now()->year)
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


  public function getDistinctBases(int $empresaId): array
  {
    return DB::table('contatos')
      ->where('empresa_id', $empresaId)
      ->whereNotNull('nome_base')
      ->where('nome_base', '!=', '')
      ->distinct()
      ->pluck('nome_base')
      ->sort()
      ->values()
      ->toArray();
  }

  public function getAllLeadsServerSide(int $empresaId, Request $request): array
  {
    $remarketingId = Tabulations::REMARKETING;

    $baseQuery = DB::table('contatos as a')
      ->leftJoin('contatos_corretores as b', function ($join) use ($empresaId) {
        $join->on('b.contato_id', '=', 'a.id')
          ->where('b.empresa_id', '=', $empresaId);
      })
      ->leftJoin('tabulacoes as c', 'b.tabulacao_id', '=', 'c.id')
      ->leftJoin('users as d', 'b.user_id', '=', 'd.id')
      ->leftJoin('preditiva as p', function ($join) use ($empresaId) {
        $join->on('p.contato_id', '=', 'a.id')
          ->where('p.empresa_id', '=', $empresaId)
          ->where('p.status', '=', 'Y');
      })
      ->where('a.empresa_id', $empresaId)
      ->select(
        'a.id',
        'a.nome_cliente',
        'a.cpf',
        'a.telefone1',
        'a.valor_plano_atual',
        'a.nome_base',
        'a.status as lead_status',
        DB::raw("DATE_FORMAT(a.created_at, '%d/%m/%Y') as created_at"),
        'd.name as nome_corretor',
        'c.descricao as tabulacao',
        'b.tabulacao_id',
        DB::raw("
          CASE
            WHEN a.status = 'N' THEN 'DESCARTADO'
            WHEN p.contato_id IS NOT NULL AND b.contato_id IS NULL THEN 'PREDITIVA'
            WHEN b.contato_id IS NOT NULL AND b.tabulacao_id = {$remarketingId} THEN 'REMARKETING'
            WHEN b.contato_id IS NOT NULL THEN 'COM VENDEDOR'
            ELSE 'SEM ATRIBUICAO'
          END as situacao
        ")
      );

    // Filtro: situacao
    if ($request->filled('situacao')) {
      $situacao = $request->situacao;
      $baseQuery->where(function ($q) use ($situacao, $remarketingId) {
        switch ($situacao) {
          case 'DESCARTADO':
            $q->where('a.status', 'N');
            break;
          case 'PREDITIVA':
            $q->where('a.status', 'Y')->whereNotNull('p.contato_id')->whereNull('b.contato_id');
            break;
          case 'REMARKETING':
            $q->where('a.status', 'Y')->whereNotNull('b.contato_id')->where('b.tabulacao_id', $remarketingId);
            break;
          case 'COM VENDEDOR':
            $q->where('a.status', 'Y')->whereNotNull('b.contato_id')->where('b.tabulacao_id', '!=', $remarketingId);
            break;
          case 'SEM ATRIBUICAO':
            $q->where('a.status', 'Y')->whereNull('b.contato_id')->whereNull('p.contato_id');
            break;
        }
      });
    }

    // Filtro: corretor
    if ($request->filled('corretor')) {
      $baseQuery->where('b.user_id', $request->corretor);
    }

    // Filtro: nome_base
    if ($request->filled('nome_base')) {
      $baseQuery->where('a.nome_base', $request->nome_base);
    }

    // Filtro: data_importacao (dd/mm/yyyy)
    if ($request->filled('data_importacao')) {
      $dateParts = explode('/', $request->data_importacao);
      if (count($dateParts) === 3) {
        $dateFormatted = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";
        $baseQuery->whereDate('a.created_at', $dateFormatted);
      }
    }

    // Filtro: data range (dd/mm/yyyy a dd/mm/yyyy)
    if ($request->filled('data_inicio') && $request->filled('data_fim')) {
      $inicParts = explode('/', $request->data_inicio);
      $fimParts = explode('/', $request->data_fim);
      if (count($inicParts) === 3 && count($fimParts) === 3) {
        $dataInicio = "{$inicParts[2]}-{$inicParts[1]}-{$inicParts[0]}";
        $dataFim = "{$fimParts[2]}-{$fimParts[1]}-{$fimParts[0]}";
        $baseQuery->whereDate('a.created_at', '>=', $dataInicio)
          ->whereDate('a.created_at', '<=', $dataFim);
      }
    }

    // Total records (sem filtro de busca)
    $recordsTotal = DB::table('contatos')->where('empresa_id', $empresaId)->count();

    // Busca global
    $searchValue = $request->input('search.value', '');
    if ($searchValue !== '') {
      $baseQuery->where(function ($q) use ($searchValue) {
        $q->where('a.nome_cliente', 'LIKE', "%{$searchValue}%")
          ->orWhere('a.cpf', 'LIKE', "%{$searchValue}%")
          ->orWhere('a.telefone1', 'LIKE', "%{$searchValue}%")
          ->orWhere('a.nome_base', 'LIKE', "%{$searchValue}%")
          ->orWhere('d.name', 'LIKE', "%{$searchValue}%");
      });
    }

    // Records filtered (após filtros + busca)
    $recordsFiltered = $baseQuery->count();

    // Ordenação
    $orderColumnIndex = $request->input('order.0.column', 0);
    $orderDir = $request->input('order.0.dir', 'desc');
    $columns = ['a.id', 'a.id', 'a.nome_cliente', 'a.cpf', 'd.name', 'situacao', 'c.descricao', 'a.nome_base', 'a.created_at'];
    $orderColumn = $columns[$orderColumnIndex] ?? 'a.id';

    if ($orderColumn === 'situacao') {
      $baseQuery->orderByRaw("
        CASE
          WHEN a.status = 'N' THEN 'DESCARTADO'
          WHEN p.contato_id IS NOT NULL AND b.contato_id IS NULL THEN 'PREDITIVA'
          WHEN b.contato_id IS NOT NULL AND b.tabulacao_id = {$remarketingId} THEN 'REMARKETING'
          WHEN b.contato_id IS NOT NULL THEN 'COM VENDEDOR'
          ELSE 'SEM ATRIBUICAO'
        END {$orderDir}
      ");
    } else {
      $baseQuery->orderBy($orderColumn, $orderDir);
    }

    // Paginação
    $start = $request->input('start', 0);
    $length = $request->input('length', 25);
    $data = $baseQuery->offset($start)->limit($length)->get();

    return [
      'draw' => (int) $request->input('draw', 1),
      'recordsTotal' => $recordsTotal,
      'recordsFiltered' => $recordsFiltered,
      'data' => $data,
    ];
  }

  public function getLeadsmarketing($empresa_id) {
      return $this->model::leftJoin('contatos_corretores as b', function ($join) use ($empresa_id) {
        $join->on('b.contato_id', '=', 'contatos.id')
            ->where('b.empresa_id', '=', $empresa_id);
    })
    ->leftJoin('preditiva as p', function ($join) use ($empresa_id) {
        $join->on('p.contato_id', '=', 'contatos.id')
            ->where('p.empresa_id', '=', $empresa_id);
    })
    ->where('contatos.is_ads', 'Y')
    ->whereNull('b.contato_id')
    ->whereNull('p.contato_id')
    ->where('contatos.empresa_id', '=', $empresa_id)
    ->select('contatos.id', 'contatos.nome_cliente', 'contatos.email', 'contatos.telefone1', 'contatos.created_at')
    ->get();
  }

}
