<?php

namespace App\Providers;

use App\Http\Interfaces\API\v1\NotebookServiceInterface;
use App\Http\Services\API\v1\NotebookService;
use Illuminate\Support\ServiceProvider;

class NotebookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(NotebookServiceInterface::class, NotebookService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
