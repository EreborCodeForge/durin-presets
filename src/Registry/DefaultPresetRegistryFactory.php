<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Registry;

use EreborCodeForge\Durin\Presets\Preset\MinimalPreset;
use EreborCodeForge\Durin\Presets\Preset\ServicePreset;
use EreborCodeForge\Durin\Presets\Preset\WorkerPreset;

/**
 * Builds the default registry of shipped presets.
 */
final class DefaultPresetRegistryFactory
{
    public function create(): PresetRegistry
    {
        $registry = new PresetRegistry();
        $registry->register(new MinimalPreset());
        $registry->register(new ServicePreset());
        $registry->register(new WorkerPreset());

        return $registry;
    }

    public function engine(): PresetEngine
    {
        return new PresetEngine($this->create());
    }
}
