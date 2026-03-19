<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly: recalculate scores, rankings, brand trust, category counts
Schedule::command('supplements:calculate-scores')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();
