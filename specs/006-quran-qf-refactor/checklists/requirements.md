# Requirements Checklist: Quran Module Refactor on the Quran Foundation Content API

**Purpose**: Prove each success criterion in `spec.md` against something runnable
**Created**: 2026-09-17
**Feature**: [spec.md](../spec.md)

## Content Quality

- [x] No [NEEDS CLARIFICATION] markers remain in the spec or the plan
- [x] Requirements are testable and unambiguous
- [x] Scope is bounded: kept Ask-AI and playlists, moved live channels to Media, dropped the rest
- [x] Dependencies and assumptions recorded (QF credentials, undocumented rate limits, asset size)

## Success criteria

| Criterion | Proof |
|---|---|
| SC-001 bootstrap parity (114 / 6,236 / words / 30 / 60 / 240 / 7 / 558 / 604) | `quran:qf:bootstrap` on the dev database, printed gates recorded in `rollout-dev.md` |
| SC-002 a second bootstrap and sync write nothing | re-run recorded in `rollout-dev.md`; `tests/Feature/Quran/Import/*` assert idempotent re-runs |
| SC-003 API 200/401/404/422, admin 302/403/422, no N+1 | `tests/Feature/Api/Quran/QuranApiTest.php`, `tests/Feature/Admin/Quran/QuranAdminTest.php`, `tests/Feature/NoNPlusOneQueriesTest.php`, `tests/Feature/Web/QuranPublicQueryCountTest.php` |
| SC-004 every kept page renders in ar and en | `tests/Feature/Web/QuranPublicPagesTest.php` plus the browser walkthrough in `rollout-dev.md` |
| SC-005 test, pint, deptrac, build | full suite green except the pre-existing `baseline.txt` failure; `pint` clean; `deptrac` 0 violations and 0 errors; `npm run build` succeeds |
| SC-006 no reference to the removed names | `grep -rnE "quran_surahs\|quran_recording_sources\|reciter_translations\|everyayah\|qurancdn\|RevelationContext\|ReciterProfile\|RecordingSource\|Domain\\\\Quran\\\\Application\|SurahNameIndex\|VerseAudioUrlResolver" app routes resources config database tests` returns only the `Mp3QuranReciterFetcher` lines and one migration's history comment |
| SC-007 API key, secret, scope, origin, 429, CORS | `tests/Feature/Api/ApiClientAuthTest.php`, `tests/Feature/Api/ApiSecurityBoundaryTest.php`, `tests/Feature/Admin/ApiClientAdminTest.php`, and the scope cases in `tests/Feature/Api/Quran/QuranApiTest.php` |

## Deviations recorded in Complexity Tracking

- [x] No CQRS layer: reads and writes both go controller → service → repository (CLAUDE.md updated)
- [x] The QF-shaped API is shaped by `QuranApiService`, not by 14 Resource classes and 11 controllers
- [x] Cross-content (hadith references, reflections, QF articles) is deferred: `quran:qf:doctor`
      reports `hadith: not_found` and `reflect: insufficient_scope` on the current credentials
