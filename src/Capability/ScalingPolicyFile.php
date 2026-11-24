<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

/**
 * Reads one scaling fragment. The file cannot select a strategy, a guard, or a command.
 * Tags, anchors and aliases are rejected before the text is forwarded.
 */
final class ScalingPolicyFile
{
    private const int MAX_BYTES = 65536;

    /**
     * @return array{raw: string}
     */
    public static function read(string $path, string $strategy, string $mode): array
    {
        if ($path === '' || strpbrk($path, "\0") !== false) {
            throw new WorkloadOptionException('Invalid --scaling-policy path.');
        }
        if (!is_file($path) || is_link($path)) {
            throw new WorkloadOptionException('--scaling-policy must be a regular file.');
        }
        $size = filesize($path);
        if ($size === false || $size > self::MAX_BYTES) {
            throw new WorkloadOptionException('--scaling-policy must be a regular file of at most 64 KiB.');
        }
        $raw = file_get_contents($path);
        if (!is_string($raw)) {
            throw new WorkloadOptionException('Unable to read --scaling-policy.');
        }
        if (str_contains($raw, "\t") || str_contains($raw, '!') || str_contains($raw, '&') || str_contains($raw, '*')) {
            throw new WorkloadOptionException('--scaling-policy rejected unsafe YAML.');
        }
        $top = self::topKeys($raw);
        self::assertAllowed($top, $strategy, $mode);

        return ['raw' => rtrim($raw) . "\n"];
    }

    /**
     * @return list<string>
     */
    private static function topKeys(string $raw): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $raw);
        if ($lines === false) {
            throw new WorkloadOptionException('--scaling-policy is not valid YAML.');
        }
        $keys = [];
        $seen = [];
        foreach ($lines as $number => $line) {
            if (trim($line) === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }
            $indent = strspn($line, ' ');
            if ($indent % 2 !== 0) {
                throw new WorkloadOptionException('--scaling-policy is not valid YAML at line ' . ($number + 1) . '.');
            }
            if (preg_match('/^[A-Za-z0-9_]+:/', ltrim($line)) !== 1 && !str_starts_with(ltrim($line), '- ')) {
                throw new WorkloadOptionException('--scaling-policy is not valid YAML at line ' . ($number + 1) . '.');
            }
            if ($indent === 0 && preg_match('/^([A-Za-z0-9_]+):/', $line, $match) === 1) {
                if (isset($seen[$match[1]])) {
                    throw new WorkloadOptionException('scaling policy duplicates ' . $match[1] . '.');
                }
                $seen[$match[1]] = true;
                $keys[] = $match[1];
            }
        }

        return $keys;
    }

    /**
     * @param list<string> $keys
     */
    private static function assertAllowed(array $keys, string $strategy, string $mode): void
    {
        $allowed = ['control', 'scale_up_cooldown', 'scale_down_idle_for'];
        $allowed = array_merge($allowed, match ($strategy) {
            'cpu_utilization' => ['cpu', 'backlog_source'],
            'latency' => ['latency'],
            'throughput' => ['throughput'],
            'hybrid' => $mode === 'job'
                ? ['cpu', 'backlog_source', 'avg_job_duration', 'target_drain_time']
                : ['cpu', 'latency', 'throughput'],
            'scheduled' => ['scheduled'],
            'adaptive' => ['adaptive'],
            default => [],
        });
        foreach ($keys as $key) {
            if (!in_array($key, $allowed, true)) {
                throw new WorkloadOptionException("scaling policy field {$key} is not valid for {$strategy}.");
            }
        }
        if ($strategy === 'throughput' && !in_array('throughput', $keys, true)) {
            throw new WorkloadOptionException('throughput policy requires target_rps_per_worker.');
        }
        if ($strategy === 'scheduled' && !in_array('scheduled', $keys, true)) {
            throw new WorkloadOptionException('scheduled policy requires a scheduled block.');
        }
        if ($strategy === 'adaptive' && !in_array('adaptive', $keys, true)) {
            throw new WorkloadOptionException('adaptive policy requires an adaptive block.');
        }
        if ($strategy === 'hybrid' && $mode === 'job' && !in_array('backlog_source', $keys, true)) {
            throw new WorkloadOptionException('hybrid consumer policy requires backlog_source.');
        }
    }
}
