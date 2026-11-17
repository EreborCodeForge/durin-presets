<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Registry;

use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Capability\CapabilityCatalog;
use EreborCodeForge\Durin\Presets\Capability\CapabilityScaffoldComposer;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Preset\ManifestPlanFactory;

/**
 * Resolves a preset and returns a ScaffoldPlan (no filesystem writes).
 */
final class PresetEngine
{
    public function __construct(
        private readonly PresetRegistry $registry,
        private readonly ManifestPlanFactory $manifestFactory = new ManifestPlanFactory(),
        private readonly CapabilityCatalog $capabilities = new CapabilityCatalog(),
        private readonly CapabilityScaffoldComposer $composer = new CapabilityScaffoldComposer(),
    ) {}

    public function registry(): PresetRegistry
    {
        return $this->registry;
    }

    public function plan(ProjectOptions $options): ScaffoldPlan
    {
        $preset = $this->registry->get($options->preset);
        $plan = $preset instanceof PresetDefinition
            ? $preset->plan($options)
            : $preset->scaffold($options);

        if (!$this->planHasDurinYaml($plan)) {
            $this->manifestFactory->appendManifest($plan, $options);
        }

        $rawWith = $options->extra['with'] ?? $options->selection?->features;
        $with = $this->capabilities->normalize($rawWith ?? null);
        if ($with === []) {
            return $plan;
        }

        $mode = $preset instanceof PresetDefinition ? $preset->runtime()->mode : null;
        $this->capabilities->assertCompatible($options->preset, $mode, $with);
        $plan = $this->composer->merge($plan, $with);

        return $this->composer->enableMessaging($plan, $with);
    }

    private function planHasDurinYaml(ScaffoldPlan $plan): bool
    {
        foreach ($plan->actions() as $action) {
            if ($action->relativePath === 'durin.yaml') {
                return true;
            }
        }

        return false;
    }
}
