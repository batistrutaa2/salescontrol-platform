<?php

namespace App\Console\Commands;

use App\Jobs\Whatsapp\ProcessarMensagemRecebida;
use App\Models\Empresa;
use App\Models\WhatsappConversa;
use App\Repositories\Contracts\WhatsappInstanciaRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WhatsappResync extends Command
{
    protected $signature = 'whatsapp:resync {--chats=20 : Conversas recentes por instância} {--mensagens=30 : Mensagens por conversa} {--empresa= : Limita a uma empresa específica}';

    protected $description = 'Reconcilia mensagens perdidas em janelas de indisponibilidade do webhook (upsert idempotente)';

    public function handle(WhatsappInstanciaRepositoryInterface $instanciaRepository, EvolutionApiService $evolution): int
    {
        $limiteChats = (int) $this->option('chats');
        $limiteMensagens = (int) $this->option('mensagens');
        if ($limiteChats < 1 || $limiteChats > 200 || $limiteMensagens < 1 || $limiteMensagens > 500) {
            $this->error('Limites inválidos. Use chats entre 1 e 200 e mensagens entre 1 e 500.');

            return self::FAILURE;
        }

        $empresas = $this->empresasSelecionadas();
        if ($empresas === null) {
            return self::FAILURE;
        }

        $context = app(TenantContext::class);
        $context->clear();

        try {
            foreach ($empresas as $empresaId) {
                $context->run($empresaId, function () use ($instanciaRepository, $evolution, $limiteChats, $limiteMensagens): void {
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
                });
            }
        } finally {
            $context->clear();
        }

        return self::SUCCESS;
    }

    private function empresasSelecionadas(): ?array
    {
        $empresaId = filter_var($this->option('empresa'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('empresa') !== null && $empresaId === false) {
            $this->error('Empresa inválida.');

            return null;
        }

        $ids = Empresa::query()
            ->when($empresaId, fn ($query) => $query->whereKey($empresaId))
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($empresaId && $ids === []) {
            $this->error('Empresa inválida.');

            return null;
        }

        return $ids;
    }
}
