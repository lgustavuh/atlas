<?php

declare(strict_types=1);

namespace Tests;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Em testes, precisamos das permissões para que can() funcione.
        // Como usamos RefreshDatabase, isso roda antes de cada teste.
        $this->seed(RolesAndPermissionsSeeder::class);
    }
}
