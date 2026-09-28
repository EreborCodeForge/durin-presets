<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\Preset;
use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;
use EreborCodeForge\Durin\Presets\Preset\UnknownPresetException;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use EreborCodeForge\Durin\Presets\Registry\PresetEngine;
use EreborCodeForge\Durin\Presets\Registry\PresetRegistry;
use PHPUnit\Framework\TestCase;

final class CatalogFakePreset implements PresetDefinition
{
    public function __construct(
        private readonly string $presetId = 'catalog-fake',
    ) {}

    public function id(): string
    {
        return $this->presetId;
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Catalog Fake',
            description: 'Extensibility probe',
            category: 'test',
        );
    }

    public function runtime(): RuntimeProfile
    {
        return new RuntimeProfile(mode: 'http');
    }

    public function scaffold(ProjectOptions $options): ScaffoldPlan
    {
        return $this->plan($options);
    }

    public function plan(ProjectOptions $options): ScaffoldPlan
    {
        return (new ScaffoldPlan())->directory('src')->file('src/.gitkeep', '');
    }
}

final class LegacyFakePreset implements Preset
{
    public function name(): string
    {
        return 'legacy';
    }

    public function scaffold(ProjectOptions $options): ScaffoldPlan
    {
        return (new ScaffoldPlan())->directory('src');
    }
}

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

final class DefaultPresetTest extends TestCase
{
    public function test_factory_default_is_minimal(): void
    {
        $registry = (new DefaultPresetRegistryFactory())->create();
        $this->assertSame('minimal', $registry->default()->id());
        $this->assertSame('Minimal', $registry->default()->metadata()->label);
    }
}

final class PresetMetadataTest extends TestCase
{
    public function test_metadata_serialization_is_deterministic(): void
    {
        $meta = new PresetMetadata(
            id: 'service',
            label: 'Service',
            description: 'General-purpose structured backend service',
            category: 'service',
            capabilities: ['http', 'dependency-injection'],
            tags: ['backend'],
            experimental: false,
        );

        $this->assertSame([
            'id' => 'service',
            'label' => 'Service',
            'description' => 'General-purpose structured backend service',
            'category' => 'service',
            'capabilities' => ['http', 'dependency-injection'],
            'tags' => ['backend'],
            'experimental' => false,
        ], $meta->toArray());
    }
}

final class RuntimeProfileTest extends TestCase
{
    public function test_runtime_profile_serialization_is_deterministic(): void
    {
        $profile = new RuntimeProfile(
            mode: 'worker',
            runner: null,
            installRunner: null,
            capabilities: ['messaging'],
        );

        $this->assertSame([
            'mode' => 'worker',
            'runner' => null,
            'install_runner' => null,
            'capabilities' => ['messaging'],
        ], $profile->toArray());
    }
}

final class BuiltInPresetCatalogTest extends TestCase
{
    public function test_catalog_payload_includes_metadata_and_runtime(): void
    {
        $catalog = (new DefaultPresetRegistryFactory())->create()->catalog();

        $this->assertSame('minimal', $catalog['default']);
        $this->assertCount(3, $catalog['presets']);

        $byId = [];
        foreach ($catalog['presets'] as $row) {
            $byId[$row['id']] = $row;
        }

        $this->assertSame('http', $byId['minimal']['runtime']['mode']);
        $this->assertSame('http', $byId['service']['runtime']['mode']);
        $this->assertSame('worker', $byId['worker']['runtime']['mode']);
        $this->assertNull($byId['service']['runtime']['runner']);
    }

    public function test_all_built_ins_produce_valid_plans(): void
    {
        $engine = (new DefaultPresetRegistryFactory())->engine();

        foreach (['minimal', 'service', 'worker'] as $id) {
            $plan = $engine->plan(new ProjectOptions('demo', $id, '/tmp/demo'));
            $paths = array_map(static fn ($a) => $a->relativePath, $plan->actions());
            $this->assertContains('durin.yaml', $paths, "preset {$id} must plan durin.yaml");
            $this->assertNotEmpty($plan->actions());
        }
    }
}

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

    public function test_legacy_preset_still_registers_via_core_contract(): void
    {
        $registry = new PresetRegistry();
        $registry->register(new LegacyFakePreset());

        $this->assertTrue($registry->has('legacy'));
        $this->assertSame('legacy', $registry->get('legacy')->name());
    }
}
