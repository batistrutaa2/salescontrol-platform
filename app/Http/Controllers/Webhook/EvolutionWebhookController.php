<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\Whatsapp\AtualizarAckMensagem;
use App\Jobs\Whatsapp\AtualizarStatusInstancia;
use App\Jobs\Whatsapp\ProcessarMensagemEnviada;
use App\Jobs\Whatsapp\ProcessarMensagemRecebida;
use App\Jobs\Whatsapp\ProcessarQrCodeAtualizado;
use App\Models\WhatsappInstancia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvolutionWebhookController extends Controller
{
    /**
     * Recebe todos os webhooks da Evolution API.
     * Valida o token da instância e enfileira o processamento —
     * responde 200 imediatamente para nunca segurar a Evolution.
     */
    public function handle(Request $request, string $instanceName, string $token): JsonResponse
    {
        $instancia = WhatsappInstancia::where('instance_name', $instanceName)->first();

        if (! $instancia || ! hash_equals($instancia->webhook_token, $token)) {
            return response()->json(['ok' => false], 401);
        }

        $payload = $request->all();
        $evento = strtolower((string) ($payload['event'] ?? ''));

        match ($evento) {
            'messages.upsert' => ProcessarMensagemRecebida::dispatch($instancia->id, $payload),
            'send.message' => ProcessarMensagemEnviada::dispatch($instancia->id, $payload),
            'messages.update' => AtualizarAckMensagem::dispatch($instancia->id, $payload),
            'connection.update' => AtualizarStatusInstancia::dispatch($instancia->id, $payload),
            'qrcode.updated' => ProcessarQrCodeAtualizado::dispatch($instancia->id, $payload),
            default => null,
        };

        return response()->json(['ok' => true]);
    }
}
