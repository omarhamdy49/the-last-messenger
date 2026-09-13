# Phase 2 — Quran reading quality

[Plan index](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/roadmap/README.md)

**Outcome:** better copying, playback, and Tajweed guidance using one canonical text source and the existing shared player. R14 can start before the complete import inventory; R13 and R15 need explicit source/reciter coverage.

## R13 — Tajweed Color Coding for Quran Reader

**Rewritten outcome:** add verified Tajweed annotations without changing canonical verse text.

**Assessment:** new feature. No integrated Tajweed dataset/renderer was found. Its main risk is text/annotation integrity, followed by readability and dataset compatibility.

**Evidence:** [reader view](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/quran/surah.blade.php), [Quran import commands](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/app/Console/Commands/Quran/).

**Priority / owner / estimate:** reading enhancement; Quran editor + backend/frontend + QA; 7–12 days after dataset selection. Depends on R07's canonical edition/version contract and reviewed annotation rights.

### Action items

- [ ] **R13.1** Select a sourced annotated dataset matching the displayed script/edition. Record license/attribution, annotation rules, version, and coverage; obtain expert review of the mapping.
- [ ] **R13.2** Define an annotation format using validated boundaries in the exact canonical string. Account for Arabic combining marks and grapheme behavior; never calculate arbitrary offsets against normalized search text.
- [ ] **R13.3** Build an import/preflight check that proves stripping presentation annotations yields the identical canonical verse text for the full corpus across all 114 surahs.
- [ ] **R13.4** Add a reader toggle and localized legend with rule names/explanations; persist the preference and render a readable uncolored fallback on missing/invalid annotations.
- [ ] **R13.5** Provide non-color ways to identify a rule when needed, adequate contrast in supported themes, keyboard/touch access, and RTL wrapping without disturbing ayah markers.
- [ ] **R13.6** Verify compatibility with R14 copy formats, selection, search highlighting, font settings, and R15 active-verse highlighting. Export canonical text rather than display markup.
- [ ] **R13.7** Validate full annotation coverage programmatically and review difficult samples with an editor; measure long-surah rendering overhead and device behavior.

**Acceptance:** every annotated verse reproduces the canonical text exactly; unsupported/missing data falls back visibly and safely; all 114 surahs are accounted for; toggle/legend work in supported locales and modes; active playback and copy remain correct.

**Rollout / requirement revision:** deliver a reviewed sample behind a toggle, then enable the full validated version. Retain the prior annotation version for rollback. The original all-surah requirement is retained; a partial dataset must be labeled a preview, not Done. Regex coloring without authoritative annotations is excluded.

## R14 — Verse Copy/Share with Configurable Formats

**Rewritten outcome:** extend verse copying with exact formats, ranges, and honest delivery feedback.

**Assessment:** partial. The reader already copies Arabic, selected translation, and a reference; a shared sharing component exists. Build one formatter that all entry points reuse.

**Evidence:** [reader and copy action](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/quran/surah.blade.php), [shared components](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/components/).

**Priority / owner / estimate:** useful bounded enhancement; frontend + Quran editor + QA; 3–5 days. Depends on stable reference/edition metadata from R07, not the entire content import target.

### Action items

- [ ] **R14.1** Define output fixtures for Arabic only, translation only, both, reference inclusion, plain text, formatted text, and academic citation including translator/edition attribution.
- [ ] **R14.2** Extract/extend one pure formatting path using canonical verse data and selected edition; add a preview and persist reasonable format preferences.
- [ ] **R14.3** Add bounded contiguous multi-verse selection with explicit range labels; validate start/end, preserve order, and handle unavailable translations without silently substituting another edition.
- [ ] **R14.4** Wire clipboard and available native/platform sharing to that formatter. Encode share URLs safely, avoid duplicating reference text, and clearly identify when a platform only receives a link.
- [ ] **R14.5** Handle clipboard denial/unavailable APIs with a selectable-text fallback. Confirm copy success only after the API succeeds; distinguish opening a share destination from actually posting content.
- [ ] **R14.6** Verify Arabic marks, whitespace, ranges, long text, locale references, quotation formatting, keyboard focus, mobile share behavior, and the absence of Tajweed/display HTML in copied plain text.

**Acceptance:** each format matches approved fixtures; the selected range and edition are correct; errors are recoverable; WhatsApp/X/Facebook/native/clipboard entry points deliver the supported payload accurately. No text is automatically posted during testing.

**Rollout / requirement revision:** keep the existing single-verse action as the default. Add choices progressively and cap ranges for usability. Platform-specific limits should be explained in the UI; opening a share sheet is not evidence that a post was sent.

## R15 — Audio-Text Synchronization for Quran Recitation

**Rewritten outcome:** complete and verify the existing shared playback state and timing coverage.

**Assessment:** substantial existing implementation. The shared player already handles timing lookup, current ayah, per-verse queues, speed, and saved state. Current playlist work modifies the same player and page transitions. Remaining gaps require behavioral audit and data coverage, not another audio architecture.

**Evidence:** [shared player](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/components/audio-player.blade.php), [reader](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/quran/surah.blade.php), [playlist view](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/resources/views/public/quran/playlists/show.blade.php), [existing audio work plan](/Volumes/Data/work/projects/the-last-massenger/dev/the-last-messenger/docs/QURAN-AUDIO-REFACTOR-TASKS.md).

**Priority / owner / estimate:** reader correctness; frontend/audio owner + QA + content reviewer; 5–9 days excluding new timing acquisition. Coordinate with the active playlist task; depends on R07's reciter/audio manifest.

### Action items

- [ ] **R15.1** Inventory current behavior and define one state model for source, reciter, surah/ayah, playlist, repeat mode, range, speed, position, and auto-next preference.
- [ ] **R15.2** Produce a coverage matrix by reciter/recording: per-verse audio, full-surah audio, timing source/version, missing segments, and bismillah/intro offsets. Expose supported synchronization only for the applicable recording.
- [ ] **R15.3** Complete next/previous, repeat one/range, continuous playback from any verse, and the required 0.5/0.75/1/1.25/1.5 speeds without conflicting with playlist auto-next.
- [ ] **R15.4** Reconcile verse highlighting and scrolling on seeking, reciter changes, browser navigation, restored sessions, and playback completion. Stop automatic scrolling when the user intentionally reads elsewhere and provide a return-to-current action.
- [ ] **R15.5** Version and validate saved state; clear unavailable tracks and prevent one account's private playlist state leaking into another session. Handle corrupt browser storage and blocked autoplay.
- [ ] **R15.6** Exercise delayed/missing audio, failed timing lookup, end-of-range boundaries, rapid repeated taps, background/foreground changes, and mobile gesture controls.
- [ ] **R15.7** Review synchronization samples against the recording and agree a timing tolerance from the source quality. Add deterministic boundary/transition tests plus manual device audio checks.

**Acceptance:** one player remains authoritative across pages; controls move to the expected verse/track; repeat cannot escape its selected range; unavailable timing is clearly degraded rather than misleadingly highlighted; resume is safe; the declared reciter coverage has been tested.

**Rollout / requirement revision:** merge/reconcile the existing playlist work first. Keep per-verse/plain audio playback available if timing fails, and roll back the synchronization slice independently. Retain the original control/coverage requirements; do not mark all reciters supported based on one timing source.

