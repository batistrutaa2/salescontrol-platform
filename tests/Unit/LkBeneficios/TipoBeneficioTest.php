<?php

namespace Tests\Unit\LkBeneficios;

use App\Modules\LkBeneficios\Enums\TipoBeneficio;
use PHPUnit\Framework\TestCase;

class TipoBeneficioTest extends TestCase
{
    public function test_all_retorna_quatro_tipos_canonicos(): void
    {
        $this->assertSame([
            TipoBeneficio::VIDA,
            TipoBeneficio::ODONTO,
            TipoBeneficio::PREVIDENCIA,
            TipoBeneficio::PATRIMONIAL,
        ], TipoBeneficio::all());
    }

    public function test_label_traduz_cada_tipo(): void
    {
        $this->assertSame('Seguro de Vida', TipoBeneficio::label(TipoBeneficio::VIDA));
        $this->assertSame('Odontológico', TipoBeneficio::label(TipoBeneficio::ODONTO));
        $this->assertSame('Previdência Privada', TipoBeneficio::label(TipoBeneficio::PREVIDENCIA));
        $this->assertSame('Seguros Patrimoniais', TipoBeneficio::label(TipoBeneficio::PATRIMONIAL));
    }

    public function test_label_devolve_o_proprio_valor_quando_desconhecido(): void
    {
        $this->assertSame('XPTO', TipoBeneficio::label('XPTO'));
    }

    public function test_icon_retorna_classe_remixicon_para_cada_tipo(): void
    {
        foreach (TipoBeneficio::all() as $tipo) {
            $icon = TipoBeneficio::icon($tipo);
            $this->assertNotEmpty($icon);
            $this->assertStringStartsWith('ri-', $icon);
        }
    }
}
