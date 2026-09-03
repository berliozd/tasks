<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function __invoke(Request $request, string $document)
    {
        return Inertia::render('Documents/Show', [
            'documentId' => (int) $document,
        ]);
    }
}
