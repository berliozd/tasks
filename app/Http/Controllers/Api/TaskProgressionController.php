<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TaskProgressionService;

class TaskProgressionController extends Controller
{
    public function __construct(private readonly TaskProgressionService $taskProgressionService)
    {
    }

    public function start(int $taskId)
    {
        $this->taskProgressionService->create($taskId);
    }

    public function stop(int $taskId)
    {
        $this->taskProgressionService->stop($taskId);
    }
}
