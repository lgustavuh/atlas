<?php

declare(strict_types=1);

/**
 * Configurações específicas do sistema Atlas.
 *
 * Acesso via: config('atlas.chave')
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Inicial (seeder)
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'email'    => env('ADMIN_EMAIL', 'admin@atlas.local'),
        'password' => env('ADMIN_PASSWORD', 'Admin@123456'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Segurança
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Login
        'login_max_attempts' => env('LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => env('LOGIN_DECAY_MINUTES', 1),

        // Política de senha
        'password' => [
            'min_length' => 12,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            // Bloqueia reuso das últimas N senhas
            'history_count' => 5,
            // Expira a senha após N dias (0 = não expira)
            'expires_in_days' => 0,
        ],

        // Sessão
        'force_logout_on_password_change' => true,
        'session_timeout_minutes' => env('SESSION_LIFETIME', 120),

        // Email
        'require_email_verification' => env('REQUIRE_EMAIL_VERIFICATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload de Arquivos
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        // Disk padrão para uploads privados (fora do public)
        'disk' => 'local',
        'private_path' => 'private/uploads',

        // Limites
        'max_size_mb' => 30,

        // Tipos permitidos (mime real, não extensão)
        // Por categoria de uso
        'mimes' => [
            'foto' => ['image/jpeg', 'image/png', 'image/webp'],
            'documento' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
            ],
            'atestado' => ['application/pdf', 'image/jpeg', 'image/png'],
            'planilha' => [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Empresa (informações para PDFs, emails, etc)
    |--------------------------------------------------------------------------
    */
    'empresa' => [
        'nome' => env('EMPRESA_NOME', 'Atlas'),
        'cnpj' => env('EMPRESA_CNPJ', ''),
        'endereco' => env('EMPRESA_ENDERECO', ''),
        'telefone' => env('EMPRESA_TELEFONE', ''),
        'email' => env('EMPRESA_EMAIL', ''),
        'logo_path' => env('EMPRESA_LOGO', 'images/logo.png'),
    ],
];
