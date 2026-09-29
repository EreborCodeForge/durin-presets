<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Runtime\RuntimeIntent;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * Small HTTP API / webhook preset (master §17). No Domain ceremony.
 */
final class MinimalPreset implements PresetDefinition
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    public function id(): string
    {
        return 'minimal';
    }

    public function name(): string
    {
        return $this->id();
    }

    public function metadata(): PresetMetadata
    {
        return new PresetMetadata(
            id: $this->id(),
            label: 'Minimal',
            description: 'Minimal application',
            category: 'application',
            capabilities: ['http'],
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
        $plan = new ScaffoldPlan();
        $app = $options->name;
        $package = $this->files->composerPackageName($app);

        $minimalOptions = new ProjectOptions(
            name: $options->name,
            preset: $this->id(),
            targetDirectory: $options->targetDirectory,
            runtime: new RuntimeIntent(mode: 'http', capabilities: ['persistent-http']),
            http: true,
            messaging: false,
            modules: false,
            extra: $options->extra,
        );

        $plan
            ->directory('src/Http')
            ->directory('src/Application')
            ->directory('routes')
            ->directory('config')
            ->directory('tests')
            ->directory('public')
            ->directory('var/cache')
            ->directory('var/runtime')
            ->file('src/Http/.gitkeep', '')
            ->file('src/Application/.gitkeep', '')
            ->file('routes/api.php', $this->files->routesApi())
            ->file('routes/web.php', $this->files->routesWeb())
            ->file('config/app.php', $this->files->configApp($app))
            ->file('src/Kernel.php', $this->files->applicationKernel())
            ->file('public/index.php', $this->files->publicIndex('Minimal'))
            ->file('tests/ExampleTest.php', $this->files->exampleTest())
            ->file('composer.json', $this->files->composerJson($package))
            ->file('.env.example', $this->files->envExample())
            ->file('README.md', $this->files->readme($app, 'minimal', [
                'src/Http',
                'src/Application',
                'routes',
                'config',
                'tests',
            ]));

        (new ManifestPlanFactory())->appendManifest($plan, $minimalOptions);

        return $plan;
    }
}
