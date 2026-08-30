<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplatesController extends Controller
{
    public function __invoke(Request $request, string $directory)
    {
        return Inertia::render(
            'Directories/EmailTemplates', [
                'directoryId' => (int) $directory,
            ]
        );
    }
}
