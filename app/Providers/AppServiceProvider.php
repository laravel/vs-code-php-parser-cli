<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (\Phar::running()) {
            $dir = dirname(\Phar::running(false)) . '/logs';
            File::ensureDirectoryExists($dir);
            $path = $dir . '/parser.log';
        } else {
            $path = storage_path('logs/parser.log');
        }

        config([
            'logging.channels.single.path' => $path,
        ]);
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }
}
