<?php

use App\Http\Controllers\Api\Admin\ProgramController;
use App\Http\Controllers\Api\Admin\SectionController;
use App\Http\Controllers\Api\Admin\SubjectController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\MatriculationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/enrollment/context', [EnrollmentController::class, 'context']);
    Route::post('/enrollment', [EnrollmentController::class, 'store']);
    Route::get('/matriculation/context', [MatriculationController::class, 'context']);
    Route::post('/matriculation', [MatriculationController::class, 'store']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/programs', [ProgramController::class, 'index']);
    Route::get('/programs/{program}/subjects', [ProgramController::class, 'subjects']);
    Route::post('/subjects', [SubjectController::class, 'store']);
    Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);
    Route::post('/sections', [SectionController::class, 'store']);
    Route::put('/sections/{section}', [SectionController::class, 'update']);
    Route::delete('/sections/{section}', [SectionController::class, 'destroy']);
});
