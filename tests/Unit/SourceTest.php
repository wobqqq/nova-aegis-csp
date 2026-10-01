<?php

declare(strict_types=1);

use Wobqqq\AegisCsp\Policy\ReportUri;
use Wobqqq\AegisCsp\Policy\Source;

it('accepts one source expression', function (string $source): void {
    expect(Source::isValid($source))->toBeTrue();
})->with([
    "'self'",
    "'none'",
    "'unsafe-inline'",
    "'unsafe-eval'",
    "'strict-dynamic'",
    "'wasm-unsafe-eval'",
    "'sha256-B2yPHKaXnvFWtRChIbabYmUBFZdVfKKXHbWtWidDVF8='",
    'https:',
    'data:',
    'blob:',
    '*',
    'cdn.example.com',
    '*.example.com',
    'https://cdn.example.com',
    'https://cdn.example.com:8443/assets/',
    'https://*.example.com:*',
    'wss://socket.example.com',
]);

it('refuses anything that could add a directive or break the header', function (string $source): void {
    expect(Source::isValid($source))->toBeFalse();
})->with([
    'empty' => '',
    'semicolon' => "'self'; script-src *",
    'comma' => "'self',https:",
    'space' => "'self' https:",
    'tab' => "https:\t",
    'trailing newline' => "https://cdn.example.com\n",
    'header injection' => "https:\r\nSet-Cookie: a=b",
    'control character' => "https:\x00",
    'non-ascii' => 'https://exämple.com',
    'unknown keyword' => "'unsafe-everything'",
    'nonce' => "'nonce-abc123'",
    'unquoted self' => 'self',
    'unquoted none' => 'NONE',
    'unbalanced quote' => "'self",
    'quote inside' => "https://a.com/'x'",
    'too long' => 'https://' . str_repeat('a', 250) . '.com',
    'empty host' => 'https://',
]);

it('drops invalid sources, repeats and a lone none among others', function (): void {
    expect(Source::filter(["'self'", 'bad source', "'self'", "'none'", 'https:'], 50))->toBe(["'self'", 'https:'])
        ->and(Source::filter(["'none'"], 50))->toBe(["'none'"])
        ->and(Source::filter(['a.com', 'b.com', 'c.com'], 2))->toBe(['a.com', 'b.com']);
});

it('accepts a report URI that is one header token', function (string $uri, bool $valid): void {
    expect(ReportUri::isValid($uri))->toBe($valid);
})->with([
    ['/csp-report', true],
    ['https://reports.example.com/csp?site=1', true],
    ['http://localhost:8000/report', true],
    ['', false],
    ['//evil.example.com/report', false],
    ['javascript:alert(1)', false],
    ['ftp://example.com/report', false],
    ['https://example.com/a;b', false],
    ['https://example.com/a,b', false],
    ['/a b', false],
    ["/report\n", false],
    ['/' . str_repeat('a', 300), false],
    ['not a url', false],
]);
