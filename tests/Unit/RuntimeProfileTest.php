<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use PHPUnit\Framework\TestCase;

final class RuntimeProfileTest extends TestCase
{
    public function test_runtime_profile_serialization_is_deterministic(): void
    {
        $profile = new RuntimeProfile(
            mode: 'job',
            requiredCapabilities: ['job-loop', 'messaging'],
            preferredCapabilities: [],
            preferredRunner: null,
            installRunner: null,
        );

        $this->assertSame([
            'mode' => 'job',
            'required_capabilities' => ['job-loop', 'messaging'],
            'preferred_capabilities' => [],
            'preferred_runner' => null,
            'install_runner' => null,
        ], $profile->toArray());
    }

    public function test_minimal_requirements(): void
    {
        $runtime = (new DefaultPresetRegistryFactory())->create()->definition('minimal')->runtime();

        $this->assertSame('http', $runtime->mode);
        $this->assertSame(['persistent-http'], $runtime->requiredCapabilities);
        $this->assertSame([], $runtime->preferredCapabilities);
        $this->assertNull($runtime->preferredRunner);
        $this->assertNull($runtime->installRunner);
    }

    public function test_service_requirements(): void
    {
        $definition = (new DefaultPresetRegistryFactory())->create()->definition('service');
        $runtime = $definition->runtime();
        $metadata = $definition->metadata();

        $this->assertSame('http', $runtime->mode);
        $this->assertSame(['persistent-http'], $runtime->requiredCapabilities);
        $this->assertNull($runtime->preferredRunner);
        $this->assertContains('dependency-injection', $metadata->capabilities);
        $this->assertContains('persistence-ready', $metadata->capabilities);
        $this->assertNotContains('dependency-injection', $runtime->requiredCapabilities);
        $this->assertNotContains('persistence-ready', $runtime->requiredCapabilities);
    }

    public function test_worker_requirements(): void
    {
        $runtime = (new DefaultPresetRegistryFactory())->create()->definition('worker')->runtime();

        $this->assertSame('job', $runtime->mode);
        $this->assertSame(['job-loop', 'messaging'], $runtime->requiredCapabilities);
        $this->assertNull($runtime->preferredRunner);
        $this->assertNull($runtime->installRunner);
        $this->assertNotSame('worker', $runtime->mode);
        $this->assertNotSame('consumer', $runtime->mode);
        $this->assertNotSame('eregion', $runtime->preferredRunner);
    }
}
