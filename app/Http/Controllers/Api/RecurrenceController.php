<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recurrence;

class RecurrenceController extends Controller
{
    public function index()
    {
        return Recurrence::query()->orderBy('id')->get();
    }
}

