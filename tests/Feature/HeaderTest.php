<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\AegisCsp\CspModule;

use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

const SITE_POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; "
    . "img-src 'self' data: blob: https:; font-src 'self' data: https:; connect-src 'self' https:; media-src 'self' blob: https:; "
    . "frame-src 'self' https:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'";

const NOVA_POLICY = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; "
    . "img-src 'self' data: blob: https:; font-src 'self' data:; connect-src 'self'; media-src 'self' blob:; "
    . "frame-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";

it('sends nothing until the policy is enabled', function (): void {
    get('/page')->assertOk()->assertHeaderMissing('Content-Security-Policy');
});

it('sends the default site policy, its directives separated by "; "', function (): void {
    saveCsp(['enabled' => true]);

    get('/page')->assertOk()
        ->assertHeader('Content-Security-Policy', SITE_POLICY)
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('leaves Nova alone unless it is a target', function (): void {
    saveCsp(['enabled' => true]);

    get('/nova/page')->assertOk()->assertHeaderMissing('Content-Security-Policy');
    get('/nova/data')->assertOk()->assertHeaderMissing('Content-Security-Policy');
});

it('sends each part of the application its own policy', function (string $target, ?string $site, ?string $nova): void {
    saveCsp(['enabled' => true, 'apply_to' => $target]);

    $page = get('/page');
    $panel = get('/nova/page');

    $site === null ? $page->assertHeaderMissing('Content-Security-Policy') : $page->assertHeader('Content-Security-Policy', $site);
    $nova === null ? $panel->assertHeaderMissing('Content-Security-Policy') : $panel->assertHeader('Content-Security-Policy', $nova);
})->with([
    'site' => ['site', SITE_POLICY, null],
    'nova' => ['nova', null, NOVA_POLICY],
    'both' => ['both', SITE_POLICY, NOVA_POLICY],
]);

it('sends the Nova policy to the Aegis page itself', function (): void {
    saveCsp(['enabled' => true, 'apply_to' => 'nova']);

    Pest\Laravel\actingAs(admin());

    getJson('/nova-vendor/aegis/overview')->assertOk()->assertHeader('Content-Security-Policy', NOVA_POLICY);
});

it('only reports in report-only mode and names the report URI', function (): void {
    saveCsp(['enabled' => true, 'report_only' => true, 'report_uri' => 'https://reports.example.com/csp']);

    get('/page')->assertHeaderMissing('Content-Security-Policy')
        ->assertHeader('Content-Security-Policy-Report-Only', SITE_POLICY . '; report-uri https://reports.example.com/csp');
});

it('replaces a policy the response already has', function (): void {
    saveCsp(['enabled' => true]);

    get('/existing')->assertHeader('Content-Security-Policy', SITE_POLICY);
});

it('sets the header on streamed, file and JSON responses', function (string $uri): void {
    saveCsp(['enabled' => true, 'apply_to' => 'both']);

    expect(get($uri)->headers->has('Content-Security-Policy'))->toBeTrue();
})->with(['/stream', '/download', '/nova/data']);

it('only covers the web middleware group', function (): void {
    saveCsp(['enabled' => true]);

    get('/outside-web')->assertOk()->assertHeaderMissing('Content-Security-Policy');
});

it('applies the saved settings at once', function (): void {
    get('/page')->assertHeaderMissing('Content-Security-Policy');

    saveCsp(['enabled' => true, 'site_script_src' => sources("'self'")]);
    expect(get('/page')->headers->get('Content-Security-Policy'))->toContain("script-src 'self';");

    saveCsp(['enabled' => false]);
    get('/page')->assertHeaderMissing('Content-Security-Policy');
});

it('skips empty directives and sends nothing for an empty policy', function (): void {
    $empty = [];

    foreach (array_keys((new CspModule())->defaults()) as $key) {
        if (str_starts_with($key, 'site_')) {
            $empty[$key] = [];
        }
    }

    saveCsp(['enabled' => true, 'site_default_src' => sources("'none'"), 'site_img_src' => sources("'self'", '')] + $empty);
    get('/page')->assertHeader('Content-Security-Policy', "default-src 'none'; img-src 'self'");

    saveCsp(['enabled' => true] + $empty);
    get('/page')->assertOk()->assertHeaderMissing('Content-Security-Policy');
});

it('never sends a stored source the rules would refuse', function (): void {
    storeCsp([
        'enabled' => true,
        'report_uri' => 'https://example.com/r; script-src *',
        'site_default_src' => sources("'self'; script-src *", "'self'", "https:\r\nX-Injected: 1", 'self'),
        'site_script_src' => 'not a list',
        'site_img_src' => [['source' => ['nested']], 'row', ['source' => 'data:']],
    ]);

    $policy = (string)get('/page')->headers->get('Content-Security-Policy');

    expect($policy)->toStartWith("default-src 'self'; style-src")
        ->toContain('; img-src data:; ')
        ->and(preg_match('/script-src|X-Injected|report-uri/', $policy))->toBe(0);
});

it('keeps what Nova needs in a stored Nova policy', function (): void {
    storeCsp([
        'enabled' => true,
        'apply_to' => 'nova',
        'nova_script_src' => sources("'strict-dynamic'", "'sha256-B2yPHKaXnvFWtRChIbabYmUBFZdVfKKXHbWtWidDVF8='", 'https://cdn.example.com'),
        'nova_style_src' => sources("'none'"),
        'nova_connect_src' => [],
    ]);

    $policy = (string)get('/nova/page')->headers->get('Content-Security-Policy');

    expect($policy)->toContain("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.example.com;")
        ->toContain("style-src 'self' 'unsafe-inline';")
        ->toContain("connect-src 'self';")
        ->toContain("form-action 'self'");
});

it('falls back to a safe reading of a stored target and flags', function (): void {
    storeCsp([
        'enabled' => 'yes',
        'apply_to' => 'everywhere',
        'report_only' => [],
    ]);

    get('/page')->assertHeader('Content-Security-Policy');
    get('/nova/page')->assertHeaderMissing('Content-Security-Policy');
});

it('keeps answering when the settings cannot be read', function (): void {
    Aegis::module(new class () implements Module {
        public function key(): string
        {
            return CspModule::KEY;
        }

        public function label(): string
        {
            return 'Broken';
        }

        public function description(): string
        {
            return '';
        }

        public function defaults(): array
        {
            throw new RuntimeException('broken');
        }

        public function rules(): array
        {
            return [];
        }

        public function fields(): array
        {
            return [];
        }

        public function status(array $values): ?CheckResult
        {
            return null;
        }
    });

    get('/page')->assertOk()->assertSee('page')->assertHeaderMissing('Content-Security-Policy');
});

it('reads no database row on a request once the settings are cached', function (): void {
    saveCsp(['enabled' => true]);
    get('/page');

    $queries = 0;
    DB::listen(static function () use (&$queries): void {
        ++$queries;
    });

    get('/page')->assertHeader('Content-Security-Policy');
    get('/nova/page');

    expect($queries)->toBe(0);
});
