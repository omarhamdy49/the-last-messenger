# The Last Messenger Constitution

Non-negotiable principles for every feature in this repository, derived from the root `CLAUDE.md`.
Where a feature must deviate, the deviation is recorded in that feature's `plan.md` under Complexity
Tracking with its justification and the simpler alternative that was rejected.

## Core Principles

### I. Layered data flow

```
Request → Controller → Service → RepositoryInterface → EloquentRepository → DB
```

Controllers validate via FormRequest, authorize, call **exactly one** service method, and return a
Resource or view — no Eloquent, no `DB::`, no business logic. Services orchestrate and inject
**interfaces**, never concrete repositories. Repositories own all database access. Multi-step writes
are wrapped in `DB::transaction()` **inside the repository**, never in a controller or service. Route
files point at controllers and contain no logic.

### II. Query discipline

No N+1, ever — every loop, collection iteration, and API Resource is scanned for per-row relation
access before merge. Every list query carries an explicit `->select([...])`; `Model::all()` and bare
`->get()` are forbidden; eager loads name their columns and the parent select includes the foreign
key. Aggregates use `withCount`/`withExists` or DB-level aggregation, never `count()`/`sum()` on a
PHP collection and never a repository call inside `map()`/`each()`. Unbounded sets are paginated;
`per_page` is cast to `(int)` and capped. Indexes exist for every `WHERE`/`ORDER BY`/`JOIN`/FK on a
query path, and an index ships only once an `EXPLAIN` shows it used — no speculative indexes.
Predicates stay SARGable: no functions on indexed columns, no leading-wildcard `LIKE` on hot paths.

### III. Typed boundaries

`array $data` never crosses a layer boundary — use `final readonly` DTOs with named factories. Enums
for domain-constrained values; `::class` over string class names. Models declare explicit `$fillable`;
`$guarded = []` is forbidden. `mixed` is not an escape hatch.

### IV. Module isolation

Domain modules under `app/Domain/` never import one another; cross-module composition goes through
`app/Domain/Shared/` contracts and registries. `Shared` is a leaf — it imports no domain module.
`composer deptrac` enforces this and must pass before merge; the baseline records **pre-existing** debt
only and is never used to admit a new violation. Database-level foreign keys may cross modules —
deptrac cannot see them, so the PHP-import rule is enforced in review.

### V. Arabic-first and multilingual

Five locales: `ar` (default **and** fallback), `en`, `fr`, `tr`, `ur`. Every user-facing string goes
through `__()` with the key present in **all five** language files; hardcoded user-visible text is a
defect. Translatable content uses spatie/laravel-translatable JSON columns. Layout uses **logical
properties only** (`ps-`/`pe-`/`ms-`/`me-`/`start-`/`end-`); physical `left-`/`right-`/`pl-`/`pr-` are
forbidden for layout, and directional icons flip in RTL. Every page is verified in Arabic, not only
English. Quranic and hadith text renders in Amiri, UI text in Cairo.

### VI. Tests are part of the change (NON-NEGOTIABLE)

Every API endpoint ships feature tests for success + 401 + 404 + 422, and 403 where roles apply.
Domain rules get unit tests. Tests are order-independent with no shared mutable state. A bug fix
begins with a failing test that reproduces it. Tests run against MySQL (`the_last_messenger_test`), so
MySQL-specific behaviour — `FULLTEXT`, composite foreign keys, generated columns — is directly
testable and **must** be tested rather than assumed. Migrations must run on the test connection, with
driver-specific DDL wrapped in a driver guard.

### VII. Security by default

Authorize every admin action, deny by default, and verify ownership on user-owned resources (IDOR).
FormRequest on every mutating endpoint, rejecting unexpected fields. Parameterized queries only —
dynamic column and sort names come from an allowlist and user input is never interpolated into query
structure. Rate limits on auth, search, and mutation endpoints. Generic error messages to clients: no
stack traces, SQL, or internal paths. Secrets are never logged, committed, or exposed.

### VIII. Destructive operations are guarded

Before any destructive database command, the resolved database name is asserted against an explicit
allowlist and the command **aborts** if it does not match — printing the name is not a control.
`DB_DATABASE` is passed explicitly rather than inferred from `--env`, which silently falls back to
`.env` when the matching env file is absent. Rollback targets an explicit migration name; `--step=N`
is forbidden because N counts migration *files*, not tables, and silently reaches beyond the intended
set. Deleting untracked files requires a committed checkpoint first.

### IX. Simplicity

The simplest design that fully solves the problem. No speculative abstraction: no interface with one
implementation, no factory for one product, no config for a value that never changes. Three similar
lines beat a premature abstraction. **A table ships only when something populates it** — a column with
no data source is dropped, not shipped empty. Functions over 30 lines get refactored, over 50 get
split; files over 300 lines get split. Dead code created by a change is deleted, not commented out.

### X. Honest reporting

Counts are reconciled and discrepancies **itemised**, never averaged or summarised away. Silent
truncation is a defect: any cap, skip, or sampling is logged with what was dropped. Import and
extraction failures are reported, not swallowed — an empty `catch` is forbidden. Verification claims
state what was actually run versus what was assumed.

## Technology Constraints

PHP 8.2+, Laravel 12, MySQL 8. Admin panel at `/su/portal/v1/admin` (not `/admin`). Public site and
admin are both Blade + Alpine 3 + Tailwind in the same application — not a SPA. `docker compose`, never
`docker-compose`. There is no Vite dev server in this setup: `npm run build` is required after any
Blade or asset change, or new views silently break.

## Quality Gates

A change is complete only when all hold: `php artisan test` green · `./vendor/bin/pint` clean ·
`composer deptrac` passing · migrations reversible and driver-guarded · every changed line traceable to
the task · no new N+1 or unbounded query · security reviewed (auth, validation, injection, rate limits,
IDOR) · translation keys present in all five locales · RTL and both themes verified.

## Governance

This constitution supersedes ad-hoc preference. All reviews verify compliance. Complexity must be
justified in writing. Amendments require a recorded decision and a version bump.

**Version**: 1.0.0 | **Ratified**: 2026-07-30 | **Last Amended**: 2026-07-30
