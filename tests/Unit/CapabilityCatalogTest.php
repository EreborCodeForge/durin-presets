<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Manifest\DurinManifestParser;
use EreborCodeForge\Durin\Presets\Capability\CapabilityCatalog;
use EreborCodeForge\Durin\Presets\Capability\IncompatibleCapabilityException;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use PHPUnit\Framework\TestCase;

final class CapabilityCatalogTest extends TestCase
{
    public function test_catalog_is_the_only_capability_source(): void
    {
        $catalog = new CapabilityCatalog();

        $this->assertSame(['consumer', 'producer'], $catalog->ids());
        $this->assertSame(['consumer', 'producer'], array_column($catalog->toArray(), 'id'));
        $this->assertSame(
            $catalog->toArray(),
            (new DefaultPresetRegistryFactory())->create()->catalog()['capabilities'],
        );
    }

    public function test_normalize_deduplicates_and_sorts(): void
    {
        $catalog = new CapabilityCatalog();

        $this->assertSame(
            ['consumer', 'producer'],
            $catalog->normalize(['producer', 'consumer', 'producer']),
        );
        $this->assertSame(['producer'], $catalog->normalize('producer'));
    }

    public function test_rejects_unknown_and_empty_tokens(): void
    {
        $catalog = new CapabilityCatalog();

        try {
            $catalog->normalize('auth');
            $this->fail('unknown capability must be rejected');
        } catch (IncompatibleCapabilityException $e) {
            $this->assertSame('Unknown capability "auth". Available: consumer, producer.', $e->getMessage());
        }

        $this->expectException(IncompatibleCapabilityException::class);
        $this->expectExceptionMessage('Empty capability token in --with.');
        $catalog->normalize('producer,');
    }

    public function test_consumer_is_rejected_on_http_presets(): void
    {
        $catalog = new CapabilityCatalog();

        $this->expectException(IncompatibleCapabilityException::class);
        $this->expectExceptionMessage('Capability "consumer" is not supported on preset "service" (http). --with=consumer is restricted to job presets.');
        $catalog->assertCompatible('service', 'http', ['consumer']);
    }

    public function test_producer_is_accepted_on_http_and_job(): void
    {
        $catalog = new CapabilityCatalog();
        $catalog->assertCompatible('service', 'http', ['producer']);
        $catalog->assertCompatible('worker', 'job', ['consumer', 'producer']);
        $catalog->assertCompatible('minimal', 'http', ['producer']);

        $engine = (new DefaultPresetRegistryFactory())->engine();
        $plan = $engine->plan(new ProjectOptions(
            name: 'orders',
            preset: 'service',
            targetDirectory: '/tmp/orders',
            extra: ['with' => ['producer']],
        ));
        $paths = array_map(static fn ($action) => $action->relativePath, $plan->actions());
        $this->assertContains('src/Kernel.php', $paths);
        $this->assertContains('src/Infrastructure/Messaging/UnconfiguredPublisher.php', $paths);
        $this->assertNotContains('src/JobKernel.php', $paths);

        $yaml = '';
        $routes = '';
        $publisher = '';
        foreach ($plan->actions() as $action) {
            if ($action->relativePath === 'durin.yaml') {
                $yaml = (string) $action->contents;
            }
            if ($action->relativePath === 'routes/api.php') {
                $routes = (string) $action->contents;
            }
            if ($action->relativePath === 'src/Infrastructure/Messaging/UnconfiguredPublisher.php') {
                $publisher = (string) $action->contents;
            }
        }

        $manifest = (new DurinManifestParser())->parse($yaml);
        $this->assertTrue($manifest->features['http']);
        $this->assertTrue($manifest->features['messaging']);
        $this->assertStringNotContainsString('/publish', $routes);
        $this->assertStringContainsString('does not simulate a successful publish', $publisher);
    }

    public function test_worker_with_consumer_and_producer_keeps_one_job_kernel(): void
    {
        $engine = (new DefaultPresetRegistryFactory())->engine();
        $plan = $engine->plan(new ProjectOptions(
            name: 'jobs',
            preset: 'worker',
            targetDirectory: '/tmp/jobs',
            extra: ['with' => 'producer,consumer'],
        ));

        $kernels = 0;
        $paths = [];
        foreach ($plan->actions() as $action) {
            $paths[] = $action->relativePath;
            if ($action->relativePath === 'src/JobKernel.php') {
                $kernels++;
                $this->assertStringNotContainsString('namespace App\Kernel', (string) $action->contents);
            }
        }

        $this->assertSame(1, $kernels);
        $this->assertContains('src/Application/Handlers/ExampleHandler.php', $paths);
        $this->assertContains('src/Infrastructure/Messaging/Publisher.php', $paths);
        $this->assertNotContains('public/index.php', $paths);
        $this->assertNotContains('routes/api.php', $paths);
    }
}
