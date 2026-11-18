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
    ): ProjectSelection {
        $effective = $this->effectiveSupervisor($mode, $supervisorChoice);

        if ($scalingStrategy !== null && !in_array($scalingStrategy, ['fixed', 'resources', 'backlog'], true)) {
            throw new WorkloadOptionException('Unknown --scaling. Use fixed, resources, or backlog.');
        }

        if ($scalingStrategy === 'backlog') {
            throw new WorkloadOptionException(
                '--scaling=backlog requires a configured backlog provider; run ECO-03 integration or choose fixed/resources.'
            );
        }

        $workloadRequested = $scalingStrategy !== null
            || $workersMin !== null
            || $workersMax !== null
            || $resourceClass !== null
            || $memoryMb !== null;

        if ($workloadRequested && ($mode !== 'job' || $effective !== 'eregion')) {
            throw new WorkloadOptionException(
                '--scaling, --workers-min, --workers-max, --resource-class, and --memory-mb require runtime mode job and supervisor eregion.'
            );
        }

        if ($workersMin !== null && $workersMin < 0) {
            throw new WorkloadOptionException('Invalid --workers-min: expected an integer greater than or equal to 0.');
        }
        if ($workersMax !== null && $workersMax < 1) {
            throw new WorkloadOptionException('Invalid --workers-max: expected an integer greater than or equal to 1.');
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

        return new ProjectSelection(
            presetId: $presetId,
            features: array_values($features),
            supervisorChoice: $supervisorChoice,
            scalingStrategy: $scalingStrategy,
            workersMin: $workersMin,
            workersMax: $workersMax,
            resourceClass: $resourceClass,
            memoryMb: $memoryMb,
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
