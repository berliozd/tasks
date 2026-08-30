<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function __invoke(Request $request, string $directory, string $template)
    {
        return Inertia::render(
            'Directories/EmailTemplateShow', [
                'directoryId' => (int) $directory,
                'templateId' => (int) $template,
            ]
        );
    }
}
