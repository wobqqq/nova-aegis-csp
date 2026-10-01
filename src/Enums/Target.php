<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Enums;

/**
 * Where the policy is sent.
 */
enum Target: string
{
    case SITE = 'site';
    case NOVA = 'nova';
    case BOTH = 'both';

    public function covers(Scope $scope): bool
    {
        return $this === self::BOTH || $this->value === $scope->value;
    }
}
