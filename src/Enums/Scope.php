<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Enums;

/**
 * The part of the application a policy is written for.
 */
enum Scope: string
{
    case SITE = 'site';
    case NOVA = 'nova';
}
