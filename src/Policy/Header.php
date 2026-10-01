<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Policy;

final readonly class Header
{
    public const ENFORCE = 'Content-Security-Policy';

    public const REPORT_ONLY = 'Content-Security-Policy-Report-Only';

    public function __construct(public string $name, public string $value)
    {
    }
}
