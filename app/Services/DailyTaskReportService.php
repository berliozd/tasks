<?php

namespace App\Services;

use App\Models\Flag;
use App\Models\Task;
use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Collection;

readonly class DailyTaskReportService
{
    public function __construct(
        private MailSenderInterface $mailSender,
        private Markdown $markdown,
    ) {
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
        $data = [
            'userName' => $user->name,
            'appName' => (string) config('app.name'),
            'completedGroups' => $this->groupByFlag($this->getCompletedToday($user, $tz)),
            'dueTomorrowGroups' => $this->groupByFlag($this->getDueTomorrow($user, $tz)),
        ];

        $this->mailSender->send(
            $user->email,
            $user->name,
            (string) config('services.prospection.from_email'),
            (string) config('app.name'),
            'Your daily task report',
            (string) $this->markdown->renderText('emails.daily-task-report', $data),
            null,
            null,
            (string) $this->markdown->render('emails.daily-task-report', $data),
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
     * Groups tasks by their first (alphabetically, case-insensitive) flag,
     * each group carrying that flag's color for display — flagless tasks
     * land in a trailing "No flag" group. Groups themselves are ordered the
     * same way tasks previously were: flagged before flagless, alphabetical
     * by flag name.
     *
     * @return Collection<int, array{label: string, color: ?string, tasks: Collection<int, Task>}>
     */
    private function groupByFlag(Collection $tasks): Collection
    {
        return $tasks
            ->groupBy(fn (Task $task) => $this->firstFlag($task)?->id ?? 0)
            ->map(function (Collection $tasksInGroup, int $flagId) {
                $flag = $this->firstFlag($tasksInGroup->first());
                return [
                    'label' => $flag?->name ?? 'No flag',
                    'color' => $flag?->color,
                    'flagged' => $flagId !== 0,
                    'tasks' => $tasksInGroup->values(),
                ];
            })
            ->sortBy(fn (array $group) => $group['flagged'] ? mb_strtolower($group['label']) : "\u{FFFF}")
            ->values();
    }

    private function firstFlag(Task $task): ?Flag
    {
        return $task->flags->sortBy(fn (Flag $flag) => mb_strtolower($flag->name))->first();
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
