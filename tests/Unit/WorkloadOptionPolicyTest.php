<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Capability\WorkloadOptionException;
use EreborCodeForge\Durin\Presets\Capability\WorkloadOptionPolicy;
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
}
