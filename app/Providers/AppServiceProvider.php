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
use App\Models\KnowledgeBaseArticle;
use App\Observers\KnowledgeBaseArticleObserver;
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
        if (str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        Model::automaticallyEagerLoadRelationships();
        Vite::useAggressivePrefetching();

        Date::use(CarbonImmutable::class);
        // Allow HTTP requests to OpenAI API for AI features
        Http::preventStrayRequests(false); // Disable for now to allow OpenAI API calls
        // TODO: Re-enable with proper allowlist once AI features are stable
        $this->app->singleton(EmailService::class, PostmarkEmailService::class);
        $this->app->singleton(\App\Services\FileUploadService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        KnowledgeBaseArticle::observe(KnowledgeBaseArticleObserver::class);
    }
}
