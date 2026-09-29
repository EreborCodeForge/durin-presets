<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Preset\UnknownPresetException;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use EreborCodeForge\Durin\Presets\Registry\PresetRegistry;
use PHPUnit\Framework\TestCase;

final class PresetRegistryTest extends TestCase
{
    public function test_catalog_returns_all_registered_built_ins(): void
    {
        $registry = (new DefaultPresetRegistryFactory())->create();
        $ids = $registry->ids();

        $this->assertSame(['minimal', 'service', 'worker'], $ids);
        $this->assertCount(3, $registry->all());
        $this->assertTrue($registry->has('service'));
    }

    public function test_default_comes_only_from_registry(): void
    {
        $registry = (new DefaultPresetRegistryFactory())->create();

        $this->assertSame('minimal', $registry->default()->id());
        $this->assertSame('minimal', $registry->defaultId());
        $this->assertSame('minimal', $registry->default()->metadata()->id);
    }

    public function test_unknown_id_lists_valid_ids(): void
    {
        $registry = (new DefaultPresetRegistryFactory())->create();

        $this->expectException(UnknownPresetException::class);
        $this->expectExceptionMessage('Available: minimal, service, worker');

        $registry->get('missing');
    }

    public function test_custom_default_id(): void
    {
        $registry = new PresetRegistry(defaultId: 'catalog-fake');
        $registry->register(new CatalogFakePreset());

        $this->assertSame('catalog-fake', $registry->default()->id());
    }

    public function test_default_without_configuration_fails(): void
    {
        $registry = new PresetRegistry();
        $registry->register(new CatalogFakePreset());

        $this->expectException(\LogicException::class);
        $registry->default();
    }
}
