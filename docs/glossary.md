# Project Glossary — 14gis/layers (Snippet)

Concise definitions for core terms used in **14gis/layers**. Keep this short and implementation‑agnostic.

---

## Cache (Tiles/Data)
Tiles cache and (later) data/grid cache (PostGIS, SRID 3857). Invalidation via `contentVersion`.

## Canonical Fields
Stable, role‑specific keys (snake_case, English). The **Normal mode** returns only selected fields (see `identify.fields`). **Debug mode** may include a role’s maximal schema.

## Capabilities
API response listing **semantic layers** for a context, with machine‑readable hints (supported CRS, min/max zoom, constraints). Providers are hidden by default.

## Constraints / Filters
Request limits and allowed filters; always refer to **canonical** field names.

## Context
Product/config scope (e.g., `geochron`) that decides which **semantic layers** are visible and which policies apply.

## Coverage
Spatial extent of a provider (e.g., bbox). Used by ResolutionPolicy.

## CRS / SR / SRID
Coordinate reference system. **API accepts:** EPSG:4326 | EPSG:3857. **Internal standard (later for cache/processing):** EPSG:3857. **MVP:** no reprojection; choose a provider that natively supports the requested SR.

## Debug Mode
Enables extended output (Provenance, timeouts) and optionally the full role schema. Default responses stay minimal.

## Fallback
Automatic switch to lower‑priority provider per policy (e.g., GK1000 when GÜK250 yields no results or is out of coverage).

## fieldMap
Mapping of **upstream fields** to **canonical fields** (defined per provider). Labels/formatting do **not** belong in `fieldMap`.

```yaml
schema:
  canonicalFields: [unit_name, lithology, age_min_ma, age_max_ma, legend_key, source_id]
  fieldMap:
    guek250:
      unit_name: UNIT_NAME
      lithology: LITHOLOGY
      age_min_ma: AGE_MIN_MA
      age_max_ma: AGE_MAX_MA
      legend_key: LEGEND_KEY
      source_id: SOURCE_ID
    gk1000:
      unit_name: NAME
      lithology: LITHO
      age_min_ma: AGE_MIN
      age_max_ma: AGE_MAX
      legend_key: LEG_KEY
      source_id: OBJECTID
```

## Gateway (IdentifyGateway)
Adapter layer to the upstream (e.g., ArcGIS `/query`). Used by the Identify handler.

## Hints
Machine‑readable client hints (e.g., `crs.supported`, `tiles.minZoom/maxZoom`, `constraints.query.maxFeatures`).

## Identify
Point query workflow: fan‑out to eligible providers → normalize to canonical fields → merge → respond. Supports **multi‑identify** across multiple semantic layers.

## Maximal Schema (per role)
Full field set for documentation and debug. Not all fields must be present in normal responses.

## Multi‑Identify
Single click that aggregates results from several semantic layers (providers remain internal). Each feature may include `semanticLayerId`.

## Naming Conventions
- **Provider IDs:** short, upstream‑like, lowercase: `guek250`, `gk1000`.
- **SemanticLayer IDs:** domain‑focused, lowercase: `geology`, `boundaries`.
- **Canonical fields:** snake_case, English: `unit_name`, `age_min_ma`.

## Provenance
Optional origin details in Identify (e.g., `providerId`, `featureId`, dataset, license). Hidden by default; available in Debug mode.

## Provider (Upstream Layer)
Our internal descriptor of **one queryable upstream layer endpoint** (service URL + `layerId` + auth + SR support + coverage + scale + timeouts). The **server/host is not** a provider; it’s merely part of the URL.

**Examples:** `guek250`, `gk1000`.

```yaml
providers:
  guek250:
    type: arcgis-rest
    url: "https://services.bgr.de/arcgis/rest/services/geologie/guek250/MapServer"
    layerId: 0
    sr_supported: [EPSG:4326, EPSG:3857]

  gk1000:
    type: arcgis-rest
    url: "https://services.bgr.de/arcgis/rest/services/geologie/gk1000/MapServer"
    layerId: 0
    sr_supported: [EPSG:4326, EPSG:3857]
```

## Rate Limit
Global lightweight throttle, e.g., 120 requests/min per IP (+ optionally per context).

## ResolutionPolicy
Rules that determine **which provider to use when** (priority, coverage/scale conditions, fallback on zero results/timeout).

## Role (semantic)
Domain category, e.g., `geology/stratigraphy`. Defines which **canonical fields** make sense for that role.

## Scale / Zoom Range
Scale/zoom constraints where a provider is meaningful. Used by ResolutionPolicy.

## SemanticLayer (14gis Layer)
The **semantic** view exposed to clients (e.g., `geology`). It hides providers, emits **canonical fields**, and selects providers via a **ResolutionPolicy**.

```yaml
semanticLayer:
  id: geology
  role: geology/stratigraphy
  title: Geology
  defaultSelected: true

  resolution:
    order: [guek250, gk1000]           # Primary → Fallback
    useIf:
      guek250: "inCoverage && inScale"
      gk1000:  "else"
    fallbacks:
      onZeroResults: true
      onTimeoutMs: 0
```

## Tiles vs. Features
Tiles: fast visual overlay (rasterized). Features: attribute data for Identify/queries.

## Upstream / Source
External data provider (e.g., an ArcGIS service). We access it indirectly via our gateways.

## Versioning
`schemaVersion` (global build schema); `contentVersion` (layer/policy/mapping changes).

