<?php

namespace App\Services\Evolution;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionApiService
{
    private string $baseUrl;

    private string $apiKey;

    public const WEBHOOK_EVENTS = [
        'QRCODE_UPDATED',
        'CONNECTION_UPDATE',
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'SEND_MESSAGE',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.evolution.url'), '/');
        $this->apiKey = (string) config('services.evolution.api_key');
    }

    private function http(): PendingRequest
    {
        return Http::withHeaders(['apikey' => $this->apiKey])
            ->timeout(30)
            ->acceptJson();
    }

    private function request(string $method, string $path, array $data = []): array
    {
        $response = $this->http()->{$method}("{$this->baseUrl}{$path}", $data);

        if ($response->failed()) {
            Log::error('EvolutionApi: falha na requisição', [
                'method' => $method,
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            $response->throw();
        }

        return $response->json() ?? [];
    }

    public function createInstance(string $instanceName, string $webhookUrl): array
    {
        return $this->request('post', '/instance/create', [
            'instanceName' => $instanceName,
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS',
            'webhook' => [
                'url' => $webhookUrl,
                'byEvents' => false,
                'base64' => true,
                'events' => self::WEBHOOK_EVENTS,
            ],
        ]);
    }

    public function setWebhook(string $instanceName, string $webhookUrl): array
    {
        return $this->request('post', "/webhook/set/{$instanceName}", [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'byEvents' => false,
                'base64' => true,
                'events' => self::WEBHOOK_EVENTS,
            ],
        ]);
    }

    public function connect(string $instanceName): array
    {
        return $this->request('get', "/instance/connect/{$instanceName}");
    }

    public function connectionState(string $instanceName): array
    {
        return $this->request('get', "/instance/connectionState/{$instanceName}");
    }

    public function logout(string $instanceName): array
    {
        return $this->request('delete', "/instance/logout/{$instanceName}");
    }

    public function deleteInstance(string $instanceName): array
    {
        return $this->request('delete', "/instance/delete/{$instanceName}");
    }

    public function sendText(string $instanceName, string $number, string $text, ?string $quotedId = null): array
    {
        $payload = [
            'number' => $number,
            'text' => $text,
        ];

        if ($quotedId) {
            $payload['quoted'] = ['key' => ['id' => $quotedId]];
        }

        return $this->request('post', "/message/sendText/{$instanceName}", $payload);
    }

    /**
     * @param  string  $mediaType  image|video|document
     * @param  string  $media  base64 puro (sem prefixo data:) ou URL
     */
    public function sendMedia(string $instanceName, string $number, string $mediaType, string $media, ?string $caption = null, ?string $fileName = null, ?string $mimetype = null): array
    {
        $payload = [
            'number' => $number,
            'mediatype' => $mediaType,
            'media' => $media,
        ];

        if ($caption !== null) {
            $payload['caption'] = $caption;
        }
        if ($fileName !== null) {
            $payload['fileName'] = $fileName;
        }
        if ($mimetype !== null) {
            $payload['mimetype'] = $mimetype;
        }

        return $this->request('post', "/message/sendMedia/{$instanceName}", $payload);
    }

    public function sendAudio(string $instanceName, string $number, string $base64): array
    {
        return $this->request('post', "/message/sendWhatsAppAudio/{$instanceName}", [
            'number' => $number,
            'audio' => $base64,
        ]);
    }

    public function sendSticker(string $instanceName, string $number, string $base64): array
    {
        return $this->request('post', "/message/sendSticker/{$instanceName}", [
            'number' => $number,
            'sticker' => $base64,
        ]);
    }

    public function fetchProfilePicture(string $instanceName, string $number): ?string
    {
        $resposta = $this->request('post', "/chat/fetchProfilePictureUrl/{$instanceName}", [
            'number' => $number,
        ]);

        return $resposta['profilePictureUrl'] ?? null;
    }

    public function getBase64FromMediaMessage(string $instanceName, string $messageId): array
    {
        return $this->request('post', "/chat/getBase64FromMediaMessage/{$instanceName}", [
            'message' => ['key' => ['id' => $messageId]],
            'convertToMp4' => false,
        ]);
    }

    public function findMessages(string $instanceName, string $remoteJid, int $limit = 50): array
    {
        return $this->request('post', "/chat/findMessages/{$instanceName}", [
            'where' => ['key' => ['remoteJid' => $remoteJid]],
            'limit' => $limit,
        ]);
    }

    public function markAsRead(string $instanceName, array $readMessages): array
    {
        return $this->request('post', "/chat/markMessageAsRead/{$instanceName}", [
            'readMessages' => $readMessages,
        ]);
    }
}
