<?php

declare(strict_types=1);

use App\Rules\SenhaForte;
use Illuminate\Support\Facades\Validator;

function validarSenha(string $senha): array
{
    $validator = Validator::make(
        ['password' => $senha],
        ['password' => [new SenhaForte()]]
    );

    return $validator->errors()->get('password');
}

it('aceita senha forte', function () {
    expect(validarSenha('SenhaForte@123456'))->toBeEmpty();
});

it('rejeita senha curta', function () {
    expect(validarSenha('Aa1@'))->not->toBeEmpty();
});

it('rejeita senha sem maiúscula', function () {
    expect(validarSenha('senhafraca@123456'))->not->toBeEmpty();
});

it('rejeita senha sem minúscula', function () {
    expect(validarSenha('SENHAFRACA@123456'))->not->toBeEmpty();
});

it('rejeita senha sem número', function () {
    expect(validarSenha('SenhaSemNumero@'))->not->toBeEmpty();
});

it('rejeita senha sem símbolo', function () {
    expect(validarSenha('SenhaSemSimbolo123'))->not->toBeEmpty();
});

it('rejeita senhas notoriamente fracas', function () {
    expect(validarSenha('password'))->not->toBeEmpty();
    expect(validarSenha('senha123'))->not->toBeEmpty();
    expect(validarSenha('admin123'))->not->toBeEmpty();
});
