# MVP Default Policy — 14gis/layers

> This document defines clear defaults for the MVP so implementation and reviews do not need additional clarification. English only; no emojis.

## Scope
- Compile YAML layer specs → JSON artefacts.
- Serve a minimal HTTP API for **Capabilities** and **Identify**.
- Keep the stack slim and PSR‑compliant; no heavy DI frameworks.

## Endpoints (MVP)
- `GET /v1/api/capabilities?context=<ctx>` → list layers for a context plus semantic roles.
- `GET /v1/api/identify?context=<ctx>&layer=<id>&x=<lon>&y=<lat>&sr=EPSG:4326|EPSG:3857` → point identify with canonical output.

## Identify Gateway (ArcGIS)
- **Transport:** use ArcGIS **/query** (not /identify) for point queries.
- **Parameters sent upstream:**
  - `f=json`
  - `geometry={x:<x>,y:<y>}`
  - `geometryType=esriGeometryPoint`
  - `inSR=<sr>`
  - `spatialRel=esriSpatialRelIntersects`
  - `outFields=*`
  - `returnGeometry=false`
  - optional: `where=1=1`, `outSR=<sr>`
- **SR handling:** accept only `EPSG:4326` or `EPSG:3857` from clients. No server‑side reprojection; we forward `inSR/outSR` to upstream.
- **Output format:** canonical JSON mapped by `schema.fieldMap` and limited to `identify.fields` plus optional `provenance`.
- **Multiplicity:** return a list `features[]` (0..n). MVP behavior is effectively 0/1, but the API remains list‑capable.

## CRS, BBOX, Scale
- Requests may use `sr=4326` or `3857`; everything else → `400`.
- YAML `context.bbox` is used for hints and fake identify in tests; not enforced server‑side.
- Scale/zoom ranges are **hints** for clients; no hard validation in the API.

## Caching & PostGIS (design for later)
- **Tables:**
  - `layer_features(layer_id text, geom geometry(…3857), props jsonb, hash text, updated_at timestamptz)`
  - optional grid: `layer_grid(layer_id text, cell geometry, agg jsonb)`
- **Invalidation:** bumping a layer’s `meta.contentVersion` triggers a full refresh for that `layer_id`.
- **Grid cell size default:** 250 m (overridable via YAML `cache.data.grid.cellSizeMeters`).

## Compile & Validation
- **Compile CLI:** `php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION`
- **Artefacts:** JSON is written to `compiled/schema/layers/*.json` and is **not** committed to VCS.
- **Validator policy:**
  - Missing required fields or declared type mismatches ⇒ **fail** (exit 1).
  - Unknown optional fields ⇒ ignored.
  - Optional strict mode may be added later (`--strict`).

## Capabilities Output
- Root fields: `{ context, roles:[…], layers:[…] }`.
- Layer ordering: `defaultSelected=true` first, then by `meta.title` ascending.
- Include machine‑readable hints where available: `crs.supported`, `tiles.minZoom/maxZoom`, `constraints.query.maxFeatures`, etc.

## Proxy Surface
- MVP ships only `/v1/api/*` (no generic `/v1/proxy/*` yet).
- Upstream auth is defined in YAML via `providers.*.requestPolicy` and optional `authRef`. No client‑provided API key in MVP.

## GK1000 Fallback
- Separate layer with its own `fieldMap`; normalized to the same canonical fields as GÜK250.
- Usage: low‑zoom baseline (e.g., visible at small scales). Identify remains primarily GÜK250; GK1000 identify is used only when GÜK250 is not available.

## Security
- Global lightweight rate limit default: **120 rpm per IP** (configurable via YAML `limits.rate`).
- API keys and project/ctx scopes are out of scope for MVP.

## Errors
- Unified error body: `{ "error": { "code": "STRING_CODE", "message": "…", "details": {…} } }`
- HTTP status codes: 400/404/500/504 as appropriate.
- Upstream timeouts return **504** with `details.upstream` and `details.timeoutMs`. Single retry (exponential) is available but **disabled** by default.

## Tests & CI
- Pest on top of PHPUnit; unit + integration + E2E.
- E2E uses a tiny `TestServer` helper (dynamic ephemeral port).
- Offline identify tests use `FakeIdentifyGateway`.
- CI baseline: **PHP 8.4**.

## Versioning
- **schemaVersion:** global SemVer in `schema/SCHEMA_VERSION`, injected into each compiled JSON.
- **contentVersion:** per‑layer date `YYYY-MM-DD` in YAML `meta.contentVersion` for data/config changes.

## Naming
- Composer package: `14gis/layers`.
- Namespace: `Gis14\Layers`.

## Environment defaults
- `LAYERS_COMPILED_DIR=./compiled/schema`
- `IDENTIFY_GATEWAY=fake` (switch to `arcgis` when implemented)

## Quick reference
- Install deps: `docker run --rm -v "$PWD":/app -w /app -e COMPOSER_HOME=/tmp composer:2 composer install`
- Build: `docker run --rm -v "$PWD":/app -w /app php:8.4-cli php bin/compile.php ./schema ./compiled/schema ./schema/SCHEMA_VERSION`
- Tests: `docker run --rm -v "$PWD":/app -w /app php:8.4-cli ./vendor/bin/pest`
- Dev server (optional): `docker run --rm -p 8000:8000 -v "$PWD":/app -w /app php:8.4-cli php -S 0.0.0.0:8000 -t public`

## Out of scope (MVP)
- Generic `/v1/proxy/*` surface
- Real upstream identify gateway (ArcGIS) in production
- PostGIS cache implementation
- API keys, scopes, and referer enforcement beyond basic policy

