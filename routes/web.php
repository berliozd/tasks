<?php

use App\Http\Controllers\Auth\ProvidersCallbackController;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
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
        $tz = new DateTimeZone(auth()->user()->timezone ?? config('app.timezone'));
        $thisMorning = now($tz)->setHour(00)->setMinute(00)->setSecond(00)->subSeconds($tz->getOffset(now()));
        $tonight = now($tz)->setHour(23)->setMinute(59)->setSecond(59)->subSeconds($tz->getOffset(now()));
        return Inertia::render('Tasks', [
            'tasks' => DB::table('tasks')->where('user_id', auth()->user()->id)
                ->where(function (Builder $query) use ($thisMorning, $tonight) {
                    $query->where('scheduled_at', '>=', $thisMorning)
                        ->where('scheduled_at', '<=', $tonight);
                })
                ->orWhere(function (Builder $query) use ($thisMorning, $tonight) {
                    $query->where('scheduled_at', '<', $thisMorning)
                        ->where('completed_at', null);
                })
                ->orderBy('created_at')
                ->get(),

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