<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FutureTasksController extends Controller
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Tasks/Future');
    }
}
