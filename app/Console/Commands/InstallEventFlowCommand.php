<?php

namespace App\Console\Commands;

use App\Support\Install\InstallRequirements;
use Illuminate\Console\Command;
use Throwable;

class InstallEventFlowCommand extends Command
{
    protected $signature = 'eventflow:install
        {--organization= : Organization to bootstrap the initial Owner in}
        {--name= : Display name of the initial Owner}
        {--email= : Email address of the initial Owner}
        {--password= : Password for the initial Owner}
        {--no-migrate : Skip running pending migrations}
        {--no-owner : Skip the Owner bootstrap step}
        {--no-link : Skip the public storage symlink}
        {--optimize : Cache config, routes, views and events for production}';

    protected $description = 'Install or upgrade a deployment through preflight checks, migrations, Owner bootstrap and health verification';

    public function handle(): int
    {
        $this->info('EventFlow installer');
        $this->newLine();

        if (! file_exists(base_path('.env'))) {
            $this->error('No .env file found. Copy .env.example to .env, configure it, then rerun this command.');

            return self::FAILURE;
        }

        if ((string) config('app.key') === '' && ! app()->runningUnitTests()) {
            $this->warn('No application key configured - generating one now.');
            $this->call('key:generate', ['--force' => true]);
        }

        $requirements = new InstallRequirements;
        $preflight = $requirements->preflight();

        foreach ($preflight as $check) {
            [$label, $status, $message] = [...array_values($check)];
            $this->components->twoColumnDetail($label, $status === 'ok' ? '<fg=green;options=bold>OK</>' : '<fg=red;options=bold>FAIL</>');

            if ($status !== 'ok') {
                $this->line('      '.$message);
            }
        }

        if ($requirements->hasFailures($preflight)) {
            $this->newLine();
            $this->error('Preflight checks failed. Resolve the issues above, then rerun this command.');

            return self::FAILURE;
        }

        if (! $this->option('no-migrate')) {
            $this->newLine();
            $this->info('1/3 Running pending migrations...');
            $this->call('migrate', ['--force' => true]);
        }

        if (! $this->option('no-owner')) {
            $this->newLine();
            $this->info('2/3 Bootstrapping the initial Owner...');

            if ($this->hasOwnerOptions()) {
                $ownerArguments = [
                    '--organization' => $this->option('organization'),
                    '--name' => $this->option('name'),
                    '--email' => $this->option('email'),
                ];

                if ($this->option('password') !== null) {
                    $ownerArguments['--password'] = $this->option('password');
                }

                if ($this->call('eventflow:create-owner', $ownerArguments) !== self::SUCCESS) {
                    $this->warn('Owner bootstrap did not complete. Run php artisan eventflow:create-owner manually with the required details.');
                }
            } else {
                $this->warn('Skipping Owner bootstrap (missing --organization/--name/--email). Run php artisan eventflow:create-owner afterwards.');
            }
        }

        if (! app()->runningUnitTests()) {
            $this->finalizeStorageAndOptimize();
        }

        $this->newLine();
        $this->info('3/3 Verifying deployment health...');

        $healthArguments = app()->isProduction() ? ['--strict' => true] : [];

        $healthExit = $this->call('eventflow:check-health', $healthArguments);

        $this->newLine();
        $this->printNextSteps();

        return $healthExit;
    }

    private function hasOwnerOptions(): bool
    {
        return $this->option('organization') !== null
            && $this->option('name') !== null
            && $this->option('email') !== null;
    }

    private function finalizeStorageAndOptimize(): void
    {
        if (! $this->option('no-link')) {
            $link = public_path('storage');

            if (! is_link($link) && ! is_dir($link)) {
                $this->newLine();
                $this->info('Linking public storage...');

                try {
                    $this->call('storage:link');
                } catch (Throwable) {
                    $this->warn('Could not create the storage symlink automatically - run php artisan storage:link manually.');
                }
            }
        }

        if ($this->option('optimize')) {
            $this->newLine();
            $this->info('Optimizing for production...');
            $this->call('optimize');
        }
    }

    private function printNextSteps(): void
    {
        $this->info('Next steps:');
        $this->line('  - Run a queue worker as a persisted process:  php artisan queue:work');
        $this->line('  - Add the scheduler to cron:  * * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1');
        $this->line('  - Configure mail via the MAIL_* variables in .env if not already done.');
    }
}
