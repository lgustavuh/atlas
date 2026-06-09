<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação real de CPF (com cálculo de dígito verificador).
 *
 * Aceita formatos:
 *   - 000.000.000-00
 *   - 00000000000
 *
 * Rejeita:
 *   - CPFs com todos dígitos iguais (000.000.000-00, 111.111.111-11, etc)
 *   - CPFs com dígito verificador inválido
 */
final class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('O CPF deve ser um texto.');
            return;
        }

        $cpf = preg_replace('/[^0-9]/', '', $value) ?? '';

        // Tamanho exato
        if (strlen($cpf) !== 11) {
            $fail('O CPF deve ter 11 dígitos.');
            return;
        }

        // CPFs com todos dígitos iguais são inválidos (000.000.000-00 etc)
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('CPF inválido.');
            return;
        }

        // Calcula o primeiro dígito verificador
        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }
        $digito1 = (($soma * 10) % 11) % 10;

        if ((int) $cpf[9] !== $digito1) {
            $fail('CPF inválido.');
            return;
        }

        // Calcula o segundo dígito verificador
        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }
        $digito2 = (($soma * 10) % 11) % 10;

        if ((int) $cpf[10] !== $digito2) {
            $fail('CPF inválido.');
            return;
        }
    }

    /**
     * Formata um CPF (helper estático para usar em outros lugares).
     */
    public static function formatar(string $cpf): string
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf) ?? '';
        if (strlen($cpf) !== 11) {
            return $cpf;
        }
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9);
    }

    /**
     * Retorna apenas dígitos (para armazenamento).
     */
    public static function limpar(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf) ?? '';
    }
}
