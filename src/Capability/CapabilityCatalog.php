<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

/**
 * Single catalog of composable capabilities. Does not choose a supervisor or a broker.
 */
final class CapabilityCatalog
{
    /**
     * @var array<string, array{id: string, description: string, presets: list<string>}>
     */
    private const array DEFINITIONS = [
        'consumer' => [
            'id' => 'consumer',
            'description' => 'Job consumption scaffold. Does not select a supervisor or a broker.',
            'presets' => ['worker', 'consumer'],
        ],
        'producer' => [
            'id' => 'producer',
            'description' => 'Injectable publisher scaffold. Does not install a broker or a public publish route.',
            'presets' => ['minimal', 'service', 'producer', 'worker', 'consumer'],
        ],
    ];

    /**
     * @return list<string>
     */
    public function ids(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    /**
     * @return list<array{id: string, description: string, presets: list<string>}>
     */
    public function toArray(): array
    {
        return array_values(self::DEFINITIONS);
    }

    /**
     * @return list<string>
     */
    public function presetsFor(string $capabilityId): array
    {
        return self::DEFINITIONS[$capabilityId]['presets'] ?? [];
    }

    /**
     * Accepts a list of ids or a comma-separated string. Duplicates are removed
     * and the result is ordered canonically.
     *
     * @return list<string>
     */
    public function normalize(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }

        if (is_string($value)) {
            $tokens = explode(',', $value);
        } elseif (is_array($value)) {
            $tokens = $value;
        } else {
            throw new IncompatibleCapabilityException('--with must be a list of capability ids.');
        }

        $unique = [];
        foreach ($tokens as $token) {
            if (!is_string($token)) {
                throw new IncompatibleCapabilityException('--with must be a list of capability ids.');
            }
            $token = trim($token);
            if ($token === '') {
                throw new IncompatibleCapabilityException('Empty capability token in --with.');
            }
            if (!isset(self::DEFINITIONS[$token])) {
                $available = implode(', ', $this->ids());
                throw new IncompatibleCapabilityException(
                    "Unknown capability \"{$token}\". Available: {$available}."
                );
            }
            $unique[$token] = $token;
        }

        $normalized = array_values($unique);
        sort($normalized);

        return $normalized;
    }

    /**
     * @param list<string> $capabilityIds already normalized
     */
    public function assertCompatible(string $presetId, ?string $mode, array $capabilityIds): void
    {
        foreach ($capabilityIds as $capabilityId) {
            $allowed = $this->presetsFor($capabilityId);
            if (in_array($presetId, $allowed, true)) {
                continue;
            }

            $modeLabel = $mode ?? 'unknown';
            throw new IncompatibleCapabilityException(
                "Capability \"{$capabilityId}\" is not supported on preset \"{$presetId}\" ({$modeLabel}). "
                . $this->restriction($capabilityId)
            );
        }
    }

    private function restriction(string $capabilityId): string
    {
        if ($capabilityId === 'consumer') {
            return '--with=consumer is restricted to job presets.';
        }

        return 'The capability is not available for this preset.';
    }
}
