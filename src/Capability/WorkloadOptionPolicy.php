<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

use EreborCodeForge\Durin\Core\Contract\ProjectSelection;

/**
 * Pure CLI workload rules shared by Forge and the installer.
 * Does not resolve a runtime and does not touch the filesystem.
 */
final class WorkloadOptionPolicy
{
    /** @var list<string> */
    public const array LEGACY = ['fixed', 'resources', 'backlog'];

    /** @var list<string> */
    public const array ADVANCED = ['cpu_utilization', 'latency', 'throughput', 'hybrid', 'scheduled', 'adaptive'];

    /**
     * @param list<string> $features
     */
    public function compile(
        string $presetId,
        string $mode,
        array $features,
        string $supervisorChoice,
        ?string $scalingStrategy,
        ?int $workersMin,
        ?int $workersMax,
        ?string $resourceClass,
        ?int $memoryMb,
        ?string $scalingPolicy = null,
        ?string $resourceGuardMode = null,
    ): ProjectSelection {
        $effective = $this->effectiveSupervisor($mode, $supervisorChoice);
        $advanced = $scalingStrategy !== null && in_array($scalingStrategy, self::ADVANCED, true);

        if ($scalingStrategy !== null && !in_array($scalingStrategy, [...self::LEGACY, ...self::ADVANCED], true)) {
            throw new WorkloadOptionException('Unknown --scaling. Use fixed, resources, backlog, cpu_utilization, latency, throughput, hybrid, scheduled, or adaptive.');
        }

        if ($scalingStrategy === 'backlog') {
            throw new WorkloadOptionException(
                '--scaling=backlog requires a configured backlog provider; run ECO-03 integration or choose fixed/resources.'
            );
        }

        if ($scalingPolicy !== null && $scalingStrategy === null) {
            throw new WorkloadOptionException('--scaling-policy requires an explicit --scaling strategy.');
        }
        if ($scalingPolicy !== null && !$advanced) {
            throw new WorkloadOptionException('--scaling-policy applies only to cpu_utilization, latency, throughput, hybrid, scheduled, and adaptive.');
        }
        if ($resourceGuardMode !== null && !$advanced) {
            throw new WorkloadOptionException('--resource-guard-mode applies only when a new scaling strategy is selected.');
        }
        if ($resourceGuardMode !== null && !in_array($resourceGuardMode, ['observe', 'enforce'], true)) {
            throw new WorkloadOptionException('Unknown --resource-guard-mode. Use observe or enforce.');
        }
        if ($scalingStrategy === 'adaptive' && $resourceGuardMode !== 'enforce') {
            throw new WorkloadOptionException('--scaling=adaptive requires --resource-guard-mode=enforce.');
        }

        $needsPolicy = in_array($scalingStrategy, ['throughput', 'scheduled', 'adaptive'], true)
            || ($scalingStrategy === 'hybrid' && $mode === 'job');
        if ($needsPolicy && $scalingPolicy === null) {
            throw new WorkloadOptionException('--scaling=' . $scalingStrategy . ' requires --scaling-policy=PATH.');
        }

        $workloadRequested = $scalingStrategy !== null
            || $workersMin !== null
            || $workersMax !== null
            || $resourceClass !== null
            || $memoryMb !== null
            || $scalingPolicy !== null
            || $resourceGuardMode !== null;

        if ($advanced && $effective !== 'eregion') {
            throw new WorkloadOptionException('--scaling=' . $scalingStrategy . ' requires supervisor eregion.');
        }

        if ($workloadRequested && !$advanced && ($mode !== 'job' || $effective !== 'eregion')) {
            throw new WorkloadOptionException(
                '--scaling, --workers-min, --workers-max, --resource-class, and --memory-mb require runtime mode job and supervisor eregion.'
            );
        }

        if ($advanced && ($workersMin === null || $workersMax === null)) {
            throw new WorkloadOptionException('--scaling=' . $scalingStrategy . ' requires explicit --workers-min and --workers-max.');
        }

        if ($workersMin !== null && $workersMin < 0) {
            throw new WorkloadOptionException('Invalid --workers-min: expected an integer greater than or equal to 0.');
        }
        if ($workersMax !== null && $workersMax < 1) {
            throw new WorkloadOptionException('Invalid --workers-max: expected an integer greater than or equal to 1.');
        }

        $requiresMinOne = in_array($scalingStrategy, ['cpu_utilization', 'latency', 'throughput', 'adaptive'], true)
            || ($scalingStrategy === 'hybrid' && $mode === 'http')
            || ($scalingStrategy === 'scheduled' && $mode === 'http');
        if ($requiresMinOne && $workersMin !== null && $workersMin < 1) {
            throw new WorkloadOptionException('--scaling=' . $scalingStrategy . ' requires --workers-min >= 1.');
        }

        if ($workersMin !== null || $workersMax !== null) {
            $workersMin ??= 1;
            $workersMax ??= 1;
        }

        if ($workersMin !== null && $workersMax !== null && $workersMin > $workersMax) {
            throw new WorkloadOptionException('Invalid --workers-min/--workers-max: min must be less than or equal to max.');
        }

        if ($resourceClass !== null && !in_array($resourceClass, ['io', 'cpu', 'balanced'], true)) {
            throw new WorkloadOptionException('Unknown --resource-class. Use io, cpu, or balanced.');
        }

        if ($memoryMb !== null && $memoryMb < 1) {
            throw new WorkloadOptionException('Invalid --memory-mb: expected a positive integer.');
        }

        $fragment = [];
        if ($scalingPolicy !== null && $scalingStrategy !== null) {
            $fragment = ScalingPolicyFile::read($scalingPolicy, $scalingStrategy, $mode);
        }

        return new ProjectSelection(
            presetId: $presetId,
            features: array_values($features),
            supervisorChoice: $supervisorChoice,
            scalingStrategy: $scalingStrategy,
            workersMin: $workersMin,
            workersMax: $workersMax,
            resourceClass: $resourceClass,
            memoryMb: $memoryMb,
            scalingPolicyPath: $scalingPolicy,
            resourceGuardMode: $resourceGuardMode,
            scalingFragment: $fragment,
        );
    }

    public function effectiveSupervisor(string $mode, string $supervisorChoice): ?string
    {
        if (!in_array($supervisorChoice, ['auto', 'none', 'eregion'], true)) {
            throw new WorkloadOptionException('Unknown --supervisor. Use auto, none, or eregion.');
        }

        if ($supervisorChoice === 'none' && $mode === 'http') {
            throw new WorkloadOptionException(
                '--supervisor=none is not available for HTTP presets because no standalone HTTP launcher exists.'
            );
        }

        if ($supervisorChoice === 'eregion') {
            return 'eregion';
        }

        if ($supervisorChoice === 'none') {
            return null;
        }

        return $mode === 'http' ? 'eregion' : null;
    }
}
