<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação de PIS/PASEP/NIT (11 dígitos com dígito verificador).
 *
 * Aceita vazio (PIS é opcional na maioria dos cenários).
 */
final class Pis implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return; // opcional
        }

        if (!is_string($value)) {
            $fail('O PIS deve ser um texto.');
            return;
        }

        $pis = preg_replace('/[^0-9]/', '', $value) ?? '';

        if (strlen($pis) !== 11) {
            $fail('O PIS deve ter 11 dígitos.');
            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $pis)) {
            $fail('PIS inválido.');
            return;
        }

        // Pesos para cálculo do dígito verificador do PIS
        $pesos = [3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $pis[$i] * $pesos[$i];
        }

        $resto = $soma % 11;
        $digito = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $pis[10] !== $digito) {
            $fail('PIS inválido.');
        }
    }
}
