<?php

namespace Tests\Unit\Services;

use App\Services\WhatsappService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappServiceSendMediaTest extends TestCase
{
    private const ENDPOINT = 'https://whats.lkbrokers.com:443/backend/api/messages/send';

    private WhatsappService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WhatsappService();
    }

    private function tempPdf(string $content = 'fake-pdf-bytes'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'whats_media_test_');
        file_put_contents($path, $content);

        return $path;
    }

    public function test_envia_midia_com_caption_com_sucesso(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response(['ok' => true], 200),
        ]);

        $file = $this->tempPdf();

        try {
            $resp = $this->service->sendMedia('tok123', '(85) 99999-8888', $file, 'Boleto em anexo');

            $this->assertTrue($resp['success']);
            $this->assertStringContainsString('sucesso', $resp['message']);

            Http::assertSent(function (Request $request) {
                return str_contains($request->url(), 'lkbrokers.com')
                    && str_contains($request->url(), '/backend/api/messages/send')
                    && $request->method() === 'POST'
                    && $request->hasHeader('Authorization', 'Bearer tok123');
            });
        } finally {
            @unlink($file);
        }
    }

    public function test_envia_midia_inclui_campos_e_arquivo_no_multipart(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response(['ok' => true], 200),
        ]);

        $file = $this->tempPdf('payload-pdf');

        try {
            $this->service->sendMedia('tok', '(85) 99999-8888', $file, 'Boleto em anexo');

            Http::assertSent(function (Request $request) use ($file) {
                $body = $request->body();
                $contentType = $request->header('Content-Type')[0] ?? '';

                return str_contains($contentType, 'multipart/form-data')
                    && str_contains($body, '5585999998888')
                    && str_contains($body, 'saveOnTicket')
                    && str_contains($body, 'Boleto em anexo')
                    && str_contains($body, basename($file))
                    && str_contains($body, 'payload-pdf');
            });
        } finally {
            @unlink($file);
        }
    }

    public function test_retorna_falha_quando_arquivo_nao_existe(): void
    {
        Http::fake();

        $resp = $this->service->sendMedia('tok', '5585999998888', '/tmp/nao-existe-'.uniqid().'.pdf', 'caption');

        $this->assertFalse($resp['success']);
        $this->assertStringContainsString('não encontrado', $resp['message']);
        Http::assertNothingSent();
    }

    public function test_resposta_nao_2xx_retorna_falha_com_status(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response('server error', 500),
        ]);

        $file = $this->tempPdf();

        try {
            $resp = $this->service->sendMedia('tok', '5585999998888', $file, 'x');

            $this->assertFalse($resp['success']);
            $this->assertStringContainsString('500', $resp['message']);
        } finally {
            @unlink($file);
        }
    }

    public function test_excecao_de_conexao_retorna_falha(): void
    {
        Http::fake(function () {
            throw new ConnectionException('timeout');
        });

        $file = $this->tempPdf();

        try {
            $resp = $this->service->sendMedia('tok', '5585999998888', $file, 'x');

            $this->assertFalse($resp['success']);
            $this->assertStringContainsString('conexão', $resp['message']);
        } finally {
            @unlink($file);
        }
    }

    public function test_normaliza_numero_sem_prefixo_55(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response(['ok' => true], 200),
        ]);

        $file = $this->tempPdf();

        try {
            $this->service->sendMedia('tok', '(85) 91234-5678', $file, 'oi');

            Http::assertSent(function (Request $request) {
                return str_contains($request->body(), '5585912345678');
            });
        } finally {
            @unlink($file);
        }
    }

    public function test_omite_body_quando_caption_nula(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response(['ok' => true], 200),
        ]);

        $file = $this->tempPdf();

        try {
            $this->service->sendMedia('tok', '5585999998888', $file, null);

            Http::assertSent(function (Request $request) {
                return ! str_contains($request->body(), 'name="body"');
            });
        } finally {
            @unlink($file);
        }
    }

    public function test_omite_body_quando_caption_vazia(): void
    {
        Http::fake([
            '*lkbrokers.com*' => Http::response(['ok' => true], 200),
        ]);

        $file = $this->tempPdf();

        try {
            $this->service->sendMedia('tok', '5585999998888', $file, '');

            Http::assertSent(function (Request $request) {
                return ! str_contains($request->body(), 'name="body"');
            });
        } finally {
            @unlink($file);
        }
    }
}
