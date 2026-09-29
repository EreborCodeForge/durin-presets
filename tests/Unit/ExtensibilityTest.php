<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Presets\Registry\PresetEngine;
use EreborCodeForge\Durin\Presets\Registry\PresetRegistry;
use PHPUnit\Framework\TestCase;

final class ExtensibilityTest extends TestCase
{
    public function test_adding_fake_preset_requires_no_consumer_change(): void
    {
        $registry = new PresetRegistry(defaultId: 'minimal');
        $registry->register(new \EreborCodeForge\Durin\Presets\Preset\MinimalPreset());
        $registry->register(new CatalogFakePreset('extra'));

        $engine = new PresetEngine($registry);

        $this->assertTrue($registry->has('extra'));
        $this->assertContains('extra', $registry->ids());

        $plan = $engine->plan(new ProjectOptions('x', 'extra', '/tmp/x'));
        $paths = array_map(static fn ($a) => $a->relativePath, $plan->actions());
        $this->assertContains('src', $paths);
        $this->assertContains('durin.yaml', $paths);
    }

    public function test_fake_future_preset_has_requirements_without_runner(): void
    {
        $preset = new CatalogFakePreset('api');
        $runtime = $preset->runtime();

        $this->assertSame('http', $runtime->mode);
        $this->assertSame(['persistent-http'], $runtime->requiredCapabilities);
        $this->assertNull($runtime->preferredRunner);
        $this->assertArrayNotHasKey('runner', $runtime->toArray());
    }

    public function test_legacy_preset_still_registers_via_core_contract(): void
    {
        $registry = new PresetRegistry();
        $registry->register(new LegacyFakePreset());

        $this->assertTrue($registry->has('legacy'));
        $this->assertSame('legacy', $registry->get('legacy')->name());
    }
}
