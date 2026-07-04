<?php

namespace App\Repositories\Contracts;

use App\Models\WhatsappInstancia;

interface WhatsappInstanciaRepositoryInterface
{
    public function findByUser(int $empresaId, int $userId): ?WhatsappInstancia;

    public function findByInstanceName(string $instanceName): ?WhatsappInstancia;

    public function createForUser(int $empresaId, int $userId): WhatsappInstancia;

    public function getConectadas(): \Illuminate\Support\Collection;
}
