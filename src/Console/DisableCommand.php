<?php

declare(strict_types=1);

namespace Wobqqq\AegisCsp\Console;

use Illuminate\Console\Command;
use Wobqqq\AegisCsp\Actions\TurnOffCsp;

final class DisableCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:csp:disable {--nova : Stop sending the policy to Nova only, keep it on the site}';

    /** @var string */
    protected $description = 'Turn the Content-Security-Policy off, for an administrator whose pages it broke.';

    public function handle(TurnOffCsp $turnOff): int
    {
        if ($this->option('nova') === true) {
            $keptOnSite = $turnOff->forNova();
        } else {
            $turnOff->everywhere();
            $keptOnSite = false;
        }

        $this->components->info($keptOnSite
            ? 'The Content-Security-Policy is no longer sent to Nova.'
            : 'The Content-Security-Policy is off.');

        return self::SUCCESS;
    }
}
