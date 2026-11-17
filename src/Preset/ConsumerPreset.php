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
 * Job preset specialized for consumption. Reuses the worker layout and mithril-job.
 * Consumer is a scaffold feature, not a runtime mode and not a supervisor.
 */
final class ConsumerPreset implements PresetDefinition
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function id(): string
    {
        return 'consumer';
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Consumer',
            description: 'Job consumer without a broker or a default supervisor',
            category: 'worker',
            capabilities: ['messaging', 'jobs', 'consumer'],
        );
    }

    public function runtime(): RuntimeProfile
    {
        return new RuntimeProfile(
            mode: 'job',
            requiredCapabilities: ['job-loop', 'messaging'],
        );
    }

    public function scaffold(ProjectOptions $options): ScaffoldPlan
    {
        return $this->plan($options);
    }

    public function plan(ProjectOptions $options): ScaffoldPlan
    {
        $app = $options->name;
        $plan = (new JobApplicationLayout($this->files))->plan(
            options: $options,
            presetId: $this->id(),
            jobKernel: $this->files->consumerJobKernel($app),
            readme: $this->files->readmeConsumer($app, [
                'src/JobKernel.php',
                'src/Application/Handlers',
                'src/Infrastructure/Messaging',
                'src/Jobs',
                'config/messaging.php',
                'tests/ConsumerScaffoldTest.php',
            ]),
            envExample: $this->files->envExampleConsumer(),
            extraDirectories: [
                'src/Application/Handlers',
                'src/Infrastructure/Messaging',
            ],
        );

        return (new CapabilityScaffoldComposer($this->files))->merge($plan, ['consumer']);
    }
}
