<?php

namespace App\Repositories\Eloquent;

use App\Models\PreditivaRegraPriorizacao;
use App\Repositories\Contracts\PreditivaRegraRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PreditivaRegraRepository implements PreditivaRegraRepositoryInterface
{
    protected PreditivaRegraPriorizacao $model;

    public function __construct(PreditivaRegraPriorizacao $model)
    {
        $this->model = $model;
    }

    public function getRegrasByEmpresa(int $empresaId, bool $apenasAtivas = false): Collection
    {
        $query = $this->model::where('empresa_id', $empresaId)
            ->orderBy('ordem', 'asc');

        if ($apenasAtivas) {
            $query->where('ativo', 'Y');
        }

        return $query->get();
    }

    public function create(array $data): ?int
    {
        try {
            $maxOrdem = $this->model::where('empresa_id', $data['empresa_id'])
                ->max('ordem') ?? 0;

            $data['ordem'] = $maxOrdem + 1;

            $regra = $this->model::create($data);
            return $regra->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $regra = $this->model::find($id);
            if (!$regra) {
                return false;
            }

            return $regra->update($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            $regra = $this->model::find($id);
            if (!$regra) {
                return false;
            }

            $empresaId = $regra->empresa_id;
            $ordem = $regra->ordem;

            $deleted = $regra->delete();

            if ($deleted) {
                $this->model::where('empresa_id', $empresaId)
                    ->where('ordem', '>', $ordem)
                    ->decrement('ordem');
            }

            return $deleted;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function toggleAtivo(int $id): bool
    {
        try {
            $regra = $this->model::find($id);
            if (!$regra) {
                return false;
            }

            $regra->ativo = $regra->ativo === 'Y' ? 'N' : 'Y';
            return $regra->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function reordenar(array $ordens): bool
    {
        try {
            DB::beginTransaction();

            foreach ($ordens as $index => $id) {
                $this->model::where('id', $id)->update(['ordem' => $index + 1]);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function findById(int $id)
    {
        return $this->model::find($id);
    }
}
