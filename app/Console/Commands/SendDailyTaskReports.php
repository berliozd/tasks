<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DailyTaskReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDailyTaskReports extends Command
{
    protected $signature = 'reports:send-daily-tasks';

    protected $description = 'Email each opted-in user their daily task report, if the current hour matches their configured send time';

    public function handle(DailyTaskReportService $dailyTaskReportService): int
    {
        $now = now();

        User::where('daily_report_enabled', true)
            ->whereNotNull('daily_report_hour')
            ->chunkById(100, function ($users) use ($dailyTaskReportService, $now) {
                foreach ($users as $user) {
                    try {
                        $dailyTaskReportService->sendReportIfDue($user, $now->copy());
                    } catch (Throwable $e) {
                        Log::warning('Daily task report send failed', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                        $this->error("Failed report for user #{$user->id}: {$e->getMessage()}");
                    }
                }
            });

        return self::SUCCESS;
    }
}
