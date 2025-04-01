<?php

namespace App\Http\Controllers\pages\mailing;

use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\Contatos;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\Dependentes;
use App\Models\Ligacoes;
use Illuminate\Http\Request;
use App\UseCases\MailingUseCase;
use App\Models\ContatosCorretores;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use App\Imports\ContatosImportDependencies;
use App\Repositories\Eloquent\VendasRepository;
use App\Repositories\Eloquent\ContatosRepository;
use App\Repositories\Eloquent\TabulacoesRepository;
use App\Repositories\Eloquent\BaseLegaceRespository;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Repositories\Contracts\ContatosRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;
use App\Repositories\Contracts\BaseLegaceRespositoryInterface;

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

          if (!is_null($row[1]) && $row[3] == "TITULAR") {
            $cpfs[] = Helpers::cleanSpecialCharacters($row[1]);
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
      ContatosCorretores::where("contato_id", $id)->where("empresa_id", Auth::user()->empresa_id)->delete();
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

  public function contactsAdvertisement() {
    return view('content.pages.mailing.leads-anuncio');
  }
}
