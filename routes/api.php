<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/ping', function (Request $request) {
    return response()->json(['user' => $request->user()->name]);
});
