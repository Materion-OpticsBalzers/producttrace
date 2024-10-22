<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

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
    Volt::route('/', 'data.dashboard')->name('dashboard');

    Route::post('/tokens/create', [\App\Http\Controllers\API\ApiController::class, 'createToken'])->name('tokens.create');

    Volt::route('/serialise', 'data.serialize')->name('serialise');
    Volt::route('/serialise/{po}', 'data.serial-list')->name('serialise.list');

    Volt::route('/changelog', 'frontend.changelog-list')->name('changelog');

    Route::controller(\App\Http\Controllers\Data\OrderController::class)->group(function() {
        Route::middleware('can:is-admin')->group(function() {
            Route::get('/orders/create', 'create')->name('orders.create');
            Route::post('/orders', 'store')->name('orders.store');
            Route::post('/orders/update', 'update')->name('orders.update');
            Route::get('/orders/importtest', 'test')->name('orders.importtest');
        });

        Volt::route('/orders/{order}', 'data.show-order')->name('orders.show');
        Volt::route('/orders/{order}/{block}', 'data.show-block')->name('blocks.show');
    });

    Route::controller(\App\Http\Controllers\Data\WaferController::class)->group(function() {
        Route::get('/wafers/{wafer}', 'show')->name('wafer.show');
    });

    Route::get('/tests', \App\Http\Livewire\Backend\TestSection::class)->name('tests');

    Volt::route('/coa', 'backend.coa-list')->name('coa');
    Volt::route('/coa/{order}', 'backend.coa-show')->name('coa.show');

    Route::controller(\App\Http\Controllers\Data\QueryController::class)->group(function() {
        Route::get('/queries', 'index')->name('queries');
        Route::get('/queries/pareto', \App\Http\Livewire\Data\Queries\ParetoQuery::class)->name('queries.pareto');
        Route::get('/queries/cdol', \App\Http\Livewire\Data\Queries\CDOLQuery::class)->name('queries.cdol');
        Route::get('/queries/exports', \App\Http\Livewire\Data\Queries\Exports::class)->name('queries.exports');
    });



    Route::prefix('/admin')->middleware('can:is-admin')->group(function() {
        Route::get('/', [\App\Http\Controllers\Data\AdminController::class, 'index'])->name('admin.dashboard');
        Volt::route('/users', 'data.user-manager')->name('admin.users');

        Volt::route('/formats', 'backend.format-manager')->name('admin.formats');

        Route::get('/orders', \App\Http\Livewire\Backend\OrderManager::class)->name('admin.orders');

        Route::controller(\App\Http\Controllers\Data\MappingController::class)->group(function() {
            Route::get('/mappings', 'index')->name('mappings.index');
            Route::post('/mappings', 'store')->name('mappings.store');
            Volt::route('/mappings/{mapping}', 'data.mapping-editor')->name('mappings.show');
            Route::delete('/mappings/{mapping}', 'destroy')->name('mappings.destroy');
        });
    });


    Route::get('/print/test', function() {
        return view('content.print.microscope-labels');
    });
});

require __DIR__.'/auth.php';

