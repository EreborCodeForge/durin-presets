<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Metadata;

/**
 * Runtime requirements expressed by a preset (Forge owns resolution/install).
 *
 * Presets declare what the application needs; they do not pick a concrete runtime.
 * preferredRunner is an optional hint, never a requirement.
 * installRunner = null means use Forge default install policy.
 *
 * @param list<string> $requiredCapabilities
 * @param list<string> $preferredCapabilities
 */
final readonly class RuntimeProfile
{
    /**
     * @param list<string> $requiredCapabilities
     * @param list<string> $preferredCapabilities
     */
    public function __construct(
        public string $mode,
        public array $requiredCapabilities = [],
        public array $preferredCapabilities = [],
        public ?string $preferredRunner = null,
        public ?bool $installRunner = null,
    ) {}

    /**
     * @return array{
     *     mode: string,
     *     required_capabilities: list<string>,
     *     preferred_capabilities: list<string>,
     *     preferred_runner: ?string,
     *     install_runner: ?bool
     * }
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'required_capabilities' => array_values($this->requiredCapabilities),
            'preferred_capabilities' => array_values($this->preferredCapabilities),
            'preferred_runner' => $this->preferredRunner,
            'install_runner' => $this->installRunner,
        ];
    }
}
