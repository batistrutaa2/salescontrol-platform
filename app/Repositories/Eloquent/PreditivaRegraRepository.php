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

    public function create(int $empresaId, array $data): ?int
    {
        try {
            $maxOrdem = $this->model::where('empresa_id', $empresaId)
                ->max('ordem') ?? 0;

            $data['empresa_id'] = $empresaId;
            $data['ordem'] = $maxOrdem + 1;

            $regra = $this->model::create($data);

            return $regra->id;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function update(int $id, int $empresaId, array $data): bool
    {
        try {
            $regra = $this->model::where('empresa_id', $empresaId)->find($id);
            if (! $regra) {
                return false;
            }

            return $regra->update($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function delete(int $id, int $empresaId): bool
    {
        try {
            $regra = $this->model::where('empresa_id', $empresaId)->find($id);
            if (! $regra) {
                return false;
            }

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

    public function toggleAtivo(int $id, int $empresaId): bool
    {
        try {
            $regra = $this->model::where('empresa_id', $empresaId)->find($id);
            if (! $regra) {
                return false;
            }

            $regra->ativo = $regra->ativo === 'Y' ? 'N' : 'Y';

            return $regra->save();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function reordenar(int $empresaId, array $ordens): bool
    {
        try {
            DB::beginTransaction();

            foreach ($ordens as $index => $id) {
                $this->model::where('id', $id)
                    ->where('empresa_id', $empresaId)
                    ->update(['ordem' => $index + 1]);
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            return false;
        }
    }

    public function findById(int $id, int $empresaId)
    {
        return $this->model::where('empresa_id', $empresaId)->find($id);
    }
}
