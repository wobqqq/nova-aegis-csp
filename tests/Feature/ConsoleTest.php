<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\AegisCsp\CspModule;

use function Pest\Laravel\get;

it('turns the policy off from the console and keeps the directives', function (): void {
    saveCsp(['enabled' => true, 'apply_to' => 'both', 'site_script_src' => sources("'self'")]);

    expect(Artisan::call('aegis:csp:disable'))->toBe(0)
        ->and(Artisan::output())->toContain('The Content-Security-Policy is off.')
        ->and(Aegis::settings(CspModule::KEY))->toMatchArray(['enabled' => false, 'apply_to' => 'both', 'site_script_src' => sources("'self'")]);

    get('/page')->assertHeaderMissing('Content-Security-Policy');
});

it('stops sending the policy to Nova only', function (string $target, bool $enabled, string $after): void {
    saveCsp(['enabled' => true, 'apply_to' => $target]);

    expect(Artisan::call('aegis:csp:disable', ['--nova' => true]))->toBe(0)
        ->and(Aegis::settings(CspModule::KEY))->toMatchArray(['enabled' => $enabled, 'apply_to' => $after]);

    get('/nova/page')->assertHeaderMissing('Content-Security-Policy');
})->with([
    'both' => ['both', true, 'site'],
    'nova' => ['nova', false, 'nova'],
]);

it('recovers from a stored row the rules would refuse', function (): void {
    AegisSetting::query()->create(['section' => CspModule::KEY, 'values' => [
        'enabled' => true,
        'apply_to' => 'nova',
        'report_uri' => 'bad uri',
        'nova_script_src' => sources('bad source', "'none'"),
        'site_object_src' => sources("'none'", "'self'"),
        'site_img_src' => sources(...array_map(static fn (int $i): string => "h{$i}.example.com", range(1, 60))),
    ]]);

    expect(Artisan::call('aegis:csp:disable'))->toBe(0)
        ->and(Aegis::settings(CspModule::KEY))->toMatchArray(['enabled' => false, 'report_uri' => null])
        ->and(Aegis::settings(CspModule::KEY)['site_img_src'])->toHaveCount(CspModule::MAX_SOURCES);

    get('/nova/page')->assertHeaderMissing('Content-Security-Policy');
});
