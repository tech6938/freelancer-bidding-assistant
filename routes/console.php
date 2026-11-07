<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Project-Centric Auto-Bid Ingestion: runs every 30 seconds to fetch global projects and reverse match
Schedule::command('autobid:ingest')
    ->everyThirtySeconds()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('queue:work --stop-when-empty --tries=2')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();