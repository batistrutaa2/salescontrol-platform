<?php

namespace App\Http\Controllers\pages\mailing;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\Dependentes;
use App\Models\LeadAtividade;
use App\Models\Ligacoes;
use App\Models\LogPreditiva;
use App\Models\Preditiva;
use App\Models\TransferenciaContato;
use Illuminate\Http\Request;
use App\UseCases\MailingUseCase;
use App\Models\ContatosCorretores;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\ContatosImportDependencies;
use App\Imports\ContatosImportPreditiva;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;
use Carbon\Carbon;


class Mailing extends Controller
{
  protected UsuariosRepositoryInterface $usuarioRepository;
  protected TabulacoesRepository $tabulacoesRepository;

  protected BaseLegaceRespository $baseLegaceRespository;
  protected MailingUseCase $mailingUseCase;
  protected ContatosRepository $contatosRepository;
  protected VendasRepository $vendasRepository;
  private $rulesUpload = [
    'base' => 'required|string',
    'file' => 'required|file',
  ];

  public function __construct(
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ContatosRepositoryInterface $contatosRepositoryInterface,
    TabulacoesRepositoryInterface $tabulacoesRepositoryInterface,
    BaseLegaceRespositoryInterface $baseLegaceRespositoryInterface,
    VendasRepositoryInterface $vendasRepositoryInterface
  ) {

    $this->mailingUseCase = new MailingUseCase($contatosRepositoryInterface);

    $this->contatosRepository = $contatosRepositoryInterface;
    $this->usuarioRepository = $usuariosRepositoryInterface;
    $this->tabulacoesRepository = $tabulacoesRepositoryInterface;
    $this->baseLegaceRespository = $baseLegaceRespositoryInterface;
    $this->vendasRepository = $vendasRepositoryInterface;
  }

  public function index()
  {
    $users = $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    return view('content.pages.mailing.importMailing', [
      'users' => $users,
      'tabulacoes' => $tabulacoes
    ]);
  }

  public function importaMailing(Request $request)
  {
    try {
      if ($request->tipo_layout === "padrao") {
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
      } else {
        $request->validate([
          'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $rows = Excel::toArray(new ContatosImportDependencies($request->base, $request->tabulacao, $request->id_user), $request->file('file'));
     

        foreach ($rows[0] as $index => $row) {
          if ($index == 0) {
            continue;
          }

          if (!is_null($row[2]) && $row[4] == "TITULAR") {
            $cpfs[] = Helpers::cleanSpecialCharacters($row[2]);
          }
        }

        $cpfsFound = $this->contatosRepository->searchForCpfsFound($cpfs);

        if (count($cpfsFound) > 0) {
          return response()->json([
            'message' => count($cpfsFound) . " CPFs já se encontram na sua base de dados.",
            'cpfs' => $cpfsFound,
            'error' => true,
          ]);
        }

        Excel::import(new ContatosImportDependencies($request->base, $request->tabulacao, $request->id_user), $request->file('file'));
        return response()->json([
          'error' => false,
          'message' => "Mailing importado com sucesso.",
        ], 201);
      }
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => $th->getMessage()
      ]);
    }
  }

  public function deleteMailingLeadsDescarted($id)
  {
    try {
      $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($id);

      if ($searchForLaunchedSale) {
        return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', "Esse Lead possui venda cadastrada, exclusão cancelada.");
      }

      DB::beginTransaction();
      Comentarios::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Agendamento::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Dependentes::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Ligacoes::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      LeadAtividade::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      ContatosCorretores::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      LogPreditiva::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Preditiva::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      TransferenciaContato::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Contatos::where("id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      DB::commit();
      return redirect()->back()->with('status', 'success')->with('message', "Contato Excluido com sucesso");
    } catch (\Throwable $th) {
      DB::rollBack();
      return redirect()->route(route: 'mailing.leadDescartados')->with('status', 'error')->with('message', "Erro ao excluir Lead");
    }
  }


  public function deleteMailing($id)
  {
    try {
      $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($id);

      if ($searchForLaunchedSale) {
        return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', "Esse Lead possui venda cadastrada, exclusão cancelada.");
      }

      DB::beginTransaction();
      Comentarios::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Agendamento::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Dependentes::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Ligacoes::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      LeadAtividade::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      ContatosCorretores::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      LogPreditiva::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Preditiva::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      TransferenciaContato::where('contato_id', $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      Contatos::where("id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
      DB::commit();
      return redirect()->back()->with('status', 'success')->with('message', "Contato Excluido com sucesso");
    } catch (\Throwable $th) {
      DB::rollBack();
      return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', "Erro ao excluir Lead");
    }
  }

  public function viewLeads()
  {
    $users = $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulations = $this->tabulacoesRepository->getAll(Auth::user()->empresa_id);

    return view('content.pages.mailing.visualizar-leads', [
      'users' => $users,
      'tabulations' => $tabulations
    ]);
  }

  public function viewLeadslegacy()
  {

    if (Auth::user()->empresa_id == 2) {
      $contacts = $this->baseLegaceRespository->getContactsAll();
      return view('content.pages.mailing.visualizar-leads-legado', [
        'contatos' => $contacts
      ]);
    } else {
      return redirect()->route(route: 'mailing.viewLeads')->with('status', 'error')->with('message', "Acesso negado.");
    }
  }

  public function getLeads()
  {
    $data = $this->contatosRepository->getLeads(Auth::user()->empresa_id);
    return response()->json(['data' => $data]);
  }

  public function getLeadsLegacy($id_mailing)
  {
    $infoContact = $this->baseLegaceRespository->getContacts($id_mailing);
    $comments = $this->baseLegaceRespository->getCommentsMailing($id_mailing);

    return response()->json(
      [
        'contato' => $infoContact,
        'comentarios' => $comments
      ]
    );
  }

  public function contactsAdvertisement()
  {
    return view('content.pages.mailing.leads-anuncio');
  }


  public function preditiva()
  {
    $users = $this->usuarioRepository->getUserByCompany(Auth::user()->empresa_id);
    $tabulacoes = $this->tabulacoesRepository->getTabulationsCompanieCommercial(Auth::user()->empresa_id);
    return view('content.pages.mailing.preditiva', [
      'users' => $users,
      'tabulacoes' => $tabulacoes
    ]);
  }

  public function getPreditiva(Request $request)
  {

    $empresaId = Auth::user()->empresa_id;

    $hoje = Carbon::today();

    $totalLeadsFila = DB::table('preditiva')
      ->where('status', 'Y')
      ->where('empresa_id', $empresaId)
      ->count();

    $tentativasHoje = DB::table('log_preditiva')
      ->whereDate('created_at', $hoje)
      ->where('empresa_id', $empresaId)
      ->count();

    $conversoesHoje = DB::table('log_preditiva')
      ->whereDate('created_at', $hoje)
      ->where('acao', 'CONVERSAO')
      ->where('tabulacao', 'CONVERTIDO')
      ->where('empresa_id', $empresaId)
      ->count();

    $recusasHoje = DB::table('log_preditiva')
      ->whereDate('created_at', $hoje)
      ->where('acao', 'DESCARTE')
      ->whereIn('tabulacao', ['JA POSSUI PLANO', 'NAO INTERESSADO', 'NUMERO INEXISTENTE', 'NAO ATENDE'])
      ->where('empresa_id', $empresaId)
      ->count();


    // Query otimizada: buscar última tabulação por contato usando GROUP BY simples
    $query = DB::table('preditiva as p')
      ->join('contatos as c', 'c.id', '=', 'p.contato_id')
      ->leftJoin('log_preditiva as l', function($join) use ($empresaId) {
        $join->on('l.contato_id', '=', 'p.contato_id')
             ->where('l.empresa_id', '=', $empresaId);
      })
      ->where('p.status', 'Y')
      ->where('p.empresa_id', $empresaId);

    // Filtro por nome do cliente
    if ($request->has('nome_cliente') && $request->nome_cliente !== '') {
      $query->where('c.nome_cliente', 'LIKE', '%' . $request->nome_cliente . '%');
    }

    // Buscar os dados com última tabulação
    $leadsData = $query->select(
        'p.id as preditiva_id',
        'c.nome_cliente',
        'c.valor_plano_atual',
        'c.telefone1',
        'p.contato_id',
        DB::raw('COUNT(DISTINCT l.id) as tentativas'),
        DB::raw('(SELECT tabulacao FROM log_preditiva WHERE contato_id = p.contato_id AND empresa_id = ' . $empresaId . ' ORDER BY id DESC LIMIT 1) as ultima_tabulacao')
      )
      ->groupBy('p.id', 'c.nome_cliente', 'c.valor_plano_atual', 'c.telefone1', 'p.contato_id')
      ->get();

    // Aplicar filtros na coleção
    $leads = $leadsData;

    if ($request->filled('ultima_tabulacao')) {
      $leads = $leads->filter(function($lead) use ($request) {
        return $lead->ultima_tabulacao === $request->ultima_tabulacao;
      })->values();
    }

    // Filtro por quantidade de tentativas
    if ($request->filled('tentativas')) {
      $tentativasFiltro = $request->tentativas;

      $leads = $leads->filter(function($lead) use ($tentativasFiltro) {
        if ($tentativasFiltro === '5+') {
          return $lead->tentativas >= 5;
        }
        return $lead->tentativas == $tentativasFiltro;
      })->values();
    }

    return response()->json([
      'total_leads_fila' => $totalLeadsFila,
      'tentativas_hoje' => $tentativasHoje,
      'conversoes_hoje' => $conversoesHoje,
      'recusas_hoje' => $recusasHoje,
      'leads' => $leads
    ]);
  }

  public function leadDescartados()
  {
    return view('content.pages.mailing.leads-descartados');
  }

  public function getLeadsDescartados()
  {
    $empresaId = Auth::user()->empresa_id;
    $leadsDescartados = Contatos::select(['id', 'nome_cliente', 'cpf', 'telefone1', 'valor_plano_atual', 'created_at', 'nome_base', 'updated_at'])
      ->where('empresa_id', $empresaId)
      ->where('status', 'N')
      ->orderBy('updated_at', 'desc')
      ->get();

    return response()->json(['data' => $leadsDescartados]);
  }

  public function getComentariosLead($contatoId)
  {
    $comentarios = DB::table('comentarios')
      ->leftJoin('users', 'comentarios.user_id', '=', 'users.id')
      ->where('comentarios.contato_id', $contatoId)
      ->where('comentarios.empresa_id', Auth::user()->empresa_id)
      ->select('comentarios.*', 'users.name as autor')
      ->orderBy('comentarios.created_at', 'desc')
      ->get();


    return response()->json($comentarios);
  }

  public function desativarLeadPreditiva($contatoId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      DB::beginTransaction();

      // Remove da preditiva
      Preditiva::where('contato_id', $contatoId)
        ->where('empresa_id', $empresaId)
        ->update(['status' => 'N']);

      // Registra no log
      LogPreditiva::create([
        'empresa_id' => $empresaId,
        'user_id' => Auth::id(),
        'contato_id' => $contatoId,
        'tabulacao' => 'DESATIVADO',
        'acao' => 'DESCARTE'
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Lead desativado da preditiva com sucesso.'
      ]);
    } catch (\Throwable $th) {
      DB::rollBack();
      return response()->json([
        'success' => false,
        'message' => 'Erro ao desativar lead: ' . $th->getMessage()
      ], 500);
    }
  }

  public function excluirLeadPreditiva($contatoId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      // Verifica se existe venda cadastrada
      $searchForLaunchedSale = $this->vendasRepository->checkExistenceSale($contatoId);

      if ($searchForLaunchedSale) {
        return response()->json([
          'success' => false,
          'message' => 'Esse Lead possui venda cadastrada, exclusão cancelada.'
        ], 422);
      }

      DB::beginTransaction();

      // Desativa o contato
      Contatos::where('id', $contatoId)
        ->where('empresa_id', $empresaId)
        ->update(['status' => 'N']);

      // Remove da preditiva
      Preditiva::where('contato_id', $contatoId)
        ->where('empresa_id', $empresaId)
        ->delete();

      // Registra no log
      LogPreditiva::create([
        'empresa_id' => $empresaId,
        'user_id' => Auth::id(),
        'contato_id' => $contatoId,
        'tabulacao' => 'EXCLUIDO',
        'acao' => 'DESCARTE'
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Lead excluído permanentemente com sucesso.'
      ]);
    } catch (\Throwable $th) {
      DB::rollBack();
      return response()->json([
        'success' => false,
        'message' => 'Erro ao excluir lead: ' . $th->getMessage()
      ], 500);
    }
  }

  public function removerDaPreditiva($contatoId)
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      DB::beginTransaction();

      // Remove apenas da preditiva, mantém o contato ativo
      Preditiva::where('contato_id', $contatoId)
        ->where('empresa_id', $empresaId)
        ->delete();

      // Registra no log
      LogPreditiva::create([
        'empresa_id' => $empresaId,
        'user_id' => Auth::id(),
        'contato_id' => $contatoId,
        'tabulacao' => 'REMOVIDO DA FILA',
        'acao' => 'DESCARTE'
      ]);

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Lead removido da fila preditiva com sucesso.'
      ]);
    } catch (\Throwable $th) {
      DB::rollBack();
      return response()->json([
        'success' => false,
        'message' => 'Erro ao remover lead: ' . $th->getMessage()
      ], 500);
    }
  }

  public function getTabulacoesDistintas()
  {
    try {
      $empresaId = Auth::user()->empresa_id;

      $tabulacoes = DB::table('log_preditiva')
        ->where('empresa_id', $empresaId)
        ->whereNotNull('tabulacao')
        ->where('tabulacao', '!=', '')
        ->distinct()
        ->orderBy('tabulacao')
        ->pluck('tabulacao')
        ->values();

      return response()->json([
        'success' => true,
        'tabulacoes' => $tabulacoes
      ]);
    } catch (\Throwable $th) {
      return response()->json([
        'success' => false,
        'message' => 'Erro ao buscar tabulações: ' . $th->getMessage()
      ], 500);
    }
  }

  public function importarParaPreditiva(Request $request)
  {
    try {
      $request->validate([
        'file' => 'required|file|mimes:xlsx,csv',
        'base' => 'required|string',
      ]);

      $empresaId = Auth::user()->empresa_id;
      $ignorarDuplicados = $request->input('ignorar_duplicados', false);

      // Gerar ID único para a operação (usado como id_operacao)
      $uniqueIdBase = uniqid('preditiva_', true);

      // Primeira passada: ler o arquivo e extrair os CPFs válidos
      // Usar ContatosImport temporariamente para ler com cabeçalho
      $rows = Excel::toArray(new ContatosImportPreditiva($request->base, $uniqueIdBase, []), $request->file('file'));
      $cpfsParaValidar = [];

      foreach ($rows[0] as $row) {
        // CPF vem pela chave 'cpf' devido ao WithHeadingRow
        if (!empty($row['cpf'])) {
          $cpfLimpo = Helpers::cleanSpecialCharacters($row['cpf']);
          // Aceitar qualquer CPF não vazio após limpeza
          if (!empty($cpfLimpo)) {
            $cpfsParaValidar[] = $cpfLimpo;
          }
        }
      }

      // Buscar CPFs que já existem na base
      $cpfsExistentes = [];
      if (count($cpfsParaValidar) > 0) {
        $cpfsExistentes = Contatos::where('empresa_id', $empresaId)
          ->whereIn('cpf', $cpfsParaValidar)
          ->pluck('cpf')
          ->toArray();
      }

      // Se houver duplicados e o usuário não confirmou ignorá-los
      if (count($cpfsExistentes) > 0 && !$ignorarDuplicados) {
        $totalLeads = count($cpfsParaValidar);
        $totalDuplicados = count($cpfsExistentes);
        $totalNaoDuplicados = $totalLeads - $totalDuplicados;

        return response()->json([
          'error' => true,
          'duplicados_encontrados' => true,
          'message' => $totalDuplicados . ' CPF(s) duplicado(s) encontrado(s). ' . $totalNaoDuplicados . ' lead(s) podem ser importados.',
          'cpfs' => $cpfsExistentes,
          'total_leads' => $totalLeads,
          'total_duplicados' => $totalDuplicados,
          'total_nao_duplicados' => $totalNaoDuplicados,
        ], 422);
      }

      // Segunda passada: importar os dados (ignorando duplicados se solicitado)
      Excel::import(
        new ContatosImportPreditiva($request->base, $uniqueIdBase, $cpfsExistentes),
        $request->file('file')
      );

      $totalImportados = count($cpfsParaValidar) - count($cpfsExistentes);
      $mensagem = $totalImportados . ' lead(s) importado(s) para a preditiva com sucesso.';

      if (count($cpfsExistentes) > 0) {
        $mensagem .= ' ' . count($cpfsExistentes) . ' CPF(s) duplicado(s) foram ignorados.';
      }

      return response()->json([
        'error' => false,
        'message' => $mensagem,
        'total_importados' => $totalImportados,
        'total_ignorados' => count($cpfsExistentes),
      ], 201);
    } catch (\Throwable $th) {
      return response()->json([
        'error' => true,
        'message' => 'Erro ao importar leads: ' . $th->getMessage()
      ], 500);
    }
  }

  public function indexImportarPreditiva()
  {
    return view('content.pages.mailing.importarPreditiva');
  }

}
