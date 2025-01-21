<?php

namespace App\Jobs;

use App\Events\UserNotLogged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckUserNotLogged implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('Creating CheckUserNotLogged job');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Check user is logged out and dispatch event UserNotLogged');
        UserNotLogged::dispatch('Message : User logged out!');
    }
}
