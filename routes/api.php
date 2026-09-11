<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\RecurrenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/teams/{team}/invitation-links', [App\Http\Controllers\Api\TeamInvitationController::class, 'links'])
        ->name('teams.invitation-links');
    Route::patch('/daily-report-settings', [App\Http\Controllers\Api\DailyReportSettingsController::class, 'update'])
        ->name('daily-report-settings.update');
    Route::post('/feature-requests', [App\Http\Controllers\Api\FeatureRequestController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('feature-requests.store');
    Route::get('/team-features', [App\Http\Controllers\Api\TeamFeatureController::class, 'index'])
        ->name('team-features.index');
    Route::patch('/team-features', [App\Http\Controllers\Api\TeamFeatureController::class, 'update'])
        ->name('team-features.update');
    Route::middleware('feature:tasks')->group(function () {
        Route::patch('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'update'])->name('tasks.update');
        Route::get('/tasks', [App\Http\Controllers\Api\TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [App\Http\Controllers\Api\TaskController::class, 'store'])->name('tasks.store');
        Route::delete('/tasks/{id}', [App\Http\Controllers\Api\TaskController::class, 'destroy'])->name('tasks.delete');
        Route::get('/tasks/completed', [App\Http\Controllers\Api\TaskController::class, 'completed'])->name('tasks.completed');
        Route::get('/tasks/future', [App\Http\Controllers\Api\TaskController::class, 'future'])->name('tasks.future');
        Route::get('/tasks/{id}/history', [App\Http\Controllers\Api\TaskController::class, 'history'])->name('tasks.history');
        Route::post('/tasks/add-flag/{taskId}/{flagId}', [App\Http\Controllers\Api\TaskController::class, 'addFlag'])->name(
            'tasks.add.flag'
        );
        Route::post('/tasks/delete-flag/{taskId}/{flagId}', [App\Http\Controllers\Api\TaskController::class, 'deleteFlag'])
            ->name('tasks.delete.flag');
        Route::post('/tasks/{taskId}/links', [App\Http\Controllers\Api\TaskController::class, 'addLink'])->name('tasks.links.add');
        Route::post('/tasks/{taskId}/links/reorder', [App\Http\Controllers\Api\TaskController::class, 'reorderLinks'])
            ->name('tasks.links.reorder');
        Route::delete('/tasks/{taskId}/links/{linkId}', [App\Http\Controllers\Api\TaskController::class, 'deleteLink'])
            ->name('tasks.links.delete');
        Route::post('/tasks/reorder', [App\Http\Controllers\Api\TaskController::class, 'reorder'])->name('tasks.reorder');

        Route::post('/task-progression/start/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'start'])
            ->name('task-progression.start');
        Route::post('/task-progression/stop/{id}', [App\Http\Controllers\Api\TaskProgressionController::class, 'stop'])
            ->name('task-progression.stop');
    });

    Route::get('/event', [EventController::class, 'index'])->name('event.index');

    Route::get('/flags', [App\Http\Controllers\Api\FlagController::class, 'index'])->name('flags.index');
    Route::patch('/flags/{id}', [App\Http\Controllers\Api\FlagController::class, 'update'])->name('flags.update');
    Route::post('/flags', [App\Http\Controllers\Api\FlagController::class, 'store'])->name('flags.store');
    Route::delete('/flags/{id}', [App\Http\Controllers\Api\FlagController::class, 'destroy'])->name('flags.delete');

    Route::get('/recurrences', [RecurrenceController::class, 'index'])->name('recurrences.index');

    Route::middleware('feature:prospection')->group(function () {
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
        Route::post('/directories/{id}/linkedin-search', [App\Http\Controllers\Api\DirectoryController::class, 'searchLinkedInProfiles'])
            ->name('directories.linkedin-search');
        Route::post('/directories/{id}/company-search', [App\Http\Controllers\Api\DirectoryController::class, 'searchCompanies'])
            ->name('directories.company-search');

        Route::get('/directories/{directoryId}/prospects', [App\Http\Controllers\Api\ProspectController::class, 'index'])
            ->name('prospects.index');
        Route::get('/directories/{directoryId}/prospects/tree', [App\Http\Controllers\Api\ProspectController::class, 'tree'])
            ->name('prospects.tree');
        Route::post('/directories/{directoryId}/prospects', [App\Http\Controllers\Api\ProspectController::class, 'store'])
            ->name('prospects.store');
        Route::get('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'show'])->name('prospects.show');
        Route::patch('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'update'])->name('prospects.update');
        Route::delete('/prospects/{id}', [App\Http\Controllers\Api\ProspectController::class, 'destroy'])->name('prospects.delete');
        Route::post('/prospects/{id}/find-email', [App\Http\Controllers\Api\ProspectController::class, 'findEmail'])
            ->name('prospects.find-email');

        Route::get('/prospect-actions/planned', [App\Http\Controllers\Api\ProspectActionController::class, 'planned'])
            ->name('prospect-actions.planned');
        Route::get('/prospect-actions/last-sent', [App\Http\Controllers\Api\ProspectActionController::class, 'lastSent'])
            ->name('prospect-actions.last-sent');
        Route::get('/prospect-actions/activity', [App\Http\Controllers\Api\ProspectActionController::class, 'activityOverTime'])
            ->name('prospect-actions.activity');
        Route::get('/prospects/{prospectId}/actions', [App\Http\Controllers\Api\ProspectActionController::class, 'index'])
            ->name('prospect-actions.index');
        Route::post('/prospects/{prospectId}/actions', [App\Http\Controllers\Api\ProspectActionController::class, 'store'])
            ->name('prospect-actions.store');
        Route::patch('/prospect-actions/{id}', [App\Http\Controllers\Api\ProspectActionController::class, 'update'])
            ->name('prospect-actions.update');
        Route::delete('/prospect-actions/{id}', [App\Http\Controllers\Api\ProspectActionController::class, 'destroy'])
            ->name('prospect-actions.destroy');
        Route::post('/prospect-actions/{id}/send', [App\Http\Controllers\Api\ProspectActionController::class, 'send'])
            ->middleware('throttle:10,1')
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

    Route::middleware('feature:documents')->group(function () {
        Route::get('/documents', [App\Http\Controllers\Api\DocumentController::class, 'index'])->name('documents.index');
        Route::post('/documents', [App\Http\Controllers\Api\DocumentController::class, 'store'])->name('documents.store');
        Route::get('/documents/{id}', [App\Http\Controllers\Api\DocumentController::class, 'show'])->name('documents.show');
        Route::patch('/documents/{id}', [App\Http\Controllers\Api\DocumentController::class, 'update'])->name('documents.update');
        Route::patch('/documents/{id}/flags', [App\Http\Controllers\Api\DocumentController::class, 'updateFlags'])
            ->name('documents.flags.update');
        Route::post('/documents/{id}/rescan-flags', [App\Http\Controllers\Api\DocumentController::class, 'rescanFlags'])
            ->name('documents.flags.rescan');
        Route::post('/documents/{id}/images', [App\Http\Controllers\Api\DocumentController::class, 'uploadImage'])
            ->name('documents.images.store');
        Route::delete('/documents/{id}', [App\Http\Controllers\Api\DocumentController::class, 'destroy'])->name('documents.destroy');

        Route::get('/document-flags', [App\Http\Controllers\Api\DocumentFlagController::class, 'index'])->name('document-flags.index');
        Route::delete('/document-flags/{id}', [App\Http\Controllers\Api\DocumentFlagController::class, 'destroy'])
            ->name('document-flags.destroy');
    });

    Route::middleware('feature:needs')->group(function () {
        Route::get('/needs', [App\Http\Controllers\Api\NeedController::class, 'index'])->name('needs.index');
        Route::post('/needs', [App\Http\Controllers\Api\NeedController::class, 'store'])->name('needs.store');
        Route::post('/needs/reorder', [App\Http\Controllers\Api\NeedController::class, 'reorder'])->name('needs.reorder');
        Route::get('/needs/{id}', [App\Http\Controllers\Api\NeedController::class, 'show'])->name('needs.show');
        Route::patch('/needs/{id}', [App\Http\Controllers\Api\NeedController::class, 'update'])->name('needs.update');
        Route::patch('/needs/{id}/stage', [App\Http\Controllers\Api\NeedController::class, 'moveStage'])->name('needs.stage');
        Route::post('/needs/{id}/notes', [App\Http\Controllers\Api\NeedController::class, 'addNote'])->name('needs.notes.store');
        Route::delete('/needs/{id}', [App\Http\Controllers\Api\NeedController::class, 'destroy'])->name('needs.destroy');

        Route::get('/need-stage-groups', [App\Http\Controllers\Api\NeedStageController::class, 'index'])
            ->name('need-stage-groups.index');
        Route::post('/need-stage-groups', [App\Http\Controllers\Api\NeedStageController::class, 'storeGroup'])
            ->name('need-stage-groups.store');
        Route::post('/need-stage-groups/reorder', [App\Http\Controllers\Api\NeedStageController::class, 'reorderGroups'])
            ->name('need-stage-groups.reorder');
        Route::patch('/need-stage-groups/{id}', [App\Http\Controllers\Api\NeedStageController::class, 'updateGroup'])
            ->name('need-stage-groups.update');
        Route::delete('/need-stage-groups/{id}', [App\Http\Controllers\Api\NeedStageController::class, 'destroyGroup'])
            ->name('need-stage-groups.destroy');

        Route::post('/need-stages', [App\Http\Controllers\Api\NeedStageController::class, 'storeStage'])
            ->name('need-stages.store');
        Route::post('/need-stages/reorder', [App\Http\Controllers\Api\NeedStageController::class, 'reorderStages'])
            ->name('need-stages.reorder');
        Route::patch('/need-stages/{id}', [App\Http\Controllers\Api\NeedStageController::class, 'updateStage'])
            ->name('need-stages.update');
        Route::patch('/need-stages/{id}/move', [App\Http\Controllers\Api\NeedStageController::class, 'moveStage'])
            ->name('need-stages.move');
        Route::delete('/need-stages/{id}', [App\Http\Controllers\Api\NeedStageController::class, 'destroyStage'])
            ->name('need-stages.destroy');
    });
});
