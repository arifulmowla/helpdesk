<?php

namespace App\Providers;

use App\Services\Email\EmailService;
use App\Services\Email\PostmarkEmailService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Http;
use DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
        Model::shouldBeStrict();

        // Force HTTPS when APP_URL is HTTPS
        //if (str_starts_with(env('APP_URL', ''), 'https://')) {
            URL::forceScheme('https');
        //}

        Model::automaticallyEagerLoadRelationships();
        Vite::useAggressivePrefetching();

        Date::use(CarbonImmutable::class);
        Http::preventStrayRequests();
        $this->app->singleton(EmailService::class, PostmarkEmailService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
