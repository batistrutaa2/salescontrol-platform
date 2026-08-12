<?php

namespace Tests\Unit\Documentos;

use App\Services\Documentos\NomeDocumentoService;
use PHPUnit\Framework\TestCase;

class NomeDocumentoServiceTest extends TestCase
{
    private NomeDocumentoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NomeDocumentoService;
    }

    public function test_sanitiza_segmentos_para_windows_e_linux(): void
    {
        $this->assertSame('Caragi Participacoes', $this->service->segmento('  Caragi: Participacoes. '));
        $this->assertSame('Sem nome', $this->service->segmento('CON'));
        $this->assertSame('Amil Nacional', $this->service->segmento('Amil / Nacional'));
    }

    public function test_preserva_extensao_e_cria_sufixo_sem_sobrescrever(): void
    {
        $this->assertSame('contrato final.pdf', $this->service->arquivo('contrato:final.PDF'));
        $this->assertSame('contrato final - 2.pdf', $this->service->comSufixo('contrato final.pdf', 2));
    }

}
