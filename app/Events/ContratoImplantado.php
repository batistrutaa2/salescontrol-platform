<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContratoImplantado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $contratoId;
    public string $nomeContrato;
    public string $numeroProposta;
    public string $operadora;
    public int $empresaId;
    public string $alteradoPorNome;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $contratoId,
        string $nomeContrato,
        string $numeroProposta,
        string $operadora,
        int $empresaId,
        string $alteradoPorNome
    ) {
        $this->contratoId = $contratoId;
        $this->nomeContrato = $nomeContrato;
        $this->numeroProposta = $numeroProposta;
        $this->operadora = $operadora;
        $this->empresaId = $empresaId;
        $this->alteradoPorNome = $alteradoPorNome;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('contratos.administrativo.' . $this->empresaId),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'contrato.implantado';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'contrato_id' => $this->contratoId,
            'nome_contrato' => $this->nomeContrato,
            'numero_proposta' => $this->numeroProposta,
            'operadora' => $this->operadora,
            'alterado_por' => $this->alteradoPorNome,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
