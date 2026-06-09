<?php

declare(strict_types=1);

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            // 60 minutos é razoável; reduz janela de ataque vs usabilidade
            'expire' => (int) env('PASSWORD_RESET_TIMEOUT', 60),
            // 60 segundos entre solicitações (anti-spam)
            'throttle' => 60,
        ],
    ],

    // 3 horas — após esse tempo logado, ações sensíveis (mudar email, deletar conta)
    // exigem reconfirmar a senha
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
