<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Wobqqq\Aegis\AegisServiceProvider;
use Wobqqq\Aegis\Nova\AegisTool;
use Wobqqq\AegisCsp\CspServiceProvider;
use Wobqqq\AegisCsp\Tests\Fixtures\User;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Nova::$tools = [];
        Nova::tools([new AegisTool()]);

        Gate::define(AegisTool::GATE, static fn (User $user): bool => $user->is_admin);
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [\Inertia\ServiceProvider::class, NovaCoreServiceProvider::class, AegisServiceProvider::class, CspServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('aegis.users.model', User::class);
        $app['config']->set('aegis.audit.schedule', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../vendor/wobqqq/nova-aegis/database/migrations');
    }

    /**
     * @param Router $router
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(static function (Router $router): void {
            $router->get('/page', static fn (): string => 'page');
            $router->get('/nova/page', static fn (): string => 'nova');
            $router->get('/nova/data', static fn (): array => ['nova' => true]);
            $router->get('/stream', static fn (): StreamedResponse => response()->stream(static function (): void {
                echo 'chunk';
            }));
            $router->get('/download', static fn () => response()->download(__FILE__, 'file.php'));
            $router->get('/existing', static fn () => response('page')->header('Content-Security-Policy', 'default-src *'));
        });

        $router->get('/outside-web', static fn (): string => 'api');
    }
}
