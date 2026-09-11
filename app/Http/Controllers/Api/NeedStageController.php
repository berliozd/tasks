<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NeedStageService;
use Exception;
use Illuminate\Http\Request;

class NeedStageController extends Controller
{
    public function __construct(private readonly NeedStageService $needStageService)
    {
    }

    public function index()
    {
        return $this->needStageService->getAllGroups();
    }

    public function storeGroup(Request $request)
    {
        return $this->needStageService->createGroup($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function updateGroup(Request $request, string $id)
    {
        return $this->needStageService->updateGroup($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroyGroup(string $id)
    {
        $this->needStageService->destroyGroup((int) $id);
    }

    public function reorderGroups(Request $request)
    {
        $this->needStageService->reorderGroups((array) $request->input('ids', []));
    }

    /**
     * @throws Exception
     */
    public function storeStage(Request $request)
    {
        return $this->needStageService->createStage($request->toArray());
    }

    /**
     * @throws Exception
     */
    public function updateStage(Request $request, string $id)
    {
        return $this->needStageService->updateStage($request->toArray(), (int) $id);
    }

    /**
     * @throws Exception
     */
    public function destroyStage(string $id)
    {
        $this->needStageService->destroyStage((int) $id);
    }

    /**
     * @throws Exception
     */
    public function reorderStages(Request $request)
    {
        $this->needStageService->reorderStagesWithinGroup(
            (int) $request->input('group_id'),
            (array) $request->input('ids', []),
        );
    }

    /**
     * @throws Exception
     */
    public function moveStage(Request $request, string $id)
    {
        return $this->needStageService->moveStageToGroup(
            (int) $id,
            (int) $request->input('group_id'),
            $request->has('position') ? (int) $request->input('position') : null,
        );
    }
}
