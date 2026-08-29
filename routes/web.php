<?php

use App\Http\Controllers\Auth\ProvidersCallbackController;
use App\Http\Controllers\CompletedTasksController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoriesController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\FlagController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\TaskController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/tasks', TaskController::class)->name('tasks');
    Route::get('/completed-tasks', CompletedTasksController::class)->name('completed-tasks');
    Route::get('/flags', FlagController::class)->name('flags');
    Route::get('/directories', DirectoriesController::class)->name('directories');
    Route::get('/directories/{directory}', DirectoryController::class)->name('directories.view');
    Route::get('/directories/{directory}/prospects/{prospect}', ProspectController::class)->name('prospects.view');

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
