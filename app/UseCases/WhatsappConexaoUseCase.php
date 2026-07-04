<?php

namespace App\UseCases;

use App\Models\User;
use App\Models\WhatsappInstancia;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Support\Facades\Log;

class WhatsappConexaoUseCase
{
    public function __construct(
        private WhatsappInstanciaRepositoryInterface $instanciaRepository,
        private EvolutionApiService $evolution
    ) {}

    /**
     * Garante a instância do vendedor na Evolution e retorna o QR code
     * (ou o status atual, se já conectada).
     */
    public function conectar(User $user): array
    {
        $instancia = $this->instanciaRepository->findByUser($user->empresa_id, $user->id);

        if (! $instancia) {
            $instancia = $this->instanciaRepository->createForUser($user->empresa_id, $user->id);
            $this->criarInstanciaEvolution($instancia);
        }

        if ($instancia->status === 'CONECTADA') {
            return [
                'status' => 'CONECTADA',
                'numero_conectado' => $instancia->numero_conectado,
                'qrcode' => null,
            ];
        }

        try {
            $resposta = $this->evolution->connect($instancia->instance_name);
        } catch (\Throwable $e) {
            // Instância pode não existir mais na Evolution (volume recriado, etc.) — recria
            Log::warning('Whatsapp: connect falhou, recriando instância na Evolution', [
                'instance' => $instancia->instance_name,
                'erro' => $e->getMessage(),
            ]);
            $this->criarInstanciaEvolution($instancia);
            $resposta = $this->evolution->connect($instancia->instance_name);
        }

        $qrcode = $resposta['base64'] ?? data_get($resposta, 'qrcode.base64');

        if ($qrcode) {
            $instancia->update(['status' => 'QRCODE', 'last_status_at' => now()]);
        }

        return [
            'status' => $instancia->fresh()->status,
            'numero_conectado' => $instancia->numero_conectado,
            'qrcode' => $qrcode,
        ];
    }

    /**
     * QR code atual da instância (buscado sob demanda — não trafega no websocket).
     */
    public function qrAtual(User $user): ?string
    {
        $instancia = $this->instanciaRepository->findByUser($user->empresa_id, $user->id);

        if (! $instancia || $instancia->status === 'CONECTADA') {
            return null;
        }

        try {
            $resposta = $this->evolution->connect($instancia->instance_name);

            return $resposta['base64'] ?? data_get($resposta, 'qrcode.base64');
        } catch (\Throwable) {
            return null;
        }
    }

    public function status(User $user): array
    {
        $instancia = $this->instanciaRepository->findByUser($user->empresa_id, $user->id);

        if (! $instancia) {
            return ['status' => 'SEM_INSTANCIA', 'numero_conectado' => null];
        }

        // Consulta o estado real na Evolution e sincroniza divergências
        try {
            $estado = $this->evolution->connectionState($instancia->instance_name);
            $state = data_get($estado, 'instance.state');

            $statusReal = match ($state) {
                'open' => 'CONECTADA',
                'connecting' => 'QRCODE',
                'close' => 'DESCONECTADA',
                default => $instancia->status,
            };

            if ($statusReal !== $instancia->status) {
                $instancia->update(['status' => $statusReal, 'last_status_at' => now()]);
            }
        } catch (\Throwable) {
            // Evolution indisponível — mantém o último status conhecido
        }

        $instancia = $instancia->fresh();

        return [
            'status' => $instancia->status,
            'numero_conectado' => $instancia->numero_conectado,
            'connected_at' => $instancia->connected_at?->format('d/m/Y H:i'),
        ];
    }

    public function desconectar(User $user): void
    {
        $instancia = $this->instanciaRepository->findByUser($user->empresa_id, $user->id);

        if (! $instancia) {
            return;
        }

        try {
            $this->evolution->logout($instancia->instance_name);
        } catch (\Throwable $e) {
            Log::warning('Whatsapp: logout na Evolution falhou', [
                'instance' => $instancia->instance_name,
                'erro' => $e->getMessage(),
            ]);
        }

        $instancia->update(['status' => 'DESCONECTADA', 'last_status_at' => now()]);
    }

    private function criarInstanciaEvolution(WhatsappInstancia $instancia): void
    {
        $webhookUrl = sprintf(
            '%s/webhook/whatsapp/%s/%s',
            rtrim(config('services.evolution.webhook_base_url'), '/'),
            $instancia->instance_name,
            $instancia->webhook_token
        );

        try {
            $resposta = $this->evolution->createInstance($instancia->instance_name, $webhookUrl);
            $instancia->update([
                'instance_id' => data_get($resposta, 'instance.instanceId'),
            ]);
        } catch (\Throwable $e) {
            // Instância já existe na Evolution — garante o webhook atualizado
            if (str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), 'already')) {
                $this->evolution->setWebhook($instancia->instance_name, $webhookUrl);

                return;
            }
            throw $e;
        }
    }
}
