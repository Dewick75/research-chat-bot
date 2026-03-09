<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Vercel Deployment: Read-Only Filesystem Fix ──
        // Vercel only allows writing to /tmp, so redirect all writable paths there
        if (env('VERCEL') || env('VERCEL_ENV')) {
            // Views compiled cache
            config(['view.compiled' => '/tmp/storage/framework/views']);

            // Session — use cookies instead of files (no filesystem access)
            config(['session.driver' => 'cookie']);

            // Logging — use stderr so logs appear in Vercel's function logs
            config(['logging.default' => 'stderr']);

            // Cache — use array driver (in-memory, per-request)
            config(['cache.default' => 'array']);

            // Ensure the tmp directories exist
            $dirs = [
                '/tmp/storage/framework/views',
                '/tmp/storage/framework/cache',
                '/tmp/storage/logs',
            ];
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
        }
    }
}
