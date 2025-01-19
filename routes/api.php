<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::patch('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'update'])->name('tasks.update');
    Route::get('/tasks', [App\Http\Controllers\Api\TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [App\Http\Controllers\Api\TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'show']);
    Route::delete('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'destroy'])->name('tasks.delete');

    Route::post('/task-progression/start/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'start'])
        ->name('task-progression.start');
    Route::post('/task-progression/stop/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'stop'])
        ->name('task-progression.stop');

});
