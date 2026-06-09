<?php

declare(strict_types=1);

return [
    // Argon2id: vencedor da Password Hashing Competition, mais robusto que bcrypt
    'driver' => 'argon2id',

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

    'argon' => [
        // Parâmetros calibrados: ~100ms por hash em hardware moderno
        // Tempo suficiente pra defender contra brute force, baixo o suficiente pra UX
        'memory' => 65536,  // 64MB
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    // Re-hashing automático quando algoritmo/parâmetros mudam
    'rehash_on_login' => true,
];
