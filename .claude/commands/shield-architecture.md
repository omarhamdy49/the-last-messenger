---
description: Full-spectrum audit — security, performance, DB schema, architecture, clean-code/SOLID, API design, and naming conventions — then a confirmed, agent-driven fix pass
argument-hint: "[optional scope: path, module name, or 'diff' for changed files only]"
allowed-tools: Skill, Task, Agent, Bash, Read, Grep, Glob, TaskCreate, TaskUpdate, TaskList, AskUserQuestion
---

# /shield-architecture

You are the lead auditor running a **seven-lane, staff-engineer audit** — Security, Performance, Database Schema, **Architecture, Clean Code & Principles, API Design, Naming Conventions** — followed by a **confirmed, team-of-agents fix pass**. Own the outcome; don't behave like a linter. This is the superset of `/shield-systems`: it keeps the first three lanes and adds four design/quality lanes.

**Scope:** `$ARGUMENTS` — if empty, audit the whole codebase but prioritise `app/`, `routes/`, `database/`, and config. If it names a path/module, confine to it. If it's `diff`, audit only `git diff` against the main branch.

Detect the stack first (`composer.json`, `package.json`) and adapt. This repo is **Laravel 13 / PHP 8.5 / MySQL** with a **DDD architecture** — honor its CLAUDE.md conventions (translation tables NOT JSON columns, column-specific eager loading, no `Cache::flush()`, versioned cache keys, route order, `publiclyVisible` scopes, admin vs web guards, `Shared`-only cross-domain contracts).

---

## Phase 1 — Load expertise (do this before analysing)

Invoke these skills so their expertise is in context, then apply — don't narrate each load.
- Security: `security-reviewer`, `secure-code-guardian`, `owasp-security`, `09-web-security`, `02-vulnerability-scanner`.
- Performance: `laravel-arch-guardian`, `faang-reviewer`, `code-reviewer`.
- Database: `database-optimizer`, `postgres-pro`, `laravel-specialist`.
- Architecture / Clean code / API / Naming: `laravel-arch-guardian`, `faang-reviewer`, `code-reviewer`, `laravel-specialist`.
- (`snyk-fix` is available for the fix phase if the Snyk MCP tools are connected.)

## Phase 2 — Parallel deep analysis (READ-ONLY)

Dispatch analysis agents **in parallel, in a single message**. Every agent in this phase is strictly read-only: **no edits, no writes, no git changes.** Each returns findings as structured items. Scale the number per lane to scope; for a small scope you may merge the four design lanes onto fewer agents.

1. **Security** (`security-auditor`): OWASP Top 10, authn/authz gaps (admin vs web guard, missing route middleware), injection (SQL/XSS/command), mass-assignment, IDOR, secrets in code, unsafe deserialization, SSRF, CSRF gaps, unbounded input, dependency CVEs. Cross-check CLAUDE.md gotchas: silent `Throwable` swallow without `report()`, bare `is_published` reads bypassing `publiclyVisible` scopes, config-cache outage switch.
   - **Snyk scan (opt-in — uploads to Snyk's cloud):** if the Snyk MCP tools are connected, first **ASK the user to confirm** before running — `snyk_sca_scan` uploads dependency manifests (`composer.lock`/`package-lock.json`) and `snyk_code_scan` (SAST) uploads source code to Snyk's cloud. On an explicit yes, run the scan(s), verify each reported issue against the real code (drop false positives), and fold confirmed findings into this lane with `SNYK-nn` ids (record CVE id + fixed-in version). If the user declines, or the Snyk MCP tools are not connected, **skip and say so** — never scan silently. This is the only step in the audit that leaves the machine.
2. **Performance** (`performance-engineer`): N+1, eager-loads missing column constraints on translation tables, `SELECT *`, unindexed hot queries, per-node loops that should be set-based (note: genealogy recursive CTEs are intentional — don't flag), `Cache::flush()`, missing pagination, queue/timeout foot-guns, unbounded memory.
3. **Database Schema** (`database-architect`): missing FK/hot-column indexes, wrong types, nullable/constraint gaps, missing unique indexes behind app-level dedup, schema drift vs models (verify with `SHOW COLUMNS`/`SHOW INDEX` via Bash, not docblocks), translation-table integrity `(parent_id, locale)` uniqueness, cascade risks.
4. **Architecture** (`architect-reviewer`): DDD boundary violations (cross-domain imports — domains may import only from `Shared`; cross-module refs go through a `Shared` contract like `PersonLookup`, never another domain's Eloquent model), layering leaks (controllers doing repo/DB work, business logic in blades), god classes, wrong-layer responsibilities, CQRS consistency in Quran, repository-pattern adherence, deptrac.yaml boundaries, circular deps.
5. **Clean Code & Principles** (`code-reviewer`): SOLID violations (SRP god services, leaky abstractions, interface-with-one-impl over-abstraction), DRY (duplicated query/visibility logic that should be a scope/helper), KISS/YAGNI (speculative abstractions, dead code, unused `use` imports — CLAUDE.md warns several imports reference classes that don't exist), long methods/high cyclomatic complexity, deep nesting, magic numbers, unclear error handling. Honor ponytail bias: flag over-engineering as well as under-engineering.
6. **API Design** (`backend-architect`): REST correctness (verbs, resource nesting, plural collection URIs), status codes, the standard `{success,data,message,meta}` envelope + `{success,message,errors}` error shape (CLAUDE.md), consistent pagination meta, versioning under `/api/v1`, auth posture (Sanctum bearer + `abilities:api:access` on every route except register/login/health — flag any accidentally-anonymous private route), `$request->expectsJson()` content negotiation for admin controllers returning JSON to HTML forms, method-spoofing pitfalls, over/under-fetching in Resources, N+1-inducing response shapes.
7. **Naming Conventions** (`code-reviewer`): repo rules — Controllers/Models singular, tables/routes/views plural, `{model}_id` FKs, route names match resource, view directories plural, consistent domain vocabulary (Muslim not Person, no resurrected `Sahaba`/`Genealogy`/`Person`), enum value casing, method/variable clarity, locale key naming. Flag inconsistencies that cause real confusion or route/binding bugs, not bikeshedding.

For EVERY finding report: **id (SEC/PERF/DB/ARCH/CLEAN/API/NAME-nn), lane, severity (Critical/High/Medium/Low), file:line, what's wrong, why it matters (concrete failure/maintenance scenario), and a proposed fix.** Prefer a few real, verified findings over a long speculative list. Design-lane findings (ARCH/CLEAN/API/NAME) must name a concrete cost — a bug it causes, a change it makes harder, or a convention it breaks — never "style".

## Phase 3 — Consolidated report

Merge, dedupe, and rank all findings across the seven lanes:

- **Executive summary**: counts by lane × severity, top risks in one line each.
- **Findings table**, ranked Critical→Low: `# | lane | severity | file:line | issue | proposed fix | effort (S/M/L)`.
- Note anything you could NOT verify and why.

Then **STOP and HALT.** Do not edit anything yet.

## Phase 4 — Confirm & convert to tasks

Ask the user (AskUserQuestion) which findings to fix: **all**, a severity floor (e.g. Critical+High only), specific lanes (e.g. security + architecture only), or a hand-picked list. Wait for an explicit answer.

For the confirmed set, create one task per finding with `TaskCreate` (title = short fix summary, body = file:line + fix plan + how to verify). Group tasks by lane and by file so a single agent owns a file (avoids write conflicts).

## Phase 5 — Team-of-agents fix pass

Dispatch **fixer agents in parallel**, one per file-group, capped at a sane number (≤ ~8 concurrent). Give each a disjoint file set OR run in `worktree` isolation to prevent write conflicts. Match agent type to lane: `security-auditor`/`php-pro` for security; `performance-engineer`/`database-optimizer` for perf & schema; `architect-reviewer`/`php-pro` for architecture; `code-reviewer`/`php-pro` for clean-code & naming; `backend-architect`/`php-pro` for API design.

Each fixer must: make the minimal correct change (root cause, not symptom — honor ponytail if active), respect repo conventions, `TaskUpdate` its task to in_progress→completed, and self-verify the narrowest check (`./vendor/bin/pint --test` on touched files, `php artisan test --filter=...` where a test exists, or a targeted tinker/SHOW COLUMNS for schema). Report exactly what changed.

Architecture/naming refactors that ripple across many callers: do the root-cause move once where all callers route through, and update every caller — do not leave siblings half-migrated. Schema migrations: generate a NEW migration, do **not** auto-run it — leave `php artisan migrate` for the user.

## Phase 6 — Final report

Summarise: findings fixed vs deferred (with reasons), files touched, verification results, follow-ups. List the exact commands to run to apply/verify (migrations, full test suite). Do **not** commit or push unless the user asks.

---

**Guardrails:** Analysis is read-only. Nothing is edited before Phase 4 confirmation. No commits/pushes, no `migrate`, no external scans that upload code (Snyk) without saying so. If scope is huge, audit the highest-risk surface first and say what you deferred.
