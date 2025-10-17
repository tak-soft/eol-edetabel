# Guidance for AI coding agents: EOL Edetabel

This repository implements the EOL edetabel (ranking) service. Use this file to get immediately productive: what the project is, where to look, important conventions, integration points and developer workflows.

## Quick summary
- Tech: PHP 8.x backend, MySQL database, small frontend served from the app (theme CSS referenced in docs).
- Primary responsibility: import IOF (ranking) results, compute EOL ranking points, store them in MySQL, and expose API/views for tables and per-person details.

## Big-picture architecture (what to look for)
- Data flow: IOF Ranking API -> daily importer (cron or scheduled task) -> local DB (tables: `iofevents`, `iofrunners`, `iofresults`) -> HTTP API + UI.
- Why this structure: data must be cached locally to compute rolling 12-month rankings and perform per-discipline aggregations.
- Key doc: `dok/viip_edetabel.md` — contains the data model, discipline codes, and API examples. Start there.

## Important files & places to inspect
- `dok/viip_edetabel.md` — canonical spec: tables, fields, IOF API examples and discipline/group enums.
- Look for (expected) locations when implementing features:
  - `config/` or `.env` — configuration (DB, IOF API key) — key name used in docs: `RankingAPIKeyForFederation`.
  - `src/`, `app/`, or `public/` — application PHP code and HTTP entrypoints.
  - `bin/` or `scripts/` — CLI import scripts and cron runners (if present).
  - `migrations/` or `db/` — SQL schema or migrations for `iofevents`, `iofrunners`, `iofresults`.

If any of these are missing, create them following the patterns in `dok/viip_edetabel.md`.

## Data model & conventions (explicit from docs)
- Tables and minimal fields:
  - `iofevents`: `eventorId` (unique), `kuupäev` (date), `nimetus`, `distants`, `riik` (3), `alatunnus` (F|FS|M|S|T)
  - `iofrunners`: `iofId` (unique), `firstname`, `lastname`
  - `iofresults`: `id`, `eventorId`, `iofId`, `tulemus` (seconds), `koht`, `RankPoints` (WRE punktid)
- Discipline codes: F, FS, M, S, T. Group values: MEN/WOMEN. Use exactly these strings when mapping IOF responses.
- Ranking window: rolling 12 months by default; per-discipline count of results to include is specified in docs (e.g. Orienteerumisjooks=4).

## IOF integration (exact example)
- Base endpoint (from docs):
  GET https://ranking.orienteering.org/api/exports/federationrankings/EST?fromD={ISO_DATE}&toD={ISO_DATE}
  Header: `X-API-Key: {RankingAPIKeyForFederation}`
- Response shape documented in `dok/viip_edetabel.md` (fields like `EventId`, `IofId`, `RankPoints`, `RaceTimeSeconds`). Map those directly to DB columns above.

## Developer workflows (project-specific)
- Local dev / quick smoke (assume standard PHP layout):
  1. Install deps if composer is used: `composer install` (check for `composer.json`).
  2. Provide config via `.env` or `config/` (set `RankingAPIKeyForFederation`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
  3. Start a dev server (if no framework-specific command): `php -S localhost:8000 -t public`
  4. If present, run migrations or apply provided SQL to create `iofevents`, `iofrunners`, `iofresults`.
- Importer/cron:
  - The importer must call the IOF endpoint once per day and update local tables. Prefer a CLI script (e.g. `bin/import.php`) or an HTTP endpoint protected with a secret and scheduled via cron or platform scheduler.
  - Example curl to test IOF access (replace API key):
    curl -H "X-API-Key: <KEY>" "https://ranking.orienteering.org/api/exports/federationrankings/EST?fromD=2025-09-16&toD=2025-10-16"

## Project-specific UI/deployment hints
- Theme CSS referenced in spec: `https://orienteerumine.ee/wp-content/themes/eol/assets/dist/css/pp-app-theme.css` — frontend should use that as the styling baseline.
- Intended subdomain: `edetabel.orienteerumine.ee` — config/deployment expects the app to serve that host.

## UI views & routes (implementor notes)
The spec in `dok/viip_edetabel.md` describes three primary views. When adding UI code, follow these concrete routes and JSON shapes so backend and frontend stay aligned.

1) Overview / edetabel (ranking table)
   - Route (UI): `/` (web) or API: `GET /api/edetabel?year=2025&discipline=F&sex=MEN&period=12`
   - Purpose: show top N competitors for chosen discipline and period. Default to current year and rolling 12 months.
   - Response shape (array of rows):
     - `place` (int), `firstname` (string), `lastname` (string), `iofId` (int), `points` (float)
   - UI notes:
     - Highlight leader separately (big card) and show ranks 2–10 in condensed list.
     - Responsive: two-column layout on wide screens; single column under 600px. Keep men/women views consistent with spec.

2) Discipline / points detail
   - Route (UI): `/discipline/{code}?period=12` or API: `GET /api/edetabel/{discipline}?from=2025-01-01&to=2025-12-31`
   - Purpose: per-person aggregated points breakdown for that discipline and period.
   - Response shape (object): `{ "iofId": 12345, "firstname": "A", "lastname": "B", "totalPoints": 123.4, "events": [ { "eventorId": 111, "date": "2025-05-01", "name": "Event name", "points": 35.6 } ] }`
   - UI notes:
     - Table with event rows showing date, event name, result, place, and points. Each points cell should link to IOF results (use URL pattern from docs).

3) Athlete detail / results
   - Route (UI): `/athlete/{iofId}` or API: `GET /api/athlete/{iofId}?discipline=F&from=...&to=...`
   - Purpose: show all results for the athlete and discipline, sorted by date.
   - Response: array of event rows same shape as in the discipline detail's `events` array.

Shared UI expectations and patterns
- Use exact discipline codes `F, FS, M, S, T` in routes and API parameters. Use `MEN` / `WOMEN` for group filters.
- Use the theme CSS file above; prefer semantic HTML and minimal JS so the CSS can apply easily.
- When rendering event links, use: `https://ranking.orienteering.org/ResultsView?event={eventorId}&person={iofId}&ohow={alatunnus}` as specified in `dok/viip_edetabel.md`.
- Pagination: APIs should support `limit` and `offset` for large result sets.

Example backend API contract (minimal)
```
GET /api/edetabel?discipline=F&period=12&sex=MEN&year=2025
Response: [ {"place":1,"firstname":"A","lastname":"B","iofId":12345,"points":123.4}, ... ]

GET /api/athlete/12345?discipline=F&from=2025-01-01&to=2025-12-31
Response: [ {"eventorId":111,"date":"2025-05-01","name":"Name","result":3600,"place":1,"points":35.6}, ... ]
```

When adding new UI views, include small smoke tests: run `php -S localhost:8000 -t public` and verify `/health`, then wire simple templates under `public/` or `templates/` that call the API endpoints.

## Tests & debugging (what to check)
- There are no test files in `dok/` — look for `phpunit.xml` or `tests/` before assuming tests exist. If missing, provide at least a smoke test that the import endpoint runs and DB tables update.
- Enable verbose/exception display for local debugging. For production, ensure errors are logged to a file and that importer errors alert maintainers.

## Pull requests & changes guidance
- If you modify data model add migration SQL under `migrations/` or `db/` and update `dok/viip_edetabel.md` if semantics change.
- When adding or changing importer logic, include:
  - sample curl or unit test that demonstrates the importer behavior
  - explanation of how new config keys are used

## Where to ask questions / follow-up
- Start with `dok/viip_edetabel.md` for domain rules and examples.
- If something required by the spec is missing (migrations, API endpoints), implement small, self-contained additions (CLI import, simple HTTP endpoints, SQL schema) and document them.

If any section is unclear or you need more precise local commands (composer, framework CLI, or migration tooling), tell me what files exist in the root (for example `composer.json`, `public/index.php`, or framework indicator files) and I'll update these instructions with exact commands.
