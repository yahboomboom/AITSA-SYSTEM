<?php

use App\Http\Controllers\Api\Admin\FacultyController;
use App\Http\Controllers\Api\Admin\ProgramController;
use App\Http\Controllers\Api\Admin\RoomController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\SectionController;
use App\Http\Controllers\Api\Admin\SubjectController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\MatriculationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymongoWebhookController;

// Public — no login required, since this is called by PayMongo's own
// servers, not by a logged-in user. The verifySignature() check inside
// the controller is what confirms the request is genuinely from PayMongo.
Route::post('/webhooks/paymongo', [PaymongoWebhookController::class, 'handle']);
Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/enrollment/context', [EnrollmentController::class, 'context']);
    Route::post('/enrollment', [EnrollmentController::class, 'store']);
    Route::get('/matriculation/context', [MatriculationController::class, 'context']);
    Route::post('/matriculation', [MatriculationController::class, 'store']);
});

// Curriculum-editing API — moved from Admin to Registrar, then from
// Registrar to the Dept Chair (who already owned section scheduling — see
// docs/superpowers/specs/2026-09-12-scheduling-to-chair-design.md — so
// Programs/Subjects content now lives with the same role). URL prefix kept
// as 'admin' to avoid churning every frontend call site in
// approver-curriculum-app.jsx.
Route::prefix('admin')->group(function () {
    Route::middleware(['auth:sanctum', 'role:chair'])->group(function () {
        Route::get('/programs', [ProgramController::class, 'index']);
        Route::get('/programs/{program}/subjects', [ProgramController::class, 'subjects']);
        Route::post('/settings/change-matriculation', [SettingController::class, 'changeMatriculation']);
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

        Route::post('/sections', [SectionController::class, 'store']);
        Route::put('/sections/{section}', [SectionController::class, 'update']);
        Route::delete('/sections/{section}', [SectionController::class, 'destroy']);
        Route::get('/faculty', [FacultyController::class, 'index']);
        Route::post('/faculty', [FacultyController::class, 'store']);
        Route::delete('/faculty/{user}', [FacultyController::class, 'destroy']);
        Route::get('/faculty/{user}/schedule', [FacultyController::class, 'schedule']);
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);
    });
});
