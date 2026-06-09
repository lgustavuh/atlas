<?php

declare(strict_types=1);

use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;

function validarCpf(string $cpf): array
{
    $validator = Validator::make(['cpf' => $cpf], ['cpf' => [new Cpf()]]);
    return $validator->errors()->get('cpf');
}

it('aceita CPFs válidos', function () {
    // CPFs sabidamente válidos (gerados com dígito verificador real)
    expect(validarCpf('111.444.777-35'))->toBeEmpty();
    expect(validarCpf('11144477735'))->toBeEmpty();
});

it('rejeita CPFs com dígito verificador errado', function () {
    expect(validarCpf('111.444.777-00'))->not->toBeEmpty();
    expect(validarCpf('123.456.789-00'))->not->toBeEmpty();
});

it('rejeita CPFs com todos dígitos iguais', function () {
    foreach (['00000000000', '11111111111', '99999999999'] as $cpf) {
        expect(validarCpf($cpf))->not->toBeEmpty();
    }
});

it('rejeita CPFs com tamanho incorreto', function () {
    expect(validarCpf('123'))->not->toBeEmpty();
    expect(validarCpf('123456789012'))->not->toBeEmpty();
});

it('aceita CPF com ou sem máscara', function () {
    expect(validarCpf('111.444.777-35'))->toBeEmpty();
    expect(validarCpf('11144477735'))->toBeEmpty();
});

it('formata CPF corretamente', function () {
    expect(Cpf::formatar('11144477735'))->toBe('111.444.777-35');
    expect(Cpf::formatar('111.444.777-35'))->toBe('111.444.777-35');
});

it('limpa CPF removendo máscara', function () {
    expect(Cpf::limpar('111.444.777-35'))->toBe('11144477735');
    expect(Cpf::limpar('11144477735'))->toBe('11144477735');
});
