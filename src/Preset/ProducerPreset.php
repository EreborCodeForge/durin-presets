<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Capability\CapabilityScaffoldComposer;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * Convenience preset for an HTTP service with a fail-closed publisher scaffold.
 * Runtime semantics match service + producer. There is no mithril-producer.
 */
final class ProducerPreset implements PresetDefinition
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function id(): string
    {
        return 'producer';
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Producer',
            description: 'HTTP service with a fail-closed publisher scaffold',
            category: 'service',
            capabilities: ['http', 'messaging', 'producer'],
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
        $plan = (new HttpServiceLayout($this->files))->plan(
            options: $options,
            presetId: $this->id(),
            messaging: true,
            readme: $this->files->readmeProducer($app, [
                'src/Domain',
                'src/Application',
                'src/Infrastructure/Messaging',
                'src/Presentation',
                'src/Kernel.php',
                'config/messaging.php',
                'routes',
            ]),
            envExample: $this->files->envExampleProducer(),
            indexLabel: 'Producer',
        );

        return (new CapabilityScaffoldComposer($this->files))->merge($plan, ['producer']);
    }
}
