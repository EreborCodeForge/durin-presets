<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * General-purpose backend service preset (master §18).
 * Layer roots only — no empty Entity/Repository ceremony trees.
 */
final class ServicePreset implements PresetDefinition
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function id(): string
    {
        return 'service';
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Service',
            description: 'General-purpose structured backend service',
            category: 'service',
            capabilities: ['http', 'dependency-injection', 'persistence-ready'],
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
        $app = $options->name;

        return (new HttpServiceLayout($this->files))->plan(
            options: $options,
            presetId: $this->id(),
            messaging: false,
            readme: $this->files->readme($app, 'service', [
                'src/Domain',
                'src/Application',
                'src/Infrastructure',
                'src/Presentation',
                'routes',
                'config',
                'tests',
            ]),
            envExample: $this->files->envExample(),
            indexLabel: 'Service',
        );
    }
}
