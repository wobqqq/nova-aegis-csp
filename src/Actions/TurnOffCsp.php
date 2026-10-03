<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Actions;

use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisCsp\CspModule;
use Wobqqq\AegisCsp\CspService;
use Wobqqq\AegisCsp\Enums\Target;

/**
 * The recovery path: saved from the re-read settings, so a stored row the rules would refuse cannot block it.
 */
final readonly class TurnOffCsp
{
    public function __construct(private CspService $csp)
    {
    }

    public function everywhere(): void
    {
        $settings = $this->csp->settings();

        Aegis::save(CspModule::KEY, $settings->with(false, $settings->target)->toArray());
    }

    /**
     * Keeps the policy on the site only.
     *
     * @return bool false when Nova was its only target, so the policy is now off
     */
    public function forNova(): bool
    {
        $settings = $this->csp->settings();

        if ($settings->target === Target::NOVA) {
            $this->everywhere();

            return false;
        }

        Aegis::save(CspModule::KEY, $settings->with($settings->enabled, Target::SITE)->toArray());

        return true;
    }
}
