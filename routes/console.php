<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('billing:generate-recurring-invoices')
    ->dailyAt('00:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('billing:sync-invoice-lifecycle')
    ->dailyAt('00:20')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('billing:generate-disconnection-notices')
    ->dailyAt('00:30')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
