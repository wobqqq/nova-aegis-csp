<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisCsp\Checks\PolicyStrengthCheck;

final class CspServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CspService::class);
    }

    public function boot(Dispatcher $events): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis-csp');

        Aegis::module(new CspModule());
        Aegis::check($this->app->make(PolicyStrengthCheck::class));

        $events->listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if ($event->section === CspModule::KEY) {
                $this->app->make(CspService::class)->forget();
            }
        });
    }
}
