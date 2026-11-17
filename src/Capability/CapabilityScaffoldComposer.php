<?php

declare(strict_types=1);

namespace EreborCodeForge\Durin\Presets\Capability;

use EreborCodeForge\Durin\Core\Manifest\DurinManifest;
use EreborCodeForge\Durin\Core\Manifest\DurinManifestParser;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldAction;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldActionType;
use EreborCodeForge\Durin\Core\Scaffold\ScaffoldPlan;
use EreborCodeForge\Durin\Presets\Preset\PresetScaffoldSupport;

/**
 * Merges capability files onto a preset plan and fails before write on collisions.
 */
final class CapabilityScaffoldComposer
{
    public function __construct(
        private readonly PresetScaffoldSupport $files = new PresetScaffoldSupport(),
    ) {}

    /**
     * @param list<string> $capabilityIds normalized ids
     */
    public function merge(ScaffoldPlan $base, array $capabilityIds): ScaffoldPlan
    {
        /** @var array<string, ScaffoldAction> $indexed */
        $indexed = [];
        foreach ($base->actions() as $action) {
            $this->put($indexed, $action, 'preset');
        }

        foreach ($capabilityIds as $capabilityId) {
            foreach ($this->contribution($capabilityId)->actions() as $action) {
                $this->put($indexed, $action, $capabilityId);
            }
        }

        $plan = new ScaffoldPlan();
        foreach ($indexed as $action) {
            $plan->add($action);
        }

        return $plan;
    }

    public function enableMessaging(ScaffoldPlan $plan): ScaffoldPlan
    {
        $yaml = null;
        foreach ($plan->actions() as $action) {
            if ($action->type === ScaffoldActionType::WriteFile && $action->relativePath === 'durin.yaml') {
                $yaml = $action->contents ?? '';
            }
        }

        if ($yaml === null) {
            return $plan;
        }

        $manifest = (new DurinManifestParser())->parse($yaml);
        $updated = new DurinManifest(
            applicationName: $manifest->applicationName,
            preset: $manifest->preset,
            features: [
                'http' => $manifest->features['http'],
                'messaging' => true,
            ],
            architecture: $manifest->architecture,
            runtime: $manifest->runtime,
        );
        $next = $updated->toYaml();
        if ($next === $yaml) {
            return $plan;
        }

        $rewritten = new ScaffoldPlan();
        foreach ($plan->actions() as $action) {
            if ($action->type === ScaffoldActionType::WriteFile && $action->relativePath === 'durin.yaml') {
                $rewritten->file('durin.yaml', $next);
                continue;
            }
            $rewritten->add($action);
        }

        return $rewritten;
    }

    public function contribution(string $capabilityId): ScaffoldPlan
    {
        $plan = new ScaffoldPlan();

        if ($capabilityId === 'consumer') {
            return $plan
                ->directory('src/Application/Handlers')
                ->directory('src/Infrastructure/Messaging')
                ->file('config/messaging.php', $this->files->messagingConfig())
                ->file('src/Application/Handlers/ExampleHandler.php', $this->files->exampleHandler())
                ->file('tests/ConsumerScaffoldTest.php', $this->files->consumerScaffoldTest());
        }

        if ($capabilityId === 'producer') {
            return $plan
                ->directory('src/Infrastructure/Messaging')
                ->file('config/messaging.php', $this->files->messagingConfig())
                ->file('src/Infrastructure/Messaging/EventEnvelope.php', $this->files->eventEnvelope())
                ->file('src/Infrastructure/Messaging/Publisher.php', $this->files->publisherContract())
                ->file('src/Infrastructure/Messaging/UnconfiguredPublisher.php', $this->files->unconfiguredPublisher());
        }

        throw new IncompatibleCapabilityException("Unknown capability \"{$capabilityId}\".");
    }

    /**
     * @param array<string, ScaffoldAction> $indexed
     */
    private function put(array &$indexed, ScaffoldAction $action, string $capabilityId): void
    {
        $path = $action->relativePath;
        if (!isset($indexed[$path])) {
            $indexed[$path] = $action;

            return;
        }

        $existing = $indexed[$path];
        if ($existing->type !== $action->type) {
            throw ScaffoldCollisionException::forPath($path, $capabilityId);
        }

        if ($action->type === ScaffoldActionType::CreateDirectory) {
            return;
        }

        if (($existing->contents ?? '') !== ($action->contents ?? '')) {
            throw ScaffoldCollisionException::forPath($path, $capabilityId);
        }
    }
}
