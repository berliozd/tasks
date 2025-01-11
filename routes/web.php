<?php

use App\Http\Controllers\Auth\ProvidersCallbackController;
use App\Models\Task;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/tasks', function () {
        return Inertia::render('Tasks', [
            'tasks' => Task::all()->where('user_id', auth()->user()->id),
        ]);
    })->name('tasks');
});


// Service providers auth

// Github
Route::get('/auth/redirect-github', function () {
    return Socialite::driver('github')->redirect();
})->name('github_login');

// Google
Route::get('/auth/redirect-google', function () {
    return Socialite::driver('google')->redirect();
})->name('google_login');

Route::get(config('services.github.redirect'), ProvidersCallbackController::class);
Route::get(config('services.google.redirect'), ProvidersCallbackController::class);