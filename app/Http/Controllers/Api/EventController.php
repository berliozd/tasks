<?php

namespace App\Http\Controllers\Api;

use App\Events\UserNotLogged;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    public function index()
    {
        Log::info('index controller');
        try {
            UserNotLogged::dispatch('Dispatching UserNotLogged event from api controller');

        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
