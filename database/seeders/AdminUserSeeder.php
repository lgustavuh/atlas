<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cria o usuário administrador inicial.
 *
 * Em produção, ESSE SEEDER NÃO DEVE SER EXECUTADO.
 * O admin inicial deve ser criado via comando interativo
 * (a ser implementado: `php artisan etc:create-admin`).
 *
 * Aqui no dev/staging é prático ter um usuário fixo.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('atlas.admin.email', 'admin@atlas.local');
        $password = config('atlas.admin.password', 'Admin@123456');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrador do Sistema',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'active' => true,
            ]
        );

        $user->assignRole('admin');

        $this->command->info("  ✓ Usuário admin: {$email}");
        $this->command->warn('  ⚠ Senha padrão em uso. TROCAR no primeiro acesso!');
    }
}
