# ereborcodeforge/durin-presets

## Purpose

Architecture presets and project-shape policies for Durin.

## What this package owns

- Concrete presets: `minimal`, `service`, `worker`
- Preset registry and planning engine
- Preset scaffold templates / shared file contents
- Manifest plan helpers for preset scaffolding

## What this package does not own

- Project discovery / `durin.yaml` parser (see `durin-core`)
- Safe filesystem mutation engine (see `durin-core`)
- Architecture detection, drift, adopt/evolve/migrate
- Durin Forge CLI
- MithrilPHP / Eregion runtime

## Installation

```bash
composer require ereborcodeforge/durin-presets
```

Local path development (until remotes are linked):

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../durin-core",
      "options": { "symlink": true }
    },
    {
      "type": "path",
      "url": "../durin-presets",
      "options": { "symlink": true }
    }
  ]
}
```

## PHP requirement

PHP `^8.5`

## Basic usage

```php
use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Mutation\ScaffoldWriter;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;

$engine = (new DefaultPresetRegistryFactory())->engine();
$plan = $engine->plan(new ProjectOptions('billing', 'service', '/path/to/billing'));
$result = (new ScaffoldWriter())->write('/path/to/billing', $plan);
```

## Dependency direction

```text
durin-presets
  -> durin-core
```

Must not depend on `durin-architecture` or Durin Forge.

## Supported API

- `MinimalPreset`, `ServicePreset`, `WorkerPreset`
- `PresetRegistry`, `PresetEngine`, `DefaultPresetRegistryFactory`
- `ManifestPlanFactory`, `PresetScaffoldSupport`

Canonical preset identifiers: `minimal`, `service`, `worker`.

## Versioning status

Initial extraction release: **0.1.0** (pre-1.0).

## Relationship to Durin Forge

Forge CLI calls this package to resolve a preset and obtain a `ScaffoldPlan`, then applies it via `durin-core` mutation primitives.
