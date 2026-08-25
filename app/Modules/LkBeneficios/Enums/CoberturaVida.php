<?php

namespace App\Modules\LkBeneficios\Enums;

final class CoberturaVida
{
    public static function all(): array
    {
        return [
            'Morte',
            'Morte acidental',
            'Invalidez permanente total e parcial por acidente',
            'Invalidez permanente por acidente majorada',
            'Invalidez permanente total por acidente',
            'Invalidez funcional permanente total por doença',
            'Diagnóstico de câncer',
            'Doenças graves / Ampliada',
            'Diária de internação hospitalar',
            'Quebra de ossos',
            'Proteção cirúrgica',
            'Funeral individual / Familiar / Ampliado',
            'Jazigo',
        ];
    }
}
