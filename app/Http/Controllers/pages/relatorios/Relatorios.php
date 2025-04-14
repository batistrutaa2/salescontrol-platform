<?php

namespace App\Http\Controllers\pages\relatorios;

use App\Models\LogPreditiva;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Eloquent\LigacoesRepository;
use App\Repositories\Eloquent\UsuariosRepository;
use App\Repositories\Contracts\LigacoesRepositoryInterface;
use App\Repositories\Contracts\UsuariosRepositoryInterface;
use App\Repositories\Eloquent\ContatosCorretoresRepository;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;

class Relatorios extends Controller
{
  protected LigacoesRepository $ligacoesRepository;
  protected UsuariosRepository $usuariosRepository;

  protected ContatosCorretoresRepository $contatosCorretoresRepository;
  public function __construct(
    LigacoesRepositoryInterface $ligacoesRepositoryInterface,
    UsuariosRepositoryInterface $usuariosRepositoryInterface,
    ContatosCorretoresRepositoryInterface $ContatosCorretoresRepositoryInterface
  ) {
    $this->ligacoesRepository = $ligacoesRepositoryInterface;
    $this->usuariosRepository = $usuariosRepositoryInterface;
    $this->contatosCorretoresRepository = $ContatosCorretoresRepositoryInterface;
  }

  public function index()
  {
    $user = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
    return view('content.pages.relatorios.ligacoes', [
      'users' => $user
    ]);
  }


  public function getLigacoes($id_user, $data_inicial, $data_final)
  {
    $ligacoes = $this->ligacoesRepository->getLigacoes($id_user, $data_inicial, $data_final);
    $filaAtual = $this->contatosCorretoresRepository->getQueueCurrent($id_user);

    return response()->json([
      'ligacoes' => $ligacoes,
      'fila' => $filaAtual
    ]);
  }


  public function predictiveReport() {
      $users = $this->usuariosRepository->getUserByCompany(Auth::user()->empresa_id);
      return view('content.pages.relatorios.preditiva', [
        'usuarios' => $users
      ]);
  }

  public function get(Request $request)
  {
      $dataInicio = Carbon::parse($request->data_inicio)->startOfDay();
      $dataFim = Carbon::parse($request->data_fim)->endOfDay();
      $usuarioId = $request->usuario_id;
      $empresaId = auth()->user()->empresa_id; // Assumindo que o usuário logado tem empresa_id

      // Consulta base nos logs de preditiva
      $query = LogPreditiva::whereBetween('created_at', [$dataInicio, $dataFim])
          ->where('empresa_id', $empresaId);

      if ($usuarioId) {
          $query->where('user_id', $usuarioId);
      }

      // Obter logs com relacionamentos
      $logs = $query->with(['user', 'contato'])->get();

      // Preparar dados para a tabela
      $dadosTabela = $logs->map(function ($log) {
          return [
              'data' => $log->created_at->format('d/m/Y H:i'),
              'usuario' => $log->user->name ?? 'N/A',
              'cliente' => $log->contato->nome_cliente ?? 'N/A',
              'telefone' => $log->contato->telefone1 ?? 'N/A',
              'status' => $log->acao,
              'tabulacao' => $log->tabulacao,
              'observacao' => '' // Não há campo de observação no log_preditiva, então deixamos vazio
          ];
      });

      // Calcular resumo
      $total = $logs->count();
      $convertidos = $logs->where('acao', 'CONVERSAO')->count();
      $descartados = $logs->where('acao', 'DESCARTE')->count();
      $taxaConversao = $total > 0 ? round(($convertidos / $total) * 100, 1) . '%' : '0%';

      // Preparar dados para gráfico diário
      $periodoCompleto = collect(new \DatePeriod(
          $dataInicio,
          new \DateInterval('P1D'),
          $dataFim->addDay() // Adicionamos um dia para incluir o último dia no período
      ))->map(function ($date) {
          return $date->format('Y-m-d');
      });

      $logsPorDia = $logs->groupBy(function ($log) {
          return $log->created_at->format('Y-m-d');
      });

      $dadosGraficoDiario = [
          'datas' => $periodoCompleto->map(function ($data) {
              return Carbon::parse($data)->format('d/m');
          })->toArray(),
          'contatos' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
              return $logsPorDia->get($data, collect())->count();
          })->toArray(),
          'convertidos' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
              return $logsPorDia->get($data, collect())->where('acao', 'CONVERSAO')->count();
          })->toArray(),
          'descartados' => $periodoCompleto->map(function ($data) use ($logsPorDia) {
              return $logsPorDia->get($data, collect())->where('acao', 'DESCARTE')->count();
          })->toArray()
      ];

      // Preparar dados para gráfico de status
      $statusCount = $logs->groupBy('acao')->map->count();

      // Traduzir os status para exibição
      $statusLabels = $statusCount->keys()->map(function ($key) {
          switch ($key) {
              case 'CONVERSAO':
                  return 'Convertido';
              case 'DESCARTE':
                  return 'Descartado';
              default:
                  return $key;
          }
      })->toArray();

      $dadosGraficoStatus = [
          'labels' => $statusLabels,
          'valores' => $statusCount->values()->toArray()
      ];

      // Adicionar dados de tabulação
      $tabulacaoCount = $logs->groupBy('tabulacao')->map->count();
      $dadosGraficoTabulacao = [
          'labels' => $tabulacaoCount->keys()->toArray(),
          'valores' => $tabulacaoCount->values()->toArray()
      ];


      return response()->json([
          'success' => true,
          'atividades' => $dadosTabela,
          'resumo' => [
              'total' => $total,
              'convertidos' => $convertidos,
              'descartados' => $descartados,
              'taxa_conversao' => $taxaConversao
          ],
          'grafico_diario' => $dadosGraficoDiario,
          'grafico_status' => $dadosGraficoStatus,
          'grafico_tabulacao' => $dadosGraficoTabulacao
      ]);
  }


}
