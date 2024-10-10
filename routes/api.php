<?php

use App\Http\Controllers\BundleController;
use App\Http\Controllers\DirectionController;
use App\Models\StockMarket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use MongoDB\Client;
use MongoDB\Laravel\Connection;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('test', function () {
    return StockMarket::get();
});

Route::get('clearmongo', function () {
    $connection = DB::connection('mongodb');

    $collections = $connection->listCollections();

    foreach ($collections as $collection) {
        $connection->table($collection->getName())->delete();
    }
});

// Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('directions', [DirectionController::class, 'index']);
    Route::get('bundles', [BundleController::class, 'index']);
// });
