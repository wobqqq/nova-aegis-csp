<?php

declare(strict_types=1);

use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Enums\Status;
use Wobqqq\AegisCsp\Checks\PolicyStrengthCheck;
use Wobqqq\AegisCsp\CspModule;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;

function strength(): CheckResult
{
    return resolve(PolicyStrengthCheck::class)->run();
}

it('adds its line to the dashboard', function (array $values, Status $status, string $message): void {
    /** @var array<string, mixed> $values */
    $result = new CspModule()->status(array_replace(new CspModule()->defaults(), $values));

    expect($result->status)->toBe($status)->and($result->message)->toContain($message);
})->with([
    'off' => [[], Status::WARN, 'No Content-Security-Policy'],
    'on' => [['enabled' => true, 'apply_to' => 'both'], Status::PASS, 'enforced on the site and Nova'],
    'report only' => [['enabled' => true, 'report_only' => true, 'apply_to' => 'nova'], Status::WARN, 'only reports on Nova'],
]);

it('reports the site policy is not sent', function (array $values): void {
    /** @var array<string, mixed> $values */
    saveCsp($values);

    expect(strength()->status)->toBe(Status::INFO);
})->with([
    'off' => [['enabled' => false]],
    'nova only' => [['enabled' => true, 'apply_to' => 'nova']],
]);

it('warns about a policy that lets an injected script run', function (): void {
    saveCsp(['enabled' => true]);

    expect(strength()->status)->toBe(Status::WARN)
        ->and(strength()->message)->toContain("'unsafe-inline' https:");
});

it('falls back to default-src and names every missing restriction', function (): void {
    saveCsp([
        'enabled' => true,
        'site_default_src' => sources('*'),
        'site_script_src' => [],
        'site_object_src' => [],
        'site_base_uri' => [],
        'site_frame_ancestors' => [],
    ]);

    expect(strength()->message)->toContain('from *')
        ->toContain("object-src is not 'none'")
        ->toContain('base-uri is not set')
        ->toContain('frame-ancestors is not set');
});

it('passes a strict site policy', function (): void {
    saveCsp(['enabled' => true, 'site_script_src' => sources("'self'", 'https://cdn.example.com')]);

    expect(strength()->status)->toBe(Status::PASS);
});

it('runs with the core checks on the Aegis overview', function (): void {
    saveCsp(['enabled' => true]);
    actingAs(admin());

    getJson('/nova-vendor/aegis/overview')->assertOk()
        ->assertJsonFragment(['key' => PolicyStrengthCheck::KEY])
        ->assertJsonFragment(['key' => CspModule::KEY, 'status' => 'pass']);
});
