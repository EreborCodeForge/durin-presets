<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Core\Contract\Preset;
use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;

final class LegacyFakePreset implements Preset
{
    public function name(): string
    {
        return 'legacy';
    }

    public function scaffold(ProjectOptions $options): ScaffoldPlan
    {
        return (new ScaffoldPlan())->directory('src');
    }
}
