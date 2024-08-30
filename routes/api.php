<?php

use App\Http\Controllers\BundleController;
use App\Http\Controllers\DirectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('directions', [DirectionController::class, 'index']);
    Route::get('bundles', [BundleController::class, 'index']);
});
