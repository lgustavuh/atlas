<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    /** @var \Illuminate\Foundation\Console\ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// === Agendamentos ===

// Notificações de vencimento (todo dia útil às 8h da manhã)
Schedule::command('notificacoes:vencimentos')
    ->weekdays()
    ->dailyAt('08:00')
    ->timezone('America/Sao_Paulo');
