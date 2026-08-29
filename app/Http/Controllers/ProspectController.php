<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProspectController extends Controller
{
    public function __invoke(Request $request, string $directory, string $prospect)
    {
        return Inertia::render(
            'Directories/ProspectShow', [
                'directoryId' => (int) $directory,
                'prospectId' => (int) $prospect,
            ]
        );
    }
}
