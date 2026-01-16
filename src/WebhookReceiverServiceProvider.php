<?php

namespace ValueAdding\WebhookReceiver;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use ValueAdding\WebhookReceiver\Console\Commands\RegisterSourceCommand;
use ValueAdding\WebhookReceiver\Console\Commands\ListSourcesCommand;
use ValueAdding\WebhookReceiver\Console\Commands\RegenerateTokenCommand;
use ValueAdding\WebhookReceiver\Console\Commands\CleanupLogsCommand;

class WebhookReceiverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/webhook-receiver.php',
            'webhook-receiver'
        );
    }

    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/Config/webhook-receiver.php' => config_path('webhook-receiver.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/Database/Migrations/' => database_path('migrations'),
        ], 'migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        // Load routes with proper middleware
        $this->app['router']->group([], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        });

        $this->app['router']->group(['middleware' => 'web'], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'webhook-receiver');

        // Publish views
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/webhook-receiver'),
        ], 'views');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                RegisterSourceCommand::class,
                ListSourcesCommand::class,
                RegenerateTokenCommand::class,
                CleanupLogsCommand::class,
            ]);
        }

        // Schedule cleanup command
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('webhook:cleanup')->hourly();
        });
    }
}
