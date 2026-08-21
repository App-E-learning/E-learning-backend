<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rappel quotidien push à 20h — timezone explicite car le serveur peut
// tourner en UTC ; app.timezone (config/app.php) n'est pas utilisé
// automatiquement par le scheduler, il faut le préciser ici.
Schedule::command('app:rappel-quotidien')
    ->dailyAt('20:00')
    ->timezone('Africa/Douala');