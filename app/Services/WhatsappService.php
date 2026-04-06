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
     * @param  string  $body   Conteúdo da mensagem
     */
    public function send(string $token, string $number, string $body): array
    {
        $formattedNumber = $this->formatNumber($number);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ])->timeout(15)->post(self::ENDPOINT, [
                'number'      => $formattedNumber,
                'body'        => $body,
                'saveOnTicket' => true,
                'linkPreview' => true,
            ]);

            if ($response->successful()) {
                return ['success' => true, 'message' => 'Mensagem enviada com sucesso.'];
            }

            Log::warning('WhatsappService: resposta não-2xx', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'number' => $formattedNumber,
            ]);

            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem: ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            Log::error('WhatsappService: exceção ao enviar', [
                'error'  => $e->getMessage(),
                'number' => $formattedNumber,
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

        if (!str_starts_with($digits, '55')) {
            $digits = '55' . $digits;
        }

        return $digits;
    }
}
