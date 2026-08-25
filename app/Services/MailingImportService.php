<?php

namespace App\Services;

use App\Helpers\Helpers;
use App\Imports\ContatosImport;
use App\Imports\ContatosImportDependencies;
use App\Models\Agendamento;
use App\Models\Comentarios;
use App\Models\ComercialReunioes;
use App\Models\Contatos;
use App\Models\ContatosCorretores;
use App\Models\Dependentes;
use App\Models\LeadAtividade;
use App\Models\Ligacoes;
use App\Models\LogPreditiva;
use App\Models\MailingImportacao;
use App\Models\MailingImportacaoItem;
use App\Models\Preditiva;
use App\Models\PreditivaEnvio;
use App\Models\TransferenciaContato;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MailingImportService
{
    public function analisar(
        UploadedFile $arquivo,
        int $empresaId,
        int $userId,
        string $nomeBase,
        string $tipoLayout,
        int $vendedorId,
        int $tabulacaoId
    ): MailingImportacao {
        $itens = $tipoLayout === 'com_dependentes'
            ? $this->lerComDependentes($arquivo, $nomeBase, $tabulacaoId, $vendedorId)
            : $this->lerPadrao($arquivo, $empresaId, $userId, $nomeBase);

        if ($itens->isEmpty()) {
            throw new \InvalidArgumentException('O arquivo não possui leads para análise.');
        }

        $cpfs = $itens->pluck('cpf')->filter()->unique()->values();
        $existentes = Contatos::query()
            ->where('empresa_id', $empresaId)
            ->whereIn('cpf', $cpfs)
            ->get(['id', 'cpf'])
            ->keyBy('cpf');

        $vistos = [];
        $itens = $itens->map(function (array $item) use ($existentes, &$vistos) {
            $cpf = $item['cpf'];

            if ($cpf === null || $cpf === '') {
                $item['classificacao'] = 'INVALIDO';
                $item['motivo'] = 'CPF não informado';
            } elseif (isset($existentes[$cpf])) {
                $item['classificacao'] = 'DUPLICADO_BASE';
                $item['contato_existente_id'] = $existentes[$cpf]->id;
            } elseif (isset($vistos[$cpf])) {
                $item['classificacao'] = 'DUPLICADO_ARQUIVO';
                $item['motivo'] = 'CPF repetido no arquivo';
            } else {
                $item['classificacao'] = 'NOVO';
                $vistos[$cpf] = true;
            }

            return $item;
        });

        return DB::transaction(function () use ($itens, $empresaId, $userId, $nomeBase, $tipoLayout, $vendedorId, $tabulacaoId, $arquivo) {
            $importacao = MailingImportacao::create([
                'empresa_id' => $empresaId,
                'user_id' => $userId,
                'vendedor_id' => $vendedorId,
                'tabulacao_id' => $tabulacaoId,
                'nome_base' => $nomeBase,
                'arquivo_nome' => $arquivo->getClientOriginalName(),
                'tipo_layout' => $tipoLayout,
                'status' => 'EM_ANALISE',
                'total_itens' => $itens->count(),
                'total_novos' => $itens->where('classificacao', 'NOVO')->count(),
                'total_duplicados' => $itens->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])->count(),
                'total_invalidos' => $itens->where('classificacao', 'INVALIDO')->count(),
            ]);

            foreach ($itens as $item) {
                $importacao->itens()->create($item);
            }

            $this->sincronizarDuplicadosNaoElegiveis($importacao);
            $this->recontar($importacao);
            if (! $this->possuiDuplicadosPendentes($importacao) && ! $this->possuiNovosPendentes($importacao)) {
                $importacao->update(['status' => 'CONCLUIDA', 'concluida_em' => now()]);
            }

            return $importacao;
        });
    }

    public function importarNovos(MailingImportacao $importacao, int $userId): array
    {
        return Cache::lock("mailing-importacao-empresa-{$importacao->empresa_id}", 30)->block(10, function () use ($importacao, $userId) {
            return DB::transaction(function () use ($importacao, $userId) {
                $importacao = MailingImportacao::query()->lockForUpdate()->findOrFail($importacao->id);
                $itens = $importacao->itens()
                    ->where('classificacao', 'NOVO')
                    ->whereNull('contato_importado_id')
                    ->lockForUpdate()
                    ->get();

                $importados = 0;
                $convertidosEmDuplicados = 0;

                foreach ($itens as $item) {
                    $existente = Contatos::query()
                        ->where('empresa_id', $importacao->empresa_id)
                        ->where('cpf', $item->cpf)
                        ->lockForUpdate()
                        ->first();

                    if ($existente) {
                        $item->update([
                            'classificacao' => 'DUPLICADO_BASE',
                            'contato_existente_id' => $existente->id,
                            'motivo' => 'CPF cadastrado enquanto a importação aguardava confirmação',
                        ]);
                        $convertidosEmDuplicados++;

                        continue;
                    }

                    $contato = $this->criarContato($importacao, $item, $userId);
                    $item->update([
                        'contato_importado_id' => $contato->id,
                        'resolucao' => 'IMPORTADO',
                        'resolvido_por' => $userId,
                        'resolvido_em' => now(),
                    ]);
                    $importados++;
                }

                $this->vincularDuplicadosDoArquivo($importacao);
                $this->sincronizarDuplicadosNaoElegiveis($importacao);
                $this->recontar($importacao);

                $pendente = $this->possuiDuplicadosPendentes($importacao) || $this->possuiNovosPendentes($importacao);
                $importacao->update([
                    'status' => $pendente ? 'PARCIAL' : 'CONCLUIDA',
                    'importados_em' => $importacao->importados_em ?: now(),
                    'concluida_em' => $pendente ? null : now(),
                ]);

                return compact('importados', 'convertidosEmDuplicados');
            });
        });
    }

    public function resolverDuplicados(
        MailingImportacao $importacao,
        array $itemIds,
        string $destino,
        ?int $vendedorId,
        ?int $tabulacaoId,
        int $userId
    ): int {
        return DB::transaction(function () use ($importacao, $itemIds, $destino, $vendedorId, $tabulacaoId, $userId) {
            $importacao = MailingImportacao::query()->lockForUpdate()->findOrFail($importacao->id);
            $itens = $importacao->itens()
                ->whereIn('id', $itemIds)
                ->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])
                ->whereNull('resolvido_em')
                ->lockForUpdate()
                ->get();

            foreach ($itens as $item) {
                $contatoId = $item->contato_existente_id;
                if (! $contatoId && $item->cpf) {
                    $contatoId = Contatos::query()
                        ->where('empresa_id', $importacao->empresa_id)
                        ->where('cpf', $item->cpf)
                        ->value('id');
                }

                $contato = Contatos::query()
                    ->where('empresa_id', $importacao->empresa_id)
                    ->where('id', $contatoId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->contatoElegivelParaReciclagem($contato, $importacao->empresa_id)) {
                    throw new \InvalidArgumentException('Somente leads descartados ou na preditiva, sem vendedor atual, podem ser movimentados.');
                }

                $this->limparHistoricoNegociacao($contato->id, $importacao->empresa_id);
                $this->atualizarContatoComoNovo($contato, $importacao, $item, $userId);

                if ($destino === 'PREDITIVA') {
                    ContatosCorretores::where('empresa_id', $importacao->empresa_id)
                        ->where('contato_id', $contato->id)
                        ->delete();

                    Preditiva::updateOrCreate(
                        ['empresa_id' => $importacao->empresa_id, 'contato_id' => $contato->id],
                        ['status' => 'Y']
                    );
                } else {
                    Preditiva::where('empresa_id', $importacao->empresa_id)
                        ->where('contato_id', $contato->id)
                        ->delete();

                    $vinculo = ContatosCorretores::query()
                        ->where('empresa_id', $importacao->empresa_id)
                        ->where('contato_id', $contato->id)
                        ->lockForUpdate()
                        ->first();

                    $vendedorDestino = $vendedorId ?: $vinculo?->user_id;
                    $tabulacaoDestino = $tabulacaoId ?: $vinculo?->tabulacao_id;
                    if (! $vendedorDestino || ! $tabulacaoDestino) {
                        throw new \InvalidArgumentException('Informe vendedor e status para leads sem atribuição atual.');
                    }

                    $vendedorAnterior = $vinculo?->user_id;
                    if ($vinculo) {
                        $vinculo->update([
                            'user_id' => $vendedorDestino,
                            'tabulacao_id' => $tabulacaoDestino,
                        ]);
                    } else {
                        ContatosCorretores::create([
                            'empresa_id' => $importacao->empresa_id,
                            'contato_id' => $contato->id,
                            'user_id' => $vendedorDestino,
                            'tabulacao_id' => $tabulacaoDestino,
                            'temperatura' => 'FRIO',
                        ]);
                    }

                    if ($vendedorAnterior !== null && (int) $vendedorAnterior !== (int) $vendedorDestino) {
                        TransferenciaContato::create([
                            'empresa_id' => $importacao->empresa_id,
                            'contato_id' => $contato->id,
                            'de_users_id' => $vendedorAnterior,
                            'para_user_id' => $vendedorDestino,
                            'responsavel_transferencia' => $userId,
                        ]);
                    }
                }

                $contato->update(['status' => 'Y']);
                $item->update([
                    'contato_existente_id' => $contato->id,
                    'resolucao' => $destino === 'PREDITIVA' ? 'ENVIADO_PREDITIVA' : 'ATRIBUIDO_VENDEDOR',
                    'resolvido_por' => $userId,
                    'resolvido_em' => now(),
                ]);
            }

            $this->recontar($importacao);
            $pendentes = $this->possuiDuplicadosPendentes($importacao) || $this->possuiNovosPendentes($importacao);
            $importacao->update([
                'status' => $pendentes ? ($importacao->importados_em ? 'PARCIAL' : 'EM_ANALISE') : 'CONCLUIDA',
                'concluida_em' => $pendentes ? null : now(),
            ]);

            return $itens->count();
        });
    }

    public function detalhar(MailingImportacao $importacao): array
    {
        $itens = $importacao->itens()
            ->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])
            ->orderByRaw('resolvido_em IS NOT NULL')
            ->orderBy('linha')
            ->get();

        $contatoIds = $itens->pluck('contato_existente_id')->filter()->unique()->values();
        $contatos = DB::table('contatos as c')
            ->leftJoin('contatos_corretores as cc', function ($join) use ($importacao) {
                $join->on('cc.contato_id', '=', 'c.id')->where('cc.empresa_id', $importacao->empresa_id);
            })
            ->leftJoin('users as u', 'u.id', '=', 'cc.user_id')
            ->leftJoin('tabulacoes as t', 't.id', '=', 'cc.tabulacao_id')
            ->leftJoin('preditiva as p', function ($join) use ($importacao) {
                $join->on('p.contato_id', '=', 'c.id')
                    ->where('p.empresa_id', $importacao->empresa_id)
                    ->where('p.status', 'Y');
            })
            ->where('c.empresa_id', $importacao->empresa_id)
            ->whereIn('c.id', $contatoIds)
            ->select('c.id', 'c.nome_cliente', 'c.cpf', 'c.status', 'cc.user_id', 'u.name as vendedor', 'cc.tabulacao_id', 't.descricao as tabulacao', 'p.id as preditiva_id')
            ->get()
            ->keyBy('id');

        $propostas = DB::table('vendas as v')
            ->leftJoin('tabulacoes as t', 't.id', '=', 'v.tabulacao_id')
            ->where('v.empresa_id', $importacao->empresa_id)
            ->whereIn('v.contato_id', $contatoIds)
            ->orderByDesc('v.id')
            ->get(['v.id', 'v.contato_id', 'v.numero_proposta', 't.descricao as status'])
            ->groupBy('contato_id');

        return [
            'importacao' => $importacao->fresh()->toArray(),
            'itens' => $itens->map(function (MailingImportacaoItem $item) use ($contatos, $propostas) {
                $contato = $contatos->get($item->contato_existente_id);
                $situacao = 'AGUARDANDO IMPORTAÇÃO';
                if ($contato) {
                    $situacao = $contato->status === 'N'
                        ? 'DESCARTADO'
                        : ($contato->preditiva_id && ! $contato->user_id
                            ? 'PREDITIVA'
                            : ($contato->user_id ? 'COM VENDEDOR' : 'SEM ATRIBUIÇÃO'));
                }

                return [
                    'id' => $item->id,
                    'linha' => $item->linha,
                    'classificacao' => $item->classificacao,
                    'motivo' => $item->motivo,
                    'nome_arquivo' => $item->nome,
                    'cpf' => $item->cpf,
                    'contato_id' => $contato?->id,
                    'nome_cadastrado' => $contato?->nome_cliente,
                    'situacao' => $situacao,
                    'vendedor' => $contato?->vendedor,
                    'tabulacao' => $contato?->tabulacao,
                    'elegivel_movimentacao' => $contato
                        ? ! $contato->user_id && ($contato->status === 'N' || (bool) $contato->preditiva_id)
                        : false,
                    'resolucao' => $item->resolucao,
                    'resolvido_em' => optional($item->resolvido_em)?->format('d/m/Y H:i'),
                    'propostas' => collect($propostas->get($contato?->id, []))->map(fn ($venda) => [
                        'id' => $venda->id,
                        'numero' => $venda->numero_proposta,
                        'status' => $venda->status ?: 'Sem status',
                    ])->values(),
                ];
            })->values(),
        ];
    }

    private function lerPadrao(UploadedFile $arquivo, int $empresaId, int $userId, string $nomeBase): Collection
    {
        $rows = Excel::toArray(new ContatosImport($userId, $empresaId, $nomeBase, 'analise'), $arquivo)[0] ?? [];

        return collect($rows)->values()->map(function (array $row, int $index) {
            return [
                'linha' => $index + 2,
                'cpf' => $this->documento($row['cpf'] ?? null),
                'nome' => trim((string) ($row['nome'] ?? '')),
                'payload' => $row,
                'contato_existente_id' => null,
            ];
        })->filter(fn (array $item) => $item['nome'] !== '' || $item['cpf'])->values();
    }

    private function lerComDependentes(UploadedFile $arquivo, string $nomeBase, int $tabulacaoId, int $vendedorId): Collection
    {
        $rows = Excel::toArray(new ContatosImportDependencies($nomeBase, $tabulacaoId, $vendedorId), $arquivo)[0] ?? [];
        $titulares = collect();
        $atual = null;

        foreach ($rows as $index => $row) {
            if (empty(array_filter($row, fn ($value) => $value !== null && $value !== ''))) {
                continue;
            }

            $primeiraColuna = strtoupper(trim((string) ($row[0] ?? '')));
            if (in_array($primeiraColuna, ['NOME', 'CATEGORIA'], true)) {
                continue;
            }

            $dados = [
                'categoria' => (string) ($row[0] ?? ''),
                'nome' => (string) ($row[1] ?? ''),
                'cpf' => (string) ($row[2] ?? ''),
                'idade' => $row[3] ?? null,
                'parentesco' => strtoupper(trim((string) ($row[4] ?? ''))),
                'valor_plano' => $row[5] ?? null,
                'entidade' => (string) ($row[6] ?? ''),
            ];

            if ($dados['parentesco'] === 'TITULAR') {
                if ($atual) {
                    $titulares->push($atual);
                }
                $atual = [
                    'linha' => $index + 1,
                    'cpf' => $this->documento($dados['cpf']),
                    'nome' => trim($dados['nome']),
                    'payload' => ['titular' => $dados, 'dependentes' => []],
                    'contato_existente_id' => null,
                ];
            } elseif ($atual) {
                $atual['payload']['dependentes'][] = $dados;
            }
        }

        if ($atual) {
            $titulares->push($atual);
        }

        return $titulares;
    }

    private function criarContato(MailingImportacao $importacao, MailingImportacaoItem $item, int $userId): Contatos
    {
        $payload = $item->payload;
        $idOperacao = Helpers::generateUniqueId();

        if ($importacao->tipo_layout === 'com_dependentes') {
            $titular = $payload['titular'];
            $contato = Contatos::create([
                'id_operacao' => $idOperacao,
                'empresa_id' => $importacao->empresa_id,
                'user_import_id' => $userId,
                'tipo_layout' => 'com_dependentes',
                'nome_base' => $importacao->nome_base,
                'status' => 'Y',
                'nome_cliente' => $titular['nome'],
                'cpf' => $item->cpf,
                'idades' => $this->inteiro($titular['idade']),
                'valor_plano_atual' => $this->decimal($titular['valor_plano']),
                'categoria' => $titular['categoria'],
                'entidade' => $titular['entidade'],
            ]);

            foreach ($payload['dependentes'] ?? [] as $dependente) {
                Dependentes::create([
                    'empresa_id' => $importacao->empresa_id,
                    'contato_id' => $contato->id,
                    'nome' => $dependente['nome'],
                    'cpf' => $this->documento($dependente['cpf']),
                    'idade' => $this->inteiro($dependente['idade']),
                    'parentesco' => $dependente['parentesco'],
                    'valor_plano' => $this->decimal($dependente['valor_plano']),
                ]);
            }
        } else {
            $contato = Contatos::create([
                'empresa_id' => $importacao->empresa_id,
                'id_operacao' => $idOperacao,
                'user_import_id' => $userId,
                'nome_base' => $importacao->nome_base,
                'status' => 'Y',
                'nome_cliente' => $payload['nome'] ?? null,
                'data_nascimento' => Helpers::excelDateToPhpDate($payload['data_de_nascimento'] ?? null),
                'cpf' => $item->cpf,
                'plano' => $payload['plano'] ?? null,
                'categoria' => $payload['cartegoria'] ?? null,
                'entidade' => $payload['entidade'] ?? null,
                'telefone1' => Helpers::cleanSpecialCharactersTelefone($payload['contato_1'] ?? null),
                'telefone2' => Helpers::cleanSpecialCharactersTelefone($payload['contato_2'] ?? null),
                'telefone3' => Helpers::cleanSpecialCharactersTelefone($payload['contato_3'] ?? null),
                'email' => $payload['email'] ?? null,
                'idades' => $payload['idades'] ?? null,
                'valor_plano_atual' => $payload['valor'] ?? null,
                'valor_negociacao' => 0,
            ]);
        }

        ContatosCorretores::create([
            'empresa_id' => $importacao->empresa_id,
            'contato_id' => $contato->id,
            'user_id' => $importacao->vendedor_id,
            'tabulacao_id' => $importacao->tabulacao_id,
            'temperatura' => 'FRIO',
        ]);

        return $contato;
    }

    private function vincularDuplicadosDoArquivo(MailingImportacao $importacao): void
    {
        $itens = $importacao->itens()->where('classificacao', 'DUPLICADO_ARQUIVO')->whereNull('contato_existente_id')->get();
        foreach ($itens as $item) {
            $contatoId = Contatos::where('empresa_id', $importacao->empresa_id)->where('cpf', $item->cpf)->value('id');
            if ($contatoId) {
                $item->update(['contato_existente_id' => $contatoId]);
            }
        }
    }

    private function sincronizarDuplicadosNaoElegiveis(MailingImportacao $importacao): void
    {
        $itens = $importacao->itens()
            ->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])
            ->whereNull('resolvido_em')
            ->whereNotNull('contato_existente_id')
            ->get();

        foreach ($itens as $item) {
            $contato = Contatos::where('empresa_id', $importacao->empresa_id)->find($item->contato_existente_id);
            if ($contato && ! $this->contatoElegivelParaReciclagem($contato, $importacao->empresa_id)) {
                $item->update([
                    'resolucao' => 'MANTIDO_ATUAL',
                    'resolvido_por' => $importacao->user_id,
                    'resolvido_em' => now(),
                ]);
            }
        }
    }

    private function contatoElegivelParaReciclagem(Contatos $contato, int $empresaId): bool
    {
        $possuiVendedor = ContatosCorretores::where('empresa_id', $empresaId)
            ->where('contato_id', $contato->id)
            ->exists();

        if ($possuiVendedor) {
            return false;
        }

        return $contato->status === 'N'
            || Preditiva::where('empresa_id', $empresaId)
                ->where('contato_id', $contato->id)
                ->where('status', 'Y')
                ->exists();
    }

    private function limparHistoricoNegociacao(int $contatoId, int $empresaId): void
    {
        Comentarios::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        Agendamento::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        Ligacoes::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        LeadAtividade::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        LogPreditiva::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        PreditivaEnvio::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        TransferenciaContato::where('empresa_id', $empresaId)->where('contato_id', $contatoId)->delete();
        ComercialReunioes::withTrashed()->where('contato_id', $contatoId)->forceDelete();
    }

    private function atualizarContatoComoNovo(
        Contatos $contato,
        MailingImportacao $importacao,
        MailingImportacaoItem $item,
        int $userId
    ): void {
        $dados = [
            'id_operacao' => Helpers::generateUniqueId(),
            'user_import_id' => $userId,
            'nome_base' => $importacao->nome_base,
            'status' => 'Y',
            'valor_negociacao' => 0,
            'ultimo_contato_preditiva' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $item->payload ?? [];
        if ($importacao->tipo_layout === 'com_dependentes') {
            $titular = $payload['titular'] ?? [];
            $dados = array_merge($dados, $this->somenteValoresPreenchidos([
                'nome_cliente' => $titular['nome'] ?? null,
                'idades' => $this->inteiro($titular['idade'] ?? null),
                'valor_plano_atual' => $this->decimal($titular['valor_plano'] ?? null),
                'categoria' => $titular['categoria'] ?? null,
                'entidade' => $titular['entidade'] ?? null,
            ]));
        } else {
            $dados = array_merge($dados, $this->somenteValoresPreenchidos([
                'nome_cliente' => $payload['nome'] ?? null,
                'data_nascimento' => Helpers::excelDateToPhpDate($payload['data_de_nascimento'] ?? null),
                'plano' => $payload['plano'] ?? null,
                'categoria' => $payload['cartegoria'] ?? null,
                'entidade' => $payload['entidade'] ?? null,
                'telefone1' => Helpers::cleanSpecialCharactersTelefone($payload['contato_1'] ?? null),
                'telefone2' => Helpers::cleanSpecialCharactersTelefone($payload['contato_2'] ?? null),
                'telefone3' => Helpers::cleanSpecialCharactersTelefone($payload['contato_3'] ?? null),
                'email' => $payload['email'] ?? null,
                'idades' => $payload['idades'] ?? null,
                'valor_plano_atual' => $payload['valor'] ?? null,
            ]));
        }

        DB::table('contatos')
            ->where('empresa_id', $importacao->empresa_id)
            ->where('id', $contato->id)
            ->update($dados);
        $contato->refresh();
    }

    private function somenteValoresPreenchidos(array $dados): array
    {
        return array_filter($dados, fn ($valor) => $valor !== null && $valor !== '');
    }

    private function recontar(MailingImportacao $importacao): void
    {
        $importacao->update([
            'total_novos' => $importacao->itens()->where('classificacao', 'NOVO')->count(),
            'total_duplicados' => $importacao->itens()->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])->count(),
            'total_importados' => $importacao->itens()->whereNotNull('contato_importado_id')->count(),
            'total_resolvidos' => $importacao->itens()->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])->whereNotNull('resolvido_em')->count(),
        ]);
    }

    private function possuiDuplicadosPendentes(MailingImportacao $importacao): bool
    {
        return $importacao->itens()
            ->whereIn('classificacao', ['DUPLICADO_BASE', 'DUPLICADO_ARQUIVO'])
            ->whereNull('resolvido_em')
            ->exists();
    }

    private function possuiNovosPendentes(MailingImportacao $importacao): bool
    {
        return $importacao->itens()
            ->where('classificacao', 'NOVO')
            ->whereNull('contato_importado_id')
            ->exists();
    }

    private function documento(mixed $valor): ?string
    {
        $documento = preg_replace('/\D+/', '', (string) $valor);

        return $documento === '' ? null : $documento;
    }

    private function inteiro(mixed $valor): ?int
    {
        $numero = preg_replace('/\D+/', '', (string) $valor);

        return $numero === '' ? null : (int) $numero;
    }

    private function decimal(mixed $valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        if (is_numeric($valor)) {
            return round((float) $valor, 2);
        }

        return Helpers::formatCurrencyToDecimal((string) $valor);
    }
}
