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
        return $updatedTask;
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
        $this->prepareData($data);
        return $this->taskRepository->create($data);
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
            });

        $collection = $query->get();
        foreach ($collection->all() as $task) {
            if (!empty($task->scheduled_at)) {
                $task->scheduled_at = $task->scheduled_at . '.0Z';
            }
            if (!empty($task->completed_at)) {
                $task->completed_at = $task->completed_at . '.0Z';
            }
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
        $task->flags()->attach($flagId);
        return $task;
    }

    public function deleteFlag(int $taskId, int $flagId): Task
    {
        $task = $this->taskRepository->find($taskId);
        $this->checkPerms($task);
        $task->flags()->detach($flagId);
        return $task;
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
            ->with('recurrence')
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->where('completed_at', '<=', $end)
            ->orderByDesc('completed_at')
            ->get();

        foreach ($collection->all() as $task) {
            if (!empty($task->scheduled_at)) {
                $task->scheduled_at = $task->scheduled_at . '.0Z';
            }
            if (!empty($task->completed_at)) {
                $task->completed_at = $task->completed_at . '.0Z';
            }
        }

        return $collection;
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
