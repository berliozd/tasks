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
}
