<?php

use App\Jobs\ProcessPodcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Log::info('console run');

Schedule::job(new ProcessPodcast)->everyMinute();
Schedule::command('app:process-podcast-cmd')->everyMinute();

Log::info('console end');