<?php

declare(strict_types=1);

use App\Rules\Cnpj;
use Illuminate\Support\Facades\Validator;

function validarCnpj(string $cnpj): array
{
    return Validator::make(['cnpj' => $cnpj], ['cnpj' => [new Cnpj()]])
        ->errors()->get('cnpj');
}

it('aceita CNPJ válido', function () {
    // CNPJs válidos conhecidos
    expect(validarCnpj('11.222.333/0001-81'))->toBeEmpty();
    expect(validarCnpj('11222333000181'))->toBeEmpty();
});

it('rejeita CNPJ com dígito verificador errado', function () {
    expect(validarCnpj('11.222.333/0001-00'))->not->toBeEmpty();
    expect(validarCnpj('11222333000100'))->not->toBeEmpty();
});

it('rejeita CNPJ com todos dígitos iguais', function () {
    expect(validarCnpj('11111111111111'))->not->toBeEmpty();
    expect(validarCnpj('00000000000000'))->not->toBeEmpty();
});

it('rejeita CNPJ com tamanho incorreto', function () {
    expect(validarCnpj('123'))->not->toBeEmpty();
    expect(validarCnpj('112223330001811'))->not->toBeEmpty();
});

it('formata CNPJ corretamente', function () {
    expect(Cnpj::formatar('11222333000181'))->toBe('11.222.333/0001-81');
});

it('limpa CNPJ removendo máscara', function () {
    expect(Cnpj::limpar('11.222.333/0001-81'))->toBe('11222333000181');
});
