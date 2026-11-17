<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

/**
 * Shared job application layout used by worker and consumer.
 */
final class JobApplicationLayout
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    /**
     * @param list<string> $extraDirectories
     */
    public function plan(
        ProjectOptions $options,
        string $presetId,
        string $jobKernel,
        string $readme,
        string $envExample,
        array $extraDirectories = [],
    ): ScaffoldPlan {
        $plan = new ScaffoldPlan();
        $package = $this->files->composerPackageName($options->name);

        $manifestOptions = new ProjectOptions(
            name: $options->name,
            preset: $presetId,
            targetDirectory: $options->targetDirectory,
            http: false,
            messaging: true,
            modules: false,
            extra: $options->extra,
            selection: $options->selection,
        );

        $plan
            ->directory('src')
            ->directory('src/Application')
            ->directory('src/Infrastructure')
            ->directory('src/Jobs')
            ->directory('config')
            ->directory('tests')
            ->directory('var/cache')
            ->directory('var/runtime');

        foreach ($extraDirectories as $directory) {
            $plan->directory($directory);
        }

        $plan
            ->file('src/Application/.gitkeep', '')
            ->file('src/Infrastructure/.gitkeep', '')
            ->file('src/Jobs/.gitkeep', '')
            ->file('src/JobKernel.php', $jobKernel)
            ->file('config/app.php', $this->files->configApp($options->name))
            ->file('tests/ExampleTest.php', $this->files->exampleTest())
            ->file('composer.json', $this->files->composerJsonWorker($package))
            ->file('.env.example', $envExample)
            ->file('README.md', $readme);

        (new ManifestPlanFactory())->appendManifest($plan, $manifestOptions);

        return $plan;
    }
}
