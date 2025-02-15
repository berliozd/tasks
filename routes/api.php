<?php

use App\Http\Controllers\Api\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::patch('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'update'])->name('tasks.update');
    Route::get('/tasks', [App\Http\Controllers\Api\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [App\Http\Controllers\Api\TaskController::class, 'store'])->name('tasks.store');
    Route::delete('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'destroy'])->name('tasks.delete');
    Route::post('/tasks/add-flag/{taskId}/{flagId}', [App\Http\Controllers\Api\TaskController::class, 'addFlag'])->name(
        'tasks.add.flag'
    );
    Route::post('/tasks/delete-flag/{taskId}/{flagId}', [App\Http\Controllers\Api\TaskController::class, 'deleteFlag'])
        ->name('tasks.delete.flag');

    Route::post('/task-progression/start/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'start'])
        ->name('task-progression.start');
    Route::post('/task-progression/stop/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'stop'])
        ->name('task-progression.stop');

    Route::get('/event', [EventController::class, 'index'])->name('event.index');

    Route::get('/flags', [App\Http\Controllers\Api\FlagController::class, 'index'])->name('flags.index');
    Route::patch('/flags/{id}', [App\Http\Controllers\Api\FlagController::class, 'update'])->name('flags.update');
    Route::post('/flags', [App\Http\Controllers\Api\FlagController::class, 'store'])->name('flags.store');
    Route::delete('/flags/{id}', [App\Http\Controllers\Api\FlagController::class, 'destroy'])->name('flags.delete');
});
