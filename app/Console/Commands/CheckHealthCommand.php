<?php

namespace App\Console\Commands;

use App\Support\Install\InstallRequirements;
use Illuminate\Console\Command;

class CheckHealthCommand extends Command
{
    protected $signature = 'eventflow:check-health
        {--strict : Return a failure exit code when warning-level checks are not satisfied}';

    protected $description = 'Verify key assumptions about the running environment, application state and database';

    public function handle(): int
    {
        $requirements = new InstallRequirements;
        $exit = self::SUCCESS;

        $this->info('EventFlow health check');
        $this->newLine();

        foreach ($requirements->checks() as $check) {
            [$label, $status, $message] = [...array_values($check)];

            match ($status) {
                'ok' => $this->components->twoColumnDetail($label, '<fg=green;options=bold>OK</>'),
                'warn' => $this->components->twoColumnDetail($label, '<fg=yellow>WARNING</>'),
                default => $this->components->twoColumnDetail($label, '<fg=red;options=bold>FAIL</>'),
            };

            if ($status !== 'ok') {
                $this->line('      '.$message);
            }

            if ($status === 'fail' || ($this->option('strict') && $status === 'warn')) {
                $exit = self::FAILURE;
            }
        }

        $this->newLine();

        return $exit;
    }
}
