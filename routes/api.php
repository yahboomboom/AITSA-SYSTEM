<?php

use App\Http\Controllers\Api\EnrollmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::get('/enrollment/context', [EnrollmentController::class, 'context']);
    Route::post('/enrollment', [EnrollmentController::class, 'store']);
});
