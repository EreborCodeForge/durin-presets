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
        $plan = new ScaffoldPlan();
        $app = $options->name;
        $package = $this->files->composerPackageName($app);

        $serviceOptions = new ProjectOptions(
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
            ->file('config/app.php', $this->files->configApp($app))
            ->file('src/Kernel.php', $this->files->applicationKernel())
            ->file('public/index.php', $this->files->publicIndex('Service'))
            ->file('tests/ExampleTest.php', $this->files->exampleTest())
            ->file('composer.json', $this->files->composerJson($package))
            ->file('.env.example', $this->files->envExample())
            ->file('README.md', $this->files->readme($app, 'service', [
                'src/Domain',
                'src/Application',
                'src/Infrastructure',
                'src/Presentation',
                'routes',
                'config',
                'tests',
            ]));

        (new ManifestPlanFactory())->appendManifest($plan, $serviceOptions);

        return $plan;
    }
}
