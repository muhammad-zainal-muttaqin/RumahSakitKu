<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| Define scheduled commands for SIMRS operations.
| These run automatically based on the defined schedule.
|
*/

// Cache warming - Run daily at 2 AM to pre-populate cache
Schedule::command('cache:warm --force')
    ->dailyAt('02:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-cache-warm.log'));

// Clear expired cache - Run daily at 3 AM
Schedule::command('cache:clear-expired')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/schedule-cache-clear.log'));

// Database backup - Run daily at 1 AM
Schedule::command('database:backup')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->onOneServer();

// Refresh room occupancy cache every 5 minutes during business hours
Schedule::call(function () {
    \App\Services\CacheService::forgetRoomOccupancy();
    \App\Services\CacheService::getRoomOccupancy();
})
    ->everyFiveMinutes()
    ->timezone('Asia/Jakarta')
    ->between('06:00', '22:00');

// Clear old Telescope entries daily
Schedule::command('telescope:prune --hours=48')
    ->daily()
    ->at('04:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
