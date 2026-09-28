<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Metadata;

/**
 * Read-only discovery metadata for a preset (installer / Forge catalog).
 */
final readonly class PresetMetadata
{
    /**
     * @param list<string> $capabilities
     * @param list<string> $tags
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $description,
        public string $category,
        public array $capabilities = [],
        public array $tags = [],
        public bool $experimental = false,
    ) {}

    /**
     * @return array{
     *     id: string,
     *     label: string,
     *     description: string,
     *     category: string,
     *     capabilities: list<string>,
     *     tags: list<string>,
     *     experimental: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'category' => $this->category,
            'capabilities' => array_values($this->capabilities),
            'tags' => array_values($this->tags),
            'experimental' => $this->experimental,
        ];
    }
}
