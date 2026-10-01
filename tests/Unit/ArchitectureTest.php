<?php

declare(strict_types=1);

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
    ->expect([Wobqqq\AegisCsp\Policy\CspSettings::class, Wobqqq\AegisCsp\Policy\Header::class])
    ->toBeFinal()
    ->toBeReadonly();

arch('enums back every shared code')
    ->expect('Wobqqq\AegisCsp\Enums')
    ->toBeStringBackedEnums();

arch('the module never queries the database itself')
    ->expect('Wobqqq\AegisCsp')
    ->not->toUse([Illuminate\Support\Facades\DB::class, Illuminate\Database\Eloquent\Model::class, Wobqqq\Aegis\Settings\AegisSetting::class]);

arch('the module opens no network connection')
    ->expect(['stream_socket_client', 'fsockopen', 'curl_init', 'file_get_contents', Illuminate\Support\Facades\Http::class])
    ->not->toBeUsedIn('Wobqqq\AegisCsp');
