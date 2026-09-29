<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

/**
 * Shared scaffold file contents for HTTP-oriented presets.
 */
final class PresetScaffoldSupport
{
    public function composerPackageName(string $app): string
    {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $app) ?? $app);
        $slug = trim($slug, '-') ?: 'app';

        return 'app/' . $slug;
    }

    public function routesApi(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use EreborCodeForge\Durin\Forge\Core\Http\Controllers\HealthCheckController;
use Erebor\Mithril\Router;

return function (Router $router): void {
    $router->get('/api/health', [HealthCheckController::class, 'check']);
};

PHP;
    }

    public function routesWeb(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use Erebor\Mithril\Router;

return function (Router $router): void {
};

PHP;
    }

    public function configApp(string $app): string
    {
        $name = var_export($app, true);

        return <<<PHP
<?php

declare(strict_types=1);

return [
    'name' => {$name},
    'env' => getenv('APP_ENV') ?: 'development',
    'providers' => [
        \\EreborCodeForge\\Durin\\Forge\\Core\\DiscoveryServiceProvider::class,
    ],
];

PHP;
    }

    public function applicationKernel(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App;

use EreborCodeForge\Durin\Forge\Core\Http\HttpApplicationKernel;
use Erebor\Mithril\Container;
use Erebor\Mithril\Contracts\HttpApplication;
use Erebor\Mithril\Http\Request;
use Erebor\Mithril\Http\Response;
use Erebor\Mithril\Router;

/**
 * Application-owned HTTP kernel. Delegates boot/dispatch to Durin Forge.
 */
final class Kernel implements HttpApplication
{
    private HttpApplicationKernel $inner;

    public function __construct(?Container $container = null)
    {
        $this->inner = new HttpApplicationKernel($container);
    }

    public function boot(): void
    {
        $this->inner->boot();
    }

    public function handle(Request $request): Response
    {
        return $this->inner->handle($request);
    }

    public function getContainer(): Container
    {
        return $this->inner->getContainer();
    }

    public function getRouter(): Router
    {
        return $this->inner->getRouter();
    }
}

PHP;
    }

    public function publicIndex(string $label): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

/**
 * {$label} Durin app — Eregion worker entry via Mithril HttpApplication.
 */

\$appRoot = dirname(__DIR__);
require \$appRoot . '/vendor/autoload.php';

use App\\Kernel;
use EreborCodeForge\\Durin\\Forge\\Support\\ApplicationPath;
use Erebor\\Mithril\\Runtime\\Eregion\\EregionBridge;
use Erebor\\Mithril\\Runtime\\Eregion\\Exceptions\\ProtocolException;
use Erebor\\Mithril\\Runtime\\Eregion\\WorkerCliOptions;
use Erebor\\Mithril\\Runtime\\Recycling\\CompositeRecyclingPolicy;
use Erebor\\Mithril\\Runtime\\Recycling\\MaxRequestsPolicy;
use Erebor\\Mithril\\Runtime\\Recycling\\MemoryLimitPolicy;
use Erebor\\Mithril\\Runtime\\Worker;
use Erebor\\Mithril\\Runtime\\WorkerExitCode;
use Erebor\\Mithril\\Runtime\\WorkerResult;
use Erebor\\Mithril\\Runtime\\WorkerStopReason;
use Erebor\\Mithril\\Support\\PackageVersion;

ApplicationPath::setRoot(\$appRoot);

if (PHP_SAPI !== 'cli') {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => true,
        'message' => 'HTTP entry is Eregion. Run: vendor/bin/durin serve',
    ]);
    exit(1);
}

try {
    \$options = WorkerCliOptions::fromArgv(\$argv);
} catch (ProtocolException \$e) {
    fwrite(STDERR, \$e->getMessage() . "\\n");
    exit(WorkerExitCode::BootstrapFailure->value);
}

\$bridge = new EregionBridge(
    socketPath: \$options->socket,
    workerId: \$options->workerId,
    generation: \$options->generation,
    mithrilVersion: PackageVersion::mithril(),
);

\$policy = new CompositeRecyclingPolicy(
    new MaxRequestsPolicy(\$options->maxRequests),
    new MemoryLimitPolicy(\$options->memoryLimitBytes()),
);

\$worker = new Worker(
    app: new Kernel(),
    bridge: \$bridge,
    maxRequests: \$options->maxRequests,
    recyclingPolicy: \$policy,
    memoryLimitBytes: \$options->memoryLimitBytes(),
);

try {
    \$result = \$worker->runResult();
} catch (\\Throwable) {
    \$result = new WorkerResult(0, WorkerStopReason::BootstrapFailure);
} finally {
    \$bridge->close();
}

exit(\$result->exitCode()->value);

PHP;
    }

    public function exampleTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function test_truth(): void
    {
        $this->assertTrue(true);
    }
}

PHP;
    }

    /**
     * Generated apps resolve Forge from Packagist — no root VCS repositories.
     *
     * @return list<array{type: string, url: string}>
     */
    private function composerRepositories(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $json
     */
    private function encodeComposerJson(array $json): string
    {
        $repositories = $this->composerRepositories();
        if ($repositories !== []) {
            // Keep repositories ahead of require for readability.
            $ordered = ['name' => $json['name'], 'type' => $json['type'], 'repositories' => $repositories];
            unset($json['name'], $json['type']);
            $json = $ordered + $json;
        }

        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    public function composerJson(string $package): string
    {
        return $this->encodeComposerJson([
            'name' => $package,
            'type' => 'project',
            'require' => [
                'php' => '^8.5',
                'ext-msgpack' => '*',
                'ext-sockets' => '*',
                'ereborcodeforge/durins-forge' => '^0.4',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^12.5',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'App\\Tests\\' => 'tests/',
                ],
            ],
            'extra' => [
                'mithril' => [
                    'kernel' => 'App\\Kernel',
                    'eregion' => 'v0.4.0',
                    'eregion_repo' => 'EreborCodeForge/eregion',
                ],
            ],
            'scripts' => [
                'test' => 'phpunit',
            ],
            'config' => [
                'sort-packages' => true,
            ],
        ]);
    }

    public function composerJsonWorker(string $package): string
    {
        return $this->encodeComposerJson([
            'name' => $package,
            'type' => 'project',
            'require' => [
                'php' => '^8.5',
                'ext-msgpack' => '*',
                'ext-sockets' => '*',
                'ereborcodeforge/durins-forge' => '^0.4',
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^12.5',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'src/',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'App\\Tests\\' => 'tests/',
                ],
            ],
            'extra' => [
                'mithril' => [
                    'job_kernel' => 'App\\JobKernel',
                ],
            ],
            'scripts' => [
                'test' => 'phpunit',
                'job:work' => 'job-worker',
            ],
            'config' => [
                'sort-packages' => true,
            ],
        ]);
    }

    public function jobKernel(string $app): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App;

use Erebor\\Mithril\\Container;
use Erebor\\Mithril\\Contracts\\JobApplication;
use Erebor\\Mithril\\Jobs\\InMemoryJobTransport;
use Erebor\\Mithril\\Jobs\\JobEnvelope;
use Erebor\\Mithril\\Jobs\\JobResult;
use Erebor\\Mithril\\Jobs\\JobTransport;

/**
 * Job application kernel for {$app}.
 * Bind a real JobTransport adapter for production brokers.
 */
final class JobKernel implements JobApplication
{
    private Container \$container;
    private bool \$booted = false;

    public function __construct()
    {
        \$this->container = new Container();
    }

    public function boot(): void
    {
        if (\$this->booted) {
            return;
        }

        // Demo transport — idleWhenEmpty keeps the consumer persistent until stop/drain.
        \$this->container->singleton(JobTransport::class, new InMemoryJobTransport([], idleWhenEmpty: true));

        \$this->booted = true;
    }

    public function handle(JobEnvelope \$job): JobResult
    {
        // Dispatch by \$job->name into src/Jobs handlers.
        return JobResult::ack();
    }

    public function getContainer(): Container
    {
        return \$this->container;
    }
}

PHP;
    }

    public function envExample(): string
    {
        return <<<'ENV'
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8080
APP_PORT=8080

ENV;
    }

    public function envExampleWorker(): string
    {
        return <<<'ENV'
APP_ENV=development
APP_DEBUG=true
# Optional: MITHRIL_JOB_KERNEL=App\JobKernel

ENV;
    }

    /**
     * @param list<string> $structureLines
     */
    public function readmeWorker(string $app, array $structureLines): string
    {
        $structure = implode("\n", array_map(
            static fn (string $line): string => '- `' . $line . '`',
            $structureLines,
        ));

        return <<<MD
# {$app}

Created with `durin new` preset **worker** (job / non-HTTP).

## Next steps

```bash
composer install
cp .env.example .env
# bind a real JobTransport in JobKernel::boot()
php vendor/bin/job-worker
# or: composer job:work
vendor/bin/durin doctor
```

This app does **not** use Eregion. See Mithril job-worker docs and Durin SPEC-DX-017.

Structure:

{$structure}

MD;
    }

    /**
     * @param list<string> $structureLines
     */
    public function readme(string $app, string $preset, array $structureLines): string
    {
        $structure = implode("\n", array_map(
            static fn (string $line): string => '- `' . $line . '`',
            $structureLines,
        ));

        return <<<MD
# {$app}

Created with `durin new` preset **{$preset}**.

## Next steps

```bash
composer install
cp .env.example .env
vendor/bin/durin doctor
vendor/bin/durin dev
```

Structure:

{$structure}

MD;
    }
}
