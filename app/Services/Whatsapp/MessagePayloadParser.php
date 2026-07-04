<?php

namespace App\Services\Whatsapp;

class MessagePayloadParser
{
    /**
     * Interpreta o objeto data.message de um webhook da Evolution API e
     * retorna: tipo, body (texto/caption), base64 (mídia, se presente),
     * mime e nome de arquivo.
     */
    public static function parse(array $data): array
    {
        $message = $data['message'] ?? [];

        $result = [
            'tipo' => 'unknown',
            'body' => null,
            'base64' => $message['base64'] ?? null,
            'mime' => null,
            'file_name' => null,
        ];

        if (isset($message['conversation'])) {
            $result['tipo'] = 'text';
            $result['body'] = $message['conversation'];
        } elseif (isset($message['extendedTextMessage'])) {
            $result['tipo'] = 'text';
            $result['body'] = $message['extendedTextMessage']['text'] ?? '';
        } elseif (isset($message['imageMessage'])) {
            $result['tipo'] = 'image';
            $result['body'] = $message['imageMessage']['caption'] ?? null;
            $result['mime'] = $message['imageMessage']['mimetype'] ?? 'image/jpeg';
        } elseif (isset($message['videoMessage'])) {
            $result['tipo'] = 'video';
            $result['body'] = $message['videoMessage']['caption'] ?? null;
            $result['mime'] = $message['videoMessage']['mimetype'] ?? 'video/mp4';
        } elseif (isset($message['audioMessage'])) {
            $audio = $message['audioMessage'];
            $result['tipo'] = ! empty($audio['ptt']) ? 'ptt' : 'audio';
            $result['mime'] = $audio['mimetype'] ?? 'audio/ogg';
        } elseif (isset($message['stickerMessage'])) {
            $result['tipo'] = 'sticker';
            $result['mime'] = $message['stickerMessage']['mimetype'] ?? 'image/webp';
        } elseif (isset($message['documentMessage']) || isset($message['documentWithCaptionMessage'])) {
            $doc = $message['documentMessage']
              ?? ($message['documentWithCaptionMessage']['message']['documentMessage'] ?? []);
            $result['tipo'] = 'document';
            $result['body'] = $doc['caption'] ?? null;
            $result['mime'] = $doc['mimetype'] ?? 'application/octet-stream';
            $result['file_name'] = $doc['fileName'] ?? null;
        } elseif (isset($message['locationMessage'])) {
            $loc = $message['locationMessage'];
            $result['tipo'] = 'location';
            $result['body'] = trim(($loc['name'] ?? '').' '.($loc['degreesLatitude'] ?? '').','.($loc['degreesLongitude'] ?? ''));
        } elseif (isset($message['contactMessage']) || isset($message['contactsArrayMessage'])) {
            $result['tipo'] = 'contact';
            $result['body'] = $message['contactMessage']['displayName'] ?? 'Contato compartilhado';
        }

        return $result;
    }

    /**
     * Texto curto para preview na lista de conversas / card do kanban.
     */
    public static function preview(array $parsed): string
    {
        $labels = [
            'image' => '📷 Imagem',
            'video' => '🎬 Vídeo',
            'audio' => '🎵 Áudio',
            'ptt' => '🎤 Mensagem de voz',
            'sticker' => '🩵 Figurinha',
            'document' => '📄 Documento',
            'location' => '📍 Localização',
            'contact' => '👤 Contato',
            'unknown' => 'Mensagem',
        ];

        if ($parsed['tipo'] === 'text') {
            return mb_substr($parsed['body'] ?? '', 0, 250);
        }

        $label = $labels[$parsed['tipo']] ?? 'Mensagem';

        if (! empty($parsed['body'])) {
            $label .= ': '.mb_substr($parsed['body'], 0, 200);
        }

        return $label;
    }

    /**
     * Mapeia o status de messages.update para o nível de ack numérico.
     */
    public static function ackFromStatus(?string $status): ?int
    {
        return match (strtoupper((string) $status)) {
            'PENDING' => 0,
            'SERVER_ACK' => 1,
            'DELIVERY_ACK' => 2,
            'READ' => 3,
            'PLAYED' => 4,
            default => null,
        };
    }
}
