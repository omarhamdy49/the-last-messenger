---
description: Deep security + performance + database-schema audit by expert skills, then a confirmed, agent-driven fix pass
argument-hint: "[optional scope: path, module name, or 'diff' for changed files only]"
allowed-tools: Skill, Task, Agent, Bash, Read, Grep, Glob, TaskCreate, TaskUpdate, TaskList, AskUserQuestion
---

# /shield-systems

You are the lead auditor running a **pro-grade, three-lane audit** (Security, Performance, Database Schema) followed by a **confirmed, team-of-agents fix pass**. Act like a staff engineer who owns the outcome, not a linter.

**Scope:** `$ARGUMENTS` — if empty, audit the whole codebase but prioritise `app/`, `routes/`, `database/`, and config. If it names a path/module, confine to it. If it's `diff`, audit only `git diff` against the main branch.

Detect the stack first (`composer.json`, `package.json`) and adapt. This repo is **Laravel 13 / PHP 8.5 / MySQL** — honor its CLAUDE.md conventions (translation tables NOT JSON columns, column-specific eager loading, no `Cache::flush()`, versioned cache keys, route order, `publiclyVisible` scopes, admin vs web guards).

---

## Phase 1 — Load expertise (do this before analysing)

Invoke these skills so their expertise is in context. Security lane: `security-reviewer`, `secure-code-guardian`, `owasp-security`, `09-web-security`, `02-vulnerability-scanner`. Performance lane: `laravel-arch-guardian`, `faang-reviewer`, `code-reviewer`. Database lane: `database-optimizer`, `postgres-pro`, `laravel-specialist`. (`snyk-fix` is available for the fix phase if the Snyk MCP tools are connected.)

Don't narrate every skill load — just apply their guidance.

## Phase 2 — Parallel deep analysis (READ-ONLY)

Dispatch analysis agents **in parallel, in a single message**. Every agent in this phase is strictly read-only: **no edits, no writes, no git changes.** Each returns findings as structured items.

Run at least these three, scaled to scope (add more per lane for a large codebase):

1. **Security agent** (`security-auditor`): OWASP Top 10, authn/authz gaps, injection (SQL/XSS/command), mass-assignment, IDOR, secrets in code, unsafe deserialization, SSRF, CSRF gaps, unbounded input, dependency CVEs. Cross-check the CLAUDE.md security gotchas (silent `Throwable` swallow, bare `is_published` reads bypassing visibility scopes, config-cache outage switch).
   - **Snyk scan (opt-in — uploads to Snyk's cloud):** if the Snyk MCP tools are connected, first **ASK the user to confirm** before running — `snyk_sca_scan` uploads dependency manifests (`composer.lock`/`package-lock.json`) and `snyk_code_scan` (SAST) uploads source code to Snyk's cloud. On an explicit yes, run the scan(s), verify each reported issue against the real code (drop false positives), and fold confirmed findings into this lane with `SNYK-nn` ids (record CVE id + fixed-in version). If the user declines, or the Snyk MCP tools are not connected, **skip and say so** — never scan silently. This is the only step in the audit that leaves the machine.
2. **Performance agent** (`performance-engineer`): N+1 queries, missing eager-load column constraints, `SELECT *`, unindexed hot queries, per-node loops that should be set-based, cache misuse (`Cache::flush`), missing pagination, queue/timeout foot-guns, unbounded memory.
3. **Database-schema agent** (`database-architect`): missing/foreign-key indexes, wrong column types, nullable/constraint gaps, missing unique indexes behind app-level dedup, schema drift vs models (verify with `SHOW COLUMNS` via Bash + artisan/tinker, not docblocks), translation-table integrity, cascade risks.

Instruct each agent to report every finding with: **id, lane, severity (Critical/High/Medium/Low), file:line, what's wrong, why it matters (concrete failure scenario), and a proposed fix.** Tell them to prefer a few real, verified findings over a long speculative list.

## Phase 3 — Consolidated report

Merge, dedupe, and rank all findings. Present a single report:

- **Executive summary**: counts by lane × severity, top 5 risks in one line each.
- **Findings table**, ranked Critical→Low: `# | lane | severity | file:line | issue | proposed fix | effort (S/M/L)`.
- Note anything you could NOT verify and why.

Then **STOP and HALT.** Do not edit anything yet.

## Phase 4 — Confirm & convert to tasks

Ask the user (AskUserQuestion) which findings to fix: **all**, a severity floor (e.g. Critical+High only), or a hand-picked list. Wait for an explicit answer.

For the confirmed set, create one task per finding with `TaskCreate` (title = short fix summary, body = file:line + fix plan + how to verify). Group tasks by lane and by file so a single agent owns a file (avoids write conflicts).

## Phase 5 — Team-of-agents fix pass

Dispatch **fixer agents in parallel**, one per file-group, capped at a sane number (≤ ~8 concurrent). To prevent parallel write conflicts, either give each agent a disjoint file set OR run them in `worktree` isolation. Match agent type to lane: `security-auditor`/`php-pro` for security, `performance-engineer`/`database-optimizer` for perf & schema (migrations for schema changes — never hand-edit committed migrations that already ran; add a new one).

Each fixer must: make the minimal correct change (root cause, not symptom — honor ponytail if active), respect repo conventions, `TaskUpdate` its task to in_progress→completed, and self-verify (run the narrowest check: `./vendor/bin/pint --test` on touched files, `php artisan test --filter=...` where a test exists, or a targeted tinker/SHOW COLUMNS for schema). Report exactly what changed.

Schema migrations: generate a new migration, do **not** auto-run it against any database — leave `php artisan migrate` for the user to run.

## Phase 6 — Final report

Summarise: findings fixed vs deferred (with reasons), files touched, verification results, and any follow-ups. List the exact commands the user should run to apply/verify (migrations, full test suite). Do **not** commit or push unless the user asks.

---

**Guardrails:** Analysis is read-only. Nothing is edited before Phase 4 confirmation. No commits/pushes, no `migrate`, no external scans that upload code (Snyk) without saying so. If scope is huge, audit the highest-risk surface first and say what you deferred.
