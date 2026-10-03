<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp;

use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisCsp\Enums\Scope;
use Wobqqq\AegisCsp\Policy\CspSettings;
use Wobqqq\AegisCsp\Policy\Header;

final class CspService
{
    private ?CspSettings $settings = null;

    /** @var array<value-of<Scope>, Header|null> */
    private array $headers = [];

    public function settings(): CspSettings
    {
        return $this->settings ??= CspSettings::fromArray(Aegis::settings(CspModule::KEY));
    }

    public function header(Scope $scope): ?Header
    {
        if (!array_key_exists($scope->value, $this->headers)) {
            $this->headers[$scope->value] = $this->settings()->header($scope);
        }

        return $this->headers[$scope->value];
    }

    public function forget(): void
    {
        $this->settings = null;
        $this->headers = [];
    }
}
