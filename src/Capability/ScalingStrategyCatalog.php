<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

/**
 * Names the scaling strategies a preset may select.
 * The catalog does not change the preset default: omission still means no strategy flag.
 */
final class ScalingStrategyCatalog
{
    /** @var list<string> */
    public const array LEGACY = WorkloadOptionPolicy::LEGACY;

    /** @var list<string> */
    public const array ADVANCED = WorkloadOptionPolicy::ADVANCED;

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return [...self::LEGACY, ...self::ADVANCED];
    }

    public function defaultStrategy(): ?string
    {
        return null;
    }
}
