<?php

namespace App\Services;

use App\Models\Recurrence;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

readonly class TaskService
{

    public function __construct(
        private TaskRepository $taskRepository,
        private TaskProgressionService $taskProgressionService
    ) {
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $this->checkPerms($task);
        // Completed tasks are read-only, except for toggling completion back to "not completed".
        if ($task->completed_at !== null) {
            $allowedKeys = ['completed_at'];
            $extraKeys = array_diff(array_keys($data), $allowedKeys);
            if (!empty($extraKeys)) {
                throw new Exception('Completed tasks cannot be updated');
            }
        }
        $wasCompleted = $task->completed_at !== null;
        $data = array_merge($task->only([
            'label',
            'description',
            'completed_at',
            'scheduled_at',
            'recurrence_id',
        ]), $data);
        $this->prepareData($data);
        if ($data['completed_at'] !== null) {
            $this->taskProgressionService->stop($task->id);
        }
        $updatedTask = $this->taskRepository->update($task, $data);
        if (!$wasCompleted && $updatedTask->completed_at !== null && $this->isRecurring($updatedTask)) {
            $this->createNextOccurrence($updatedTask);
        }
        if ($wasCompleted && $updatedTask->completed_at === null && $this->isRecurring($updatedTask)) {
            $this->deleteFutureOccurrences($updatedTask);
        }
        return $this->normalizeTaskDates($updatedTask);
    }

    /**
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $task = $this->taskRepository->find($id);
        $this->checkPerms($task);
        $this->taskRepository->destroy($task);
    }

    public function create(array $data): Task
    {
        $data['user_id'] = auth()->user()->id;
        $flagIds = $data['flag_ids'] ?? null;
        unset($data['flag_ids']);
        $this->prepareData($data);
        // New tasks land at the end of the user's list by default.
        $data['sort_order'] = (Task::where('user_id', $data['user_id'])->max('sort_order') ?? -1) + 1;
        $task = $this->taskRepository->create($data);

        if (is_array($flagIds) && !empty($flagIds)) {
            $task->flags()->sync(array_map('intval', $flagIds));
            $task->load('flags');
        }

        return $this->normalizeTaskDates($task);
    }

    private function checkPerms(Task $task): void
    {
        if ($task->owner()->getResults()->id !== auth()->user()->id) {
            throw new Exception('Not allowed');
        }
    }

    private function prepareData(array &$data): void
    {
        $data['description'] = $data['description'] ?? '';
        $data['completed_at'] = !empty($data['completed_at']) ? Carbon::parse($data['completed_at']) : null;
        $data['scheduled_at'] = !empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at']) : now()->setMilli(0);
        $data['recurrence_id'] = !empty($data['recurrence_id']) && $data['recurrence_id'] !== 'none'
            ? (int)$data['recurrence_id']
            : null;
    }

    private function isRecurring(Task $task): bool
    {
        if (empty($task->recurrence_id)) {
            return false;
        }
        $recurrence = $task->relationLoaded('recurrence')
            ? $task->recurrence
            : Recurrence::find($task->recurrence_id);
        return in_array($recurrence?->code, ['daily', 'weekly', 'monthly', 'yearly'], true);
    }

    private function createNextOccurrence(Task $task): void
    {
        $task->loadMissing(['recurrence', 'flags']);
        $recurrenceCode = $task->recurrence?->code;
        if (!$recurrenceCode) {
            throw new Exception('Invalid recurrence');
        }

        $completedAt = Carbon::parse($task->completed_at);
        $nextScheduledAt = $this->addRecurrenceInterval($completedAt, $recurrenceCode);

        // Ensure we don't accumulate duplicate future occurrences.
        $this->deleteFutureOccurrences($task, $completedAt);

        $nextTask = $this->taskRepository->create([
            'label' => $task->label,
            'description' => $task->description,
            'scheduled_at' => $nextScheduledAt,
            'completed_at' => null,
            'recurrence_id' => $task->recurrence_id,
            'parent_task_id' => $task->id,
            'user_id' => $task->user_id,
        ]);

        $nextTask->flags()->sync($task->flags()->pluck('flags.id')->all());
    }

    private function deleteFutureOccurrences(Task $task, ?Carbon $after = null): void
    {
        // When completing/uncompleting a recurring task, remove any future duplicates for the same series.
        // Use the task's scheduled date by default (uncomplete flow), or the completion date (complete flow).
        $after = $after ?? Carbon::parse($task->scheduled_at);

        Task::where('user_id', $task->user_id)
            ->where('parent_task_id', $task->id)
            ->whereNull('completed_at')
            ->where('id', '!=', $task->id)
            ->where('scheduled_at', '>', $after)
            ->delete();
    }

    private function addRecurrenceInterval(Carbon $date, string $recurrenceCode): Carbon
    {
        return match ($recurrenceCode) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'monthly' => $date->copy()->addMonthNoOverflow(),
            'yearly' => $date->copy()->addYearNoOverflow(),
        };
    }

    public function getAll(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        $tonight = $this->getTonight($tz);

        /** @var  $query Builder */
        $query = Task::where('user_id', auth()->user()->id)
            ->with('progressions')
            ->with('flags')
            ->with('links')
            ->with('recurrence')
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where(function ($query) use ($thisMorning, $tonight) {
                    $query->where('scheduled_at', '>=', $thisMorning)
                        ->where('scheduled_at', '<=', $tonight)
                        ->where('completed_at', null);
                })->orWhere(function (Builder $query) use ($thisMorning, $tonight) {
                    $query->where('completed_at', '>=', $thisMorning)
                        ->where('completed_at', '<=', $tonight);
                })->orWhere(function (Builder $query) use ($thisMorning) {
                    $query->where('scheduled_at', '<', $thisMorning)
                        ->where('completed_at', null);
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id');

        $collection = $query->get();
        foreach ($collection->all() as $task) {
            $this->normalizeTaskDates($task);
        }
        return $collection;
    }

    /**
     * Not-yet-completed tasks scheduled strictly after today, soonest first
     * — the counterpart to getCompletedPast() for what's still ahead.
     */
    public function getFutureTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $tonight = $this->getTonight($tz);

        $collection = Task::where('user_id', auth()->user()->id)
            ->with('flags')
            ->with('links')
            ->with('recurrence')
            ->where('completed_at', null)
            ->where('scheduled_at', '>', $tonight)
            ->orderBy('scheduled_at')
            ->get();

        foreach ($collection->all() as $task) {
            $this->normalizeTaskDates($task);
        }

        return $collection;
    }

    public function getTodayTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        $tonight = $this->getTonight($tz);

        return Task::where('user_id', auth()->user()->id)
            ->with('progressions')
            ->with('recurrence')
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('scheduled_at', '>=', $thisMorning)
                    ->where('scheduled_at', '<=', $tonight)
                    ->where('completed_at', null);
            })->get();
    }

    public function getCompletedTodayTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        $tonight = $this->getTonight($tz);
        return Task::where('user_id', auth()->user()->id)
            ->with('progressions')
            ->with('recurrence')
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('completed_at', '>=', $thisMorning)
                    ->where('completed_at', '<=', $tonight);
            })->get();
    }

    public function getLateTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        return Task::where('user_id', auth()->user()->id)
            ->with('progressions')
            ->with('recurrence')
            ->where(function (Builder $query) use ($thisMorning) {
                $query->where('scheduled_at', '<', $thisMorning)
                    ->where('completed_at', null);
            })->get();
    }

    /**
     * @param DateTimeZone $tz
     * @return \Illuminate\Support\Carbon
     */
    private function getThisMorning(DateTimeZone $tz): \Illuminate\Support\Carbon
    {
        return now($tz)->setHour(00)->setMinute(00)->setSecond(00)->subSeconds($tz->getOffset(now()));
    }

    /**
     * @param DateTimeZone $tz
     * @return \Illuminate\Support\Carbon
     */
    private function getTonight(DateTimeZone $tz): \Illuminate\Support\Carbon
    {
        return now($tz)->setHour(23)->setMinute(59)->setSecond(59)->subSeconds($tz->getOffset(now()));
    }

    private function getMorningForDate(string $ymd, DateTimeZone $tz): Carbon
    {
        $local = Carbon::parse($ymd . ' 00:00:00', $tz);
        return $local->copy()->subSeconds($tz->getOffset($local));
    }

    private function getNightForDate(string $ymd, DateTimeZone $tz): Carbon
    {
        $local = Carbon::parse($ymd . ' 23:59:59', $tz);
        return $local->copy()->subSeconds($tz->getOffset($local));
    }

    public function addFlag(int $taskId, int $flagId): Task
    {
        $task = $this->taskRepository->find($taskId);
        $this->checkPerms($task);
        if ($task->completed_at !== null) {
            throw new Exception('Completed tasks cannot be updated');
        }
        $task->flags()->syncWithoutDetaching($flagId);
        $task->load('flags');
        return $this->normalizeTaskDates($task);
    }

    public function deleteFlag(int $taskId, int $flagId): Task
    {
        $task = $this->taskRepository->find($taskId);
        $this->checkPerms($task);
        if ($task->completed_at !== null) {
            throw new Exception('Completed tasks cannot be updated');
        }
        $task->flags()->detach($flagId);
        $task->load('flags');
        return $this->normalizeTaskDates($task);
    }

    /**
     * @throws Exception
     */
    public function addLink(int $taskId, string $url, ?string $label = null): Task
    {
        $task = $this->taskRepository->find($taskId);
        $this->checkPerms($task);
        if ($task->completed_at !== null) {
            throw new Exception('Completed tasks cannot be updated');
        }

        $url = trim($url);
        if ($url === '') {
            throw new Exception('A URL is required');
        }
        // A bare "example.com" is a common thing to paste — treat it as https
        // rather than reject it, so the anchor tag on the frontend actually navigates.
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        $task->links()->create(['url' => $url, 'label' => trim((string) $label) ?: null]);
        $task->load('links');
        return $this->normalizeTaskDates($task);
    }

    /**
     * @throws Exception
     */
    public function deleteLink(int $taskId, int $linkId): Task
    {
        $task = $this->taskRepository->find($taskId);
        $this->checkPerms($task);
        if ($task->completed_at !== null) {
            throw new Exception('Completed tasks cannot be updated');
        }
        $task->links()->where('id', $linkId)->delete();
        $task->load('links');
        return $this->normalizeTaskDates($task);
    }

    /**
     * Persist a new manual order for the given task ids (as dragged in the
     * list). Ids that don't belong to the current user are silently
     * ignored rather than erroring, so a stale/tampered list can't reorder
     * someone else's tasks.
     *
     * @param array<int, int|string> $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $userId = auth()->user()->id;
        $tasks = Task::where('user_id', $userId)
            ->whereIn('id', $orderedIds)
            ->get(['id', 'sort_order'])
            ->keyBy('id');

        foreach (array_values($orderedIds) as $index => $id) {
            $task = $tasks->get((int) $id);
            if (!$task || (int) $task->sort_order === $index) {
                continue;
            }
            $task->update(['sort_order' => $index]);
        }
    }

    /**
     * Completed tasks over a past window ending on a given day (default: yesterday).
     *
     * period: day|week|month
     * endDateYmd: YYYY-MM-DD in user's timezone.
     */
    public function getCompletedPast(string $period = 'day', ?string $endDateYmd = null): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));

        $endLocal = $endDateYmd
            ? Carbon::parse($endDateYmd, $tz)
            : now($tz)->subDay();

        $endYmd = $endLocal->format('Y-m-d');

        $days = match ($period) {
            'day' => 1,
            'week' => 7,
            'month' => 30,
            default => 1,
        };

        $startLocal = $endLocal->copy()->subDays($days - 1);
        $startYmd = $startLocal->format('Y-m-d');

        $start = $this->getMorningForDate($startYmd, $tz);
        $end = $this->getNightForDate($endYmd, $tz);

        $collection = Task::where('user_id', auth()->user()->id)
            ->with('flags')
            ->with('links')
            ->with('recurrence')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<=', $end)
            ->orderBy('completed_at')
            ->get();

        foreach ($collection->all() as $task) {
            $this->normalizeTaskDates($task);
        }

        return $collection;
    }

    private function normalizeTaskDates(Task $task): Task
    {
        foreach (['scheduled_at', 'completed_at', 'updated_at'] as $field) {
            $v = $task->{$field} ?? null;
            if (empty($v)) {
                continue;
            }
            $s = (string) $v;
            if (str_ends_with($s, 'Z')) {
                $task->{$field} = $s;
                continue;
            }
            $task->{$field} = $s . '.0Z';
        }
        return $task;
    }

    /**
     * Return the "series" history for a task by walking its parent chain (previous occurrences).
     * Includes the immediate parent and all earlier ancestors up to the root.
     *
     * Also returns the upcoming occurrence (child) when it exists.
     *
     * @return array{history: array<int, array{id:int,label:string,scheduled_at:mixed,completed_at:mixed}>, upcoming: array{id:int,label:string,scheduled_at:mixed,completed_at:mixed}|null}
     * @throws Exception
     */
    public function getHistory(int $id): array
    {
        $task = Task::find($id);
        if (!$task) {
            throw new Exception('Task not found');
        }
        $this->checkPerms($task);

        $history = [];
        $visited = [];
        $current = $task;
        $depth = 0;

        while (!empty($current->parent_task_id)) {
            $depth++;
            if ($depth > 200) {
                break;
            }

            $parentId = (int)$current->parent_task_id;
            if (isset($visited[$parentId])) {
                break;
            }
            $visited[$parentId] = true;

            $parent = Task::find($parentId);
            if (!$parent) {
                break;
            }
            $this->checkPerms($parent);

            $row = [
                'id' => $parent->id,
                'label' => (string)$parent->label,
                'scheduled_at' => $parent->scheduled_at,
                'completed_at' => $parent->completed_at,
            ];

            // Match the frontend date parsing convention used in getAll().
            if (!empty($row['scheduled_at'])) {
                $row['scheduled_at'] = $row['scheduled_at'] . '.0Z';
            }
            if (!empty($row['completed_at'])) {
                $row['completed_at'] = $row['completed_at'] . '.0Z';
            }

            $history[] = $row;
            $current = $parent;
        }

        $upcoming = Task::where('user_id', $task->user_id)
            ->where('parent_task_id', $task->id)
            ->orderBy('scheduled_at')
            ->first();

        $upcomingRow = null;
        if ($upcoming) {
            $this->checkPerms($upcoming);
            $upcomingRow = [
                'id' => $upcoming->id,
                'label' => (string)$upcoming->label,
                'scheduled_at' => $upcoming->scheduled_at,
                'completed_at' => $upcoming->completed_at,
            ];
            if (!empty($upcomingRow['scheduled_at'])) {
                $upcomingRow['scheduled_at'] = $upcomingRow['scheduled_at'] . '.0Z';
            }
            if (!empty($upcomingRow['completed_at'])) {
                $upcomingRow['completed_at'] = $upcomingRow['completed_at'] . '.0Z';
            }
        }

        return [
            'history' => $history,
            'upcoming' => $upcomingRow,
        ];
    }
}
