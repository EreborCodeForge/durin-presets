<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Manifest\DurinManifest;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

/**
 * Helpers to attach durin.yaml (and related metadata) to a ScaffoldPlan.
 * Presets must not write files themselves — only plan.
 * Runtime stays unresolved; Forge resolves engine/execution/supervisor.
 */
final class ManifestPlanFactory
{
    public function forOptions(ProjectOptions $options): DurinManifest
    {
        return new DurinManifest(
            applicationName: $options->name,
            preset: $options->preset,
            features: [
                'http' => $options->http,
                'messaging' => $options->messaging,
            ],
            architecture: [
                'modules' => $options->modules,
            ],
            runtime: null,
        );
    }

    public function appendManifest(ScaffoldPlan $plan, ProjectOptions $options): ScaffoldPlan
    {
        $manifest = $this->forOptions($options);

        return $plan->file('durin.yaml', $manifest->toYaml());
    }
}
