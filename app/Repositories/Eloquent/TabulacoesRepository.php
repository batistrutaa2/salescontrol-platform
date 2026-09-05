<?php

namespace App\Repositories\Eloquent;

use App\Enums\TabulationCode;
use App\Models\Tabulacoes;
use App\Repositories\Contracts\TabulacoesRepositoryInterface;

class TabulacoesRepository implements TabulacoesRepositoryInterface
{
    protected $model;

    public function __construct(Tabulacoes $model)
    {
        $this->model = $model;
    }

    public function getTabulationsCompanieCommercial($empresa_id)
    {
        return $this->model->select(['id', 'codigo', 'descricao', 'ordem_kanban'])->where('empresa_id', $empresa_id)->where('status', 'Y')->where('tipo_tabulacao', 'C')->get();
    }

    public function getAll($empresa_id)
    {
        return $this->model->select(['id', 'descricao'])->where('empresa_id', $empresa_id)->where('status', 'Y')->get();
    }

    public function getTabulationsBackoffice($empresa_id)
    {
        return $this->model->select(['id', 'codigo', 'descricao'])
            ->where('empresa_id', $empresa_id)
            ->where('status', 'Y')
            ->where('tipo_tabulacao', 'A')
            ->whereIn('codigo', [
                TabulationCode::VENDA,
                TabulationCode::ESTORNO,
                TabulationCode::IMPLANTADO,
                TabulationCode::DECLINADO,
                TabulationCode::ANALISE_OPERADORA,
                TabulationCode::BOLETO_DISPONIVEL,
                TabulationCode::REGULARIZADO,
                TabulationCode::CONTRATO_GERADO_AGUARDANDO_ASSINATURA,
                TabulationCode::PENDENCIA,
                TabulationCode::ANALISE_DOCUMENTOS,
                TabulationCode::AGUARDANDO_ASSINATURA_DS,
            ])
            ->get();
    }

    public function getSubTabulations($empresa_id)
    {
        return $this->model->select(['id', 'descricao'])
            ->where('empresa_id', $empresa_id)
            ->where('status', 'Y')
            ->where('sub_tabulacao', 'S')
            ->get();
    }
}
