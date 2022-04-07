<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function() {
    Route::get('/', [\App\Http\Controllers\Controller::class, 'index'])->name('dashboard');

    Route::post('/tokens/create', [\App\Http\Controllers\API\ApiController::class, 'createToken'])->name('tokens.create');

    Route::controller(\App\Http\Controllers\Data\SerialController::class)->group(function() {
        Route::get('/serialise', 'index')->name('serialise');
        Route::post('/serialise', 'search')->name('serialise.search');
        Route::post('/serialise/{order}', 'store')->name('serialise.store');
        Route::delete('/serialise/{order}', 'destroy')->name('serialise.destroy');
    });

    Route::controller(\App\Http\Controllers\Data\OrderController::class)->group(function() {
        Route::get('/orders/create', 'create')->name('orders.create');
        Route::post('/orders/create', 'store')->name('orders.store');
        Route::get('/orders/{order}', 'show')->name('orders.show');
    });

    Route::controller(\App\Http\Controllers\Data\WaferController::class)->group(function() {
        Route::get('/wafers/{wafer}', 'show')->name('wafer.show');
    });

    Route::controller(\App\Http\Controllers\Generic\BlockController::class)->group(function() {
        Route::get('/orders/{order}/{block}', 'show')->name('blocks.show');
    });
});

require __DIR__.'/auth.php';

