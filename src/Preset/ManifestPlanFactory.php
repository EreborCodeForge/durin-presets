<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Preset;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Manifest\DurinManifest;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Capability\CapabilityCatalog;

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
            features: $this->featuresFor($options),
            architecture: [
                'modules' => $options->modules,
            ],
            runtime: null,
        );
    }

    /**
     * @return array{http: bool, messaging: bool, consumer: bool, producer: bool}
     */
    private function featuresFor(ProjectOptions $options): array
    {
        $with = $options->selection?->features ?? [];
        if ($with === [] && isset($options->extra['with'])) {
            $with = (new CapabilityCatalog())->normalize($options->extra['with']);
        }

        $consumer = $options->preset === 'consumer' || in_array('consumer', $with, true);
        $producer = $options->preset === 'producer' || in_array('producer', $with, true);

        return [
            'http' => $options->http,
            'messaging' => $options->messaging || $consumer || $producer,
            'consumer' => $consumer,
            'producer' => $producer,
        ];
    }

    public function appendManifest(ScaffoldPlan $plan, ProjectOptions $options): ScaffoldPlan
    {
        $manifest = $this->forOptions($options);

        return $plan->file('durin.yaml', $manifest->toYaml());
    }
}
