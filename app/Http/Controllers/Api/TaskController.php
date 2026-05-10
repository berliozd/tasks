<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaskService;
use Exception;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function index()
    {
        return $this->taskService->getAll();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return $this->taskService->create($request->toArray());
    }

    /**
     * Update the specified resource in storage.
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->taskService->update($request->toArray(), (int)$id);
    }

    /**
     * Remove the specified resource from storage.
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->taskService->destroy((int)$id);
    }

    public function completed(Request $request)
    {
        $period = (string) $request->query('period', 'day');
        $endDate = $request->query('end_date'); // YYYY-MM-DD in user's timezone
        return $this->taskService->getCompletedPast($period, is_string($endDate) ? $endDate : null);
    }

    public function history(string $id)
    {
        return $this->taskService->getHistory((int)$id);
    }

    public function addFlag(string $taskId, string $flagId)
    {
        return $this->taskService->addFlag((int)$taskId, (int)$flagId);
    }

    public function deleteFlag(string $taskId, string $flagId)
    {
        return $this->taskService->deleteFlag((int)$taskId, (int)$flagId);
    }
}
