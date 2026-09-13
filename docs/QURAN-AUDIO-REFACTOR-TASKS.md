# Quran audio refactor — phased implementation tasks

Status: Ready for implementation planning; all implementation tasks remain open.

Design: [Quran reciter and provider proposal](QURAN-RECITER-GROUPING-PROPOSAL.md).

Execution order is **provider independence first, canonical reciter grouping second**. Complete each phase's exit criteria before starting the next phase. This file is the execution checklist; the proposal explains the design decisions. No migrations or application changes have been executed as part of preparing this plan.

## Scope and invariants

- Keep Laravel/MySQL and the existing playback behavior.
- Provider names are adapter keys stored as values. Core column names are provider-neutral.
- Preserve internal recording IDs, audio URLs, available surahs, translations and source-specific timings.
- Group people without deleting their distinct styles, riwayat or recording editions.
- Preserve spoken translations, excluding them from the qari directory.
- Do not assume equal names, style/riwayah pairs or source folders prove that two complete recording configurations are interchangeable.
- The initial audit found 301 recording entries and 27,371 audio rows. Refresh these numbers before implementation; they are evidence, not permanent expected counts.

## Phase overview

| Phase | Workstream | Deliverable | Depends on |
| --- | --- | --- | --- |
| P0 | Provider | Reproducible baseline and consumer inventory | Existing audit |
| P1 | Provider | Generic source schema and adapter contracts | P0 |
| P2 | Provider | Repeatable source backfill and reconciliation | P1 |
| P3 | Provider | Source-aware audio services and player | P2 |
| P4 | Provider | Source-aware imports, admin and exports | P3 |
| P5 | Provider | Verified cutover and removal of vendor columns | P4 |
| M0 | Grouping | Explicit person-to-recording mapping | P5 |
| M1 | Grouping | Canonical profiles, translations and aliases | M0 |
| M2 | Grouping | One-person directory and recording selection | M1 |
| M3 | Grouping | Group-aware imports, admin and exports | M2 |
| M4 | Grouping | Verified rollout and compatibility | M3 |
| M5 | Optional cleanup | Final table/model naming | M4 |

## P0 — Establish the provider migration baseline

**Primary areas:** Quran models, repositories, services, commands, player, public/admin controllers, portal import/export and tests.

- [ ] **P0.1 — Inventory every provider-field reader and writer.** Search for `mp3quran_read_id`, `mp3quran_folder`, `everyayah_folder`, embedded provider URLs and their DTO equivalents. Include seeders, JSON importers, enrichment, admin validation, bulk actions, portal snapshots, cache keys and frontend URL construction. Record the affected paths in the implementation notes.
- [ ] **P0.2 — Capture a repeatable read-only audit.** Export record IDs/slugs/kinds, localized metadata, audio IDs/URLs/formats, folder/timing references and per-recording surah coverage. Produce stable comparison hashes and orphan/duplicate reports; keep database credentials out of artifacts.
- [ ] **P0.3 — Classify actual source configurations.** Account for whole-surah audio from mp3quran and other hosts, EveryAyah-only entries, mixed-source recordings, direct uploaded URLs, missing mappings and spoken translations. Preserve unknown origins with a direct-URL resolver rather than assigning an invented upstream identity.
- [ ] **P0.4 — Capture compatibility behavior.** Record the current `siteReciterId` semantics, default Husary entry, old profile URLs, timing endpoint responses, per-verse fallback and representative queue/seek behavior.
- [ ] **P0.5 — Establish verification and recovery inputs.** Run relevant existing tests on an isolated test database and record unrelated failures. Create a consistent recoverable data snapshot for each deployment environment before its migration. Define how imports/admin writes are paused or reconciled during final backfill and cutover.

**Exit criteria:** Every current source configuration has a documented preservation path; a baseline can compare IDs, metadata and audio mappings before and after migration. No application/database behavior has changed.

## P1 — Add generic recording sources

**Primary areas:** `database/migrations`, `app/Domain/Quran/Models`, Quran repositories, provider infrastructure and service registration.

- [ ] **P1.1 — Create `quran_recording_sources`.** Include `id`, `recording_id`, `provider`, `kind`, `source_key`, nullable string external person/recording/timing IDs, nullable `base_url`, JSON `metadata`, `priority`, `is_active`, `revision` and timestamps. During the provider workstream, `recording_id` references the existing `quran_reciters.id`; no person grouping is needed.
- [ ] **P1.2 — Define source identity and constraints.** Make `source_key` non-null and stable across CDN URL changes. Initially enforce uniqueness within `(recording_id, provider, kind, source_key)`. Index recording/source selection and external lookup fields. Report cross-recording duplicate upstream references without enforcing global uniqueness: IDs 2 and 9 currently share one upstream whole-surah mapping.
- [ ] **P1.3 — Add nullable `source_id` to `quran_recitations`.** Add its index/foreign key while retaining `reciter_id` and the current audio uniqueness constraint through backfill. Choose restrictive deletion for sources with audio so deleting a source cannot silently remove recordings. Do not enable multiple same-format sources yet.
- [ ] **P1.4 — Add source models and repositories.** Provide ownership, active-source lookup, deterministic priority ordering and revision updates. Verify the source and legacy audio owner agree while both foreign keys exist.
- [ ] **P1.5 — Define provider adapter contracts.** Separate catalogue import, surah audio resolution, ayah URL resolution and timing retrieval capabilities. Register mp3quran, EveryAyah and direct-URL implementations in configuration. Keep vendor-specific parsing and endpoint URLs inside adapters.
- [ ] **P1.6 — Define a generic playback result.** Include internal recording/source IDs, source revision, resolved URL or ayah URL mechanism and supported capabilities. Return provider-independent payloads to the player. Metadata must not contain credentials; source URLs must follow the application's URL validation policy.

**Exit criteria:** Additive migrations work on existing and empty test databases; existing playback still uses its original path; source contracts represent all P0 configurations without requiring provider-named columns.

## P2 — Backfill provider sources without data loss

**Primary areas:** New Quran migration/audit commands, source repositories and isolated migration fixtures.

- [ ] **P2.1 — Implement a backfill command.** Provide `--dry-run`, bounded batches, deterministic output and safe reruns. Use transactions per batch, checkpoints and an execution lock. Validate required input before modifying a batch; report ambiguous mappings separately.
- [ ] **P2.2 — Migrate whole-surah configurations.** Create source rows from existing mappings and actual audio URLs. Match external person/recording IDs only with verified catalogue evidence. Preserve timing references even if catalogue enrichment is unavailable. A null timing ID must not prevent source creation.
- [ ] **P2.3 — Migrate per-ayah configurations.** Copy EveryAyah folder paths into source metadata, preserving exact URL resolution. Do not materialize thousands of verse audio rows when the existing resolver can construct them. Keep per-ayah and whole-surah sources separate.
- [ ] **P2.4 — Assign every existing surah audio row to a source.** Respect the actual asset URL rather than assuming every surah uses the record's nominal provider folder. Preserve audio row IDs, formats, URLs and metadata; leave no unresolved rows before cutover.
- [ ] **P2.5 — Preserve duplicate and partial configurations.** Keep IDs 2 and 9 separately addressable, including both EveryAyah folders. Preserve partial libraries such as Minshawi ID 339 with 110 surahs and all spoken translations.
- [ ] **P2.6 — Produce reconciliation output.** Compare baseline and migrated mappings by recording/audio ID, coverage, exact URLs, timing references and verse URL samples. Report both unmapped audio and sources with inconsistent owners. Record legitimate concurrent inserts separately from migration losses.
- [ ] **P2.7 — Test rerun and failure recovery.** Interrupt a batch and rerun; verify no duplicate sources or changed IDs. Verify unavailable upstream enrichment never empties existing audio or guesses external IDs. Re-run on a restored baseline.

**Exit criteria:** Every baseline audio asset and per-verse configuration is represented exactly; a second backfill makes no duplicate entries; interruption recovery works. Runtime and writer cutover are still pending.

## P3 — Make playback source-aware

**Primary areas:** `AyahTimingService`, `VerseAudioUrlResolver`, `RecitationRepository`, `QuranCacheKeys`, `QuranController`, `ReciterDTO`, `resources/views/components/audio-player.blade.php`, surah/profile views and routes.

- [ ] **P3.1 — Resolve audio through source services.** Retain current recording IDs and original source preference. Support sources with only surah audio, only ayah audio or both layouts through separate source entries. Avoid cross-style or cross-riwayah substitution.
- [ ] **P3.2 — Remove provider URL construction from the player.** Return generic validated URL templates, resolved URLs or application resolver endpoints. Keep verse padding/path conventions within adapters and preserve queue/repeat/seek behavior.
- [ ] **P3.3 — Bind timings to the selected audio source.** Fetch using that source's timing reference. Cache by source ID, revision and surah; invalidate on audio location or timing-reference changes. Absence of timing support must retain playable audio.
- [ ] **P3.4 — Add source-aware request compatibility.** Keep existing recording-ID endpoints functional and pinned to legacy source selection during transition. New playback/timing requests carry the exact source and revision; reject mismatched ownership and ignore stale asynchronous timing responses.
- [ ] **P3.5 — Handle source switching explicitly.** Clear old timing state and pending requests. Do not carry wall-clock offsets between unverified performances. Resume at a known ayah only if the new source supports it; otherwise use a defined restart behavior. Prefer no silent provider fallback in the initial rollout.
- [ ] **P3.6 — Check public playback behavior.** Cover surah pages, profile playback, global player, saved recording selection, default Husary selection, verse following and unavailable-source states. Source priority and public visibility must be enforced server-side.

**Exit criteria:** Legacy selections play the same audio; source-specific timings cannot leak across sources; public payloads need no vendor-specific fields. Relevant playback tests pass with deterministic provider fixtures.

## P4 — Move every provider writer and management flow

**Primary areas:** Quran import/sync/enrichment commands, `Mp3QuranReciterImporter`, `QuranReciterSeeder`, admin controllers/requests/views, Quran importer/exporter classes, portal commands and command configuration.

- [ ] **P4.1 — Update catalogue synchronization.** Upsert source references through adapters using stable source keys. Persist external person, recording and timing identities independently. Preserve source and recording IDs on repeat imports and URL changes.
- [ ] **P4.2 — Replace destructive refresh semantics.** Make `--fresh` reconcile sources and assets while retaining recording IDs and curated data, or explicitly block incompatible destructive behavior until the replacement is ready. Define removed-upstream handling; missing/partial upstream responses must not delete valid local data.
- [ ] **P4.3 — Update all other writers.** Convert EveryAyah import, surah audio import, JSON import, seeders, enrichment, bulk operations and any scheduled calls found in P0. During transition, centralize any compatibility writes so legacy columns and source rows cannot drift independently. Stop incompatible legacy-only workers before final reconciliation.
- [ ] **P4.4 — Update admin source management.** Expose generic source fields, capabilities, availability and priority. Validate ownership and source uniqueness; preserve existing authorization. Provider-specific configuration belongs in the adapter's admin configuration, not person fields.
- [ ] **P4.5 — Version import/export formats.** Include source records, stable mappings and source-owned assets in exports and portal snapshots. Support importing the previous format through an explicit translator. Update verification foreign-key maps and backup restore ordering.
- [ ] **P4.6 — Make source replacement reviewable.** Provide a preview of source identity, surah coverage, ayah support and timing availability before changing priority. Test with an alternate direct URL or fixture provider; no live external migration is needed to prove the schema supports replacement.

**Exit criteria:** All known writes and exports understand sources; repeated sync preserves IDs; source replacement needs configuration/data changes rather than new vendor columns. Compatibility policy for old workers and old exports is explicit.

## P5 — Complete provider cutover and remove vendor columns

**Primary areas:** Final migration, deployment notes, cache invalidation and provider regression tests.

- [ ] **P5.1 — Reconcile at the cutover boundary.** Pause affected writes for a bounded window or use the P0 reconciliation strategy. Finish backfill, verify no audio rows lack `source_id`, and verify source ownership against the latest baseline. Restart only compatible workers.
- [ ] **P5.2 — Change audio constraints.** Make `source_id` required and replace `(reciter_id, surah_id, format)` with `(source_id, surah_id, format)`. Verify constraint names and MySQL behavior on a restored copy. Only then enable multiple sources for the same recording/surah/format.
- [ ] **P5.3 — Remove legacy dependencies.** Search all runtime code, templates, validations, DTOs, import/export schemas and tests for vendor columns. Keep legitimate vendor names in adapters/configuration and historical migrations. Remove temporary compatibility writes after the last consumer is converted.
- [ ] **P5.4 — Drop obsolete columns.** Remove `mp3quran_read_id`, `mp3quran_folder` and `everyayah_folder` in a separate cleanup migration after verification. Remove the redundant audio `reciter_id` when every consumer derives recording ownership through its source. Keep the snapshot needed to reconstruct legacy state.
- [ ] **P5.5 — Verify recovery boundaries.** Before multiple sources are enabled, prove the compatibility rollback restores original behavior. After multiple sources exist, an old-schema rollback cannot represent all data: define a forward fix or explicit snapshot restore with reconciliation of later writes; never promise a lossless simple `down()` migration.
- [ ] **P5.6 — Complete the provider regression pass.** Run relevant timing, synchronization, admin audio and playback suites; exercise the production database engine on a migrated copy. Verify public reads, admin saves, export/import round trips, cache invalidation and actual playback smoke checks. Record results and any remaining unrelated failures.

**Exit criteria — provider work complete:** No runtime dependency or live column named after mp3quran/EveryAyah remains in core reciter data; original recordings remain reachable; a second source works; timings follow the chosen source; all affected writes and exports work. Only now start the grouping workstream.

## M0 — Establish canonical person mappings

**Primary areas:** Source catalogue adapters, `ReciterNameMatcher`, a versioned mapping manifest and grouping audit command.

- [ ] **M0.1 — Refresh the catalogue audit.** Recount recording entries, sources, audio rows and translation entries after P5. Use this new snapshot for grouping validation, preserving the original recording IDs.
- [ ] **M0.2 — Build candidate groups from verified identities.** Use `(provider, external_reciter_id)` where reliable; resolve cross-provider identities and legacy-only names through explicit mappings. English slug stripping and Arabic normalization may suggest candidates but must not automatically merge them.
- [ ] **M0.3 — Record each grouping decision.** Map old recording ID to canonical person key with evidence, confidence/status, canonical names/slug, default recording and metadata precedence. Leave unresolved identities in separate profiles; report them for follow-up without blocking unrelated confirmed groups.
- [ ] **M0.4 — Review known groups and distinctions.** Cover Minshawi IDs 33/34/339/340, Husary IDs 20/22/65/344/345/346, Abdul Basit IDs 2/3/9/62 and additional candidates from the refreshed audit. Do not infer null style as murattal. Keep separate labels for recordings sharing style/riwayah.
- [ ] **M0.5 — Define metadata reconciliation.** Choose canonical portraits, country, biography, translations, featured status and ordering with explicit precedence. Preserve original values in the audit. Do not add follower scalars together because overlapping followers cannot be determined.

**Exit criteria:** A deterministic manifest assigns confirmed recording variants to people, records uncertainty and preserves all recording/source identities. No recording deletion or source deduplication is part of grouping.

## M1 — Add canonical profiles and compatibility aliases

**Primary areas:** New profile/translation/alias migrations and models, existing `Reciter` model, grouping migration commands.

- [ ] **M1.1 — Create canonical profiles.** Add `quran_reciter_profiles` with canonical slug, presentation metadata and visibility. Add a dedicated translation table with `(profile_id, locale)` uniqueness; keep existing recording translations intact during transition.
- [ ] **M1.2 — Link existing recordings.** Add indexed nullable `profile_id` to current `quran_reciters`. Retain recording-specific kind, style, riwayah, label, ordering and visibility. Sources continue referencing the same recording IDs. Spoken translations retain their existing path and may remain unlinked to qari profiles.
- [ ] **M1.3 — Define defaults and ownership.** Store the default recording for a profile with same-profile validation and visibility checks. Choose an explicit fallback when that default is unavailable, without silently changing riwayah for a missing surah.
- [ ] **M1.4 — Create legacy alias mappings.** Preserve every old recording ID and slug, resolving to both profile and recording. Use a shared slug reservation rule so canonical slugs cannot shadow another recording's old URL. Existing canonical/base slug requests must have deterministic behavior.
- [ ] **M1.5 — Implement the grouping backfill.** Provide dry-run, transactions, safe reruns, checkpoints and a manifest checksum. Create profiles, copy reconciled metadata and attach existing rows without rewriting source/audio IDs. Every recitation entry gets a profile; unresolved people receive separate provisional profiles.
- [ ] **M1.6 — Verify grouping invariants.** Confirm unchanged recording/source/audio counts and mappings, one profile per confirmed person, unique canonical slugs and complete aliases. Establish how to undo profile associations while preserving provider changes completed in P5.

**Exit criteria:** Canonical profiles exist alongside unchanged recording/source structures; grouping is repeatable and reversible; old IDs still refer to exactly the original recording.

## M2 — Show one person with selectable recordings

**Primary areas:** Reciter query services/repositories/DTOs, public `QuranController`, reciter directory/profile views, surah picker, global player and language files.

- [ ] **M2.1 — Switch directory queries and totals.** Query profiles with eligible active recitation recordings. Deduplicate featured results and search results at the database/query layer before pagination. Search canonical names and aliases, including spelling variants.
- [ ] **M2.2 — Update filtering and counts.** A style/riwayah filter matches people with an eligible recording. Count recording variants separately from distinct available surahs, deduplicating across sources and formats. Do not relabel recording counts as follower counts.
- [ ] **M2.3 — Build the canonical profile page.** Show shared biography/portrait once with available recordings and their actual coverage. Use localized style/riwayah labels and an extra recording label when needed. Render missing/unspecified metadata accurately.
- [ ] **M2.4 — Add person and recording selection.** Select a person then an available recording; hide the extra selector when only one exists. Keep the source layer internal to playback. Honor defaults and same-recording persistence, with clear missing-surah states.
- [ ] **M2.5 — Migrate saved selection safely.** Read old `siteReciterId` as a recording ID and derive its profile. Introduce versioned explicit person/recording preferences if needed; never reinterpret the old number as a profile ID. Preserve the Husary default for new visitors.
- [ ] **M2.6 — Resolve old URLs.** Redirect legacy style slugs to the canonical profile with that recording selected. Avoid loops, preserve locale routing and relevant parameters, and validate that a selected recording belongs to the profile. Keep old numeric audio/timing APIs compatible.
- [ ] **M2.7 — Verify interactive behavior.** Exercise keyboard/mobile selection, Arabic/RTL and locale fallbacks, search, pagination, inactive profiles/recordings, partial libraries and queue/timing state after switching recordings.

**Exit criteria:** Confirmed people appear once in directory/featured results; all old variants remain selectable with original audio; old URLs and saved selections retain their meaning.

## M3 — Make management and imports group-aware

**Primary areas:** Reciter admin controllers/forms/routes, import/sync/enrichment, importer/exporter classes, portal commands, events and caches.

- [ ] **M3.1 — Separate person and recording administration.** Edit biography/portrait at profile level and style/riwayah/recording labels at recording level. Manage sources under recordings. Preserve authorization and validate profile → recording → source → audio ownership on nested operations.
- [ ] **M3.2 — Update grouping-aware imports.** Match or create the canonical person, then recording and sources. Use the M0 mapping evidence for cross-provider matches. Reimporting must not recreate duplicate profiles or replace curated metadata without an explicit policy.
- [ ] **M3.3 — Preserve identity through refreshes.** Extend P4 refresh behavior to retain profile associations, aliases, defaults, recording IDs and source IDs. Handle upstream removals through the established reconciliation policy rather than cascading away a person.
- [ ] **M3.4 — Update enrichment, statistics and bulk operations.** Enrich shared metadata once per person; distinguish person counts from recording/source counts. Define deactivation, deletion and reordering behavior at each level. Prevent deleting a profile with retained recordings accidentally.
- [ ] **M3.5 — Version grouped imports/exports.** Include profiles, profile translations, recordings, aliases, sources and audio with explicit ID mapping. Support the previous flat format by creating provisional profiles and applying known mappings; verify restore ordering and orphan checks.
- [ ] **M3.6 — Invalidate the complete cache chain.** Updating a profile affects directory/profile/featured caches; recording/source edits affect availability and playback caches. Preserve source/revision timing isolation introduced in P3.

**Exit criteria:** Admins and importers maintain the new hierarchy consistently; repeat imports do not recreate duplicate people; exports can restore all entities and compatibility mappings.

## M4 — Validate and roll out grouping

**Primary areas:** Grouping integration tests, existing Quran/admin suites, deployment notes and audit reports.

- [ ] **M4.1 — Reconcile the final dataset.** Compare the M0 snapshot against current data, accounting for legitimate new writes. Confirm no lost recording/source/audio rows, URLs, timing references or locales; all old IDs/slugs resolve.
- [ ] **M4.2 — Verify the representative cases.** One Minshawi profile with four preserved variants, one Husary profile with six variants and one Abdul Basit profile with four preserved configurations, subject to any evidenced corrections in M0. Preserve the 110-surah Minshawi coverage and both Abdul Basit per-verse folders.
- [ ] **M4.3 — Exercise boundary cases.** Cover same style/different riwayah, same style/same riwayah/different recording, null style, unknown person identity, inactive parent, inactive source, unavailable default and spoken translations. Verify malicious cross-profile recording/source selections are rejected.
- [ ] **M4.4 — Check query behavior.** Confirm eager loading, indexed parent/source lookups, accurate pagination and one-person counts. Measure representative directory/profile payloads and query counts against P0; address observed regressions without unrelated optimization work.
- [ ] **M4.5 — Complete rollout and recovery notes.** Reconcile final grouping writes, switch grouped reads, invalidate/version affected caches and restart compatible workers. Smoke-check public/admin/import/export paths. Document reverting grouped views/associations while keeping the provider schema intact.
- [ ] **M4.6 — Record completion evidence.** Attach audit differences, test results, manual playback observations and any unresolved identity candidates. Uncertain identities must be visible as outstanding data review, not silently marked merged.

**Exit criteria — grouping work complete:** One main profile per confirmed reciter; every distinct recording remains playable; sources remain provider-neutral; all identified compatibility and integrity checks pass.

## M5 — Optional naming cleanup after stable rollout

This phase improves schema naming only. P5 and M4 already deliver the requested behavior.

- [ ] **M5.1 — Prepare the complete rename map.** Rename the existing recording table/model to `quran_reciter_recordings`/`ReciterRecording` and canonical profiles to `quran_reciters`/`Reciter`. Account for translation tables, foreign keys, DTO names, validation, queries, fixtures, exports and polymorphic references if the inventory finds any.
- [ ] **M5.2 — Execute a coordinated schema/code transition.** Validate fresh-install and upgrade migration paths on MySQL, stop incompatible workers and preserve all numeric identities. Keep legacy endpoint and preference interpretation explicitly recording-based.
- [ ] **M5.3 — Remove temporary duplication.** Remove legacy shared metadata copies and transitional alias code only where compatibility is still guaranteed and the reconciliation snapshot preserves the original values. Retain public slug and ID aliases for backward compatibility.
- [ ] **M5.4 — Re-run affected integration checks.** Verify import/export restore, grouped directory, old links, saved recording selection, source audio and timing behavior after renaming.

**Exit criteria:** Table/model names match the domain without changing user-visible behavior or breaking legacy identifiers.

## Verification references

Extend existing suites where they cover the behavior, especially `tests/Feature/Quran/SyncMp3QuranTest.php`, `tests/Feature/Quran/SurahAyahTimingsTest.php` and `tests/Feature/Admin/RecitationAdminTest.php`. Add focused migration/idempotency, source-selection, profile-grouping and alias-compatibility tests for the new behavior. Locate existing frontend/player coverage during P0 before choosing additional test tooling.

Run data-mutating tests only against isolated databases. Integration fixtures should not depend on a live upstream service. Each phase records what changed, verification evidence and any migration/recovery limits before its tasks are checked off.
