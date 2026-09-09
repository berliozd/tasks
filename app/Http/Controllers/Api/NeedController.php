<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NeedService;
use Exception;
use Illuminate\Http\Request;

class NeedController extends Controller
{
    public function __construct(private readonly NeedService $needService)
    {
    }

    public function index()
    {
        return $this->needService->getAll();
    }

    /**
     * @throws Exception
     */
    public function show(string $id)
    {
        return $this->needService->find((int) $id);
    }

    public function store(Request $request)
    {
        return $this->needService->create($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function update(Request $request, string $id)
    {
        return $this->needService->update($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function moveStage(Request $request, string $id)
    {
        return $this->needService->moveStage((int) $id, (string) $request->input('stage'));
    }

    /**
     * @throws Exception
     */
    public function reorder(Request $request)
    {
        $this->needService->reorderWithinStage(
            (string) $request->input('stage'),
            (array) $request->input('ids', []),
        );
    }

    /**
     * @throws Exception
     */
    public function addNote(Request $request, string $id)
    {
        return $this->needService->addNote((int) $id, (string) $request->input('note', ''));
    }

    /**
     * @throws Exception
     */
    public function destroy(string $id)
    {
        $this->needService->destroy((int) $id);
    }
}
