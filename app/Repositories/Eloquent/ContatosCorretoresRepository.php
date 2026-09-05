<?php

namespace App\Repositories\Eloquent;

use App\Enums\TabulationCode;
use App\Enums\UserRole;
use App\Models\ContatosCorretores;
use App\Models\Tabulacoes;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use App\Services\TabulationCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContatosCorretoresRepository implements ContatosCorretoresRepositoryInterface
{
    protected $model;

    public function __construct(ContatosCorretores $model, private readonly TabulationCatalog $tabulations)
    {
        $this->model = $model;
    }

    public function getClientComercial(string $rulerUser, string $empresa_id)
    {
        // Colunas base usadas por todos os papéis. A contagem de comentários vai
        // como subquery correlacionada — evita cross-product do JOIN + GROUP BY,
        // que era o gargalo do endpoint em empresas com volume.
        $baseSelect = [
            'tabulacoes.id',
            'tabulacoes.descricao as title',
            'tabulacoes.ordem_kanban',
            'contatos.id as idContato',
            'contatos.nome_cliente',
            DB::raw("
            IF(
                contatos.data_nascimento LIKE '%/%',
                contatos.data_nascimento,
                FROM_UNIXTIME((contatos.data_nascimento - 25569) * 86400, '%d/%m/%Y')
            ) as data_nascimento_formatado
        "),
            'contatos.cpf',
            'contatos.plano',
            'contatos.is_ads',
            'contatos.categoria',
            'contatos.entidade',
            'contatos.telefone1',
            'contatos.telefone2',
            'contatos.telefone3',
            'contatos.email',
            'contatos.idades',
            'contatos.valor_plano_atual',
            'contatos.valor_negociacao',
            'contatos_corretores.temperatura',
            'contatos_corretores.user_id',
            'contatos_corretores.updated_at',
            'contatos_corretores.created_at',
            'users.name as nameVendedor',
        ];

        $applyJoinsAndScalarSubquery = function ($query, ?int $vendedorId = null) use ($empresa_id) {
            $query
                ->leftJoin('contatos', function ($join) use ($empresa_id) {
                    $join->on('contatos.id', '=', 'contatos_corretores.contato_id')
                        ->where('contatos.empresa_id', '=', $empresa_id);
                })
                ->leftJoin('tabulacoes', function ($join) use ($empresa_id) {
                    $join->on('tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
                        ->where('tabulacoes.empresa_id', '=', $empresa_id);
                })
                ->leftJoin('users', function ($join) use ($empresa_id) {
                    $join->on('users.id', '=', 'contatos_corretores.user_id')
                        ->where('users.empresa_id', '=', $empresa_id)
                        ->where('users.is_platform_admin', false);
                });

            if ($vendedorId !== null) {
                // Vendedor: conta apenas comentários do próprio usuário.
                $query->selectSub(function ($sub) use ($vendedorId, $empresa_id) {
                    $sub->from('comentarios')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comentarios.contato_id', 'contatos.id')
                        ->where('comentarios.empresa_id', $empresa_id)
                        ->where('comentarios.user_id', $vendedorId);
                }, 'qt_comentarios');
            } else {
                $query->selectSub(function ($sub) use ($empresa_id) {
                    $sub->from('comentarios')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comentarios.contato_id', 'contatos.id')
                        ->where('comentarios.empresa_id', $empresa_id);
                }, 'qt_comentarios');
            }
        };

        if ($rulerUser == UserRole::ADMINISTRATIVO || $rulerUser == UserRole::BACKOFFICE || $rulerUser == UserRole::SUPERVISOR) {
            $query = $this->model::select($baseSelect);
            $applyJoinsAndScalarSubquery($query);

            return $query
                ->where('contatos_corretores.empresa_id', $empresa_id)
                ->where('contatos.empresa_id', $empresa_id)
                ->where('tabulacoes.empresa_id', $empresa_id)
                ->orderBy('contatos.created_at', 'desc')
                ->get();
        }

        if ($rulerUser == UserRole::DEVELOPER) {
            $query = $this->model::select($baseSelect);
            $applyJoinsAndScalarSubquery($query);

            return $query
                ->where('contatos_corretores.empresa_id', $empresa_id)
                ->where('contatos.empresa_id', $empresa_id)
                ->where('tabulacoes.empresa_id', $empresa_id)
                ->where('tabulacoes.tipo_tabulacao', 'C') // Apenas tabulações comerciais (exclui pós-venda)
                ->orderBy('contatos.created_at', 'desc')
                ->get();
        }

        if ($rulerUser == UserRole::VENDEDOR) {
            $query = $this->model::select($baseSelect);
            $applyJoinsAndScalarSubquery($query, (int) Auth::user()->id);

            return $query
                ->where('contatos_corretores.user_id', Auth::user()->id)
                ->where('contatos_corretores.empresa_id', $empresa_id)
                ->where('contatos.empresa_id', $empresa_id)
                ->where('tabulacoes.empresa_id', $empresa_id)
                ->where('tabulacoes.tipo_tabulacao', 'C') // Apenas tabulações comerciais (exclui pós-venda)
                ->orderBy('contatos.created_at', 'desc')
                ->get();
        }
    }

    public function changeStatusLead($data): bool
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            DB::beginTransaction();
            $card = $this->model->where('contato_id', $data['contato_id'])->where('empresa_id', $empresaId)->first();
            if ($card) {
                $tabulacao = Tabulacoes::query()->where('empresa_id', $empresaId)->find($data['tabulacao_id']);
                if (! $tabulacao) {
                    DB::rollBack();

                    return false;
                }

                $card->tabulacao_id = $tabulacao->codigo === TabulationCode::NEGOCIO_NAO_FECHADO
                    ? $this->tabulations->id($empresaId, TabulationCode::REMARKETING)
                    : $tabulacao->id;
                $save = $card->save();
                DB::commit();

                return $save;
            } else {
                DB::rollBack();

                return false;
            }
        } catch (\Throwable $th) {
            DB::rollBack();

            return false;
        }
    }

    public function updateLeadTemperature($idMailing, $temperatura)
    {
        try {
            $contactRelationship = $this->model->where('contato_id', $idMailing)
                ->where('empresa_id', app(\App\Support\TenantContext::class)->id())->first();
            if (! $contactRelationship) {
                return false;
            }
            $contactRelationship->temperatura = $temperatura;

            return $contactRelationship->save();
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getClientInfo($idMailing)
    {
        $dateExpr = "
        CASE
            WHEN contatos.data_nascimento IS NULL OR contatos.data_nascimento = '' THEN NULL
            WHEN CAST(contatos.data_nascimento AS CHAR) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
                THEN DATE_FORMAT(contatos.data_nascimento, '%d/%m/%Y')
            WHEN CAST(contatos.data_nascimento AS CHAR) REGEXP '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$'
                THEN DATE_FORMAT(STR_TO_DATE(CAST(contatos.data_nascimento AS CHAR), '%d/%m/%Y'), '%d/%m/%Y')
            WHEN CAST(contatos.data_nascimento AS CHAR) REGEXP '^[0-9]{1,5}(\\.[0-9]+)?$'
                THEN DATE_FORMAT(DATE_ADD('1899-12-30', INTERVAL CAST(contatos.data_nascimento AS UNSIGNED) DAY), '%d/%m/%Y')
            ELSE NULL
        END
    ";

        $client = $this->model
            ->leftJoin('contatos', function ($join) {
                $join->on('contatos.id', '=', 'contatos_corretores.contato_id')
                    ->on('contatos.empresa_id', '=', 'contatos_corretores.empresa_id');
            })
            ->where('contatos_corretores.contato_id', $idMailing)
            ->where('contatos_corretores.empresa_id', app(\App\Support\TenantContext::class)->id())
            ->where('contatos.empresa_id', app(\App\Support\TenantContext::class)->id())
            ->select(
                'contatos.empresa_id',
                'contatos.id',
                'contatos.nome_cliente',
                'contatos.email',
                'contatos.cpf',
                DB::raw("$dateExpr as data_nascimento"),
                'contatos_corretores.temperatura',
                'contatos.plano',
                'contatos.categoria',
                'contatos.entidade',
                'contatos.telefone1',
                'contatos.telefone2',
                'contatos.telefone3',
                'contatos.idades',
                'contatos.valor_plano_atual',
                'contatos.valor_negociacao',
                'contatos.tipo_criativo',
                'contatos.is_ads',
                'contatos.possui_cnpj',
                'contatos.plano_ativo',
                'contatos.vidas',
                'contatos.tipo_layout'
            )
            ->first(); // evita undefined offset

        return $client;
    }

    public function updateTemperatureAndTabulation(string $temperature, string $idMailing, string $tabulacao_id)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            if (! Tabulacoes::query()->where('empresa_id', $empresaId)->whereKey($tabulacao_id)->exists()) {
                return false;
            }
            if (! DB::table('contatos')->where('empresa_id', $empresaId)->where('id', $idMailing)->exists()) {
                return false;
            }

            $searchRegister = [
                'contato_id' => $idMailing,
                'empresa_id' => $empresaId,
            ];

            $this->model::updateOrCreate($searchRegister, ['temperatura' => $temperature, 'tabulacao_id' => $tabulacao_id]);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getRemarketingLeads(string $empresa_id)
    {
        $empresaId = (int) $empresa_id;
        $results = ContatosCorretores::with(['contato', 'tabulacao', 'subTabulacao'])
            ->leftJoin('tabulacoes as c', function ($join) {
                $join->on('contatos_corretores.tabulacao_id', '=', 'c.id')
                    ->on('contatos_corretores.empresa_id', '=', 'c.empresa_id');
            })
            ->leftJoin('tabulacoes as sub', function ($join) {
                $join->on('contatos_corretores.sub_tabulacao_id', '=', 'sub.id')
                    ->on('contatos_corretores.empresa_id', '=', 'sub.empresa_id');
            })
            ->leftJoin('contatos as b', function ($join) {
                $join->on('contatos_corretores.contato_id', '=', 'b.id')
                    ->on('contatos_corretores.empresa_id', '=', 'b.empresa_id');
            })
            ->leftJoin('users as u', function ($join) {
                $join->on('contatos_corretores.user_id', '=', 'u.id')
                    ->on('contatos_corretores.empresa_id', '=', 'u.empresa_id')
                    ->where('u.is_platform_admin', false);
            })
            ->select(
                'b.id',
                'b.nome_cliente',
                'b.email',
                'b.telefone1',
                DB::raw('DATE_FORMAT(contatos_corretores.updated_at, "%d/%m/%Y %H:%i") as ultima_atualizacao'),
                'b.plano',
                DB::raw('DATE_FORMAT(b.created_at, "%d/%m/%Y") as data_importacao'),
                DB::raw('COALESCE(sub.descricao, c.descricao) AS motivo_remarketing'),
                'b.categoria',
                'u.name as corretor_descarte'
            )
            ->where('contatos_corretores.empresa_id', $empresaId)
            ->where('b.empresa_id', $empresaId)
            ->where('c.empresa_id', $empresaId)
            ->where('c.id', $this->tabulations->id($empresaId, TabulationCode::REMARKETING))
            ->where(function ($query) use ($empresaId) {
                $query->whereNull('sub.id')->orWhere('sub.empresa_id', $empresaId);
            })
            ->where(function ($query) use ($empresaId) {
                $query->whereNull('u.id')->orWhere('u.empresa_id', $empresaId);
            })
            ->orderBy('contatos_corretores.updated_at', 'desc')
            ->get();

        return $results;
    }

    public function getTabulationId(int $idMailing, ?int $empresaId = null, ?int $userId = null)
    {
        $empresaId ??= (int) app(\App\Support\TenantContext::class)->id();

        return $this->model->select('tabulacao_id')
            ->where('contato_id', $idMailing)
            ->where('empresa_id', $empresaId)
            ->when($userId !== null, fn ($query) => $query->where('user_id', $userId))
            ->first();
    }

    public function transferContact(array $data)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            if (! $this->destinoValido($empresaId, (int) $data['user_id'], isset($data['tabulation_id']) ? (int) $data['tabulation_id'] : null)) {
                return false;
            }

            $lead = $this->model::where('contato_id', $data['idMailing'])->where('empresa_id', $empresaId)->first();

            if ($lead) {
                $lead->user_id = $data['user_id'];
                $lead->tabulacao_id = $data['tabulation_id'];
                $lead->updated_at = Carbon::now();

                return $lead->save();
            } else {
                if (! DB::table('contatos')->where('empresa_id', $empresaId)->where('id', $data['idMailing'])->exists()) {
                    return false;
                }

                $newLead = new $this->model();
                $newLead->contato_id = $data['idMailing'];
                $newLead->user_id = $data['user_id'];
                $newLead->tabulacao_id = $data['tabulation_id'] ?? $this->tabulations->id($empresaId, TabulationCode::NOVOS_CLIENTES);
                $newLead->empresa_id = $empresaId;
                $newLead->temperatura = 'FRIO';
                $newLead->created_at = Carbon::now();
                $newLead->updated_at = Carbon::now();

                return $newLead->save();
            }
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function transferContactInNulk(array $data)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            if (! $this->destinoValido($empresaId, (int) $data['user_id'], (int) $data['tabulation_id'])) {
                return false;
            }

            DB::beginTransaction();
            $leadIds = explode(',', $data['selectedLeadIds']);
            array_map(function ($leadId) use ($data, $empresaId) {
                $this->model->where('contato_id', $leadId)->where('empresa_id', $empresaId)->update([
                    'user_id' => $data['user_id'],
                    'tabulacao_id' => $data['tabulation_id'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }, $leadIds);
            DB::commit();

            return true;
        } catch (\Throwable $th) {
            DB::rollBack();

            return false;
        }
    }

    public function sendRemaketing($idLead, $sub_tabulacao_id)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $subTabulacaoValida = Tabulacoes::query()
                ->where('empresa_id', $empresaId)
                ->where('sub_tabulacao', 'Y')
                ->whereKey($sub_tabulacao_id)
                ->exists();
            if (! $subTabulacaoValida) {
                return false;
            }

            $update = $this->model::where('contato_id', $idLead)->where('empresa_id', $empresaId)->update(
                [
                    'sub_tabulacao_id' => $sub_tabulacao_id,
                    'tabulacao_id' => $this->tabulations->id($empresaId, TabulationCode::REMARKETING),
                ]
            );

            return $update;
        } catch (\Throwable $th) {
            return false;
        }
    }

    // Status de CONTRATO não passa mais por aqui — use VendasRepository::alterStatusVenda().
    // contatos_corretores.tabulacao_id representa apenas o estágio do LEAD (comercial).

    public function sendSchedule($idLead)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            $agendamentoId = $this->tabulations->id($empresaId, TabulationCode::AGENDAMENTO);
            $update = $this->model::where('contato_id', $idLead)->where('empresa_id', $empresaId)->update(
                [
                    'sub_tabulacao_id' => $agendamentoId,
                    'tabulacao_id' => $agendamentoId,
                ]
            );

            return $update;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getQueueCurrent($id_user)
    {
        try {
            $empresaId = (int) app(\App\Support\TenantContext::class)->id();
            if (! DB::table('users')->where('empresa_id', $empresaId)->where('is_platform_admin', false)->where('id', $id_user)->exists()) {
                return collect();
            }

            return DB::table('contatos_corretores as a')
                ->leftJoin('tabulacoes as b', function ($join) {
                    $join->on('b.id', '=', 'a.tabulacao_id')
                        ->on('b.empresa_id', '=', 'a.empresa_id');
                })
                ->select('b.descricao as status', DB::raw('COUNT(*) as total_tabulacoes'))
                ->where('a.user_id', $id_user)
                ->where('a.empresa_id', $empresaId)
                ->where('b.empresa_id', $empresaId)
                ->whereIn('b.id', array_values($this->tabulations->requiredIds($empresaId, TabulationCode::COMERCIAL_ATIVO)))
                ->groupBy('b.descricao')
                ->orderBy('total_tabulacoes', 'desc')
                ->get();
        } catch (\Throwable $th) {

        }
    }

    public function getContactOwner($id_contato)
    {
        return $this->model::select('user_id')
            ->where('contato_id', $id_contato)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->first();
    }

    public function deleteMailing($id_mailing)
    {
        return $this->model
            ->where('contato_id', $id_mailing)
            ->where('empresa_id', app(\App\Support\TenantContext::class)->id())
            ->delete();
    }

    private function destinoValido(int $empresaId, int $userId, ?int $tabulacaoId): bool
    {
        $usuarioValido = DB::table('users')->where('empresa_id', $empresaId)->where('is_platform_admin', false)->where('ativo', 'Y')->where('id', $userId)->exists();
        $tabulacaoValida = $tabulacaoId === null || Tabulacoes::query()->where('empresa_id', $empresaId)->whereKey($tabulacaoId)->exists();

        return $usuarioValido && $tabulacaoValida;
    }
}
