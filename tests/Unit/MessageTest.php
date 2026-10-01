<?php

declare(strict_types=1);

use Wobqqq\AegisCsp\Support\Message;

it('returns the translated line', function (): void {
    expect(Message::get('aegis-csp::csp.label'))->toBe(__('aegis-csp::csp.label'))
        ->and(Message::get('aegis-csp::csp.label'))->not->toBe('aegis-csp::csp.label');
});

it('answers the key itself for a key that names a group of lines', function (): void {
    expect(Message::get('aegis-csp::csp.fields'))->toBe('aegis-csp::csp.fields');
});
