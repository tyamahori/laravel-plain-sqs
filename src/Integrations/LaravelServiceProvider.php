<?php

namespace Tyamahori\PlainSqs\Integrations;

use Tyamahori\PlainSqs\Sqs\Connector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\Events\JobProcessed;

/**
 * Class CustomQueueServiceProvider
 * @package App\Providers
 */
class LaravelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/sqs-plain.php' => config_path('sqs-plain.php')
        ]);

        Queue::after(static function (JobProcessed $event): void {
            $event->job->delete();
        });
    }

    public function register(): void
    {
         $this->app->booted(function (): void {
            $this->app['queue']->extend('sqs-plain', static function (): Connector {
                return new Connector();
            });
        });
    }
}
