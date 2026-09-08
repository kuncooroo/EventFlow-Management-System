<?php

namespace App\Support\Install;

use Illuminate\Support\Facades\DB;
use Throwable;

class InstallRequirements
{
    /**
     * PHP extensions required at runtime for this application.
     *
     * @var list<string>
     */
    public const REQUIRED_EXTENSIONS = [
        'bcmath',
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'iconv',
        'json',
        'libxml',
        'mbstring',
        'openssl',
        'pcre',
        'pdo',
        'pdo_mysql',
        'session',
        'tokenizer',
        'xml',
        'xmlwriter',
        'zlib',
    ];

    /**
     * Critical checks that must pass before migrations can run.
     *
     * @return list<array{label: string, status: string, message: string}>
     */
    public function preflight(): array
    {
        return [
            $this->phpVersion(),
            $this->extensions(),
            $this->directories(),
            $this->appKey(),
            $this->database(),
        ];
    }

    /**
     * Full post-install health checks.
     *
     * @return list<array{label: string, status: string, message: string}>
     */
    public function checks(): array
    {
        return array_merge($this->preflight(), [
            $this->migrations(),
            $this->debugMode(),
            $this->storageLink(),
            $this->queue(),
            $this->scheduler(),
        ]);
    }

    public function hasFailures(array $checks): bool
    {
        return collect($checks)->contains('status', 'fail');
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function phpVersion(): array
    {
        $ok = version_compare(PHP_VERSION, '8.3.0', '>=');

        return [
            'label' => 'PHP version',
            'status' => $ok ? 'ok' : 'fail',
            'message' => $ok
                ? PHP_VERSION.' (>= 8.3 required)'
                : PHP_VERSION.' is below the 8.3 minimum',
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function extensions(): array
    {
        $missing = array_values(array_filter(
            self::REQUIRED_EXTENSIONS,
            fn (string $extension): bool => ! extension_loaded($extension),
        ));

        return [
            'label' => 'PHP extensions',
            'status' => $missing === [] ? 'ok' : 'fail',
            'message' => $missing === []
                ? 'All required extensions present'
                : 'Missing: '.implode(', ', $missing),
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function directories(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $unwritable = collect($paths)
            ->filter(fn (string $path): bool => ! is_writable($path))
            ->keys()
            ->values()
            ->all();

        return [
            'label' => 'Writable directories',
            'status' => $unwritable === [] ? 'ok' : 'fail',
            'message' => $unwritable === []
                ? 'storage and bootstrap/cache are writable'
                : 'Not writable: '.implode(', ', $unwritable),
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function appKey(): array
    {
        $key = (string) config('app.key');

        return [
            'label' => 'Application key',
            'status' => $key === '' || $key === null ? 'fail' : 'ok',
            'message' => $key === '' || $key === null
                ? 'Missing APP_KEY — run php artisan key:generate'
                : 'APP_KEY configured',
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function database(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'label' => 'Database connection',
                'status' => 'ok',
                'message' => 'Connected via '.config('database.default'),
            ];
        } catch (Throwable $exception) {
            return [
                'label' => 'Database connection',
                'status' => 'fail',
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function migrations(): array
    {
        try {
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                return [
                    'label' => 'Migrations',
                    'status' => 'fail',
                    'message' => 'Migration repository missing — run php artisan migrate --force',
                ];
            }

            $files = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff($files, $ran));

            return [
                'label' => 'Migrations',
                'status' => $pending === [] ? 'ok' : 'fail',
                'message' => $pending === []
                    ? 'Schema is up to date'
                    : 'Pending migrations: '.count($pending),
            ];
        } catch (Throwable $exception) {
            return [
                'label' => 'Migrations',
                'status' => 'fail',
                'message' => 'Unable to inspect migrations: '.$exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function debugMode(): array
    {
        $environment = (string) config('app.env');
        $debug = (bool) config('app.debug');

        $status = ($environment === 'production' && $debug) ? 'fail' : ($debug ? 'warn' : 'ok');

        $message = match (true) {
            $environment === 'production' && $debug => 'Debug mode must be disabled in production',
            $debug => 'Debug mode enabled (acceptable outside production)',
            default => 'Debug mode disabled',
        };

        return [
            'label' => 'Debug mode',
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function storageLink(): array
    {
        $link = public_path('storage');
        $linked = is_link($link) || is_dir($link);

        return [
            'label' => 'Storage link',
            'status' => $linked ? 'ok' : 'warn',
            'message' => $linked ? 'public/storage linked' : 'public/storage not linked — run php artisan storage:link',
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function queue(): array
    {
        return [
            'label' => 'Queue worker',
            'status' => 'warn',
            'message' => 'QUEUE_CONNECTION='.config('queue.default').' — run php artisan queue:work as a persisted process',
        ];
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function scheduler(): array
    {
        return [
            'label' => 'Scheduler',
            'status' => 'warn',
            'message' => 'Add cron entry: * * * * * cd /path/to/app && php artisan schedule:run',
        ];
    }
}
