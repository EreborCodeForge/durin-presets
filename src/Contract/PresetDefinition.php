<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Contract;

use EreborCodeForge\Durin\Core\Contract\Preset;
use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use EreborCodeForge\Durin\Presets\Metadata\RuntimeProfile;

/**
 * Public preset contract: identity, discovery metadata, runtime needs, and plan.
 *
 * Consumers discover via PresetRegistry; they must not infer presets from class names.
 */
interface PresetDefinition extends Preset
{
    public function id(): string;

    public function metadata(): PresetMetadata;

    public function runtime(): RuntimeProfile;

    public function plan(ProjectOptions $options): ScaffoldPlan;
}
