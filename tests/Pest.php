<?php

declare(strict_types=1);

use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\AegisCsp\CspModule;
use Wobqqq\AegisCsp\Tests\Fixtures\User;
use Wobqqq\AegisCsp\Tests\TestCase;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

function admin(): User
{
    return User::query()->create(['email' => 'admin@example.com', 'is_admin' => true, 'last_login_at' => now()]);
}

function editor(): User
{
    return User::query()->create(['email' => 'editor@example.com', 'is_admin' => false, 'last_login_at' => now()]);
}

/**
 * @return list<array{source: string}>
 */
function sources(string ...$sources): array
{
    return array_values(array_map(static fn (string $source): array => ['source' => $source], $sources));
}

/**
 * Saves the CSP section through the core, like the settings page does.
 *
 * @param array<string, mixed> $values
 *
 * @return array<string, mixed>
 */
function saveCsp(array $values): array
{
    return Aegis::save(CspModule::KEY, array_replace((new CspModule())->defaults(), $values));
}

/**
 * Writes a CSP row straight into the core's table, past the rules, as an old release or a hand edit could leave it.
 *
 * @param array<string, mixed> $values
 */
function storeCsp(array $values): void
{
    AegisSetting::query()->create(['section' => CspModule::KEY, 'values' => $values]);
}
