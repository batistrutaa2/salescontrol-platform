<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\ContatosCorretores;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\Vendas;
use App\Models\VendaTitular;
use App\Models\VendaDependente;
use App\Models\VendaPortabilidade;
use App\Models\VendaHistorico;
use App\Repositories\Contracts\VendasRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VendasRepository implements VendasRepositoryInterface
{
  protected $model;

  public function __construct(Vendas $model)
  {
    $this->model = $model;
  }

  public function create(array $data)
  {
    try {
      return DB::transaction(function () use ($data) {
        $contatoId = (int) ($data['contato_id'] ?? 0);
        $operadoraId = (int) ($data['operadora_id'] ?? 0);
        $titulares = is_array($data['titulares'] ?? null) ? $data['titulares'] : [];

        // Detectar se é novo layout PME
        $tipoContrato = $data['tipo_contrato'] ?? null;
        $isNovoLayout = !empty($tipoContrato) && $tipoContrato === 'PME';

        $nomeContrato = strtoupper(trim($data['nome_contrato'] ?? ''));
        $cpfCnpj = Helpers::cleanSpecialCharacters($data['cpf_cnpj'] ?? '');
        $emailEmpresa = $data['email'] ?? null;
        $tel1 = Helpers::cleanSpecialCharacters($data['telefone1'] ?? '');
        $tel2 = Helpers::cleanSpecialCharacters($data['telefone2'] ?? '');
        $valorContrato = Helpers::converterParaDecimal($data['valor_contrato'] ?? '0');
        $taxaAngariacao = Helpers::converterParaDecimal($data['taxa_angariacao'] ?? '0');
        $vidas = (int) ($data['vidas'] ?? count($titulares));
        $obsContrato = $data['obs_contrato'] ?? null;

        $operadora = Operadora::findOrFail($operadoraId);

        $primeiroTitular = $titulares[0] ?? null;
        $planoPrimeiro = $primeiroTitular
          ? Plano::find($primeiroTitular['plano_id'] ?? null)
          : null;

        $copValues = array_values(array_unique(array_map(function ($t) {
          return isset($t['coparticipacao']) && $t['coparticipacao'] !== ''
            ? strtoupper($t['coparticipacao'])
            : null;
        }, $titulares)));

        $coparticipacaoVenda = null;
        if (count($copValues) === 1) {
          $coparticipacaoVenda = $copValues[0];
        }

        // Usar valor do formulário, com fallback para Supermed
        $isAngariacao = $data['angariacao_status']
            ?? ($operadora->nome === "AMIL - SUPERMED" ? "SIM" : "NÃO");

        // Dados base da venda
        $vendaData = [
          'empresa_id' => Auth::user()->empresa_id,
          'user_id' => Auth::user()->id,
          'contato_id' => $contatoId,
          'nome_contrato' => $nomeContrato,
          'cpf_cnpj' => $cpfCnpj,
          'email' => $emailEmpresa,
          'telefone1' => $tel1,
          'telefone2' => $tel2,
          'operadora' => $operadora->nome,
          'operadora_id' => $operadoraId,
          'nome_plano' => $planoPrimeiro?->nome,
          'plano_id' => $planoPrimeiro?->id,
          'valor_contrato' => $valorContrato,
          'vidas' => $vidas,
          'obs_contrato' => $obsContrato,
          'data_vigencia' => now(),
          'coparticipacao' => $coparticipacaoVenda,
          'angariacao_valor' => $taxaAngariacao,
          'angariacao_status' => $isAngariacao,
        ];

        // Campos adicionais para novo layout PME
        if ($isNovoLayout) {
          $vendaData['layout_venda'] = 'NOVO';
          $vendaData['tipo_contrato'] = $tipoContrato;
          $vendaData['tipo_empresa'] = $data['tipo_empresa'] ?? null;
          $vendaData['portabilidade_status'] = $data['portabilidade_status'] ?? 'NAO';
          $vendaData['qtd_portabilidade'] = (int) ($data['qtd_portabilidade'] ?? 0);

          // Converter data de abertura de d/m/Y para Y-m-d
          if (!empty($data['data_abertura'])) {
            $dataAbertura = $this->converterDataBrParaDb($data['data_abertura']);
            if ($dataAbertura) {
              $vendaData['data_abertura'] = $dataAbertura;
            }
          }
        } else {
          $vendaData['layout_venda'] = 'ANTIGO';
        }

        $venda = $this->model->create($vendaData);

        // Processar titulares
        if (!empty($titulares)) {
          foreach ($titulares as $titularData) {
            $titularPayload = [
              'nome' => mb_strtoupper(trim($titularData['nome'] ?? ''), 'UTF-8'),
              'email' => $titularData['email'] ?? null,
              'telefone' => Helpers::cleanSpecialCharacters($titularData['telefone1'] ?? $titularData['telefone'] ?? ''),
              'plano_id' => !empty($titularData['plano_id']) ? (int) $titularData['plano_id'] : null,
              'coparticipacao' => isset($titularData['coparticipacao']) && $titularData['coparticipacao'] !== ''
                ? strtoupper($titularData['coparticipacao'])
                : null,
            ];

            // Campos adicionais para novo layout
            if ($isNovoLayout) {
              $titularPayload['telefone2'] = Helpers::cleanSpecialCharacters($titularData['telefone2'] ?? '');
              $titularPayload['cargo'] = $titularData['cargo'] ?? null;
              $titularPayload['cpf'] = Helpers::cleanSpecialCharacters($titularData['cpf'] ?? '');
              $titularPayload['plano_anterior'] = $titularData['plano_anterior'] ?? 'NAO';
              $titularPayload['operadora_anterior_id'] = !empty($titularData['operadora_anterior_id'])
                ? (int) $titularData['operadora_anterior_id']
                : null;

              if (!empty($titularData['data_nascimento'])) {
                $dataNasc = $this->converterDataBrParaDb($titularData['data_nascimento']);
                if ($dataNasc) {
                  $titularPayload['data_nascimento'] = $dataNasc;
                }
              }
            }

            $titular = $venda->titulares()->create($titularPayload);

            // Processar dependentes do titular (somente novo layout)
            if ($isNovoLayout && !empty($titularData['dependentes']) && is_array($titularData['dependentes'])) {
              foreach ($titularData['dependentes'] as $depData) {
                $dependentePayload = [
                  'venda_id' => $venda->id,
                  'titular_id' => $titular->id,
                  'nome' => mb_strtoupper(trim($depData['nome'] ?? ''), 'UTF-8'),
                  'email' => $depData['email'] ?? null,
                  'telefone1' => Helpers::cleanSpecialCharacters($depData['telefone1'] ?? ''),
                  'telefone2' => Helpers::cleanSpecialCharacters($depData['telefone2'] ?? ''),
                  'parentesco' => $depData['parentesco'] ?? null,
                  'plano_anterior' => $depData['plano_anterior'] ?? 'NAO',
                  'operadora_anterior_id' => !empty($depData['operadora_anterior_id'])
                    ? (int) $depData['operadora_anterior_id']
                    : null,
                ];

                if (!empty($depData['cpf'])) {
                  $dependentePayload['cpf'] = Helpers::cleanSpecialCharacters($depData['cpf']);
                }

                if (!empty($depData['data_nascimento'])) {
                  $dataNascDep = $this->converterDataBrParaDb($depData['data_nascimento']);
                  if ($dataNascDep) {
                    $dependentePayload['data_nascimento'] = $dataNascDep;
                  }
                }

                VendaDependente::create($dependentePayload);
              }
            }
          }
        }

        // Processar portabilidades (somente novo layout)
        if ($isNovoLayout && !empty($data['portabilidades']) && is_array($data['portabilidades'])) {
          $sequencial = 1;
          foreach ($data['portabilidades'] as $portData) {
            if (empty($portData['nome'])) {
              continue;
            }

            VendaPortabilidade::create([
              'venda_id' => $venda->id,
              'nome' => mb_strtoupper(trim($portData['nome'] ?? ''), 'UTF-8'),
              'cpf' => !empty($portData['cpf']) ? Helpers::cleanSpecialCharacters($portData['cpf']) : null,
              'operadora_anterior_id' => !empty($portData['operadora_anterior_id'])
                ? (int) $portData['operadora_anterior_id']
                : null,
              'plano_anterior' => $portData['plano_anterior'] ?? null,
              'numero_carteirinha' => $portData['numero_carteirinha'] ?? null,
              'sequencial' => $sequencial++,
            ]);
          }
        }

        // Atualizar contatos_corretores para o status VENDA (backoffice)
        ContatosCorretores::where('contato_id', $contatoId)
          ->update([
            'tabulacao_id' => Tabulations::VENDA,
            'updated_at' => now(),
          ]);

        // Criar histórico inicial com status VENDA
        VendaHistorico::create([
          'empresa_id' => Auth::user()->empresa_id,
          'venda_id' => $venda->id,
          'user_id' => Auth::user()->id,
          'tabulacao_anterior_id' => null,
          'tabulacao_nova_id' => Tabulations::VENDA,
          'observacao' => 'Venda cadastrada no sistema',
        ]);

        return true;
      });
    } catch (\Throwable $ex) {
      throw $ex; // Propaga o erro para o controller tratar
    }
  }

  /**
   * Converte data do formato brasileiro (d/m/Y) para formato do banco (Y-m-d)
   */
  private function converterDataBrParaDb(?string $dataBr): ?string
  {
    if (empty($dataBr)) {
      return null;
    }

    $partes = explode('/', $dataBr);
    if (count($partes) !== 3) {
      return null;
    }

    $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
    $mes = str_pad($partes[1], 2, '0', STR_PAD_LEFT);
    $ano = $partes[2];

    return "{$ano}-{$mes}-{$dia}";
  }


  public function find($id)
  {
    return $this->model::find($id);
  }

  public function all($empresa_id)
  {
    return $this->model::select([
      'vendas.id',
      'users.name',
      'vendas.nome_contrato',
      'vendas.cpf_cnpj',
      'vendas.telefone1',
      'tabulacoes.descricao',
      'vendas.valor_contrato',
      'vendas.motivo_pendencia',
      'vendas.created_at',
      'vendas.updated_at',
      'tabulacoes.prazo'
    ])
      ->where('vendas.empresa_id', $empresa_id)
      ->leftJoin('contatos_corretores', 'vendas.contato_id', '=', 'contatos_corretores.contato_id')
      ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
      ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id')
      ->orderBy('vendas.created_at', 'desc')
      ->get();
  }

  public function getSalesFilter($startDate, $endDate, $empresa_id)
  {

    return $this->model::select([
      'vendas.id',
      'users.name',
      'vendas.nome_contrato',
      'vendas.cpf_cnpj',
      'vendas.telefone1',
      'tabulacoes.descricao',
      'vendas.valor_contrato',
      'vendas.motivo_pendencia',
      'vendas.created_at',
      'vendas.updated_at',
      'tabulacoes.prazo'
    ])
      ->whereBetween('vendas.created_at', [$startDate, $endDate])
      ->where('vendas.empresa_id', $empresa_id)
      ->leftJoin('contatos_corretores', 'vendas.contato_id', '=', 'contatos_corretores.contato_id')
      ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
      ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id')
      ->orderBy('vendas.created_at', 'desc')
      ->get();
  }

  public function vendasDoMesAnoAtual($user_id, $empresa_id, $role_user_id)
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if (Auth::user()->user_role_id == UserRole::VENDEDOR) {

      return DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->leftJoin('users as c', 'a.user_id', '=', 'c.id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at', 'c.name as nome_corretor')
        ->where('a.user_id', Auth::user()->id)
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();
    } else {
      return DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->leftJoin('users as c', 'a.user_id', '=', 'c.id')
        ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'b.tabulacao_id as status', 'a.created_at', 'c.name as nome_corretor')
        ->where('a.empresa_id', Auth::user()->empresa_id)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();
    }
  }


  public function totalVendasCadastradasAnoMesAtual($user_id, $empresa_id, $role_user_id)
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if ($role_user_id == UserRole::VENDEDOR) {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.user_id', $user_id)
        ->where('a.empresa_id', $empresa_id)
        ->whereIn('b.tabulacao_id', [Tabulations::VENDA, Tabulations::IMPLANTADO])
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    } else {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.empresa_id', $empresa_id)
        ->whereIn('b.tabulacao_id', [Tabulations::VENDA, Tabulations::IMPLANTADO])
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    }
  }


  public function totalVendasImplantadasAnoMesAtual($user_id, $empresa_id, $role_user_id)
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if ($role_user_id == UserRole::VENDEDOR) {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.user_id', $user_id)
        ->where('a.empresa_id', $empresa_id)
        ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    } else {
      $vendas = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_vendido'),
          DB::raw('COUNT(a.id) as quantidade_vendida')
        )
        ->where('a.empresa_id', $empresa_id)
        ->where('b.tabulacao_id', Tabulations::IMPLANTADO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
        'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
      ];
    }
  }

  public function totalVendasEstornadasAnoMesAtual($user_id, $empresa_id, $role_user_id)
  {
    $currentMonth = Carbon::now()->month;
    $currentYear = Carbon::now()->year;

    if ($role_user_id == UserRole::VENDEDOR) {
      $estornos = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_estornado'),
          DB::raw('COUNT(a.id) as quantidade_estornada')
        )
        ->where('a.user_id', $user_id)
        ->where('a.empresa_id', $empresa_id)
        ->where('b.tabulacao_id', Tabulations::ESTORNO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_estornado' => $estornos[0]->valor_estornado ?? 0.0,
        'quantidade_estornada' => $estornos[0]->quantidade_estornada ?? 0.0,
      ];
    } else {
      $estornos = DB::table('vendas as a')
        ->leftJoin('contatos_corretores as b', 'b.contato_id', '=', 'a.contato_id')
        ->select(
          DB::raw('SUM(a.valor_contrato) as valor_estornado'),
          DB::raw('COUNT(a.id) as quantidade_estornada')
        )
        ->where('a.empresa_id', $empresa_id)
        ->where('b.tabulacao_id', Tabulations::ESTORNO)
        ->whereMonth('a.created_at', $currentMonth)
        ->whereYear('a.created_at', $currentYear)
        ->get();

      return [
        'valor_estornado' => $estornos[0]->valor_estornado ?? 0.0,
        'quantidade_estornada' => $estornos[0]->quantidade_estornada ?? 0.0,
      ];
    }
  }


  public function conversaoMensal($user_id, $empresa_id, $role_user_id)
  {
    if ($role_user_id == UserRole::VENDEDOR) {
      $quantidadeVendasMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where(function ($query) {
          $query->where('b.tabulacao_id', Tabulations::VENDA)
            ->orWhere('b.tabulacao_id', Tabulations::IMPLANTADO);
        })
        ->where('b.user_id', $user_id)
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();

      $quantidadeContatosMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.user_id', $user_id)
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
      return $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
    } else {
      $quantidadeVendasMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where(function ($query) {
          $query->where('b.tabulacao_id', Tabulations::VENDA)
            ->orWhere('b.tabulacao_id', Tabulations::IMPLANTADO);
        })
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();

      $quantidadeContatosMes = DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
      return $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
    }
  }

  public function conversaoMensalPorData($empresa_id, $month, $year)
  {
    $inicio = Carbon::createFromDate($year, $month, 1, 'America/Sao_Paulo')->startOfMonth();
    $fim = Carbon::createFromDate($year, $month, 1, 'America/Sao_Paulo')->endOfMonth();

    // Timestamp efetivo do log (event time do registro)
    $tsCol = DB::raw('COALESCE(lead_atividades.updated_at, lead_atividades.created_at)');

    // === Denominador: contatos trabalhados no período (dedup por contato_id) ===
    $quantidadeContatosMes = DB::table('lead_atividades')
      ->where('empresa_id', $empresa_id)
      ->whereBetween($tsCol, [$inicio, $fim])
      ->distinct('contato_id')
      ->count('contato_id');

    // === Numerador: total de vendas no período ===
    // Mantive o filtro de tabulação, mas removi select/groupBy para contar vendas de fato.
    $quantidadeVendasMes = DB::table('vendas as a')
      ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
      ->where('a.empresa_id', $empresa_id)
      ->whereBetween('a.created_at', [$inicio, $fim])
      ->whereIn('c.tabulacao_id', [
        Tabulations::VENDA,
        Tabulations::IMPLANTADO,
        Tabulations::PENDENCIA,
        Tabulations::ANALISE_OPERADORA,
        Tabulations::BOLETO_DISPONIVEL,
        Tabulations::REGULARIZADO,
        Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
        Tabulations::ANALISE_DOCUMENTOS,
        Tabulations::AGUARD_ASSINATURA_DS,
      ])
      ->distinct('a.id')          // evita duplicar se o join gerar múltiplas linhas
      ->count('a.id');

    return $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
  }

  private function calculoConversao(int $quantidadeContatos, int $quantidadeVendas): string
  {
    if ($quantidadeContatos === 0 || $quantidadeVendas === 0) {
      return '0,00';
    }

    $conversao = ($quantidadeVendas / $quantidadeContatos) * 100;
    return number_format($conversao, 2, ',', '.'); // ex.: 3,52
  }




  public function quantidadeContatosMes($user_id, $empresa_id, $role_user_id)
  {
    if ($role_user_id == UserRole::VENDEDOR) {
      return DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.user_id', $user_id)
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
    } else {
      return DB::table('contatos as a')
        ->leftJoin('contatos_corretores as b', 'a.id', '=', 'b.contato_id')
        ->where('b.empresa_id', $empresa_id)
        ->whereMonth('a.created_at', now()->month)
        ->whereYear('a.created_at', now()->year)
        ->count();
    }
  }

  public function quantidadeVendasCadastradasPorVendedor($month, $year, $empresa_id)
  {
    return DB::table('vendas as a')
      ->select('b.name', DB::raw('SUM(a.valor_contrato) as total_vendas'))
      ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
      ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
      ->leftJoin('tabulacoes as d', 'd.id', '=', 'c.tabulacao_id')
      ->whereYear('a.created_at', $year)
      ->whereMonth('a.created_at', $month)
      ->where('a.empresa_id', $empresa_id)
      ->whereIn('c.tabulacao_id', [
        Tabulations::VENDA,
        Tabulations::IMPLANTADO,
        Tabulations::PENDENCIA,
        Tabulations::ANALISE_OPERADORA,
        Tabulations::BOLETO_DISPONIVEL,
        Tabulations::REGULARIZADO,
        Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
        Tabulations::ANALISE_DOCUMENTOS,
        Tabulations::AGUARD_ASSINATURA_DS,
      ])
      ->groupBy('b.name')
      ->get();
  }

  public function quantidadeVendasImplantadasVendedor($month, $year, $empresa_id)
  {
    return DB::table('vendas as a')
      ->select('b.name', DB::raw('SUM(a.valor_contrato) as total_vendas'))
      ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
      ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
      ->whereYear('a.created_at', $year)
      ->whereMonth('a.created_at', $month)
      ->where('c.tabulacao_id', Tabulations::IMPLANTADO)
      ->where('a.empresa_id', $empresa_id)
      ->groupBy('b.name')
      ->get();
  }


  public function listaVendasCadastradasMes($month, $year, $empresa_id)
  {
    return DB::table('vendas as a')
      ->select('a.nome_contrato', 'a.valor_contrato')
      ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
      ->whereYear('a.created_at', $year)
      ->whereMonth('a.created_at', $month)
      ->whereIn(
        'c.tabulacao_id',
        [
          Tabulations::VENDA,
          Tabulations::IMPLANTADO,
          Tabulations::PENDENCIA,
          Tabulations::ANALISE_OPERADORA,
          Tabulations::BOLETO_DISPONIVEL,
          Tabulations::REGULARIZADO,
          Tabulations::CONTR_GERADO_AGUARDANDO_ASSINATURA,
          Tabulations::ANALISE_DOCUMENTOS,
          Tabulations::AGUARD_ASSINATURA_DS,
        ]
      )
      ->where('a.empresa_id', $empresa_id)
      ->get();
  }

  public function listaVendasImplantadasMes($month, $year, $empresa_id)
  {
    return DB::table('vendas as a')
      ->select('a.nome_contrato', 'a.valor_contrato')
      ->leftJoin('contatos_corretores as c', 'c.contato_id', '=', 'a.contato_id')
      ->whereYear('a.created_at', $year)
      ->whereMonth('a.created_at', $month)
      ->where('c.tabulacao_id', Tabulations::IMPLANTADO)
      ->where('a.empresa_id', $empresa_id)
      ->get();
  }

  public function getSalesAnalytical($empresa_id, $month, $year)
  {
    return DB::table('vendas as a')
      ->leftJoin('users as b', 'b.id', '=', 'a.user_id')
      ->select(
        'a.id',
        'b.name as corretor',
        'a.nome_contrato',
        'a.valor_contrato',
        'a.created_at as dataCadastro'
      )
      ->when($month, function ($query, $month) {
        return $query->whereMonth('a.created_at', $month);
      })
      ->when($year, function ($query, $year) {
        return $query->whereYear('a.created_at', $year);
      })
      ->when($empresa_id, function ($query, $empresa_id) {
        return $query->where('a.empresa_id', $empresa_id); // Correção aqui
      })
      ->orderBy('a.created_at', 'desc')
      ->get();
  }

  public function updateContract($data)
  {
    try {
      $contract = $this->model::find($data['id']);
      $operadora = Operadora::find($data['operadora']);

      // Usar valor do formulário, com fallback para Supermed
      $isAngariacao = $data['angariacao_status']
          ?? ($operadora->nome === "AMIL - SUPERMED" ? "SIM" : "NÃO");

      $contract->operadora = $operadora->nome;
      $contract->nome_contrato = $data['nome_contrato'];
      $contract->cpf_cnpj = Helpers::cleanSpecialCharacters($data['cpf_cnpj']);
      $contract->email = $data['email'];
      $contract->telefone1 = Helpers::cleanSpecialCharacters($data['telefone1']);
      $contract->telefone2 = Helpers::cleanSpecialCharacters($data['telefone2']);
      $contract->valor_contrato = Helpers::moneyForRealSaveBank($data['valor_contrato']);
      $contract->vidas = $data['vidas'];
      $contract->plano_id = $data['plano_id'];
      $contract->motivo_pendencia = $data['motivo_pendencia'] ?? null;
      $contract->obs_contrato = $data['obs_contrato'];
      $contract->updated_at = now();
      $contract->angariacao_valor = Helpers::moneyForRealSaveBank($data['angariacao_valor']);
      $contract->angariacao_status = $isAngariacao;

      return $contract->save();
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function updateDataImplantacao($id, $dataImplantacao, $motivo_pendencia = null, $path_ticket = null, $numero_proposta)
  {
    try {

      $contract = $this->model::find($id);
      if ($contract) {
        $contract->data_implantacao = $dataImplantacao;
        $contract->motivo_pendencia = $motivo_pendencia;
        $contract->numero_proposta = $numero_proposta ?? "";
        $contract->updated_at = now();
        return $contract->save();
      }
            dd($id, $dataImplantacao, $motivo_pendencia, $path_ticket, $numero_proposta);
      return false;
    } catch (\Throwable $th) {
      dd($th);
      return false;
    }
  }

  public function saveTicket($id, $path_ticket = null)
  {
    try {
      $contract = $this->model::find($id);
      if ($contract) {
        $contract->path_boleto_disponivel = $path_ticket;
        $contract->updated_at = now();
        return $contract->save();
      }
      return false;
    } catch (\Throwable $th) {
      return false;
    }
  }

  public function delete($id)
  {
    try {
      $contract = $this->model::find($id);

      if ($contract) {
        return $contract->delete();
      }
      return false;
    } catch (\Throwable $th) {
      return false;
    }
  }


  public function checkExistenceSale($id)
  {
    return $this->model->where('contato_id', $id)->exists();
  }
}
