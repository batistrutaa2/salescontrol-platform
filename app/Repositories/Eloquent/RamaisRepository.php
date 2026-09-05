<?php

namespace App\Repositories\Eloquent;

use App\Models\Ramais;
use App\Repositories\Contracts\RamaisRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RamaisRepository implements RamaisRepositoryInterface
{
    public function __construct(private readonly Ramais $model) {}

    public function create(int $empresaId, int $userId, string $ramal): Ramais
    {
        return $this->model->updateOrCreate(
            ['empresa_id' => $empresaId, 'user_id' => $userId],
            ['ramal' => $ramal]
        );
    }

    public function getRamais(int $empresaId): Collection
    {
        return DB::table('ramais as ramal')
            ->leftJoin('users as usuario', function ($join) {
                $join->on('usuario.id', '=', 'ramal.user_id')
                    ->on('usuario.empresa_id', '=', 'ramal.empresa_id')
                    ->where('usuario.is_platform_admin', false);
            })
            ->where('ramal.empresa_id', $empresaId)
            ->select('ramal.id', 'usuario.name', 'ramal.ramal', 'ramal.created_at')
            ->orderBy('usuario.name')
            ->get()
            ->map(function ($registro) {
                $registro->created_at = $registro->created_at
                    ? Carbon::parse($registro->created_at)->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i:s')
                    : null;

                return $registro;
            });
    }

    public function getRamal(int $empresaId, int $userId): ?Ramais
    {
        return $this->model
            ->where('empresa_id', $empresaId)
            ->where('user_id', $userId)
            ->first();
    }
}
