<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

/**
 * Shared HTTP service layout used by service and producer.
 */
final class HttpServiceLayout
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function plan(
        ProjectOptions $options,
        string $presetId,
        bool $messaging,
        string $readme,
        string $envExample,
        string $indexLabel,
    ): ScaffoldPlan {
        $plan = new ScaffoldPlan();
        $package = $this->files->composerPackageName($options->name);

        $manifestOptions = new ProjectOptions(
            name: $options->name,
            preset: $presetId,
            targetDirectory: $options->targetDirectory,
            http: true,
            messaging: $messaging,
            modules: false,
            extra: $options->extra,
            selection: $options->selection,
        );

        $plan
            ->directory('src/Domain')
            ->directory('src/Application')
            ->directory('src/Infrastructure')
            ->directory('src/Presentation')
            ->directory('routes')
            ->directory('config')
            ->directory('tests')
            ->directory('public')
            ->directory('var/cache')
            ->directory('var/runtime')
            ->file('src/Domain/.gitkeep', '')
            ->file('src/Application/.gitkeep', '')
            ->file('src/Infrastructure/.gitkeep', '')
            ->file('src/Presentation/.gitkeep', '')
            ->file('routes/api.php', $this->files->routesApi())
            ->file('routes/web.php', $this->files->routesWeb())
            ->file('config/app.php', $this->files->configApp($options->name))
            ->file('src/Kernel.php', $this->files->applicationKernel())
            ->file('public/index.php', $this->files->publicIndex($indexLabel))
            ->file('tests/ExampleTest.php', $this->files->exampleTest())
            ->file('composer.json', $this->files->composerJson($package))
            ->file('.env.example', $envExample)
            ->file('README.md', $readme);

        (new ManifestPlanFactory())->appendManifest($plan, $manifestOptions);

        return $plan;
    }
}
