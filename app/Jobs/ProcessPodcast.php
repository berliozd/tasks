<?php

namespace App\Jobs;

use App\Events\PodcastCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPodcast implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

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
        Log::info('Process podcast and dispatch event PodcastCreated');
        PodcastCreated::dispatch();
    }
}
