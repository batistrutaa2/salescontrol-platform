<?php

namespace Tests\Unit\Support;

use App\Support\DocumentoFiscal;
use PHPUnit\Framework\TestCase;

class DocumentoFiscalTest extends TestCase
{
    public function test_valida_cpf_e_cnpj_com_mascara(): void
    {
        $this->assertTrue(DocumentoFiscal::cpfValido('111.444.777-35'));
        $this->assertTrue(DocumentoFiscal::cnpjValido('12.345.678/0001-95'));
    }

    public function test_rejeita_documentos_incompletos_repetidos_ou_com_digito_invalido(): void
    {
        foreach (['111.111.111-11', '111.444.777-34', '11.111.111/1111-11', '12.345.678/0001-90', '123'] as $documento) {
            $this->assertFalse(DocumentoFiscal::valido($documento));
        }
    }
}
