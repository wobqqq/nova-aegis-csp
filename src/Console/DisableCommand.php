<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Console;

use Illuminate\Console\Command;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\AegisCsp\CspModule;
use Wobqqq\AegisCsp\CspService;
use Wobqqq\AegisCsp\Enums\Target;

final class DisableCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:csp:disable {--nova : Stop sending the policy to Nova only, keep it on the site}';

    /** @var string */
    protected $description = 'Turn the Content-Security-Policy off, for an administrator whose pages it broke.';

    public function handle(SettingsRepository $repository, CspService $csp): int
    {
        // Saved from the re-read settings, so a stored row the rules would refuse cannot block the recovery.
        $settings = $csp->settings();
        $novaOnly = $this->option('nova') === true && $settings->target !== Target::NOVA;

        $repository->save(CspModule::KEY, $novaOnly
            ? $settings->with($settings->enabled, Target::SITE)->toArray()
            : $settings->with(false, $settings->target)->toArray());

        $this->components->info($novaOnly
            ? 'The Content-Security-Policy is no longer sent to Nova.'
            : 'The Content-Security-Policy is off.');

        return self::SUCCESS;
    }
}
