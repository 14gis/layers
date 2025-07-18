# 14gis-layers

**Layers backend service for the 14gis platform.**  
Provides the proxy layer, role-based layer access, and data routing for geospatial analysis and decision support.

## Purpose

This service acts as the core of the 14gis system, enabling:

- Layer proxying and routing
- Role-based access control (layer roles)
- Layer scoring and evaluation logic (planned)
- Source abstraction for external GIS data (ArcGIS, WMS, etc.)
- API entry point for frontend or third-party clients

## Tech Stack

> This may evolve – adjust as needed.

- PHP / Node.js / Python (tbd)
- REST API (or GraphQL)
- JSON-based layer configuration
- Optional: caching (e.g., Redis/PostGIS)
- Dockerized deployment setup

## Structure (planned)

```
/src            → Layers logic
/config         → Layer config, routing, roles
/tests          → Unit and integration tests
/docs           → Internal docs (e.g. API overview, config format)
README.md
LICENSE
.gitignore
```

## Setup (coming soon)

Installation instructions, Docker setup, and development guide will be added as the project progresses.

## License

MIT (or AGPLv3 – to be defined)

---

_This repository is part of the [14gis](https://github.com/14gis) project, powered by [14co.de](https://14co.de)._
