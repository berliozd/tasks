<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Support\Collection;

readonly class DailyTaskReportService
{
    public function __construct(private MailSenderInterface $mailSender)
    {
    }

    /**
     * Sends the report only if the user has it enabled and the given
     * instant falls within their configured local hour to send at — safe
     * to call for every enabled user on every hourly tick.
     *
     * @throws Exception
     */
    public function sendReportIfDue(User $user, Carbon $now): void
    {
        if (!$user->daily_report_enabled || $user->daily_report_hour === null) {
            return;
        }

        $tz = new DateTimeZone($user->timezone ?? config('app.timezone'));
        if ((int) $now->copy()->setTimezone($tz)->format('G') !== (int) $user->daily_report_hour) {
            return;
        }

        $this->sendReport($user, $tz);
    }

    /**
     * @throws Exception
     */
    public function sendReport(User $user, DateTimeZone $tz): void
    {
        $completedToday = $this->orderByFlags($this->getCompletedToday($user, $tz));
        $dueTomorrow = $this->orderByFlags($this->getDueTomorrow($user, $tz));

        $this->mailSender->send(
            $user->email,
            $user->name,
            (string) config('services.prospection.from_email'),
            (string) config('app.name'),
            'Your daily task report',
            $this->formatReport($completedToday, $dueTomorrow),
        );
    }

    private function getCompletedToday(User $user, DateTimeZone $tz): Collection
    {
        [$morning, $night] = $this->localDayBounds($tz, 0);
        return Task::where('user_id', $user->id)
            ->with('flags')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $morning)
            ->where('completed_at', '<=', $night)
            ->get();
    }

    private function getDueTomorrow(User $user, DateTimeZone $tz): Collection
    {
        [$morning, $night] = $this->localDayBounds($tz, 1);
        return Task::where('user_id', $user->id)
            ->with('flags')
            ->whereNull('completed_at')
            ->where('scheduled_at', '>=', $morning)
            ->where('scheduled_at', '<=', $night)
            ->get();
    }

    /**
     * Tasks with a flag sort before flagless ones, alphabetically by their
     * (first, case-insensitive) flag name.
     */
    private function orderByFlags(Collection $tasks): Collection
    {
        return $tasks->sortBy(function (Task $task) {
            $firstFlagName = $task->flags->pluck('name')->filter()->sort()->first();
            // Flagless tasks sort after everything else.
            return $firstFlagName === null ? "\u{FFFF}" : mb_strtolower($firstFlagName);
        })->values();
    }

    private function formatReport(Collection $completedToday, Collection $dueTomorrow): string
    {
        $lines = [];

        $lines[] = 'Completed today (' . $completedToday->count() . ')';
        $lines[] = $completedToday->isEmpty()
            ? '  Nothing completed today.'
            : $completedToday->map(fn (Task $t) => $this->formatLine($t))->implode("\n");

        $lines[] = '';
        $lines[] = 'To do tomorrow (' . $dueTomorrow->count() . ')';
        $lines[] = $dueTomorrow->isEmpty()
            ? '  Nothing scheduled for tomorrow.'
            : $dueTomorrow->map(fn (Task $t) => $this->formatLine($t))->implode("\n");

        return implode("\n", $lines);
    }

    private function formatLine(Task $task): string
    {
        $flagNames = $task->flags->pluck('name')->filter()->all();
        $line = '  - ' . $task->label;
        if (!empty($flagNames)) {
            $line .= ' (' . implode(', ', $flagNames) . ')';
        }
        return $line;
    }

    /**
     * Local midnight-to-23:59:59 bounds for "today + $dayOffset days", as
     * UTC instants — same construction TaskService's getThisMorning()/
     * getTonight() use, so this stays consistent with how the rest of the
     * app decides what counts as "today"/"tomorrow" for a given user.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function localDayBounds(DateTimeZone $tz, int $dayOffset): array
    {
        $base = Carbon::now($tz)->addDays($dayOffset);
        $morning = $base->copy()->setHour(0)->setMinute(0)->setSecond(0)->subSeconds($tz->getOffset(now()));
        $night = $base->copy()->setHour(23)->setMinute(59)->setSecond(59)->subSeconds($tz->getOffset(now()));
        return [$morning, $night];
    }
}
