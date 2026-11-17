<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Manifest\DurinManifestParser;
use EreborCodeForge\Durin\Presets\Preset\ConsumerPreset;
use EreborCodeForge\Durin\Presets\Preset\ProducerPreset;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use PHPUnit\Framework\TestCase;

final class ConsumerProducerPresetTest extends TestCase
{
    public function test_consumer_is_a_job_scaffold_without_http_or_automatic_ack(): void
    {
        $plan = (new ConsumerPreset())->plan(new ProjectOptions('events', 'consumer', '/tmp/events'));
        $files = [];
        foreach ($plan->actions() as $action) {
            $files[$action->relativePath] = $action->contents;
        }

        $this->assertArrayHasKey('src/JobKernel.php', $files);
        $this->assertArrayHasKey('src/Application/Handlers/ExampleHandler.php', $files);
        $this->assertArrayHasKey('config/messaging.php', $files);
        $this->assertArrayHasKey('tests/ConsumerScaffoldTest.php', $files);
        $this->assertArrayNotHasKey('public/index.php', $files);
        $this->assertArrayNotHasKey('src/Kernel.php', $files);
        $this->assertArrayNotHasKey('routes/api.php', $files);

        $kernel = (string) $files['src/JobKernel.php'];
        $this->assertStringContainsString('implements JobApplication', $kernel);
        $this->assertStringContainsString('InMemoryJobTransport', $kernel);
        $this->assertStringNotContainsString('return JobResult::ack()', $kernel);
        $this->assertStringContainsString('only available when APP_ENV is development or testing', $kernel);

        $handler = (string) $files['src/Application/Handlers/ExampleHandler.php'];
        $this->assertStringNotContainsString('JobResult::ack()', $handler);

        $manifest = (new DurinManifestParser())->parse((string) $files['durin.yaml']);
        $this->assertSame('consumer', $manifest->preset);
        $this->assertFalse($manifest->features['http']);
        $this->assertTrue($manifest->features['messaging']);
        $this->assertNull($manifest->runtime);

        $runtime = (new DefaultPresetRegistryFactory())->create()->definition('consumer')->runtime();
        $this->assertSame('job', $runtime->mode);
        $this->assertSame(['job-loop', 'messaging'], $runtime->requiredCapabilities);
        $this->assertNull($runtime->preferredRunner);
    }

    public function test_producer_reuses_http_service_without_a_publish_route(): void
    {
        $plan = (new ProducerPreset())->plan(new ProjectOptions('events', 'producer', '/tmp/publisher'));
        $files = [];
        foreach ($plan->actions() as $action) {
            $files[$action->relativePath] = $action->contents;
        }

        $this->assertArrayHasKey('src/Kernel.php', $files);
        $this->assertArrayHasKey('public/index.php', $files);
        $this->assertArrayHasKey('src/Infrastructure/Messaging/Publisher.php', $files);
        $this->assertArrayHasKey('src/Infrastructure/Messaging/UnconfiguredPublisher.php', $files);
        $this->assertArrayNotHasKey('src/JobKernel.php', $files);
        $this->assertStringNotContainsString('/publish', (string) $files['routes/api.php']);
        $this->assertStringContainsString(
            'does not simulate a successful publish',
            (string) $files['src/Infrastructure/Messaging/UnconfiguredPublisher.php'],
        );
        $this->assertStringNotContainsString('password', strtolower((string) $files['.env.example']));
        $this->assertStringNotContainsString('secret', strtolower((string) $files['.env.example']));

        $manifest = (new DurinManifestParser())->parse((string) $files['durin.yaml']);
        $this->assertSame('producer', $manifest->preset);
        $this->assertTrue($manifest->features['http']);
        $this->assertTrue($manifest->features['messaging']);

        $runtime = (new DefaultPresetRegistryFactory())->create()->definition('producer')->runtime();
        $this->assertSame('http', $runtime->mode);
        $this->assertSame(['persistent-http'], $runtime->requiredCapabilities);
        $this->assertNull($runtime->preferredRunner);
    }
}
