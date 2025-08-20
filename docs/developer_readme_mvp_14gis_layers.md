# 14gis/layers — Developer README (MVP)

> English in files; German in chat. No emojis in project texts.

## Overview
Core library and thin HTTP layer to describe and expose geospatial layers for 14gis projects. YAML specs are compiled to JSON, then served via a small PSR-7/15 stack.

**Key endpoints (dev):**
- `GET /v1/api/capabilities?context=geochron` → lists layers for a context
- `GET /v1/api/identify?context=geochron&layer=guek250&x=10&y=50&sr=EPSG:4326` → MVP Identify (uses a fake gateway in dev)

**Minimal stack:** nyholm/psr7, FastRoute, tiny DI, Pest (tests). PHP 8.4 via Docker.

---

## Prerequisites
- Docker (no local PHP required)
- Images used: `composer:2`, `php:8.4-cli`

---

## Install dependencies
```sh
# Install (dev included)
docker run --rm -v "$PWD":/app -w /app -e COMPOSER_HOME=/tmp composer:2 composer install
```

If Composer prompts for Pest plugin trust in CI, preconfigure:
```sh
docker run --rm -v "$PWD":/app -w /app -e COMPOSER_HOME=/tmp composer:2 \
  composer config --no-interaction allow-plugins.pestphp/pest-plugin true
```

---

## Build (compile YAML → JSON)
```sh
# Explicit CLI (recommended in CI)
docker run --rm -v "$PWD":/app -w /app php:8.4-cli \
  php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION

# Or via Composer script
# composer build
```
Output JSON lives under `compiled/schema/layers/*.json`.

**Notes:**
- The compiler injects `meta.schemaVersion` (from `schema/SCHEMA_VERSION`) and `meta.contentVersion` (typically a date string) into each compiled JSON.
- Validation runs during compile; failures abort with exit code `1` and detailed paths.

---

## Run (optional dev server)
```sh
docker run --rm -p 8000:8000 -v "$PWD":/app -w /app php:8.4-cli \
  php -S 0.0.0.0:8000 -t public
```
Environment variables:
- `LAYERS_COMPILED_DIR` (default: `./compiled/schema`)
- `IDENTIFY_GATEWAY` (`fake` now; `arcgis` planned)

---

## Tests
We use Pest (on top of PHPUnit) with unit, integration and E2E tests.

```sh
# Run all tests without starting any external server
docker run --rm -v "$PWD":/app -w /app php:8.4-cli ./vendor/bin/pest

# E2E tests (start/stop built-in server inside tests)
docker run --rm -v "$PWD":/app -w /app php:8.4-cli ./vendor/bin/pest tests/E2E
```

Handy scripts:
```json
{
  "scripts": {
    "build": "php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION",
    "test": "pest --colors=always",
    "test:e2e": "php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION && pest --colors=always tests/E2E"
  }
}
```

Test infrastructure:
- `tests/Support/TestServer.php` starts/stops the PHP built-in server for E2E.
- Identify tests use a `FakeIdentifyGateway` (no network calls).

---

## Versioning & schema changes
Two layers of versioning:

1) **Schema version** (`schema/SCHEMA_VERSION`, injected as `meta.schemaVersion`)  
   Bump when the **shape/contract** for layer YAML/JSON changes (breaking or feature-level change that affects consumers or validator).

2) **Content version** (`meta.contentVersion` per layer)  
   Bump when the **data/config** of a layer changes but the schema contract stays the same (e.g., tags, bbox, fieldMap tweaks). Use a date (e.g., `YYYY-MM-DD`).

### Workflow for schema adjustments
- **Non‑breaking changes** (fields, types, defaults, tags):
  1. Edit YAML under `schema/layers/*.yaml`.
  2. Optionally update `meta.contentVersion` (date) and `meta.updatedAt`.
  3. Run build → tests.

- **Breaking/contract changes** (validator rules, required properties, structure):
  1. Update validator (`src/Validation/SchemaValidator.php`) and/or JSON meta‑schema (if used).
  2. Bump `schema/SCHEMA_VERSION` (e.g., `1.0.0` → `1.1.0`).
  3. Adjust code that reads the compiled JSON if needed (factory/repository).
  4. Rebuild and run tests (unit + E2E).

**Tip:** Keep YAML as the single source of truth; compiled JSONs are build artefacts.

---

## Add a new layer (quick checklist)
1. Create `schema/layers/<id>.yaml` following the GÜK250 example (roles, providers.features, identify.fields, etc.).
2. `composer build` (or run the explicit CLI) → JSON appears under `compiled/schema/layers/<id>.json`.
3. `composer test` → capabilities/identify tests should pass.
4. Wire in front‑end as needed (ol.js consumes the proxy/API).

---

## Troubleshooting
- **404 in Identify tests:** repository did not find the layer → ensure `compiled/schema` exists and the test points to the correct path (or run build in `tests/Pest.php` global `beforeAll`).
- **Composer asks to allow Pest plugin:** preconfigure `allow-plugins.pestphp/pest-plugin: true`.
- **PHPStorm JSON‑schema warnings:** ensure your project schema mapping points to *compiled JSON*, not to the meta‑schema itself.

---

## Project layout (excerpt)
```
bin/compile.php
schema/                # YAML (source of truth)
  SCHEMA_VERSION
  layers/
compiled/schema/       # JSON artefacts (generated)
public/index.php       # front controller (FastRoute + nyholm)
src/
  Command/CompileSchemaCommand.php
  Http/Handler/{CapabilitiesHandler,IdentifyHandler}.php
  Infrastructure/{Gateway,Factory,Repository}/...
  Validation/SchemaValidator.php
  Util/Dot.php
tests/
  Unit/ Integration/ E2E/
  Support/TestServer.php
```

---

## Current limitations (MVP)
- Identify uses `FakeIdentifyGateway` (no upstream calls yet).
- `/v1/proxy/*` not implemented in MVP branch.
- Caching (tiles/data) and PostGIS grid cache are placeholders in YAML.

---

## License
MIT

