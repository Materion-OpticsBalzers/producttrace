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
        Route::get('/serialise/{po}', 'list')->name('serialise.list');
        Route::post('/serialise/generate/{po}', 'generate')->name('serialise.generate');
        Route::post('/serialise/{order}', 'store')->name('serialise.store');
        Route::delete('/serialise/{order}', 'destroy')->name('serialise.destroy');
    });

    Route::controller(\App\Http\Controllers\Data\OrderController::class)->group(function() {
        Route::middleware('can:is-admin')->group(function() {
            Route::get('/orders/create', 'create')->name('orders.create');
            Route::post('/orders', 'store')->name('orders.store');
            Route::get('/orders/importtest', 'test')->name('orders.importtest');
        });

        Route::get('/orders/{order}', 'show')->name('orders.show');
    });

    Route::controller(\App\Http\Controllers\Data\WaferController::class)->group(function() {
        Route::get('/wafers/{wafer}', 'show')->name('wafer.show');
    });

    Route::get('/tests', \App\Http\Livewire\Backend\TestSection::class)->name('tests');

    Route::controller(\App\Http\Controllers\Data\QueryController::class)->group(function() {
        Route::get('/queries', 'index')->name('queries');
        Route::get('/queries/pareto', \App\Http\Livewire\Data\Queries\ParetoQuery::class)->name('queries.pareto');
        Route::get('/queries/cdol', \App\Http\Livewire\Data\Queries\CDOLQuery::class)->name('queries.cdol');
    });

    Route::controller(\App\Http\Controllers\Generic\BlockController::class)->group(function() {
        Route::get('/orders/{order}/{block}', 'show')->name('blocks.show');
    });

    Route::prefix('/admin')->middleware('can:is-admin')->group(function() {
        Route::controller(\App\Http\Controllers\Data\AdminController::class)->group(function() {
            Route::get('/', 'index')->name('admin.dashboard');
            Route::get('/users', 'users')->name('admin.users');
        });

        Route::controller(\App\Http\Controllers\Data\MappingController::class)->group(function() {
            Route::get('/mappings', 'index')->name('mappings.index');
            Route::post('/mappings', 'store')->name('mappings.store');
            Route::get('/mappings/{mapping}', 'show')->name('mappings.show');
            Route::delete('/mappings/{mapping}', 'destroy')->name('mappings.destroy');
        });
    });


    Route::get('/print/test', function() {
        return view('content.print.microscope-labels');
    });
});

require __DIR__.'/auth.php';

