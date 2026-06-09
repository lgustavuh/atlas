<?php

declare(strict_types=1);

use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations customizadas
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function asAdmin(): \App\Models\User
{
    $user = \App\Models\User::factory()->create();
    $user->assignRole('admin');
    test()->actingAs($user);
    return $user;
}

function asUser(string $role = 'visualizador'): \App\Models\User
{
    $user = \App\Models\User::factory()->create();
    $user->assignRole($role);
    test()->actingAs($user);
    return $user;
}
