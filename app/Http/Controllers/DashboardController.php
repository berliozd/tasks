<?php

namespace App\Http\Controllers;

use App\Services\DocumentService;
use App\Services\ProspectionSummaryService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly ProspectionSummaryService $prospectionSummaryService,
        private readonly DocumentService $documentService,
    ) {
    }

    public function __invoke(Request $request)
    {
        return Inertia::render('Dashboard', [
            'todayTasks' => $this->taskService->getTodayTasks()->toArray(),
            'lateTasks' => $this->taskService->getLateTasks()->toArray(),
            'completedTodayTasks' => $this->taskService->getCompletedTodayTasks()->toArray(),
            'prospection' => $this->prospectionSummaryService->getSummary(auth()->user()->currentTeam->id),
            'documents' => $this->documentService->getDashboardSummary(),
        ]);
    }
}
