<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Enums\FieldType;
use Wobqqq\AegisCsp\CspModule;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('registers its section with the core, off by default', function (): void {
    expect(Aegis::settings(CspModule::KEY))->toMatchArray(['enabled' => false, 'report_only' => false, 'apply_to' => 'site'])
        ->and(Aegis::settings(CspModule::KEY)['nova_script_src'])->toBe(sources("'self'", "'unsafe-inline'", "'unsafe-eval'"));
});

it('describes a field for every setting', function (): void {
    $module = new CspModule();
    $names = array_map(static fn (Wobqqq\Aegis\Settings\Field $field): string => $field->name, $module->fields());

    expect($names)->toEqualCanonicalizing(array_keys($module->defaults()))
        ->and(array_keys($module->defaults()))->toEqualCanonicalizing(array_keys(array_filter(
            $module->rules(),
            static fn (string $key): bool => !str_contains($key, '.'),
            ARRAY_FILTER_USE_KEY,
        )));

    $table = collect($module->fields())->firstWhere('name', 'site_script_src');
    expect($table?->type)->toBe(FieldType::TABLE)->and($table?->label)->toBe('Site: script-src');
});

it('saves a valid policy', function (): void {
    $saved = saveCsp([
        'enabled' => true,
        'apply_to' => 'both',
        'report_uri' => '/csp-report',
        'site_script_src' => sources("'self'", "'sha256-B2yPHKaXnvFWtRChIbabYmUBFZdVfKKXHbWtWidDVF8='", 'https://cdn.example.com'),
    ]);

    expect($saved)->toMatchArray(['enabled' => true, 'apply_to' => 'both', 'report_uri' => '/csp-report'])
        ->and(Aegis::settings(CspModule::KEY)['site_script_src'])->toHaveCount(3);
});

it('refuses a value that could add a directive or break the header', function (array $values): void {
    /** @var array<string, mixed> $values */
    saveCsp($values);
})->throws(ValidationException::class)->with([
    'semicolon' => [['site_script_src' => sources("'self'; script-src *")]],
    'comma' => [['site_img_src' => sources('data:,https:')]],
    'space' => [['site_default_src' => sources("'self' https:")]],
    'newline' => [['nova_img_src' => sources("'self'", 'data:', "https:\r\nX-Injected: 1")]],
    'nonce' => [['site_script_src' => sources("'nonce-r4nd0m'")]],
    'unquoted keyword' => [['site_object_src' => sources('none')]],
    'none with others' => [['site_object_src' => sources("'none'", "'self'")]],
    'not a list' => [['site_script_src' => "'self'"]],
    'unknown column' => [['site_script_src' => [['source' => "'self'", 'other' => 'x']]]],
    'too many' => [['site_img_src' => sources(...array_map(static fn (int $i): string => "h{$i}.example.com", range(1, 51)))]],
    'report URI' => [['report_uri' => 'https://example.com/r; script-src *']],
    'target' => [['apply_to' => 'everywhere']],
    'flag' => [['report_only' => 'sometimes']],
]);

it('refuses a Nova policy Nova cannot work with', function (string $key, array $rows, string $message): void {
    $errors = [];

    try {
        saveCsp([$key => $rows]);
    } catch (ValidationException $e) {
        $errors = $e->errors();
    }

    expect($errors)->toHaveKey($key)
        ->and((string)json_encode($errors[$key] ?? null))->toContain($message);
})->with([
    'no unsafe-eval' => ['nova_script_src', sources("'self'", "'unsafe-inline'"), "Nova cannot work without 'unsafe-eval'"],
    'empty scripts' => ['nova_script_src', [], "'self' 'unsafe-inline' 'unsafe-eval'"],
    'strict-dynamic' => ['nova_script_src', sources("'self'", "'unsafe-inline'", "'unsafe-eval'", "'strict-dynamic'"), "Nova cannot work with 'strict-dynamic'"],
    'style hash' => ['nova_style_src', sources("'self'", "'unsafe-inline'", "'sha256-B2yPHKaXnvFWtRChIbabYmUBFZdVfKKXHbWtWidDVF8='"), 'Nova cannot work with'],
    'no API' => ['nova_connect_src', sources('https://api.example.com'), "Nova cannot work without 'self'"],
]);

it('lets the site policy be as strict as the administrator wants', function (): void {
    saveCsp(['site_script_src' => sources("'self'"), 'site_style_src' => sources("'self'"), 'site_connect_src' => sources("'none'")]);

    expect(Aegis::settings(CspModule::KEY)['site_connect_src'])->toBe(sources("'none'"));
});

it('saves through the Aegis page and shows the errors per row', function (): void {
    actingAs(admin());

    $values = array_replace((new CspModule())->defaults(), ['enabled' => true, 'site_script_src' => sources("'self'", 'bad source')]);

    putJson('/nova-vendor/aegis/settings/csp', ['values' => $values])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['site_script_src.1.source']);

    $values['site_script_src'] = sources("'self'");

    putJson('/nova-vendor/aegis/settings/csp', ['values' => $values])->assertOk()->assertJsonPath('values.enabled', true);

    getJson('/nova-vendor/aegis/settings')->assertOk()->assertJsonFragment(['key' => 'csp', 'label' => 'Content Security Policy']);
});

it('keeps the section away from users the gate refuses', function (): void {
    actingAs(editor());

    putJson('/nova-vendor/aegis/settings/csp', ['values' => ['enabled' => true]])->assertForbidden();

    expect(Aegis::settings(CspModule::KEY)['enabled'])->toBeFalse();
});
