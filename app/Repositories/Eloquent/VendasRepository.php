<?php

namespace App\Repositories\Eloquent;

use App\Enums\FaseCancelamento;
use App\Enums\TabulationCode;
use App\Enums\TipoDemandaContrato;
use App\Enums\UserRole;
use App\Helpers\Helpers;
use App\Models\ContatosCorretores;
use App\Models\Operadora;
use App\Models\Plano;
use App\Models\User;
use App\Models\VendaDemanda;
use App\Models\VendaDependente;
use App\Models\VendaHistorico;
use App\Models\VendaPortabilidade;
use App\Models\Vendas;
use App\Models\VendaTitular;
use App\Repositories\Contracts\VendasRepositoryInterface;
use App\Services\TabulationCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendasRepository implements VendasRepositoryInterface
{
    protected $model;

    protected TabulationCatalog $tabulationCatalog;

    public function __construct(Vendas $model, TabulationCatalog $tabulationCatalog)
    {
        $this->model = $model;
        $this->tabulationCatalog = $tabulationCatalog;
    }

    private function tabulationId(int $empresaId, string $code): int
    {
        return $this->tabulationCatalog->id($empresaId, $code);
    }

    private function reportableSaleStatusIds(int $empresaId): array
    {
        $codes = array_values(array_unique([
            ...TabulationCode::POS_VENDA_ELEGIVEIS,
            TabulationCode::ESTORNO,
        ]));

        return array_values($this->tabulationCatalog->requiredIds($empresaId, $codes));
    }

    public function create(array $data)
    {
        try {
            return DB::transaction(function () use ($data) {
                $empresaId = (int) app(\App\Support\TenantContext::class)->id();
                $contatoId = (int) ($data['contato_id'] ?? 0);
                $vendedorId = $this->saleOwnerId($empresaId, $contatoId);
                $operadoraId = (int) ($data['operadora_id'] ?? 0);
                $titulares = is_array($data['titulares'] ?? null) ? $data['titulares'] : [];
                $portabilidades = array_values(array_filter(
                    is_array($data['portabilidades'] ?? null) ? $data['portabilidades'] : [],
                    fn (array $item) => trim((string) ($item['nome'] ?? '')) !== ''
                ));

                // Tipo de contrato (default PME se não informado)
                $tipoContrato = $data['tipo_contrato'] ?? 'PME';

                $nomeContrato = strtoupper(trim($data['nome_contrato'] ?? ''));
                $cpfCnpj = Helpers::cleanSpecialCharacters($data['cpf_cnpj'] ?? '');
                $emailEmpresa = $data['email'] ?? null;
                $tel1 = Helpers::cleanSpecialCharacters($data['telefone1'] ?? '');
                $tel2 = Helpers::cleanSpecialCharacters($data['telefone2'] ?? '');
                $valorContrato = Helpers::converterParaDecimal($data['valor_contrato'] ?? '0');
                $taxaAngariacao = Helpers::converterParaDecimal($data['taxa_angariacao'] ?? '0');
                $vidas = (int) ($data['vidas'] ?? count($titulares));
                $obsContrato = $data['obs_contrato'] ?? null;

                $operadora = Operadora::where('empresa_id', $empresaId)->findOrFail($operadoraId);

                $primeiroTitular = $titulares[0] ?? null;
                $planoPrimeiro = $primeiroTitular
                  ? Plano::where('empresa_id', $empresaId)->find($primeiroTitular['plano_id'] ?? null)
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

                // Usa a configuração da operadora do tenant, nunca o nome dela.
                $isAngariacao = $data['angariacao_status']
                    ?? ($operadora->angariacao_padrao ? 'SIM' : 'NAO');

                // Dados base da venda
                $vendaData = [
                    'empresa_id' => $empresaId,
                    'user_id' => $vendedorId,
                    'contato_id' => $contatoId,
                    'tabulacao_id' => $this->tabulationId($empresaId, TabulationCode::VENDA),
                    'tabulacao_updated_at' => now(),
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

                // Campos adicionais - SEMPRE layout NOVO
                $vendaData['layout_venda'] = 'NOVO';
                $vendaData['tipo_contrato'] = $tipoContrato;
                $vendaData['portabilidade_status'] = $portabilidades === [] ? 'NAO' : 'SIM';
                $vendaData['qtd_portabilidade'] = count($portabilidades);
                $vendaData['plano_dental'] = $data['plano_dental'] ?? 'SIM';

                // Campos específicos de PME (empresa) - não aplicáveis para ADESAO
                if ($tipoContrato !== 'ADESAO') {
                    $vendaData['tipo_empresa'] = $data['tipo_empresa'] ?? null;
                    if (! empty($data['data_abertura'])) {
                        $dataAbertura = $this->converterDataBrParaDb($data['data_abertura']);
                        if ($dataAbertura) {
                            $vendaData['data_abertura'] = $dataAbertura;
                        }
                    }
                }

                $venda = $this->model->create($vendaData);

                // Processar titulares - SEMPRE salvar todos os campos
                if (! empty($titulares)) {
                    foreach ($titulares as $titularData) {
                        $titularPayload = [
                            'nome' => mb_strtoupper(trim($titularData['nome'] ?? ''), 'UTF-8'),
                            'email' => $titularData['email'] ?? null,
                            'telefone' => Helpers::cleanSpecialCharacters($titularData['telefone1'] ?? $titularData['telefone'] ?? ''),
                            'telefone2' => Helpers::cleanSpecialCharacters($titularData['telefone2'] ?? ''),
                            'cpf' => Helpers::cleanSpecialCharacters($titularData['cpf'] ?? ''),
                            'cargo' => ! empty($titularData['cargo']) ? $titularData['cargo'] : null,
                            'plano_id' => ! empty($titularData['plano_id']) ? (int) $titularData['plano_id'] : null,
                            'coparticipacao' => isset($titularData['coparticipacao']) && $titularData['coparticipacao'] !== ''
                              ? strtoupper($titularData['coparticipacao'])
                              : null,
                            'plano_anterior' => $titularData['plano_anterior'] ?? 'NAO',
                            'operadora_anterior_id' => ! empty($titularData['operadora_anterior_id'])
                              ? (int) $titularData['operadora_anterior_id']
                              : null,
                            'precisa_cancelamento' => filter_var($titularData['precisa_cancelamento'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ];

                        // Data de nascimento - converter formato BR para DB
                        if (! empty($titularData['data_nascimento'])) {
                            $dataNasc = $this->converterDataBrParaDb($titularData['data_nascimento']);
                            if ($dataNasc) {
                                $titularPayload['data_nascimento'] = $dataNasc;
                            }
                        }

                        $titular = $venda->titulares()->create($titularPayload);

                        // Sinal do vendedor: se pediu cancelamento do plano anterior,
                        // nasce o processo (venda_demandas) por titular, fase SOLICITADO.
                        if ($titularPayload['precisa_cancelamento'] && $titularPayload['operadora_anterior_id']) {
                            VendaDemanda::create([
                                'venda_id' => $venda->id,
                                'titular_id' => $titular->id,
                                'operadora_anterior_id' => $titularPayload['operadora_anterior_id'],
                                'empresa_id' => $venda->empresa_id,
                                'created_by' => Auth::id(),
                                'origem' => 'VENDEDOR',
                                'tipo' => TipoDemandaContrato::CANCELAMENTO_OPERADORA_ANTERIOR->value,
                                'titulo' => 'Cancelamento na operadora anterior — '.$titular->nome,
                                'meta' => ['fase' => FaseCancelamento::SOLICITADO->value, 'modalidade' => null],
                                'status' => 'PENDENTE',
                            ]);
                        }

                        // Processar dependentes do titular - SEMPRE salvar
                        if (! empty($titularData['dependentes']) && is_array($titularData['dependentes'])) {
                            foreach ($titularData['dependentes'] as $depData) {
                                $dependentePayload = [
                                    'venda_id' => $venda->id,
                                    'titular_id' => $titular->id,
                                    'nome' => mb_strtoupper(trim($depData['nome'] ?? ''), 'UTF-8'),
                                    'email' => $depData['email'] ?? null,
                                    'telefone1' => Helpers::cleanSpecialCharacters($depData['telefone1'] ?? ''),
                                    'telefone2' => Helpers::cleanSpecialCharacters($depData['telefone2'] ?? ''),
                                    'parentesco' => $depData['parentesco'] ?? null,
                                    'plano_id' => ! empty($depData['plano_id']) ? (int) $depData['plano_id'] : null,
                                    'coparticipacao' => isset($depData['coparticipacao']) && $depData['coparticipacao'] !== ''
                                      ? strtoupper($depData['coparticipacao']) : null,
                                    'plano_anterior' => $depData['plano_anterior'] ?? 'NAO',
                                    'operadora_anterior_id' => ! empty($depData['operadora_anterior_id'])
                                      ? (int) $depData['operadora_anterior_id']
                                      : null,
                                ];

                                if (! empty($depData['cpf'])) {
                                    $dependentePayload['cpf'] = Helpers::cleanSpecialCharacters($depData['cpf']);
                                }

                                if (! empty($depData['data_nascimento'])) {
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

                // Processar portabilidades - SEMPRE salvar
                if ($portabilidades !== []) {
                    $sequencial = 1;
                    foreach ($portabilidades as $portData) {
                        VendaPortabilidade::create([
                            'venda_id' => $venda->id,
                            'nome' => mb_strtoupper(trim($portData['nome'] ?? ''), 'UTF-8'),
                            'cpf' => ! empty($portData['cpf']) ? Helpers::cleanSpecialCharacters($portData['cpf']) : null,
                            'operadora_anterior_id' => ! empty($portData['operadora_anterior_id'])
                              ? (int) $portData['operadora_anterior_id']
                              : null,
                            'plano_anterior' => $portData['plano_anterior'] ?? null,
                            'numero_carteirinha' => $portData['numero_carteirinha'] ?? null,
                            'operadora_destino_id' => (int) $portData['operadora_destino_id'],
                            'plano_destino_id' => (int) $portData['plano_destino_id'],
                            'sequencial' => $sequencial++,
                        ]);
                    }
                }

                // Atualizar contatos_corretores para o status VENDA (backoffice)
                ContatosCorretores::where('contato_id', $contatoId)
                    ->where('empresa_id', $empresaId)
                    ->update([
                        'tabulacao_id' => $this->tabulationId($empresaId, TabulationCode::VENDA),
                        'updated_at' => now(),
                    ]);

                // Criar histórico inicial com status VENDA
                VendaHistorico::create([
                    'empresa_id' => $empresaId,
                    'venda_id' => $venda->id,
                    'user_id' => Auth::user()->id,
                    'tabulacao_anterior_id' => null,
                    'tabulacao_nova_id' => $this->tabulationId($empresaId, TabulationCode::VENDA),
                    'observacao' => 'Venda cadastrada no sistema',
                ]);

                return $venda;
            });
        } catch (\Throwable $ex) {
            throw $ex; // Propaga o erro para o controller tratar
        }
    }

    /**
     * Altera o status (tabulação) de UMA venda específica e registra o histórico.
     * O status do contrato vive em vendas.tabulacao_id — nunca alterar via
     * contatos_corretores, pois um contato pode ter várias vendas em estágios
     * diferentes do ciclo de vida.
     */
    public function alterStatusVenda(int $vendaId, int $tabulacaoId, ?string $observacao = null, ?string $motivoPendencia = null): bool
    {
        try {
            return DB::transaction(function () use ($vendaId, $tabulacaoId, $observacao, $motivoPendencia) {
                $empresaId = (int) app(\App\Support\TenantContext::class)->id();
                $venda = $this->model::where('empresa_id', $empresaId)
                    ->where('id', $vendaId)
                    ->lockForUpdate()
                    ->first();

                if (! $venda) {
                    return false;
                }

                $tabulacaoValida = DB::table('tabulacoes')
                    ->where('empresa_id', $empresaId)
                    ->where('id', $tabulacaoId)
                    ->exists();

                if (! $tabulacaoValida) {
                    return false;
                }

                $tabulacaoAnteriorId = $venda->tabulacao_id;

                $venda->update([
                    'tabulacao_id' => $tabulacaoId,
                    'tabulacao_updated_at' => Carbon::now(),
                ]);

                VendaHistorico::create([
                    'empresa_id' => $venda->empresa_id,
                    'venda_id' => $venda->id,
                    'contato_corretor_id' => optional($venda->contatoCorretor)->id,
                    'user_id' => Auth::id(),
                    'tabulacao_anterior_id' => $tabulacaoAnteriorId,
                    'tabulacao_nova_id' => $tabulacaoId,
                    'observacao' => $observacao,
                    'motivo_pendencia' => $motivoPendencia,
                ]);

                return true;
            });
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Atualiza venda + filhos (titulares, dependentes, portabilidades)
     * substituindo completamente, exatamente como `create()` faz, mas para
     * uma venda existente. Usado pelo fluxo de estorno/reenvio do vendedor.
     */
    public function updateContractFull(int $vendaId, array $data): bool
    {
        return DB::transaction(function () use ($vendaId, $data) {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $venda = $this->model::where('id', $vendaId)
                ->where('empresa_id', $empresaId)
                ->lockForUpdate()
                ->firstOrFail();

            $operadoraId = (int) ($data['operadora_id'] ?? $venda->operadora_id);
            $titulares = is_array($data['titulares'] ?? null) ? $data['titulares'] : [];
            $portabilidades = array_values(array_filter(
                is_array($data['portabilidades'] ?? null) ? $data['portabilidades'] : [],
                fn (array $item) => trim((string) ($item['nome'] ?? '')) !== ''
            ));
            $tipoContrato = $data['tipo_contrato'] ?? $venda->tipo_contrato ?? 'PME';

            $operadora = Operadora::where('empresa_id', $empresaId)->findOrFail($operadoraId);

            $primeiroTitular = $titulares[0] ?? null;
            $planoPrimeiro = $primeiroTitular
                ? Plano::where('empresa_id', $empresaId)->find($primeiroTitular['plano_id'] ?? null)
                : null;

            $copValues = array_values(array_unique(array_map(function ($t) {
                return isset($t['coparticipacao']) && $t['coparticipacao'] !== ''
                    ? strtoupper($t['coparticipacao'])
                    : null;
            }, $titulares)));

            $coparticipacaoVenda = count($copValues) === 1 ? $copValues[0] : null;

            $isAngariacao = $data['angariacao_status']
                ?? ($operadora->angariacao_padrao ? 'SIM' : 'NAO');

            // Atualiza campos da venda em si
            $venda->fill([
                'nome_contrato' => strtoupper(trim($data['nome_contrato'] ?? $venda->nome_contrato)),
                'cpf_cnpj' => Helpers::cleanSpecialCharacters($data['cpf_cnpj'] ?? $venda->cpf_cnpj),
                'email' => $data['email'] ?? null,
                'telefone1' => Helpers::cleanSpecialCharacters($data['telefone1'] ?? ''),
                'telefone2' => Helpers::cleanSpecialCharacters($data['telefone2'] ?? ''),
                'operadora' => $operadora->nome,
                'operadora_id' => $operadoraId,
                'nome_plano' => $planoPrimeiro?->nome,
                'plano_id' => $planoPrimeiro?->id,
                'valor_contrato' => Helpers::converterParaDecimal($data['valor_contrato'] ?? '0'),
                'vidas' => (int) ($data['vidas'] ?? count($titulares)),
                'obs_contrato' => $data['obs_contrato'] ?? null,
                'coparticipacao' => $coparticipacaoVenda,
                'angariacao_valor' => Helpers::converterParaDecimal($data['taxa_angariacao'] ?? '0'),
                'angariacao_status' => $isAngariacao,
                'layout_venda' => 'NOVO',
                'tipo_contrato' => $tipoContrato,
                'portabilidade_status' => $portabilidades === [] ? 'NAO' : 'SIM',
                'qtd_portabilidade' => count($portabilidades),
                'plano_dental' => $data['plano_dental'] ?? 'SIM',
            ]);

            if ($tipoContrato !== 'ADESAO') {
                $venda->tipo_empresa = $data['tipo_empresa'] ?? null;
                if (! empty($data['data_abertura'])) {
                    $dataAbertura = $this->converterDataBrParaDb($data['data_abertura']);
                    if ($dataAbertura) {
                        $venda->data_abertura = $dataAbertura;
                    }
                } else {
                    $venda->data_abertura = null;
                }
            } else {
                $venda->tipo_empresa = null;
                $venda->data_abertura = null;
            }

            $venda->save();

            // Substitui filhos: remove antigos e recria a partir do payload.
            // O cascade do FK também removeria; manter explícito por clareza e
            // para não depender do storage engine.
            VendaDependente::where('venda_id', $venda->id)->delete();
            VendaTitular::where('venda_id', $venda->id)->delete();
            VendaPortabilidade::where('venda_id', $venda->id)->delete();

            foreach ($titulares as $titularData) {
                $titular = $venda->titulares()->create([
                    'nome' => mb_strtoupper(trim($titularData['nome'] ?? ''), 'UTF-8'),
                    'email' => $titularData['email'] ?? null,
                    'telefone' => Helpers::cleanSpecialCharacters($titularData['telefone1'] ?? $titularData['telefone'] ?? ''),
                    'telefone2' => Helpers::cleanSpecialCharacters($titularData['telefone2'] ?? ''),
                    'cpf' => Helpers::cleanSpecialCharacters($titularData['cpf'] ?? ''),
                    'cargo' => ! empty($titularData['cargo']) ? $titularData['cargo'] : null,
                    'plano_id' => ! empty($titularData['plano_id']) ? (int) $titularData['plano_id'] : null,
                    'coparticipacao' => isset($titularData['coparticipacao']) && $titularData['coparticipacao'] !== ''
                        ? strtoupper($titularData['coparticipacao'])
                        : null,
                    'plano_anterior' => $titularData['plano_anterior'] ?? 'NAO',
                    'operadora_anterior_id' => ! empty($titularData['operadora_anterior_id'])
                        ? (int) $titularData['operadora_anterior_id']
                        : null,
                    'data_nascimento' => $this->converterDataBrParaDb($titularData['data_nascimento'] ?? null),
                ]);

                foreach ($titularData['dependentes'] ?? [] as $depData) {
                    VendaDependente::create([
                        'venda_id' => $venda->id,
                        'titular_id' => $titular->id,
                        'nome' => mb_strtoupper(trim($depData['nome'] ?? ''), 'UTF-8'),
                        'email' => $depData['email'] ?? null,
                        'telefone1' => Helpers::cleanSpecialCharacters($depData['telefone1'] ?? ''),
                        'telefone2' => Helpers::cleanSpecialCharacters($depData['telefone2'] ?? ''),
                        'cpf' => ! empty($depData['cpf']) ? Helpers::cleanSpecialCharacters($depData['cpf']) : null,
                        'parentesco' => $depData['parentesco'] ?? null,
                        'plano_id' => ! empty($depData['plano_id']) ? (int) $depData['plano_id'] : null,
                        'coparticipacao' => isset($depData['coparticipacao']) && $depData['coparticipacao'] !== ''
                            ? strtoupper($depData['coparticipacao']) : null,
                        'plano_anterior' => $depData['plano_anterior'] ?? 'NAO',
                        'operadora_anterior_id' => ! empty($depData['operadora_anterior_id'])
                            ? (int) $depData['operadora_anterior_id']
                            : null,
                        'data_nascimento' => $this->converterDataBrParaDb($depData['data_nascimento'] ?? null),
                    ]);
                }
            }

            $sequencial = 1;
            foreach ($portabilidades as $portData) {
                VendaPortabilidade::create([
                    'venda_id' => $venda->id,
                    'nome' => mb_strtoupper(trim($portData['nome'] ?? ''), 'UTF-8'),
                    'cpf' => ! empty($portData['cpf']) ? Helpers::cleanSpecialCharacters($portData['cpf']) : null,
                    'operadora_anterior_id' => ! empty($portData['operadora_anterior_id'])
                        ? (int) $portData['operadora_anterior_id']
                        : null,
                    'plano_anterior' => $portData['plano_anterior'] ?? null,
                    'numero_carteirinha' => $portData['numero_carteirinha'] ?? null,
                    'operadora_destino_id' => (int) $portData['operadora_destino_id'],
                    'plano_destino_id' => (int) $portData['plano_destino_id'],
                    'sequencial' => $sequencial++,
                ]);
            }

            return true;
        });
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
        return $this->model::where('empresa_id', app(\App\Support\TenantContext::class)->id())->find($id);
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
            'tabulacoes.prazo',
        ])
            ->where('vendas.empresa_id', $empresa_id)
            ->leftJoin('tabulacoes', function ($join) use ($empresa_id) {
                $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                    ->where('tabulacoes.empresa_id', '=', $empresa_id);
            })
            ->leftJoin('users', function ($join) use ($empresa_id) {
                $join->on('users.id', '=', 'vendas.user_id')
                    ->where('users.empresa_id', '=', $empresa_id)
                    ->where('users.is_platform_admin', false);
            })
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
            'tabulacoes.prazo',
        ])
            ->whereBetween('vendas.created_at', [$startDate, $endDate])
            ->where('vendas.empresa_id', $empresa_id)
            ->leftJoin('tabulacoes', function ($join) use ($empresa_id) {
                $join->on('tabulacoes.id', '=', 'vendas.tabulacao_id')
                    ->where('tabulacoes.empresa_id', '=', $empresa_id);
            })
            ->leftJoin('users', function ($join) use ($empresa_id) {
                $join->on('users.id', '=', 'vendas.user_id')
                    ->where('users.empresa_id', '=', $empresa_id)
                    ->where('users.is_platform_admin', false);
            })
            ->orderBy('vendas.created_at', 'desc')
            ->get();
    }

    public function vendasDoMesAnoAtual($user_id, $empresa_id, $role_user_id)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if (Auth::user()->user_role_id == UserRole::VENDEDOR) {

            return DB::table('vendas as a')
                ->leftJoin('users as c', function ($join) {
                    $join->on('c.id', '=', 'a.user_id')
                        ->on('c.empresa_id', '=', 'a.empresa_id')
                        ->where('c.is_platform_admin', false);
                })
                ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'a.tabulacao_id as status', 'a.created_at', 'c.name as nome_corretor')
                ->where('a.user_id', Auth::user()->id)
                ->where('a.empresa_id', app(\App\Support\TenantContext::class)->id())
                ->whereMonth('a.created_at', $currentMonth)
                ->whereYear('a.created_at', $currentYear)
                ->get();
        } else {
            return DB::table('vendas as a')
                ->leftJoin('users as c', function ($join) {
                    $join->on('c.id', '=', 'a.user_id')
                        ->on('c.empresa_id', '=', 'a.empresa_id')
                        ->where('c.is_platform_admin', false);
                })
                ->select('a.id', 'a.nome_contrato', 'a.email', 'a.valor_contrato', 'a.data_vigencia', 'a.tabulacao_id as status', 'a.created_at', 'c.name as nome_corretor')
                ->where('a.empresa_id', app(\App\Support\TenantContext::class)->id())
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
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_vendido'),
                    DB::raw('COUNT(a.id) as quantidade_vendida')
                )
                ->where('a.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->whereIn('a.tabulacao_id', array_values($this->tabulationCatalog->requiredIds(
                    (int) $empresa_id,
                    [TabulationCode::VENDA, TabulationCode::IMPLANTADO]
                )))
                ->whereMonth('a.created_at', $currentMonth)
                ->whereYear('a.created_at', $currentYear)
                ->get();

            return [
                'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
                'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
            ];
        } else {
            $vendas = DB::table('vendas as a')
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_vendido'),
                    DB::raw('COUNT(a.id) as quantidade_vendida')
                )
                ->where('a.empresa_id', $empresa_id)
                ->whereIn('a.tabulacao_id', array_values($this->tabulationCatalog->requiredIds(
                    (int) $empresa_id,
                    [TabulationCode::VENDA, TabulationCode::IMPLANTADO]
                )))
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
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_vendido'),
                    DB::raw('COUNT(a.id) as quantidade_vendida')
                )
                ->where('a.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::IMPLANTADO))
                ->whereMonth('a.created_at', $currentMonth)
                ->whereYear('a.created_at', $currentYear)
                ->get();

            return [
                'valor_vendido' => $vendas[0]->valor_vendido ?? 0.0,
                'quantidade_vendida' => $vendas[0]->quantidade_vendida ?? 0,
            ];
        } else {
            $vendas = DB::table('vendas as a')
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_vendido'),
                    DB::raw('COUNT(a.id) as quantidade_vendida')
                )
                ->where('a.empresa_id', $empresa_id)
                ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::IMPLANTADO))
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
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_estornado'),
                    DB::raw('COUNT(a.id) as quantidade_estornada')
                )
                ->where('a.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::ESTORNO))
                ->whereMonth('a.created_at', $currentMonth)
                ->whereYear('a.created_at', $currentYear)
                ->get();

            return [
                'valor_estornado' => $estornos[0]->valor_estornado ?? 0.0,
                'quantidade_estornada' => $estornos[0]->quantidade_estornada ?? 0.0,
            ];
        } else {
            $estornos = DB::table('vendas as a')
                ->select(
                    DB::raw('SUM(a.valor_contrato) as valor_estornado'),
                    DB::raw('COUNT(a.id) as quantidade_estornada')
                )
                ->where('a.empresa_id', $empresa_id)
                ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::ESTORNO))
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
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->whereIn('b.tabulacao_id', array_values($this->tabulationCatalog->requiredIds(
                    (int) $empresa_id,
                    [TabulationCode::VENDA, TabulationCode::IMPLANTADO]
                )))
                ->where('b.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->where('b.empresa_id', $empresa_id)
                ->whereMonth('a.created_at', now()->month)
                ->whereYear('a.created_at', now()->year)
                ->count();

            $quantidadeContatosMes = DB::table('contatos as a')
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->where('b.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->where('b.empresa_id', $empresa_id)
                ->whereMonth('a.created_at', now()->month)
                ->whereYear('a.created_at', now()->year)
                ->count();

            return $this->calculoConversao($quantidadeContatosMes, $quantidadeVendasMes);
        } else {
            $quantidadeVendasMes = DB::table('contatos as a')
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->whereIn('b.tabulacao_id', array_values($this->tabulationCatalog->requiredIds(
                    (int) $empresa_id,
                    [TabulationCode::VENDA, TabulationCode::IMPLANTADO]
                )))
                ->where('a.empresa_id', $empresa_id)
                ->where('b.empresa_id', $empresa_id)
                ->whereMonth('a.created_at', now()->month)
                ->whereYear('a.created_at', now()->year)
                ->count();

            $quantidadeContatosMes = DB::table('contatos as a')
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->where('a.empresa_id', $empresa_id)
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
            ->where('a.empresa_id', $empresa_id)
            ->whereBetween('a.created_at', [$inicio, $fim])
            ->whereIn('a.tabulacao_id', $this->reportableSaleStatusIds((int) $empresa_id))
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
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->where('b.user_id', $user_id)
                ->where('a.empresa_id', $empresa_id)
                ->where('b.empresa_id', $empresa_id)
                ->whereMonth('a.created_at', now()->month)
                ->whereYear('a.created_at', now()->year)
                ->count();
        } else {
            return DB::table('contatos as a')
                ->leftJoin('contatos_corretores as b', function ($join) {
                    $join->on('b.contato_id', '=', 'a.id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->where('a.empresa_id', $empresa_id)
                ->where('b.empresa_id', $empresa_id)
                ->whereMonth('a.created_at', now()->month)
                ->whereYear('a.created_at', now()->year)
                ->count();
        }
    }

    public function quantidadeVendasCadastradasPorVendedor($month, $year, $empresa_id)
    {
        return DB::table('vendas as a')
            ->select(
                'b.name',
                DB::raw('SUM(a.valor_contrato) as total_vendas'),
                DB::raw("SUM(CASE WHEN a.angariacao_status = 'SIM' THEN COALESCE(a.angariacao_valor, 0) ELSE 0 END) as total_angariacao")
            )
            ->leftJoin('users as b', function ($join) {
                $join->on('b.id', '=', 'a.user_id')
                    ->on('b.empresa_id', '=', 'a.empresa_id')
                    ->where('b.is_platform_admin', false);
            })
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.empresa_id', $empresa_id)
            ->whereIn('a.tabulacao_id', $this->reportableSaleStatusIds((int) $empresa_id))
            ->groupBy('b.name')
            ->get();
    }

    public function quantidadeVendasImplantadasVendedor($month, $year, $empresa_id)
    {
        return DB::table('vendas as a')
            ->select(
                'b.name',
                DB::raw('SUM(a.valor_contrato) as total_vendas'),
                DB::raw("SUM(CASE WHEN a.angariacao_status = 'SIM' THEN COALESCE(a.angariacao_valor, 0) ELSE 0 END) as total_angariacao")
            )
            ->leftJoin('users as b', function ($join) {
                $join->on('b.id', '=', 'a.user_id')
                    ->on('b.empresa_id', '=', 'a.empresa_id')
                    ->where('b.is_platform_admin', false);
            })
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::IMPLANTADO))
            ->where('a.empresa_id', $empresa_id)
            ->groupBy('b.name')
            ->get();
    }

    public function listaVendasCadastradasMes($month, $year, $empresa_id)
    {
        return DB::table('vendas as a')
            ->select('a.nome_contrato', 'a.valor_contrato', 'a.angariacao_status', 'a.angariacao_valor')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->whereIn(
                'a.tabulacao_id',
                $this->reportableSaleStatusIds((int) $empresa_id)
            )
            ->where('a.empresa_id', $empresa_id)
            ->get();
    }

    public function listaVendasImplantadasMes($month, $year, $empresa_id)
    {
        return DB::table('vendas as a')
            ->select('a.nome_contrato', 'a.valor_contrato', 'a.angariacao_status', 'a.angariacao_valor')
            ->whereYear('a.created_at', $year)
            ->whereMonth('a.created_at', $month)
            ->where('a.tabulacao_id', $this->tabulationId((int) $empresa_id, TabulationCode::IMPLANTADO))
            ->where('a.empresa_id', $empresa_id)
            ->get();
    }

    public function getSalesAnalytical($empresa_id, $month, $year)
    {
        return DB::table('vendas as a')
            ->leftJoin('users as b', function ($join) {
                $join->on('b.id', '=', 'a.user_id')
                    ->on('b.empresa_id', '=', 'a.empresa_id')
                    ->where('b.is_platform_admin', false);
            })
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
            ->where('a.empresa_id', $empresa_id)
            ->orderBy('a.created_at', 'desc')
            ->get();
    }

    public function updateContract($data)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $contract = $this->model::where('empresa_id', $empresaId)->findOrFail($data['id']);
            $operadora = Operadora::where('empresa_id', $empresaId)->findOrFail($data['operadora']);

            $plano = null;
            if (! empty($data['plano_id'])) {
                $plano = Plano::where('empresa_id', $empresaId)
                    ->where('operadora_id', $operadora->id)
                    ->findOrFail($data['plano_id']);
            }

            // Usa a configuração da operadora do tenant, nunca o nome dela.
            $isAngariacao = $data['angariacao_status']
                ?? ($operadora->angariacao_padrao ? 'SIM' : 'NAO');

            $contract->operadora = $operadora->nome;
            $contract->operadora_id = $operadora->id;
            $contract->nome_contrato = $data['nome_contrato'];
            $contract->cpf_cnpj = Helpers::cleanSpecialCharacters($data['cpf_cnpj']);
            $contract->email = $data['email'];
            $contract->telefone1 = Helpers::cleanSpecialCharacters($data['telefone1']);
            $contract->telefone2 = Helpers::cleanSpecialCharacters($data['telefone2']);
            $contract->valor_contrato = Helpers::moneyForRealSaveBank($data['valor_contrato']);
            $contract->vidas = $data['vidas'];
            $contract->plano_id = $data['plano_id'];
            $contract->nome_plano = $plano?->nome;
            $contract->motivo_pendencia = $data['motivo_pendencia'] ?? null;
            $contract->obs_contrato = $data['obs_contrato'];
            $contract->updated_at = now();
            $contract->angariacao_valor = Helpers::moneyForRealSaveBank($data['angariacao_valor']);
            $contract->angariacao_status = $isAngariacao;
            $contract->plano_dental = $data['plano_dental'] ?? $contract->plano_dental ?? 'SIM';

            // Campos PME/ADESAO
            if (isset($data['tipo_contrato'])) {
                $contract->tipo_contrato = $data['tipo_contrato'];
            }
            if (isset($data['layout_venda'])) {
                $contract->layout_venda = $data['layout_venda'];
            }
            if (isset($data['tipo_empresa'])) {
                $contract->tipo_empresa = $data['tipo_empresa'] ?: null;
            }
            if (isset($data['data_abertura'])) {
                if ($data['data_abertura']) {
                    try {
                        $contract->data_abertura = \Carbon\Carbon::createFromFormat('d/m/Y', $data['data_abertura'])->startOfDay();
                    } catch (\Exception $e) {
                        $contract->data_abertura = null;
                    }
                } else {
                    $contract->data_abertura = null;
                }
            }

            return $contract->save();
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function updateDataImplantacao($id, $dataImplantacao, $motivo_pendencia, $path_ticket, $numero_proposta)
    {
        try {

            $contract = $this->model::where('empresa_id', app(\App\Support\TenantContext::class)->id())->find($id);
            if ($contract) {
                $contract->data_implantacao = $dataImplantacao;
                $contract->motivo_pendencia = $motivo_pendencia;
                $contract->numero_proposta = $numero_proposta ?? '';
                $contract->updated_at = now();

                return $contract->save();
            }

            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function saveTicket($id, $path_ticket = null)
    {
        try {
            $contract = $this->model::where('empresa_id', app(\App\Support\TenantContext::class)->id())->find($id);
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
            $contract = $this->model::where('empresa_id', app(\App\Support\TenantContext::class)->id())->find($id);

            if ($contract) {
                // Solicitações do pós-venda são registros operacionais e não
                // podem sumir como efeito colateral da exclusão do contrato.
                if ($contract->solicitacoesPosVenda()->exists()) {
                    return false;
                }

                return $contract->delete();
            }

            return false;
        } catch (\Throwable $th) {
            return false;
        }
    }

    private function saleOwnerId(int $empresaId, int $contatoId): int
    {
        $actor = Auth::user();

        if (! $actor->isPlatformAdmin()) {
            if (! User::query()->tenantMember($empresaId)->whereKey($actor->id)->exists()) {
                throw ValidationException::withMessages([
                    'contato_id' => 'O usuário autenticado não pertence à empresa ativa.',
                ]);
            }

            return (int) $actor->id;
        }

        $ownerId = ContatosCorretores::query()
            ->where('empresa_id', $empresaId)
            ->where('contato_id', $contatoId)
            ->whereNotNull('user_id')
            ->value('user_id');

        if (! $ownerId || ! User::query()->tenantMember($empresaId)->whereKey((int) $ownerId)->exists()) {
            throw ValidationException::withMessages([
                'contato_id' => 'Para cadastrar a venda como master, atribua primeiro o lead a um vendedor da empresa ativa.',
            ]);
        }

        return (int) $ownerId;
    }

    public function checkExistenceSale($id)
    {
        return $this->model->where('contato_id', $id)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->exists();
    }
}
