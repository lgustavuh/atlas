<?php

declare(strict_types=1);

use App\Rules\Placa;
use Illuminate\Support\Facades\Validator;

function validarPlaca(string $placa): array
{
    return Validator::make(['placa' => $placa], ['placa' => [new Placa()]])
        ->errors()->get('placa');
}

it('aceita placa antiga válida (ABC-1234)', function () {
    expect(validarPlaca('ABC-1234'))->toBeEmpty();
    expect(validarPlaca('ABC1234'))->toBeEmpty();
    expect(validarPlaca('abc-1234'))->toBeEmpty(); // case insensitive
});

it('aceita placa Mercosul (ABC1D23)', function () {
    expect(validarPlaca('ABC1D23'))->toBeEmpty();
    expect(validarPlaca('abc1d23'))->toBeEmpty();
});

it('rejeita placa muito curta', function () {
    expect(validarPlaca('ABC123'))->not->toBeEmpty();
    // Strings vazias são tratadas pela regra 'required', não pela Placa em si
});

it('rejeita placa muito longa', function () {
    expect(validarPlaca('ABC12345'))->not->toBeEmpty();
});

it('rejeita formato inválido', function () {
    // 4 letras + 3 dígitos
    expect(validarPlaca('ABCD123'))->not->toBeEmpty();
    // tudo dígito
    expect(validarPlaca('1234567'))->not->toBeEmpty();
});

it('formata placa antiga inserindo hífen', function () {
    expect(Placa::formatar('ABC1234'))->toBe('ABC-1234');
});

it('mantém placa Mercosul sem hífen', function () {
    expect(Placa::formatar('ABC1D23'))->toBe('ABC1D23');
});

it('limpa caracteres especiais e converte para maiúsculas', function () {
    expect(Placa::limpar('abc-1234'))->toBe('ABC1234');
    expect(Placa::limpar(' abc1d23 '))->toBe('ABC1D23');
});
