<?php

namespace App\Support;

use App\Models\Empresa;
use LogicException;

class TenantContext
{
    public const SESSION_KEY = 'active_empresa_id';

    private ?int $empresaId = null;

    public function set(int $empresaId): void
    {
        $this->empresaId = $empresaId;
    }

    public function clear(): void
    {
        $this->empresaId = null;
    }

    public function run(int $empresaId, callable $callback): mixed
    {
        $previousEmpresaId = $this->empresaId;
        $this->set($empresaId);

        try {
            return $callback();
        } finally {
            $this->empresaId = $previousEmpresaId;
        }
    }

    public function id(): int
    {
        if ($this->empresaId === null) {
            throw new LogicException('Nenhuma empresa ativa foi definida para esta requisição.');
        }

        return $this->empresaId;
    }

    public function empresa(): Empresa
    {
        return Empresa::query()->findOrFail($this->id());
    }

    public function isResolved(): bool
    {
        return $this->empresaId !== null;
    }
}
