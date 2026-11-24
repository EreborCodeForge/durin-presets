<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Capability\WorkloadOptionException;
use EreborCodeForge\Durin\Presets\Capability\WorkloadOptionPolicy;
use EreborCodeForge\Durin\Presets\Capability\ScalingStrategyCatalog;
use PHPUnit\Framework\TestCase;

final class WorkloadOptionPolicyTest extends TestCase
{
    private WorkloadOptionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WorkloadOptionPolicy();
    }

    public function test_job_auto_rejects_scaling_without_supervisor(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('require runtime mode job and supervisor eregion');
        $this->policy->compile('worker', 'job', [], 'auto', 'fixed', null, null, null, null);
    }

    public function test_backlog_is_rejected(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('--scaling=backlog requires a configured backlog provider; run ECO-03 integration or choose fixed/resources.');
        $this->policy->compile('consumer', 'job', ['consumer'], 'eregion', 'backlog', 1, 4, null, null);
    }

    public function test_http_none_is_rejected(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('--supervisor=none is not available for HTTP presets');
        $this->policy->compile('producer', 'http', ['producer'], 'none', null, null, null, null, null);
    }

    public function test_resources_selection_keeps_min_default(): void
    {
        $selection = $this->policy->compile('consumer', 'job', ['consumer'], 'eregion', 'resources', null, 4, 'io', null);

        $this->assertSame(1, $selection->workersMin);
        $this->assertSame(4, $selection->workersMax);
        $this->assertSame('resources', $selection->scalingStrategy);
        $this->assertSame('eregion', $this->policy->effectiveSupervisor('job', 'eregion'));
        $this->assertNull($this->policy->effectiveSupervisor('job', 'auto'));
    }

    public function test_min_greater_than_max_is_rejected(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('min must be less than or equal to max');
        $this->policy->compile('consumer', 'job', ['consumer'], 'eregion', 'fixed', 4, 1, null, null);
    }

    public function test_fixed_on_http_stays_rejected(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('require runtime mode job and supervisor eregion');
        $this->policy->compile('service', 'http', [], 'eregion', 'fixed', 1, 2, null, null);
    }

    public function test_latency_http_requires_explicit_bounds(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('requires explicit --workers-min and --workers-max');
        $this->policy->compile('service', 'http', [], 'eregion', 'latency', null, 4, null, null);
    }

    public function test_latency_http_is_accepted_with_bounds(): void
    {
        $selection = $this->policy->compile('service', 'http', [], 'eregion', 'latency', 1, 4, null, null);

        $this->assertSame('latency', $selection->scalingStrategy);
        $this->assertSame(1, $selection->workersMin);
        $this->assertSame(4, $selection->workersMax);
    }

    public function test_adaptive_requires_enforce(): void
    {
        $this->expectException(WorkloadOptionException::class);
        $this->expectExceptionMessage('--scaling=adaptive requires --resource-guard-mode=enforce');
        $this->policy->compile('service', 'http', [], 'eregion', 'adaptive', 1, 4, null, null, null, 'observe');
    }

    public function test_throughput_policy_rejects_strategy_and_tags(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eco11-policy-' . uniqid('', true);
        mkdir($dir);
        $bad = $dir . DIRECTORY_SEPARATOR . 'bad.yaml';
        file_put_contents($bad, "strategy: throughput\nthroughput:\n  target_rps_per_worker: 50\n");
        try {
            $this->expectException(WorkloadOptionException::class);
            $this->expectExceptionMessage('strategy');
            $this->policy->compile('service', 'http', [], 'eregion', 'throughput', 1, 4, null, null, $bad, 'observe');
        } finally {
            @unlink($bad);
            @rmdir($dir);
        }
    }

    public function test_throughput_policy_is_forwarded(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eco11-policy-' . uniqid('', true);
        mkdir($dir);
        $path = $dir . DIRECTORY_SEPARATOR . 'policy.yaml';
        file_put_contents($path, "throughput:\n  target_rps_per_worker: 50\n");
        try {
            $selection = $this->policy->compile('service', 'http', [], 'eregion', 'throughput', 2, 8, null, null, $path, 'observe');
            $this->assertSame('observe', $selection->resourceGuardMode);
            $this->assertStringContainsString('target_rps_per_worker: 50', $selection->scalingFragment['raw']);
            $this->assertNull((new ScalingStrategyCatalog())->defaultStrategy());
        } finally {
            @unlink($path);
            @rmdir($dir);
        }
    }
}
