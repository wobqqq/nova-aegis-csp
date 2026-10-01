<?php

declare(strict_types=1);
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Wobqqq\Aegis\Checks\CheckRegistry;
use Wobqqq\Aegis\Checks\CheckRunner;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\AegisCsp\Policy\CspSettings;
use Wobqqq\AegisCsp\Policy\Header;

arch('every file declares strict types')
    ->expect('Wobqqq\AegisCsp')
    ->toUseStrictTypes();

arch('every class is final')
    ->expect('Wobqqq\AegisCsp')
    ->classes()
    ->toBeFinal();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('value objects are immutable')
    ->expect([CspSettings::class, Header::class])
    ->toBeFinal()
    ->toBeReadonly();

arch('enums back every shared code')
    ->expect('Wobqqq\AegisCsp\Enums')
    ->toBeStringBackedEnums();

arch('the module never queries the database itself')
    ->expect('Wobqqq\AegisCsp')
    ->not->toUse([DB::class, Model::class, AegisSetting::class]);

arch('the module reaches the core only through its public API')
    ->expect('Wobqqq\\AegisCsp')
    ->not->toUse([SettingsRepository::class, 'Wobqqq\\Aegis\\Modules', CheckRegistry::class, CheckRunner::class]);

arch('the module opens no network connection')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents', Http::class])
    ->not->toBeUsedIn('Wobqqq\AegisCsp');
