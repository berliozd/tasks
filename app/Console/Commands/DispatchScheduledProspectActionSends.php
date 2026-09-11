<?php

namespace App\Console\Commands;

use App\Models\ProspectAction;
use App\Services\ProspectActionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchScheduledProspectActionSends extends Command
{
    /**
     * This command runs every minute (see routes/console.php), so capping a
     * run at this many actions paces a large batch submission out over
     * several runs instead of sending it all in a single burst — matching
     * the per-request throttle on the manual "send" endpoint.
     */
    private const MAX_PER_RUN = 10;

    protected $signature = 'prospect-actions:dispatch-scheduled-sends';

    protected $description = 'Send planned, queued email actions whose scheduled time has arrived';

    public function handle(ProspectActionService $prospectActionService): int
    {
        $due = ProspectAction::query()
            ->where('type', 'email')
            ->where('status', 'planned')
            ->where('queued_for_send', true)
            ->where('scheduled_at', '<=', now())
            ->oldest('scheduled_at')
            ->limit(self::MAX_PER_RUN)
            ->get();

        $this->info("Found {$due->count()} due action(s) (capped at " . self::MAX_PER_RUN . " per run).");

        foreach ($due as $action) {
            try {
                // Bypasses per-user auth checks inside the service (there is no
                // authenticated user in a scheduled console run) by sending
                // directly through the same mail-resolution logic the service
                // uses, rather than calling ProspectActionService::send()
                // (which asserts auth()->user()->currentTeam ownership).
                $prospectActionService->sendAsSystem($action->id);
                $this->line("Sent action #{$action->id}");
            } catch (Throwable $e) {
                Log::warning('Scheduled prospect action send failed', [
                    'action_id' => $action->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("Failed action #{$action->id}: {$e->getMessage()}");
                // Left as planned/queued so it's retried on the next run.
            }
        }

        return self::SUCCESS;
    }
}
