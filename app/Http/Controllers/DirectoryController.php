<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DirectoryController extends Controller
{
    public function __invoke(Request $request, string $directory)
    {
        return Inertia::render(
            'Directories/Show', [
                'directoryId' => (int) $directory,
            ]
        );
    }
}
