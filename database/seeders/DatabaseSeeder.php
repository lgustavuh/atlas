<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('▶ Seeding: Permissões e Perfis');
        $this->call(RolesAndPermissionsSeeder::class);

        $this->command->info('▶ Seeding: Geografia (Brasil)');
        $this->call(GeografiaSeeder::class);

        $this->command->info('▶ Seeding: Usuário Admin');
        $this->call(AdminUserSeeder::class);

        $this->command->info('✓ Banco populado com sucesso.');
    }
}
