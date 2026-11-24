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
 * {$label} Durin app — HTTP CLI entry via Mithril HttpApplication.
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
        'message' => 'HTTP entry is CLI-only. Run: durins-forge run (or vendor/bin/durins-forge run)',
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
        \$this->container->scoped(JobUnitCleanup::class, static fn (): JobUnitCleanup => new JobUnitCleanup());

        \$this->booted = true;
    }

    public function handle(JobEnvelope \$job): JobResult
    {
        // Materialize the scoped cleanup so endScope runs it after this job.
        \$this->container->get(JobUnitCleanup::class);

        // Dispatch by \$job->name into src/Jobs handlers.
        return JobResult::ack();
    }

    public function getContainer(): Container
    {
        return \$this->container;
    }
}

/**
 * Ends the database unit after the job. Does not open a connection by itself.
 */
final class JobUnitCleanup
{
    public function cleanup(): void
    {
        if (!class_exists(\\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::class)) {
            return;
        }

        if (!\\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::isBooted()) {
            return;
        }

        \\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::onRequestEnd();
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

    public function envExampleConsumer(): string
    {
        return <<<'ENV'
APP_ENV=development
APP_DEBUG=true
# Opt-in. Leave unset until a real JobTransport adapter is bound.
# The in-memory transport is used only when APP_ENV is development or testing.
# MESSAGING_TRANSPORT=

ENV;
    }

    public function envExampleProducer(): string
    {
        return <<<'ENV'
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8080
APP_PORT=8080
# Opt-in. Leave unset until a real publisher adapter is bound.
# MESSAGING_TRANSPORT=

ENV;
    }

    public function messagingConfig(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Opt-in messaging configuration.
 * No broker is installed by this scaffold.
 * Publishing without a bound adapter must fail closed.
 * Consuming without a bound JobTransport must fail closed outside development and testing.
 */
return [
    'transport' => getenv('MESSAGING_TRANSPORT') ?: null,
];

PHP;
    }

    public function exampleHandler(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Application\Handlers;

use Erebor\Mithril\Jobs\JobEnvelope;
use Erebor\Mithril\Jobs\JobResult;

/**
 * Example consumer handler. It does not acknowledge jobs.
 * Replace it with a handler that returns an explicit JobResult after real processing.
 */
final class ExampleHandler
{
    public function handle(JobEnvelope $job): JobResult
    {
        throw new \RuntimeException(
            'ExampleHandler refuses to acknowledge this job. Replace App\\Application\\Handlers\\ExampleHandler with a handler that returns an explicit JobResult after real processing.'
        );
    }
}

PHP;
    }

    public function consumerScaffoldTest(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ConsumerScaffoldTest extends TestCase
{
    public function test_example_handler_does_not_acknowledge(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__) . '/src/Application/Handlers/ExampleHandler.php');

        $this->assertStringContainsString('refuses to acknowledge', $source);
        $this->assertStringNotContainsString('JobResult::ack()', $source);
    }
}

PHP;
    }

    public function eventEnvelope(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

/**
 * Application-level event. Transport concerns stay in the publisher adapter.
 */
final readonly class EventEnvelope
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $name,
        public array $payload = [],
    ) {}
}

PHP;
    }

    public function publisherContract(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

interface Publisher
{
    public function publish(EventEnvelope $event): void;
}

PHP;
    }

    public function unconfiguredPublisher(): string
    {
        return <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

/**
 * Fail-closed publisher. This scaffold never reports a successful publish
 * without a concrete adapter.
 */
final class UnconfiguredPublisher implements Publisher
{
    public function publish(EventEnvelope $event): void
    {
        throw new \RuntimeException(
            'No messaging publisher adapter is configured. Bind App\\Infrastructure\\Messaging\\Publisher before publishing. This scaffold does not simulate a successful publish.'
        );
    }
}

PHP;
    }

    public function consumerJobKernel(string $app): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App;

use App\\Application\\Handlers\\ExampleHandler;
use Erebor\\Mithril\\Container;
use Erebor\\Mithril\\Contracts\\JobApplication;
use Erebor\\Mithril\\Jobs\\InMemoryJobTransport;
use Erebor\\Mithril\\Jobs\\JobEnvelope;
use Erebor\\Mithril\\Jobs\\JobResult;
use Erebor\\Mithril\\Jobs\\JobTransport;

/**
 * Job application kernel for {$app}.
 * Does not acknowledge jobs on its own and does not open a broker connection.
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

        \$environment = getenv('APP_ENV');
        \$environment = is_string(\$environment) && \$environment !== '' ? \$environment : 'production';
        \$transport = getenv('MESSAGING_TRANSPORT');
        \$transport = is_string(\$transport) ? trim(\$transport) : '';

        if (\$transport !== '') {
            throw new \\RuntimeException(
                'MESSAGING_TRANSPORT is set but no JobTransport adapter is registered. Bind a real adapter before handling jobs. This scaffold does not connect to a broker.'
            );
        }

        if (!in_array(\$environment, ['development', 'testing', 'test'], true)) {
            throw new \\RuntimeException(
                'Consumer transport is not configured. Set MESSAGING_TRANSPORT and bind a JobTransport adapter. The in-memory transport is only available when APP_ENV is development or testing.'
            );
        }

        // Development and testing only. This is not a production broker.
        \$this->container->singleton(JobTransport::class, new InMemoryJobTransport([], idleWhenEmpty: true));
        \$this->container->singleton(ExampleHandler::class, static fn (): ExampleHandler => new ExampleHandler());
        \$this->container->scoped(JobUnitCleanup::class, static fn (): JobUnitCleanup => new JobUnitCleanup());

        \$this->booted = true;
    }

    public function handle(JobEnvelope \$job): JobResult
    {
        \$this->container->get(JobUnitCleanup::class);

        return \$this->container->get(ExampleHandler::class)->handle(\$job);
    }

    public function getContainer(): Container
    {
        return \$this->container;
    }
}

/**
 * Ends the database unit after the job. Does not open a connection by itself.
 */
final class JobUnitCleanup
{
    public function cleanup(): void
    {
        if (!class_exists(\\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::class)) {
            return;
        }

        if (!\\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::isBooted()) {
            return;
        }

        \\EreborCodeForge\\Durin\\Forge\\Infrastructure\\Database\\DB::onRequestEnd();
    }
}

PHP;
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
durins-forge run
durins-forge doctor
# CI / container: vendor/bin/durins-forge run
```

This worker runs standalone with Mithril by default.
Eregion is optional and may supervise the worker when configured by Durin.

- **Mithril** executes the JobWorker.
- **Eregion** optionally supervises processes.

Low-level / debug execution (not the primary Durin UX):

```bash
php vendor/bin/job-worker
# or: composer job:work
```

See Mithril job-worker docs and Durin SPEC-DX-017.

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
durins-forge run
durins-forge doctor
# CI / container: vendor/bin/durins-forge run
```

Structure:

{$structure}

MD;
    }

    /**
     * @param list<string> $structureLines
     */
    public function readmeConsumer(string $app, array $structureLines): string
    {
        $structure = implode("\n", array_map(
            static fn (string $line): string => '- `' . $line . '`',
            $structureLines,
        ));

        return <<<MD
# {$app}

Created with `durin new` preset **consumer** (job / non-HTTP).

Execution is `mithril-job`. Eregion is optional and is not selected by this preset.
No broker is installed. `JobKernel` does not acknowledge jobs. The example handler refuses to ack until you replace it.
The in-memory transport is only for `APP_ENV=development` or `testing`.

## Next steps

```bash
composer install
cp .env.example .env
durins-forge run
durins-forge doctor
# CI / container: vendor/bin/durins-forge run
```

Low-level / debug execution (not the primary Durin UX):

```bash
php vendor/bin/job-worker
```

Structure:

{$structure}

MD;
    }

    /**
     * @param list<string> $structureLines
     */
    public function readmeProducer(string $app, array $structureLines): string
    {
        $structure = implode("\n", array_map(
            static fn (string $line): string => '- `' . $line . '`',
            $structureLines,
        ));

        return <<<MD
# {$app}

Created with `durin new` preset **producer** (HTTP service with a publisher scaffold).

Execution stays `mithril-http`. This preset does not add a public `/publish` route and does not install a broker.
`UnconfiguredPublisher::publish()` fails until you bind a real `Publisher` adapter.

## Next steps

```bash
composer install
cp .env.example .env
durins-forge run
durins-forge doctor
# CI / container: vendor/bin/durins-forge run
```

Structure:

{$structure}

MD;
    }
}
