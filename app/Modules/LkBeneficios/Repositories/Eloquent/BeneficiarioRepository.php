<?php

namespace App\Modules\LkBeneficios\Repositories\Eloquent;

use App\Modules\LkBeneficios\Enums\TipoMovimentacao;
use App\Modules\LkBeneficios\Models\Beneficiario;
use App\Modules\LkBeneficios\Models\Movimentacao;
use App\Modules\LkBeneficios\Repositories\Contracts\BeneficiarioRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BeneficiarioRepository implements BeneficiarioRepositoryInterface
{
    public function listarPorContrato(int $contratoId): Collection
    {
        return Beneficiario::where('contrato_id', $contratoId)
            ->orderByRaw("FIELD(tipo, 'TITULAR', 'DEPENDENTE')")
            ->orderBy('nome')
            ->get();
    }

    public function incluir(int $contratoId, array $data, int $userId): Beneficiario
    {
        return DB::transaction(function () use ($contratoId, $data, $userId) {
            $beneficiario = Beneficiario::create([
                'contrato_id' => $contratoId,
                'tipo' => $data['tipo'],
                'nome' => $data['nome'],
                'cpf' => isset($data['cpf']) ? preg_replace('/\D+/', '', $data['cpf']) : null,
                'data_nascimento' => $data['data_nascimento'] ?? null,
                'grau_parentesco' => $data['grau_parentesco'] ?? null,
                'telefone' => $data['telefone'] ?? null,
                'email' => $data['email'] ?? null,
                'status' => 'ATIVO',
                'data_inclusao' => $data['data_inclusao'] ?? now()->toDateString(),
            ]);

            Movimentacao::create([
                'contrato_id' => $contratoId,
                'beneficiario_id' => $beneficiario->id,
                'user_id' => $userId,
                'tipo' => TipoMovimentacao::INCLUSAO,
                'descricao' => "Inclusão de {$beneficiario->tipo}: {$beneficiario->nome}",
            ]);

            return $beneficiario;
        });
    }

    public function excluir(int $id, int $contratoId, int $userId, ?string $motivo = null): bool
    {
        return DB::transaction(function () use ($id, $contratoId, $userId, $motivo) {
            $beneficiario = Beneficiario::where('id', $id)
                ->where('contrato_id', $contratoId)
                ->firstOrFail();

            $beneficiario->update([
                'status' => 'EXCLUIDO',
                'data_exclusao' => now()->toDateString(),
            ]);

            Movimentacao::create([
                'contrato_id' => $contratoId,
                'beneficiario_id' => $beneficiario->id,
                'user_id' => $userId,
                'tipo' => TipoMovimentacao::EXCLUSAO,
                'descricao' => trim("Exclusão de {$beneficiario->tipo}: {$beneficiario->nome}" . ($motivo ? " — {$motivo}" : '')),
            ]);

            return true;
        });
    }
}
