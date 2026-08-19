<?php

namespace App\Support;

final class DocumentoFiscal
{
    public static function somenteDigitos(?string $documento): string
    {
        return preg_replace('/\D+/', '', (string) $documento);
    }

    public static function valido(?string $documento): bool
    {
        $digitos = self::somenteDigitos($documento);

        return strlen($digitos) === 11
            ? self::cpfValido($digitos)
            : (strlen($digitos) === 14 && self::cnpjValido($digitos));
    }

    public static function cpfValido(?string $cpf): bool
    {
        $cpf = self::somenteDigitos($cpf);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($digito = 9; $digito < 11; $digito++) {
            $soma = 0;
            for ($i = 0; $i < $digito; $i++) {
                $soma += (int) $cpf[$i] * (($digito + 1) - $i);
            }
            $verificador = (10 * $soma) % 11;
            $verificador = $verificador === 10 ? 0 : $verificador;
            if ((int) $cpf[$digito] !== $verificador) {
                return false;
            }
        }

        return true;
    }

    public static function cnpjValido(?string $cnpj): bool
    {
        $cnpj = self::somenteDigitos($cnpj);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        foreach ([[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]] as $indice => $pesos) {
            $soma = 0;
            foreach ($pesos as $i => $peso) {
                $soma += (int) $cnpj[$i] * $peso;
            }
            $resto = $soma % 11;
            $verificador = $resto < 2 ? 0 : 11 - $resto;
            if ((int) $cnpj[12 + $indice] !== $verificador) {
                return false;
            }
        }

        return true;
    }
}
