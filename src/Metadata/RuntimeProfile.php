<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Metadata;

/**
 * Runtime requirements expressed by a preset (Forge owns install/config).
 *
 * runner = null means use Forge default runner.
 * installRunner = null means use Forge default install policy.
 *
 * @param list<string> $capabilities
 */
final readonly class RuntimeProfile
{
    /**
     * @param list<string> $capabilities
     */
    public function __construct(
        public string $mode,
        public ?string $runner = null,
        public ?bool $installRunner = null,
        public array $capabilities = [],
    ) {}

    /**
     * @return array{
     *     mode: string,
     *     runner: ?string,
     *     install_runner: ?bool,
     *     capabilities: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'runner' => $this->runner,
            'install_runner' => $this->installRunner,
            'capabilities' => array_values($this->capabilities),
        ];
    }
}
