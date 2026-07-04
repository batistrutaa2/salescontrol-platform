<?php

namespace App\Repositories\Eloquent;

use App\Models\WhatsappInstancia;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WhatsappInstanciaRepository implements WhatsappInstanciaRepositoryInterface
{
    protected $model;

    public function __construct(WhatsappInstancia $model)
    {
        $this->model = $model;
    }

    public function findByUser(int $empresaId, int $userId): ?WhatsappInstancia
    {
        return $this->model
            ->where('empresa_id', $empresaId)
            ->where('user_id', $userId)
            ->first();
    }

    public function findByInstanceName(string $instanceName): ?WhatsappInstancia
    {
        return $this->model->where('instance_name', $instanceName)->first();
    }

    public function createForUser(int $empresaId, int $userId): WhatsappInstancia
    {
        return $this->model->create([
            'empresa_id' => $empresaId,
            'user_id' => $userId,
            'instance_name' => "sc_{$empresaId}_{$userId}",
            'status' => 'CRIADA',
            'webhook_token' => Str::random(48),
        ]);
    }

    public function getConectadas(): Collection
    {
        return $this->model->whereIn('status', ['CONECTADA', 'QRCODE'])->get();
    }
}
