<?php

declare(strict_types=1);

namespace InteractSms\InteractSms\Console\Commands;

use Illuminate\Console\Command;

class InteractSmsCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'interact-sms:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package interact-sms.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('InteractSms placeholder command executed.');

        return self::SUCCESS;
    }
}
