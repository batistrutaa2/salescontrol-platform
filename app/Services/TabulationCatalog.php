<?php

namespace App\Services;

use App\Enums\TabulationCode;
use App\Models\Tabulacoes;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TabulationCatalog
{
    private array $ids = [];

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function provision(int $empresaId): void
    {
        $this->tenantContext->run($empresaId, function () use ($empresaId): void {
            DB::transaction(function () use ($empresaId) {
                foreach (TabulationCode::defaults() as $codigo => $definition) {
                    Tabulacoes::query()->firstOrCreate(
                        ['empresa_id' => $empresaId, 'codigo' => $codigo],
                        $definition + ['sub_tabulacao' => 'N']
                    );
                }
            });
        });

        unset($this->ids[$empresaId]);
    }

    public function id(int $empresaId, string $codigo): int
    {
        $id = $this->ids($empresaId, [$codigo])[$codigo] ?? null;

        if (! $id) {
            throw ValidationException::withMessages([
                'tabulacao' => "A etapa obrigatória {$codigo} não está configurada para a empresa ativa.",
            ]);
        }

        return $id;
    }

    public function ids(int $empresaId, array $codigos): array
    {
        if (! isset($this->ids[$empresaId])) {
            $this->ids[$empresaId] = $this->tenantContext->run(
                $empresaId,
                fn () => Tabulacoes::query()
                    ->where('empresa_id', $empresaId)
                    ->whereNotNull('codigo')
                    ->pluck('id', 'codigo')
                    ->map(fn ($id) => (int) $id)
                    ->all()
            );
        }

        return collect($codigos)
            ->mapWithKeys(fn ($codigo) => [$codigo => $this->ids[$empresaId][$codigo] ?? null])
            ->filter()
            ->all();
    }

    public function requiredIds(int $empresaId, array $codigos): array
    {
        $ids = $this->ids($empresaId, $codigos);
        $missing = array_values(array_diff($codigos, array_keys($ids)));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'tabulacao' => 'Etapas obrigatórias não configuradas: '.implode(', ', $missing).'.',
            ]);
        }

        return $ids;
    }
}
