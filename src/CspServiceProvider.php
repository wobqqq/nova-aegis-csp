<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisCsp\Checks\PolicyStrengthCheck;
use Wobqqq\AegisCsp\Console\DisableCommand;
use Wobqqq\AegisCsp\Http\Middleware\ContentSecurityPolicy;

final class CspServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CspService::class);
    }

    public function boot(Router $router, Dispatcher $events): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis-csp');

        Aegis::module(new CspModule());
        Aegis::check($this->app->make(PolicyStrengthCheck::class));

        $events->listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if ($event->section === CspModule::KEY) {
                $this->app->make(CspService::class)->forget();
            }
        });

        $router->pushMiddlewareToGroup('web', ContentSecurityPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([DisableCommand::class]);
        }
    }
}
