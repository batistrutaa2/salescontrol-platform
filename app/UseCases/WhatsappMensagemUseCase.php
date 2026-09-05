<?php

namespace App\UseCases;

use App\Jobs\Whatsapp\EnviarMensagemWhatsapp;
use App\Models\WhatsappConversa;
use App\Models\WhatsappMensagem;
use App\Repositories\Contracts\WhatsappConversaRepositoryInterface;
use App\Repositories\Contracts\WhatsappMensagemRepositoryInterface;
use App\Services\Evolution\EvolutionApiService;
use App\Services\Whatsapp\MessagePayloadParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsappMensagemUseCase
{
    public function __construct(
        private WhatsappMensagemRepositoryInterface $mensagemRepository,
        private WhatsappConversaRepositoryInterface $conversaRepository,
        private EvolutionApiService $evolution
    ) {}

    public function getThread(int $conversaId, ?int $beforeId, int $limit = 30)
    {
        return $this->mensagemRepository->getThread($conversaId, $beforeId, $limit);
    }

    public function enviarTexto(WhatsappConversa $conversa, string $texto, ?string $quotedMessageId = null): WhatsappMensagem
    {
        $mensagem = $this->criarMensagemLocal($conversa, [
            'tipo' => 'text',
            'body' => $texto,
            'quoted_message_id' => $quotedMessageId,
        ]);

        $this->atualizarPreview($conversa, MessagePayloadParser::preview(['tipo' => 'text', 'body' => $texto]));

        EnviarMensagemWhatsapp::dispatch($mensagem->id);

        return $mensagem;
    }

    public function enviarMidia(WhatsappConversa $conversa, UploadedFile $arquivo, ?string $caption, ?string $tipoForcado = null): WhatsappMensagem
    {
        $mime = $arquivo->getMimeType() ?: 'application/octet-stream';
        $tipo = $tipoForcado ?: $this->tipoPorMime($mime);

        $extensao = $arquivo->getClientOriginalExtension() ?: $arquivo->guessExtension() ?: 'bin';
        $path = sprintf('whatsapp/%d/%d/%s.%s', $conversa->empresa_id, $conversa->id, (string) Str::uuid(), $extensao);

        Storage::disk('public')->put($path, $arquivo->get());

        $mensagem = $this->criarMensagemLocal($conversa, [
            'tipo' => $tipo,
            'body' => $caption,
            'media_path' => $path,
            'media_mime' => $mime,
            'media_size' => $arquivo->getSize() ?: null,
        ]);

        $this->atualizarPreview($conversa, MessagePayloadParser::preview(['tipo' => $tipo, 'body' => $caption]));

        EnviarMensagemWhatsapp::dispatch($mensagem->id);

        return $mensagem;
    }

    public function reenviar(WhatsappConversa $conversa, int $mensagemId): ?WhatsappMensagem
    {
        $mensagem = WhatsappMensagem::where('id', $mensagemId)
            ->where('conversa_id', $conversa->id)
            ->where('status_envio', 'ERRO')
            ->first();

        if (! $mensagem) {
            return null;
        }

        $mensagem->update(['status_envio' => 'PENDENTE', 'erro_envio' => null]);

        EnviarMensagemWhatsapp::dispatch($mensagem->id);

        return $mensagem->fresh();
    }

    public function marcarComoLida(WhatsappConversa $conversa): void
    {
        $this->conversaRepository->zerarNaoLidas($conversa->id, (int) $conversa->empresa_id);

        // Best-effort: sinaliza leitura no WhatsApp (✓✓ azul para o cliente)
        try {
            $naoLidas = WhatsappMensagem::where('conversa_id', $conversa->id)
                ->where('direcao', 'IN')
                ->orderByDesc('id')
                ->limit(10)
                ->pluck('message_id')
                ->map(fn ($id) => [
                    'remoteJid' => $conversa->remote_jid,
                    'fromMe' => false,
                    'id' => $id,
                ])
                ->all();

            if ($naoLidas) {
                $this->evolution->markAsRead($conversa->instancia->instance_name, $naoLidas);
            }
        } catch (\Throwable) {
            // Falha ao marcar como lida no WhatsApp não é crítica
        }
    }

    private function criarMensagemLocal(WhatsappConversa $conversa, array $atributos): WhatsappMensagem
    {
        return WhatsappMensagem::create(array_merge([
            'empresa_id' => $conversa->empresa_id,
            'conversa_id' => $conversa->id,
            'message_id' => 'local-'.(string) Str::uuid(),
            'direcao' => 'OUT',
            'ack' => 0,
            'status_envio' => 'PENDENTE',
            'message_timestamp' => now(),
        ], $atributos));
    }

    private function atualizarPreview(WhatsappConversa $conversa, string $preview): void
    {
        $conversa->update([
            'last_message_at' => now(),
            'last_message_preview' => $preview,
        ]);
    }

    private function tipoPorMime(string $mime): string
    {
        return match (true) {
            $mime === 'image/webp' => 'sticker',
            str_starts_with($mime, 'image/') => 'image',
            str_starts_with($mime, 'video/') => 'video',
            str_starts_with($mime, 'audio/') => 'audio',
            default => 'document',
        };
    }
}
