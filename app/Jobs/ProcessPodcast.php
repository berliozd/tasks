<?php

namespace App\Jobs;

use App\Events\SubscriptionCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPodcast implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        Log::info('Creating podcast job');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing podcast job');
        SubscriptionCreated::dispatch();
    }
}
