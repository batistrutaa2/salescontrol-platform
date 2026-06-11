<?php

namespace App\Repositories\Eloquent;

use App\Enums\Tabulations;
use App\Enums\UserRole;
use App\Models\ContatosCorretores;
use App\Models\Tabulacoes;
use App\Repositories\Contracts\ContatosCorretoresRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContatosCorretoresRepository implements ContatosCorretoresRepositoryInterface
{
    protected $model;

    public function __construct(ContatosCorretores $model)
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

        $applyJoinsAndScalarSubquery = function ($query, ?int $vendedorId = null) {
            $query
                ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
                ->leftJoin('tabulacoes', 'tabulacoes.id', '=', 'contatos_corretores.tabulacao_id')
                ->leftJoin('users', 'users.id', '=', 'contatos_corretores.user_id');

            if ($vendedorId !== null) {
                // Vendedor: conta apenas comentários do próprio usuário.
                $query->selectSub(function ($sub) use ($vendedorId) {
                    $sub->from('comentarios')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comentarios.contato_id', 'contatos.id')
                        ->where('comentarios.user_id', $vendedorId);
                }, 'qt_comentarios');
            } else {
                $query->selectSub(function ($sub) {
                    $sub->from('comentarios')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn('comentarios.contato_id', 'contatos.id');
                }, 'qt_comentarios');
            }
        };

        if ($rulerUser == UserRole::ADMINISTRATIVO || $rulerUser == UserRole::BACKOFFICE || $rulerUser == UserRole::SUPERVISOR) {
            $query = $this->model::select($baseSelect);
            $applyJoinsAndScalarSubquery($query);

            return $query
                ->where('contatos_corretores.empresa_id', $empresa_id)
                ->orderBy('contatos.created_at', 'desc')
                ->get();
        }

        if ($rulerUser == UserRole::DEVELOPER) {
            $query = $this->model::select($baseSelect);
            $applyJoinsAndScalarSubquery($query);

            return $query
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
                ->where('tabulacoes.tipo_tabulacao', 'C') // Apenas tabulações comerciais (exclui pós-venda)
                ->orderBy('contatos.created_at', 'desc')
                ->get();
        }
    }

    public function changeStatusLead($data): bool
    {
        try {
            DB::beginTransaction();
            $card = $this->model->where('contato_id', $data['contato_id'])->first();
            if ($card) {
                if ($data['tabulacao_id'] == Tabulations::NEGOCIO_NAO_FECHADO) {
                    $card->tabulacao_id = Tabulations::REMARKETING;
                } else {
                    $card->tabulacao_id = $data['tabulacao_id'];
                }
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
            $contactRelationship = $this->model->where('contato_id', $idMailing)->first();
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
            ->leftJoin('contatos', 'contatos.id', '=', 'contatos_corretores.contato_id')
            ->where('contatos_corretores.contato_id', $idMailing)
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
            $searchRegister = [
                'contato_id' => $idMailing,
            ];

            $this->model::updateOrCreate($searchRegister, ['temperatura' => $temperature, 'tabulacao_id' => $tabulacao_id]);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function getRemarketingLeads(string $empresa_id)
    {
        $results = ContatosCorretores::with(['contato', 'tabulacao', 'subTabulacao'])
            ->leftJoin('tabulacoes as c', 'contatos_corretores.tabulacao_id', '=', 'c.id')
            ->leftJoin('tabulacoes as sub', 'contatos_corretores.sub_tabulacao_id', '=', 'sub.id')
            ->leftJoin('contatos as b', 'contatos_corretores.contato_id', '=', 'b.id')
            ->leftJoin('users as u', 'contatos_corretores.user_id', '=', 'u.id')
            ->select(
                'b.id',
                'b.nome_cliente',
                'b.email',
                'b.telefone1',
                DB::raw('DATE_FORMAT(contatos_corretores.updated_at, "%d/%m/%Y %H:%i") as ultima_atualizacao'),
                'b.plano',
                DB::raw('DATE_FORMAT(b.created_at, "%d/%m/%Y") as data_importacao'),
                DB::raw('COALESCE(sub.descricao, "REMARKETING") AS motivo_remarketing'),
                'b.categoria',
                'u.name as corretor_descarte'
            )
            ->where('contatos_corretores.empresa_id', $empresa_id)
            ->where('c.descricao', 'REMARKETING')
            ->orderBy('contatos_corretores.updated_at', 'desc')
            ->get();

        return $results;
    }

    public function getTabulationId($idMailing)
    {
        return $this->model->select('tabulacao_id')->where('contato_id', $idMailing)->first();
    }

    public function transferContact(array $data)
    {
        try {
            $lead = $this->model::where('contato_id', $data['idMailing'])->first();

            if ($lead) {
                $lead->user_id = $data['user_id'];
                $lead->tabulacao_id = $data['tabulation_id'];
                $lead->updated_at = Carbon::now();

                return $lead->save();
            } else {
                $tabulation = Tabulacoes::where('descricao', 'NOVOS CLIENTES')
                    ->where('empresa_id', Auth::user()->empresa_id)
                    ->first();

                $newLead = new $this->model();
                $newLead->contato_id = $data['idMailing'];
                $newLead->user_id = $data['user_id'];
                $newLead->tabulacao_id = $data['tabulation_id'] ?? $tabulation->id;
                $newLead->empresa_id = Auth::user()->empresa_id;
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
            DB::beginTransaction();
            $leadIds = explode(',', $data['selectedLeadIds']);
            array_map(function ($leadId) use ($data) {
                $this->model->where('contato_id', $leadId)->update([
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
            $update = $this->model::where('contato_id', $idLead)->update(
                [
                    'sub_tabulacao_id' => $sub_tabulacao_id,
                    'tabulacao_id' => Tabulations::REMARKETING,
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
            $update = $this->model::where('contato_id', $idLead)->update(
                [
                    'sub_tabulacao_id' => Tabulations::AGENDAMENTO,
                    'tabulacao_id' => Tabulations::AGENDAMENTO,
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
            return DB::table('contatos_corretores as a')
                ->leftJoin('tabulacoes as b', 'b.id', '=', 'a.tabulacao_id')
                ->select('b.descricao as status', DB::raw('COUNT(*) as total_tabulacoes'))
                ->where('a.user_id', $id_user)
                ->whereIn('b.id', [
                    Tabulations::PROSPECCAO,
                    Tabulations::SEM_CONTATO,
                    Tabulations::NEGOCIAÇÃO,
                    Tabulations::FOLLOWUP,
                    Tabulations::REUNIÃO,
                    Tabulations::DOCUMENTO,
                    Tabulations::NEGOCIO_FECHADO,
                    Tabulations::NEGOCIO_NAO_FECHADO,
                ])
                ->groupBy('b.descricao')
                ->orderBy('total_tabulacoes', 'desc')
                ->get();
        } catch (\Throwable $th) {

        }
    }

    public function getContactOwner($id_contato)
    {
        return $this->model::select('user_id')->where('contato_id', $id_contato)->first();
    }

    public function deleteMailing($id_mailing)
    {
        return $this->model
            ->where('contato_id', $id_mailing)
            ->where('empresa_id', Auth::user()->empresa_id)
            ->delete();
    }
}
