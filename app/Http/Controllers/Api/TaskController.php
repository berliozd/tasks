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
}
