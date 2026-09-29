<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * Future-shaped preset with runtime requirements and no preferred runner.
 */
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
            capabilities: ['api'],
        );
    }

    public function runtime(): RuntimeProfile
    {
        return new RuntimeProfile(
            mode: 'http',
            requiredCapabilities: ['persistent-http'],
        );
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
