<?php

namespace App\Providers;

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

        if (!$this->app->environment('local')) {
            URL::forceScheme('https');
        }

        Model::automaticallyEagerLoadRelationships();
        Vite::useAggressivePrefetching();
        Date::use(CarbonImmutable::class);
        Http::preventStrayRequests();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
