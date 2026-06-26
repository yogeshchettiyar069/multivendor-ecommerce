<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Behind a TLS-terminating proxy (e.g. Render), requests reach the app as
        // plain HTTP, so generated asset/route URLs would default to http:// and
        // get blocked as mixed content on the https page. When APP_URL is https,
        // force every generated URL to https regardless of the proxied scheme.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
