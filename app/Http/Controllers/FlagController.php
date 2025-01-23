<?php

namespace App\Http\Controllers;

use App\Services\FlagService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FlagController extends Controller
{

    public function __construct(private readonly FlagService $flagService)
    {
    }

    public function __invoke(Request $request)
    {
        return Inertia::render(
            'Flags/Flags', [
                'flags' => $this->flagService->getAll()->toArray(),
            ]
        );
    }
}
