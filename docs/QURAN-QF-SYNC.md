# Quran: importing and syncing from the Quran Foundation

Everything the site and the API serve for the Quran comes from our own tables. The Quran
Foundation (QF) Content API v4 is called by the importers and the sync job only — never while
handling a request. The one exception is audio: a file's bytes are streamed from its remote URL
unless an admin uploaded it or asked for it to be downloaded.

This document is the operator's guide. The design lives in `specs/006-quran-qf-refactor/`.

## Credentials and configuration

QF uses OAuth2 client credentials with the `content` scope. Tokens live one hour and there is
no refresh token, so `TokenProvider` caches one per process and renews it about thirty seconds
early, behind a lock so parallel workers do not stampede.

Environment:

```
QF_ENV=production
QURAN_API_KEY=...        # client id
QURAN_API_SECRET=...     # client secret, never logged
QF_RATE_LIMIT_PER_SECOND=5
QF_TIMEOUT=30
```

Everything else is in `config/quran.php`: the QF hosts, the default mushaf, the pre-selected
translations per locale, the transliteration resource id, asset paths and sync defaults.

Run `php artisan quran:qf:doctor` before anything else. It prints whether the token was issued,
which capabilities the credentials carry, and the chapter count QF reports. It never prints the
secret.

## First import

`php artisan quran:qf:bootstrap` runs the whole sequence and stops at the first count-parity
gate that fails. The individual steps, in order:

| Command | Fills |
|---|---|
| `quran:qf:import-structure` | chapters and their names, the five section axes, languages |
| `quran:qf:import-verses` | all 11 scripts plus axis numbers, sajdah and image fields |
| `quran:qf:import-words` | words and their per-language glosses |
| `quran:qf:import-catalogue` | `quran_resources` for every kind, inactive by default |
| `quran:qf:import-chapter-infos` | chapter introductions per enabled resource |
| `quran:qf:import-timestamps` | per-verse timings for a chapter reciter |
| `quran:qf:download-assets` | verse images and the QCF page fonts onto the public disk |

Every command is idempotent, chunked and resumable, and prints reconciled counts. Re-running a
finished step changes nothing.

Nothing imported is published or synced by default. An admin enables the resources the site
should carry, under Quran → Resources, which is also where `sync_enabled` is set.

## Keeping it fresh

`php artisan quran:qf:sync` walks the QF mutation feed for the resources that have
`sync_enabled`, applies row creates, updates and deletes, and replaces a whole resource from its
snapshot when QF invalidates it.

Two rules matter:

- The feed is keyed by a **canonical filter** built from the enabled resources, and each filter
  keeps its own `sync_token` in `quran_sync_states`. The token is stored only after the final
  page of a run, so an interrupted run resumes rather than skipping mutations.
- A row an editor has locked (`is_locked`) is never overwritten. Skipped rows are counted and
  itemised in the run report, so an editor's change is visible rather than silently lost.

Each run writes a `quran_sync_runs` row with what it applied, what it skipped and any errors.
The admin sync dashboard reads those two tables; `--bootstrap` starts a filter from scratch.

The scheduler runs the sync nightly. The catalogue import is worth running every so often so
newly published QF resources appear in the admin list as available to enable.

## Reciters

A reciter is a row in `quran_resources`, either kind `chapter_reciter` (one file per chapter)
or `recitation` (one file per verse). Besides the QF catalogue, a reciter can be filled on
demand from a source: set `external_source` to QF or mp3quran and `external_ref_id` to that
source's id, then use Fetch data, Fetch audio files or Fetch timestamps on the reciter page.
Each runs `quran:reciter:fetch {resource}` as a queued job and writes a report of what it found,
wrote and skipped. Audio URLs are stored, not the files, unless `store_audio_locally` is set.

### Portraits and Arabic names

QF's reciter catalogue has no images and, for some reciters, no Arabic name: `/resources/
chapter_reciters` returns id, name, style and qirat only. `quran:reciter:images --download`
fills both from quran.com's public reciter list, matching on the consonant skeleton of the
name so transliteration differences ("AbdulBaset" vs "Abdul Basit") still match, and keeping
one copy of each portrait on the public disk (≈1.4 MB for the whole catalogue) rather than
linking someone else's CDN on every page view. Without `--download` it stores the remote URL.

The step never touches an image or a translation that is already set — an upload or an edit is
editorial and survives, unless you pass `--force`. It is idempotent: a second run reports every
reciter unchanged. About a quarter of the catalogue has no portrait in that source at all; the
run lists them by slug, and the resource form takes an upload (max 5 MB).

Those portraits are quran.com's, not ours. If that matters for your deployment, skip the
command and upload images through the admin instead.

## Rebuilding a database

Never `migrate:fresh`. The runbook in `specs/006-quran-qf-refactor/quickstart.md` dumps first,
drops the old Quran tables by explicit name after asserting which database it is connected to,
then migrates and re-imports.
