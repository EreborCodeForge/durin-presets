<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

final class ScaffoldCollisionException extends \RuntimeException
{
    public static function forPath(string $path, string $capabilityId): self
    {
        return new self(
            "Scaffold collision on \"{$path}\" while applying capability \"{$capabilityId}\"."
        );
    }
}
