# ereborcodeforge/durin-presets

## Purpose

Single source of truth for Durin application presets: catalog, metadata, runtime
requirements, and scaffold planning.

Runtime requirements spec: [`docs/runtime-requirements-spec.md`](docs/runtime-requirements-spec.md). Cross-repo integration: [`durin-architecture` master spec](https://github.com/EreborCodeForge/durin-architecture/blob/main/docs/specs/durin-workloads-integration-master-spec.md).

## What this package owns

- Built-in presets: `minimal`, `service`, `worker`
- Public discovery API (`PresetDefinition`, `PresetMetadata`, `RuntimeProfile`, `PresetRegistry`)
- Default preset (`minimal`) — only here
- Preset planning engine (`PresetEngine` → `ScaffoldPlan`)
- Preset scaffold templates / shared file contents
- Manifest plan helpers for preset scaffolding

## What this package does not own

- Project discovery / `durin.yaml` parser (see `durin-core`)
- Safe filesystem mutation engine (see `durin-core`)
- Architecture detection, drift, adopt/evolve/migrate
- Durin Forge CLI / Eregion installation
- Installer UX / terminal progress
- MithrilPHP / Eregion runtime

## Installation

```bash
composer require ereborcodeforge/durin-presets:^0.3
```

## PHP requirement

PHP `^8.5`

## Catalog (read-only discovery)

```php
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;

$registry = (new DefaultPresetRegistryFactory())->create();

$registry->ids();           // ['minimal', 'service', 'worker']
$registry->default()->id(); // 'minimal'
$registry->has('service');
$registry->definition('service')->metadata()->toArray();
$registry->catalog();       // { default, presets: [metadata + runtime] }
```

Installer and Forge may list/validate presets through this API only. They must not
hardcode built-in preset ID arrays.

## Planning

```php
use EreborCodeForge\Durin\Core\Contract\ProjectOptions;
use EreborCodeForge\Durin\Core\Mutation\ScaffoldWriter;
use EreborCodeForge\Durin\Presets\Registry\DefaultPresetRegistryFactory;

$engine = (new DefaultPresetRegistryFactory())->engine();
$plan = $engine->plan(new ProjectOptions('billing', 'service', '/path/to/billing'));
$result = (new ScaffoldWriter())->write('/path/to/billing', $plan);
```

## Adding a new preset

1. Implement `PresetDefinition` (id, metadata, runtime, plan).
2. Register it in `DefaultPresetRegistryFactory`.
3. Release `durin-presets`.

No production changes are required in `durin-installer`, `durins-forge`, or `durin-app`.

## Dependency direction

```text
durin-presets
  -> durin-core
```

Must not depend on `durin-architecture` or Durin Forge.

## Supported API

- `PresetDefinition`, `PresetMetadata`, `RuntimeProfile`
- `MinimalPreset`, `ServicePreset`, `WorkerPreset`
- `PresetRegistry`, `PresetEngine`, `DefaultPresetRegistryFactory`
- `ManifestPlanFactory`, `PresetScaffoldSupport`

`RuntimeProfile` declares **runtime requirements** (`mode`, `requiredCapabilities`,
optional `preferredCapabilities` / `preferredRunner`). Presets never select a concrete
runtime such as Eregion; Forge resolves a runner that satisfies the requirements.

## Versioning

**0.2.0** — preset catalog as ecosystem SSOT (discovery + default + runtime metadata).
**0.3.0** — `RuntimeProfile` expresses requirements (`requiredCapabilities`) instead of a concrete `runner`; worker mode is `job`.
