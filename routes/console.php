<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Must run before invoice generation; invoicing also fails closed on unapplied changes.
Schedule::command('subscriptions:apply-scheduled-plan-changes')
    ->dailyAt('07:05')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('billing:generate-recurring-invoices')
    ->dailyAt('07:10')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('billing:sync-invoice-lifecycle')
    ->dailyAt('07:20')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();

Schedule::command('billing:generate-disconnection-notices')
    ->dailyAt('07:30')
    ->timezone(config('app.timezone'))
    ->withoutOverlapping();
