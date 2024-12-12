<?php

use App\Http\Controllers\NotebookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group(['prefix' => '/notebook'], function () {
            Route::get('/', [NotebookController::class, 'index'])->name('notebook.index');
            Route::post('/', [NotebookController::class, 'store'])->name('notebook.store');
            Route::get('/{id}', [NotebookController::class, 'show'])->name('notebook.show');
            Route::put('/{id}', [NotebookController::class, 'update'])->name('notebook.update');
            Route::delete('/{id}', [NotebookController::class, 'destroy'])->name('notebook.destroy');
        });
    });
});
