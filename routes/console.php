<?php

use App\Jobs\CheckUserNotLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Log::info('console run');

Schedule::job(new CheckUserNotLogged)->everyMinute();

Log::info('console end');