<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Registry;

use EreborCodeForge\Durin\Presets\Preset\MinimalPreset;
use EreborCodeForge\Durin\Presets\Preset\ServicePreset;
use EreborCodeForge\Durin\Presets\Preset\WorkerPreset;

/**
 * Composition point for shipped presets. Only this package knows built-in IDs
 * and the ecosystem default preset id.
 */
final class DefaultPresetRegistryFactory
{
    private const string DEFAULT_ID = 'minimal';

    public function create(): PresetRegistry
    {
        $registry = new PresetRegistry(defaultId: self::DEFAULT_ID);
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
