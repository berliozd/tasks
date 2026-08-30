<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RecurrenceController;
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
    Route::get('/tasks/completed', [App\Http\Controllers\Api\TaskController::class, 'completed'])->name('tasks.completed');
    Route::get('/tasks/{id}/history', [App\Http\Controllers\Api\TaskController::class, 'history'])->name('tasks.history');
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

    Route::get('/recurrences', [RecurrenceController::class, 'index'])->name('recurrences.index');

    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->name('products.index');
    Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->name('products.store');
    Route::get('/products/tree', [App\Http\Controllers\Api\ProductController::class, 'tree'])->name('products.tree');
    Route::get('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'show'])->name('products.show');
    Route::patch('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->name('products.delete');

    Route::get('/directories', [App\Http\Controllers\Api\DirectoryController::class, 'index'])->name('directories.index');
    Route::post('/directories', [App\Http\Controllers\Api\DirectoryController::class, 'store'])->name('directories.store');
    Route::get('/directories/{id}', [App\Http\Controllers\Api\DirectoryController::class, 'show'])->name('directories.show');
    Route::patch('/directories/{id}', [App\Http\Controllers\Api\DirectoryController::class, 'update'])->name('directories.update');
    Route::delete('/directories/{id}', [App\Http\Controllers\Api\DirectoryController::class, 'destroy'])->name('directories.delete');
    Route::post('/directories/{id}/generate', [App\Http\Controllers\Api\DirectoryController::class, 'generate'])
        ->name('directories.generate');

    Route::get('/directories/{directoryId}/prospects', [App\Http\Controllers\Api\ProspectController::class, 'index'])
        ->name('prospects.index');
    Route::get('/directories/{directoryId}/prospects/tree', [App\Http\Controllers\Api\ProspectController::class, 'tree'])
        ->name('prospects.tree');
    Route::post('/directories/{directoryId}/prospects', [App\Http\Controllers\Api\ProspectController::class, 'store'])
        ->name('prospects.store');
    Route::get('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'show'])->name('prospects.show');
    Route::patch('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'update'])->name('prospects.update');
    Route::delete('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'destroy'])->name('prospects.delete');

    Route::get('/prospects/{prospectId}/actions', [App\Http\Controllers\Api\ProspectActionController::class, 'index'])
        ->name('prospect-actions.index');
    Route::post('/prospects/{prospectId}/actions', [App\Http\Controllers\Api\ProspectActionController::class, 'store'])
        ->name('prospect-actions.store');
    Route::patch('/prospect-actions/{id}', [App\Http\Controllers\Api\ProspectActionController::class, 'update'])
        ->name('prospect-actions.update');
    Route::delete('/prospect-actions/{id}', [App\Http\Controllers\Api\ProspectActionController::class, 'destroy'])
        ->name('prospect-actions.destroy');
    Route::post('/prospect-actions/{id}/send', [App\Http\Controllers\Api\ProspectActionController::class, 'send'])
        ->name('prospect-actions.send');

    Route::get('/directories/{directoryId}/email-templates', [App\Http\Controllers\Api\EmailTemplateController::class, 'index'])
        ->name('email-templates.index');
    Route::post('/directories/{directoryId}/email-templates', [App\Http\Controllers\Api\EmailTemplateController::class, 'store'])
        ->name('email-templates.store');
    Route::post('/directories/{directoryId}/email-templates/generate', [App\Http\Controllers\Api\EmailTemplateController::class, 'generate'])
        ->name('email-templates.generate');
    Route::get('/email-templates/{id}', [App\Http\Controllers\Api\EmailTemplateController::class, 'show'])
        ->name('email-templates.show');
    Route::patch('/email-templates/{id}', [App\Http\Controllers\Api\EmailTemplateController::class, 'update'])
        ->name('email-templates.update');
    Route::delete('/email-templates/{id}', [App\Http\Controllers\Api\EmailTemplateController::class, 'destroy'])
        ->name('email-templates.destroy');
});
