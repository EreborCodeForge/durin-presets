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
 * Requires Mithril ^2.2 JobApplication + bin/job-worker (SPEC-MITHRIL-001).
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
            mode: 'worker',
            capabilities: ['messaging'],
        );
    }

    public function scaffold(ProjectOptions $options): ScaffoldPlan
    {
        return $this->plan($options);
    }

    public function plan(ProjectOptions $options): ScaffoldPlan
    {
        $plan = new ScaffoldPlan();
        $app = $options->name;
        $package = $this->files->composerPackageName($app);

        $workerOptions = new ProjectOptions(
            name: $options->name,
            preset: $this->id(),
            targetDirectory: $options->targetDirectory,
            runtimeEngine: $options->runtimeEngine,
            runtimeServer: 'none',
            runtimeMode: 'job',
            http: false,
            messaging: true,
            modules: false,
            extra: $options->extra,
        );

        $plan
            ->directory('src')
            ->directory('src/Application')
            ->directory('src/Infrastructure')
            ->directory('src/Jobs')
            ->directory('config')
            ->directory('tests')
            ->directory('var/cache')
            ->directory('var/runtime')
            ->file('src/Application/.gitkeep', '')
            ->file('src/Infrastructure/.gitkeep', '')
            ->file('src/Jobs/.gitkeep', '')
            ->file('src/JobKernel.php', $this->files->jobKernel($app))
            ->file('config/app.php', $this->files->configApp($app))
            ->file('tests/ExampleTest.php', $this->files->exampleTest())
            ->file('composer.json', $this->files->composerJsonWorker($package))
            ->file('.env.example', $this->files->envExampleWorker())
            ->file('README.md', $this->files->readmeWorker($app, [
                'src/JobKernel.php',
                'src/Application',
                'src/Infrastructure',
                'src/Jobs',
                'config',
                'tests',
            ]));

        (new ManifestPlanFactory())->appendManifest($plan, $workerOptions);

        return $plan;
    }
}
