<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Tests\Unit;

use EreborCodeForge\Durin\Presets\Metadata\PresetMetadata;
use PHPUnit\Framework\TestCase;

final class PresetMetadataTest extends TestCase
{
    public function test_metadata_serialization_is_deterministic(): void
    {
        $meta = new PresetMetadata(
            id: 'service',
            label: 'Service',
            description: 'General-purpose structured backend service',
            category: 'service',
            capabilities: ['http', 'dependency-injection'],
            tags: ['backend'],
            experimental: false,
        );

        $this->assertSame([
            'id' => 'service',
            'label' => 'Service',
            'description' => 'General-purpose structured backend service',
            'category' => 'service',
            'capabilities' => ['http', 'dependency-injection'],
            'tags' => ['backend'],
            'experimental' => false,
        ], $meta->toArray());
    }
}
