<?php

namespace App\Providers;

use App\Commands\InitializeCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['logging.channels.single.path' => \Phar::running()
            ? dirname(\Phar::running(false)) . '/logs/modmanager.log'
            : storage_path('logs/modmanager.log')
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(\Illuminate\Encryption\EncryptionServiceProvider::class);
    }
}
