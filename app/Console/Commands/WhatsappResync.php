<?php

namespace App\Console\Commands;

use App\Jobs\Whatsapp\ProcessarMensagemRecebida;
use App\Models\WhatsappConversa;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappResync extends Command
{
    protected $signature = 'whatsapp:resync {--chats=20 : Conversas recentes por instância} {--mensagens=30 : Mensagens por conversa}';

    protected $description = 'Reconcilia mensagens perdidas em janelas de indisponibilidade do webhook (upsert idempotente)';

    public function handle(WhatsappInstanciaRepositoryInterface $instanciaRepository, EvolutionApiService $evolution): int
    {
        $limiteChats = (int) $this->option('chats');
        $limiteMensagens = (int) $this->option('mensagens');

        foreach ($instanciaRepository->getConectadas() as $instancia) {
            if ($instancia->status !== 'CONECTADA') {
                continue;
            }

            $conversas = WhatsappConversa::where('instancia_id', $instancia->id)
                ->orderByDesc('last_message_at')
                ->limit($limiteChats)
                ->get();

            foreach ($conversas as $conversa) {
                try {
                    $resposta = $evolution->findMessages($instancia->instance_name, $conversa->remote_jid, $limiteMensagens);
                } catch (\Throwable $e) {
                    Log::warning('whatsapp:resync — falha ao buscar mensagens', [
                        'instance' => $instancia->instance_name,
                        'remote_jid' => $conversa->remote_jid,
                        'erro' => $e->getMessage(),
                    ]);

                    continue;
                }

                $registros = data_get($resposta, 'messages.records', $resposta['messages'] ?? []);

                if (! is_array($registros)) {
                    continue;
                }

                foreach ($registros as $registro) {
                    $messageId = data_get($registro, 'key.id');

                    if (! $messageId) {
                        continue;
                    }

                    $existe = \App\Models\WhatsappMensagem::where('conversa_id', $conversa->id)
                        ->where('message_id', $messageId)
                        ->exists();

                    if ($existe) {
                        continue;
                    }

                    // Reinjeta no mesmo fluxo do webhook — idempotente por natureza
                    ProcessarMensagemRecebida::dispatch($instancia->id, [
                        'event' => 'messages.upsert',
                        'instance' => $instancia->instance_name,
                        'data' => $registro,
                    ]);
                }
            }
        }

        return self::SUCCESS;
    }
}
