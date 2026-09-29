<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use PHPUnit\Framework\TestCase;

final class BuiltInPresetCatalogTest extends TestCase
{
    public function test_catalog_payload_includes_metadata_and_runtime(): void
    {
        $catalog = (new DefaultPresetRegistryFactory())->create()->catalog();

        $this->assertSame('minimal', $catalog['default']);
        $this->assertCount(3, $catalog['presets']);
        $this->assertSame(
            ['minimal', 'service', 'worker'],
            array_column($catalog['presets'], 'id'),
        );

        $byId = [];
        foreach ($catalog['presets'] as $row) {
            $byId[$row['id']] = $row;
        }

        $this->assertSame('http', $byId['minimal']['runtime']['mode']);
        $this->assertSame(['persistent-http'], $byId['minimal']['runtime']['required_capabilities']);
        $this->assertNull($byId['minimal']['runtime']['preferred_runner']);

        $this->assertSame('http', $byId['service']['runtime']['mode']);
        $this->assertSame(['persistent-http'], $byId['service']['runtime']['required_capabilities']);
        $this->assertNull($byId['service']['runtime']['preferred_runner']);

        $this->assertSame('job', $byId['worker']['runtime']['mode']);
        $this->assertSame(['job-loop', 'messaging'], $byId['worker']['runtime']['required_capabilities']);
        $this->assertNull($byId['worker']['runtime']['preferred_runner']);

        $encoded = json_encode($catalog, JSON_THROW_ON_ERROR);
        $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($catalog, $decoded);
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
