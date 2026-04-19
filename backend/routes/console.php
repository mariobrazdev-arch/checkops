<?php

use App\Console\Commands\EnviarNotificacoesPendentes;
use App\Console\Commands\GerarRotinasDodia;
use App\Console\Commands\MarcarAtrasadas;
use App\Console\Commands\MarcarNaoRealizadas;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduler de rotinas ────────────────────────────────────────────────────
Schedule::command(GerarRotinasDodia::class)->dailyAt('00:01')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command(MarcarAtrasadas::class)->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command(MarcarNaoRealizadas::class)->dailyAt('23:55')
    ->withoutOverlapping()
    ->runInBackground();

// US-20 — Notificações push de rotinas pendentes
Schedule::command(EnviarNotificacoesPendentes::class)->everyThirtyMinutes()
    ->withoutOverlapping();

