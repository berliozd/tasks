<?php

namespace App\Console\Commands;

use App\Events\SubscriptionCreated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPodcastCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-podcast-cmd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Processing podcast command');
        SubscriptionCreated::dispatch();
    }
}
