<?php

use App\Jobs\ProcessPodcast;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

\Illuminate\Support\Facades\Log::info('console run');


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::job(new ProcessPodcast())->everyMinute();