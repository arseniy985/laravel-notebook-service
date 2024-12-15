<?php

use App\Http\Controllers\API\v1\NotebookController;
use Illuminate\Support\Facades\Route;
use L5Swagger\Http\Controllers\SwaggerController;

// const INT_ROUTE = '[0-9]+';

Route::middleware(['api'])->group(function () {
    Route::prefix('api/v1/notebook')->group(function () {
        Route::get('/', [NotebookController::class, 'index'])->name('notebook.index');
        Route::post('/', [NotebookController::class, 'store'])->name('notebook.store');
        Route::get('/{id}', [NotebookController::class, 'show'])
            ->where('id', '[0-9]+')
            ->name('notebook.show');
        Route::match(['put', 'patch'], '/{id}', [NotebookController::class, 'update'])
            ->where('id', '[0-9]+')
            ->name('notebook.update');
        Route::delete('/{id}', [NotebookController::class, 'destroy'])
            ->where('id', '[0-9]+')
            ->name('notebook.destroy');
    });
});
