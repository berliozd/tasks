<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Carbon\Carbon;
use DateTimeZone;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

readonly class TaskService
{

    public function __construct(private TaskRepository $taskRepository)
    {
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Task
    {
        $task = $this->taskRepository->find($id);
        $this->checkPerms($task);
        $this->prepareData($data);
        return $this->taskRepository->update($task, $data);
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
    }

    public function getTodayTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        $tonight = $this->getTonight($tz);

        return DB::table('tasks')->where('user_id', auth()->user()->id)
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
        return DB::table('tasks')->where('user_id', auth()->user()->id)
            ->where(function (Builder $query) use ($thisMorning, $tonight) {
                $query->where('completed_at', '>=', $thisMorning)
                    ->where('completed_at', '<=', $tonight);
            })->get();
    }


    public function getLateTasks(): Collection
    {
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = $this->getThisMorning($tz);
        return DB::table('tasks')->where('user_id', auth()->user()->id)
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

}