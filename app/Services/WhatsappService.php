<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    const ENDPOINT = 'https://whats.lkbrokers.com:443/backend/api/messages/send';

    /**
     * Envia uma mensagem de texto via Ticketz WhatsApp.
     *
     * @param  string  $token  Bearer token da conexão Ticketz
     * @param  string  $number  Número no formato (85) 99999-8888 ou 558599998888
     * @param  string  $body  Conteúdo da mensagem
     * @param  bool  $saveOnTicket  Quando true, o Ticketz abre/registra um ticket no painel
     *                              para esta mensagem (usado no boas-vindas p/ controle de acesso).
     */
    public function send(string $token, string $number, string $body, bool $saveOnTicket = false): array
    {
        $formattedNumber = $this->formatNumber($number);

        try {
            $payload = json_encode([
                'number' => $formattedNumber,
                'body' => $body,
                'saveOnTicket' => $saveOnTicket,
                'linkPreview' => false,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->timeout(15)
                ->withBody($payload, 'application/json')
                ->post(self::ENDPOINT);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Mensagem enviada com sucesso.'];
            }

            Log::warning('WhatsappService: resposta não-2xx', [
                'status' => $response->status(),
                'body' => $response->body(),
                'number' => $formattedNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsappService: exceção ao enviar', [
                'error' => $e->getMessage(),
                'number' => $formattedNumber,
            ]);

            return ['success' => false, 'message' => 'Erro de conexão com o WhatsApp.'];
        }
    }

    /**
     * Envia mensagem com mídia (arquivo) via Ticketz WhatsApp (multipart/form-data).
     *
     * @param  string  $token  Bearer token da conexão Ticketz
     * @param  string  $number  Número no formato (85) 99999-8888 ou 558599998888
     * @param  string  $absoluteFilePath  Caminho absoluto do arquivo no FS local
     * @param  string|null  $caption  Legenda opcional enviada como campo "body"
     *
     * Observação: usa Storage::path() na chamada — funciona com disk "local".
     * Se o disk virar remoto (ex.: s3), o chamador precisa baixar para temp antes.
     */
    public function sendMedia(string $token, string $number, string $absoluteFilePath, ?string $caption = null): array
    {
        $formattedNumber = $this->formatNumber($number);

        if (! is_file($absoluteFilePath)) {
            Log::warning('WhatsappService::sendMedia: arquivo não encontrado', [
                'file' => $absoluteFilePath,
                'number' => $formattedNumber,
            ]);

            return ['success' => false, 'message' => 'Arquivo não encontrado para envio.'];
        }

        try {
            $fields = [
                'number' => $formattedNumber,
                'saveOnTicket' => 'true',
            ];
            if ($caption !== null && $caption !== '') {
                $fields['body'] = $caption;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$token,
            ])->timeout(60)
                ->attach('medias', file_get_contents($absoluteFilePath), basename($absoluteFilePath))
                ->post(self::ENDPOINT, $fields);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Mídia enviada com sucesso.'];
            }

            Log::warning('WhatsappService::sendMedia: resposta não-2xx', [
                'status' => $response->status(),
                'body' => $response->body(),
                'number' => $formattedNumber,
                'file' => basename($absoluteFilePath),
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar mídia: '.$response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsappService::sendMedia: exceção ao enviar', [
                'error' => $e->getMessage(),
                'number' => $formattedNumber,
                'file' => basename($absoluteFilePath),
            ]);

            return ['success' => false, 'message' => 'Erro de conexão com o WhatsApp.'];
        }
    }

    /**
     * Formata o número para o padrão internacional BR: 558599998888
     */
    private function formatNumber(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number);

        if (! str_starts_with($digits, '55')) {
            $digits = '55'.$digits;
        }

        return $digits;
    }
}
