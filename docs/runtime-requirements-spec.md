# Durin Presets — Runtime Requirements

**Status:** DONE (Phase 1 — runtime requirements; ver master spec)  
**Repository:** `EreborCodeForge/durin-presets`  
**Integração transversal:** [`durin-architecture` — workloads master spec](https://github.com/EreborCodeForge/durin-architecture/blob/main/docs/specs/durin-workloads-integration-master-spec.md)

# Mission

Fazer cada preset declarar **necessidades de runtime**, não uma implementação concreta como Eregion.

Preset responde:

```text
"o que esta aplicação precisa?"
```

Forge responde:

```text
"qual runtime satisfaz isso?"
```

# Current State

Built-ins:

```text
minimal
service
worker
```

`RuntimeProfile` declara mode e capabilities; presets **não** instalam runtime nem escolhem supervisor.

# Target RuntimeProfile

```php
final readonly class RuntimeProfile
{
    public function __construct(
        public string $mode,
        public array $requiredCapabilities = [],
        public array $preferredCapabilities = [],
        public ?string $preferredRunner = null,
        public ?bool $installRunner = null,
    ) {}
}
```

`preferredRunner` é preferência opcional, não requisito.

# Built-ins

## minimal

```text
mode=http
requiredCapabilities:
  - persistent-http
```

## service

```text
mode=http
requiredCapabilities:
  - persistent-http
```

Capabilities arquiteturais como DI/persistence-ready continuam em `PresetMetadata`, não precisam ser runtime capabilities.

## worker

```text
mode=job
requiredCapabilities:
  - job-loop
  - messaging
```

Não declarar Eregion.

# Normalize Runtime Modes

Preferir:

```text
http
job
```

Evitar que `worker`, `job` e `consumer` sejam usados como sinônimos em camadas diferentes.

`consumer` pode ser um modo operacional do Eregion; o app Durin continua sendo `job`.

# Metadata vs Runtime

Separar:

```text
PresetMetadata.capabilities
→ descrição/feature do preset

RuntimeProfile.requiredCapabilities
→ resolução técnica de runtime
```

Não inferir um do outro.

# Registry

`durin-presets` continua sendo a única fonte de verdade para:

```text
IDs
default
metadata
runtime requirements
scaffold plan
```

Nenhum outro pacote deve manter lista duplicada.

# Manifest Planning

Preset não deve fabricar runtime concreto.

Fluxo alvo:

```text
Preset → intent
Forge → resolve runtime
Forge → finaliza manifest
```

Ajustes em `ManifestPlanFactory` alinhados com `durin-core`.

# Future Presets

A API deve aceitar futuros presets como:

```text
api
mvc
consumer
jobs
library
clean-arch
```

sem obrigar todos a terem servidor.

# Must Do

- trocar semântica de runner por requirements;
- `worker` mode → `job`;
- manter `worker` non-HTTP;
- preservar catálogo determinístico;
- nenhum built-in deve exigir Eregion diretamente;
- testes com fake preset/runtime requirements.

# Must Not

- instalar runtimes;
- procurar binários;
- decidir default runner;
- importar Forge;
- implementar broker.

# Tests

```text
minimal requirements
service requirements
worker requirements
worker preferredRunner=null
catalog JSON
default preset
fake future preset sem runner
ordem determinística
```

# Acceptance Criteria

- `worker` pode resolver para Mithril job runtime sem Eregion.
- HTTP pode continuar resolvendo para Eregion via Forge.
- Adicionar outro runtime compatível não exige alterar os presets.

# Definition of Done

Presets descrevem **intenção e capabilities**, nunca infraestrutura concreta.
