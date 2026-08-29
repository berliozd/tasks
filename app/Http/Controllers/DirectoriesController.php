<?php

namespace App\Http\Controllers;

use App\Services\DirectoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoriesController extends Controller
{
    public function __construct(private readonly DirectoryService $directoryService)
    {
    }

    public function __invoke(Request $request)
    {
        return Inertia::render(
            'Directories/Directories', [
                'directories' => $this->directoryService->getAll()->toArray(),
            ]
        );
    }
}
