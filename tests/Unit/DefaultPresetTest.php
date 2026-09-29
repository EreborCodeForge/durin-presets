<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;
use PHPUnit\Framework\TestCase;

final class DefaultPresetTest extends TestCase
{
    public function test_factory_default_is_minimal(): void
    {
        $registry = (new DefaultPresetRegistryFactory())->create();
        $this->assertSame('minimal', $registry->default()->id());
        $this->assertSame('Minimal', $registry->default()->metadata()->label);
    }
}
