<?php

namespace App\Modules\LkBeneficios\Repositories\Eloquent;

use App\Modules\LkBeneficios\Models\Contrato;
use App\Modules\LkBeneficios\Models\Produto;
use App\Modules\LkBeneficios\Repositories\Contracts\ProdutoRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class ProdutoRepository implements ProdutoRepositoryInterface
{
    public function queryForDataTable(int $empresaId, array $filtros = []): Builder
    {
        $query = Produto::query()
            ->with('operadora:id,nome')
            ->where('empresa_id', $empresaId)
            ->select(['id', 'empresa_id', 'nome', 'tipo', 'subtipo', 'modalidade', 'operadora_id', 'descricao', 'coberturas', 'ativo', 'created_at']);

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (isset($filtros['status']) && $filtros['status'] !== '') {
            $query->where('ativo', $filtros['status'] === 'ativo');
        }

        if (! empty($filtros['busca'])) {
            $busca = '%' . $filtros['busca'] . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('nome', 'like', $busca)
                    ->orWhere('subtipo', 'like', $busca)
                    ->orWhere('modalidade', 'like', $busca);
            });
        }

        return $query;
    }

    public function findByEmpresa(int $id, int $empresaId): ?Produto
    {
        return Produto::with('operadora:id,nome')
            ->where('id', $id)
            ->where('empresa_id', $empresaId)
            ->first();
    }

    public function create(array $data): Produto
    {
        return Produto::create([
            'empresa_id' => $data['empresa_id'],
            'nome' => $data['nome'],
            'tipo' => $data['tipo'],
            'subtipo' => $data['subtipo'] ?? null,
            'modalidade' => $data['modalidade'] ?? null,
            'operadora_id' => $data['operadora_id'] ?? null,
            'descricao' => $data['descricao'] ?? null,
            'coberturas' => $data['coberturas'] ?? [],
            'ativo' => array_key_exists('ativo', $data) ? (bool) $data['ativo'] : true,
        ]);
    }

    public function update(int $id, int $empresaId, array $data): Produto
    {
        $produto = Produto::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $camposAtualizaveis = ['nome', 'tipo', 'subtipo', 'modalidade', 'operadora_id', 'descricao', 'coberturas', 'ativo'];
        $payload = array_intersect_key($data, array_flip($camposAtualizaveis));

        if (array_key_exists('ativo', $payload)) {
            $payload['ativo'] = (bool) $payload['ativo'];
        }

        $produto->update($payload);

        return $produto->fresh('operadora');
    }

    public function delete(int $id, int $empresaId): bool
    {
        $produto = Produto::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        return (bool) $produto->delete();
    }

    public function setAtivo(int $id, int $empresaId, bool $ativo): Produto
    {
        $produto = Produto::where('id', $id)
            ->where('empresa_id', $empresaId)
            ->firstOrFail();

        $produto->update(['ativo' => $ativo]);

        return $produto->fresh();
    }

    public function temContratosVinculados(int $id, int $empresaId): bool
    {
        return Contrato::where('produto_id', $id)
            ->where('empresa_id', $empresaId)
            ->exists();
    }
}
