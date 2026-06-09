<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Teste de smoke: dashboard deve renderizar sem MultipleRootElementsException.
 *
 * Esse erro aconteceu na v1.5 quando um </div> orfão apareceu na view do dashboard,
 * deixando dois elementos raiz. O Livewire 3 rejeita componentes assim com
 * "Livewire only supports one HTML element per component".
 *
 * Esse teste garante que o dashboard pode ser renderizado sem essa exception.
 */
class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renderiza_sem_erro_de_multiplos_root_elements(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', 'admin@atlas.local')
            ->firstOrFail();

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Aqui está um resumo'); // Texto presente no dashboard
    }

    public function test_pagina_inicial_apos_login_carrega(): void
    {
        $this->seed();

        $admin = User::query()
            ->where('email', 'admin@atlas.local')
            ->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Aqui está um resumo');
    }
}
