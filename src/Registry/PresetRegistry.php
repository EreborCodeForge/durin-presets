<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Registry;

use EreborCodeForge\Durin\Core\Contract\Preset;
use EreborCodeForge\Durin\Core\Contract\PresetRegistry as PresetRegistryContract;
use EreborCodeForge\Durin\Presets\Contract\PresetDefinition;
use EreborCodeForge\Durin\Presets\Preset\UnknownPresetException;

/**
 * In-memory preset catalog. Built-in IDs and the default live only here (via factory).
 */
final class PresetRegistry implements PresetRegistryContract
{
    /** @var array<string, Preset> */
    private array $presets = [];

    public function __construct(
        private readonly ?string $defaultId = null,
    ) {}

    public function register(Preset $preset): void
    {
        $id = $preset instanceof PresetDefinition ? $preset->id() : $preset->name();
        $this->presets[$id] = $preset;
    }

    public function has(string $name): bool
    {
        return isset($this->presets[$name]);
    }

    public function get(string $name): Preset
    {
        if (!isset($this->presets[$name])) {
            throw UnknownPresetException::forName($name, $this->ids());
        }

        return $this->presets[$name];
    }

    /**
     * @return PresetDefinition
     */
    public function definition(string $id): PresetDefinition
    {
        $preset = $this->get($id);
        if (!$preset instanceof PresetDefinition) {
            throw new \LogicException("Preset \"{$id}\" does not implement PresetDefinition.");
        }

        return $preset;
    }

    /**
     * @return list<PresetDefinition>
     */
    public function all(): array
    {
        $definitions = [];
        foreach ($this->presets as $preset) {
            if ($preset instanceof PresetDefinition) {
                $definitions[] = $preset;
            }
        }

        return $definitions;
    }

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys($this->presets);
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return $this->ids();
    }

    public function default(): PresetDefinition
    {
        if ($this->defaultId === null) {
            throw new \LogicException('No default preset configured for this registry.');
        }

        return $this->definition($this->defaultId);
    }

    public function defaultId(): ?string
    {
        return $this->defaultId;
    }

    /**
     * Deterministic catalog payload for installer / Forge JSON output.
     *
     * @return array{default: ?string, presets: list<array<string, mixed>>}
     */
    public function catalog(): array
    {
        $presets = [];
        foreach ($this->all() as $definition) {
            $presets[] = array_merge(
                $definition->metadata()->toArray(),
                ['runtime' => $definition->runtime()->toArray()],
            );
        }

        return [
            'default' => $this->defaultId,
            'presets' => $presets,
        ];
    }
}
