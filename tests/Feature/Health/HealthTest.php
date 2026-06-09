<?php

declare(strict_types=1);

it('health endpoint responde sem autenticação', function () {
    $response = $this->get('/health');

    expect($response->status())->toBeIn([200, 503]);
});

it('health retorna 200 quando tudo está OK', function () {
    $response = $this->get('/health');

    $response->assertStatus(200);
    $response->assertJson(['status' => 'ok']);
});

it('health retorna JSON com a estrutura esperada', function () {
    $response = $this->get('/health');

    $response->assertJsonStructure([
        'status',
        'timestamp',
        'environment',
        'checks' => [
            'app' => ['ok', 'message'],
            'database' => ['ok', 'message'],
            'cache' => ['ok', 'message'],
            'redis' => ['ok', 'message'],
            'storage' => ['ok', 'message'],
        ],
    ]);
});

it('health inclui latência do banco em milissegundos', function () {
    $response = $this->get('/health');

    $data = $response->json();
    expect($data['checks']['database'])->toHaveKey('latency_ms');
    expect($data['checks']['database']['latency_ms'])->toBeInt();
});

it('health é acessível mesmo sem ter feito login', function () {
    // Não fazemos actingAs() — confirma que é público
    $response = $this->get('/health');

    $response->assertStatus(200);
});

it('endpoint /up nativo do Laravel também funciona', function () {
    // Endpoint provido pelo Laravel 11 nativo
    $response = $this->get('/up');

    $response->assertStatus(200);
});
