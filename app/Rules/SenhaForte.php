<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida senha forte conforme política definida em config/etc.php.
 *
 * Requisitos padrão:
 *   - Mínimo 12 caracteres
 *   - Pelo menos 1 maiúscula, 1 minúscula, 1 número, 1 símbolo
 *
 * Uso: $rules = ['password' => ['required', new SenhaForte()]];
 */
final class SenhaForte implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = config('atlas.security.password');

        if (!is_string($value)) {
            $fail('A senha deve ser um texto.');
            return;
        }

        $erros = [];

        if (mb_strlen($value) < $config['min_length']) {
            $erros[] = "Mínimo {$config['min_length']} caracteres";
        }

        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $erros[] = 'Pelo menos 1 letra maiúscula';
        }

        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $erros[] = 'Pelo menos 1 letra minúscula';
        }

        if ($config['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $erros[] = 'Pelo menos 1 número';
        }

        if ($config['require_symbols'] && !preg_match('/[^A-Za-z0-9]/', $value)) {
            $erros[] = 'Pelo menos 1 caractere especial';
        }

        // Senhas notoriamente fracas
        $fracas = ['12345678', 'password', 'senha123', 'admin123', 'qwerty123'];
        if (in_array(mb_strtolower($value), $fracas, true)) {
            $erros[] = 'Senha muito comum, escolha uma diferente';
        }

        if ($erros !== []) {
            $fail('A senha deve atender: ' . implode('; ', $erros) . '.');
        }
    }
}
