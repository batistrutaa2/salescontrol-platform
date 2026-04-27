<?php

namespace Tests\Unit\LkBeneficios;

use App\Modules\LkBeneficios\Enums\StatusLead;
use PHPUnit\Framework\TestCase;

class StatusLeadTest extends TestCase
{
    public function test_tem_seis_colunas_na_ordem_correta(): void
    {
        $this->assertSame([
            StatusLead::NOVO_CLIENTE,
            StatusLead::PROSPECTADO,
            StatusLead::SEM_CONTATO,
            StatusLead::NEGOCIANDO,
            StatusLead::FOLLOW_UP,
            StatusLead::DOCUMENTACAO,
        ], StatusLead::all());
    }

    public function test_label_retorna_nome_amigavel_pt_br(): void
    {
        $this->assertSame('Novo Cliente', StatusLead::label(StatusLead::NOVO_CLIENTE));
        $this->assertSame('Documentação', StatusLead::label(StatusLead::DOCUMENTACAO));
    }

    public function test_ordem_retorna_indice_na_pipeline(): void
    {
        $this->assertSame(0, StatusLead::ordem(StatusLead::NOVO_CLIENTE));
        $this->assertSame(5, StatusLead::ordem(StatusLead::DOCUMENTACAO));
    }

    public function test_cor_gradiente_retorna_dois_valores_hex(): void
    {
        $cor = StatusLead::corGradiente(StatusLead::NOVO_CLIENTE);
        $this->assertCount(2, $cor);
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $cor[0]);
        $this->assertMatchesRegularExpression('/^#[0-9A-Fa-f]{6}$/', $cor[1]);
    }
}
