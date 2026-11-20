<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cada minuto revisa qué excursiones en curso ya pasaron su hora de retorno.
// Necesita el programador activo: `php artisan schedule:work` (o el cron en producción).
Schedule::command('excursiones:marcar-alertas')->everyMinute();
