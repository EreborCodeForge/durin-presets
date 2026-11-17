<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * Non-HTTP job / queue / scheduled worker preset (master §21, SPEC-DX-017).
 * Declares runtime requirements (job-loop + messaging); Forge resolves the runner.
 */
final class WorkerPreset implements PresetDefinition
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function id(): string
    {
        return 'worker';
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Worker',
            description: 'Background worker',
            category: 'worker',
            capabilities: ['messaging', 'jobs'],
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

        return (new JobApplicationLayout($this->files))->plan(
            options: $options,
            presetId: $this->id(),
            jobKernel: $this->files->jobKernel($app),
            readme: $this->files->readmeWorker($app, [
                'src/JobKernel.php',
                'src/Application',
                'src/Infrastructure',
                'src/Jobs',
                'config',
                'tests',
            ]),
            envExample: $this->files->envExampleWorker(),
        );
    }
}
