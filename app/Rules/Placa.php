<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação de Placa de veículo brasileira.
 *
 * Aceita os dois formatos:
 *   - Antigo:   AAA-1234 ou AAA1234
 *   - Mercosul: AAA1A23 (com letra no quarto dígito)
 */
final class Placa implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('A placa deve ser um texto.');
            return;
        }

        $placa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');

        if (strlen($placa) !== 7) {
            $fail('A placa deve ter 7 caracteres (ex: ABC-1234 ou ABC1D23).');
            return;
        }

        // Antiga: 3 letras + 4 dígitos
        $antiga = preg_match('/^[A-Z]{3}[0-9]{4}$/', $placa) === 1;

        // Mercosul: 3 letras + 1 dígito + 1 letra + 2 dígitos
        $mercosul = preg_match('/^[A-Z]{3}[0-9][A-Z][0-9]{2}$/', $placa) === 1;

        if (!$antiga && !$mercosul) {
            $fail('Placa inválida. Use formato antigo (ABC-1234) ou Mercosul (ABC1D23).');
        }
    }

    /**
     * Formata para exibição (insere o hífen no formato antigo, mantém Mercosul sem).
     */
    public static function formatar(string $placa): string
    {
        $limpa = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa) ?? '');
        if (strlen($limpa) !== 7) {
            return $placa;
        }
        // Antiga: insere hífen depois das 3 letras
        if (preg_match('/^[A-Z]{3}[0-9]{4}$/', $limpa)) {
            return substr($limpa, 0, 3) . '-' . substr($limpa, 3);
        }
        // Mercosul: mantém sem hífen
        return $limpa;
    }

    public static function limpar(string $placa): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa) ?? '');
    }
}
