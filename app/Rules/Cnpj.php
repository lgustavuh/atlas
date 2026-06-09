<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação real de CNPJ (com cálculo de dígitos verificadores).
 *
 * Aceita formatos:
 *   - 00.000.000/0000-00
 *   - 00000000000000
 */
final class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O CNPJ deve ser um texto.');
            return;
        }

        $cnpj = preg_replace('/[^0-9]/', '', $value) ?? '';

        if (strlen($cnpj) !== 14) {
            $fail('O CNPJ deve ter 14 dígitos.');
            return;
        }

        // CNPJs com todos dígitos iguais são inválidos
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            $fail('CNPJ inválido.');
            return;
        }

        // Primeiro dígito verificador
        $pesos1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesos1[$i];
        }
        $resto = $soma % 11;
        $d1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[12] !== $d1) {
            $fail('CNPJ inválido.');
            return;
        }

        // Segundo dígito verificador
        $pesos2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesos2[$i];
        }
        $resto = $soma % 11;
        $d2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[13] !== $d2) {
            $fail('CNPJ inválido.');
        }
    }

    public static function formatar(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj) ?? '';
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }
        return substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.'
             . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12);
    }

    public static function limpar(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj) ?? '';
    }
}
