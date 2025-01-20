<?php

namespace App\Console\Commands;

use App\Events\VideoCreated;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessVideoCmd extends Command
{
    public function __construct()
    {
        parent::__construct();
        Log::info('Creating video command');
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-video-cmd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a video and dispatch a VideoProcessed event';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Process video, and dispatch event VideoCreated');
        VideoCreated::dispatch('Video has been created!');
    }
}
