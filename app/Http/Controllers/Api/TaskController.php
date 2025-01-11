<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * @throws \Exception
     */
    public function update(Request $request, string $id)
    {
        $data = $request->toArray();
        if (!empty($data['completed_at'])) {
            $data['completed_at'] = Carbon::parse($data['completed_at']);
        }
        $task = Task::whereId($id)->first();
        $this->checkPermissions($task);
        $task->fill($data);
        $task->save();
        return $data;
    }

    /**
     * Remove the specified resource from storage.
     * @throws \Exception
     */
    public function destroy(string $id)
    {
        $task = Task::whereId($id)->first();
        $this->checkPermissions($task);
        $task->delete();
    }

    /**
     * @param $task
     * @return void
     * @throws \Exception
     */
    public function checkPermissions($task): void
    {
        if ($task->owner()->getResults()->id !== auth()->user()->id) {
            throw new \Exception('Not allowed');
        }
    }
}
