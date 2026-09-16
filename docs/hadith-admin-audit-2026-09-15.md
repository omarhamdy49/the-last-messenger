# Hadith and admin action audit — 15 September 2026

## Result and deployment status

Fixes are in the local working tree, uncommitted and not deployed. Chrome confirmed that the live Nawawi and Shah Waliullah collection pages still display an empty book index. Local Chrome verification shows Nawawi's 42 narrations, including records 41–42 on page 3 and the full text of record 42; Shah Waliullah also displays its narrations locally.

## Empty collections: display failure, not missing imports

The live administration inventory contains 12 collections and 37,957 hadiths. The three collections below have direct narrations and intentionally have no books:

| Collection | Imported hadiths | Books |
| --- | ---: | ---: |
| `nawawi40` | 42 | 0 |
| `qudsi40` | 40 | 0 |
| `shahwaliullah40` | 40 | 0 |

Public and admin collection detail templates always displayed books. They now display paginated narrations for collections with `has_books=false`. Structured collections keep their book index. Public queries and counts honor publication state of the collection, book, chapter, and narration. Reimporting these three collections is unnecessary.

Hadith browse caches also had no invalidation callers. Model saves/deletes and bulk import transactions now invalidate collection, total, and grade caches after commit. Admin narration previews fall back to available English text for unpublished records missing Arabic. Statistic cards render their actual values immediately instead of initially displaying zero.

## Reproduced action failures and fixes

- **Individual hadith removal:** narrator links could trigger a foreign-key restriction and a server error. Deletion now removes that hadith's narrator links and narration in one transaction, preserving people profiles and other narrations.
- **Populated collection removal:** the server deliberately refuses to remove a collection containing books/hadiths. That guard remains, with error feedback. It is not permission to cascade-delete the collection.
- **Confirmation UI:** hadith removal and selected playlist, menu, static-page, location, and Quran actions use the shared confirmation modal. Confirmation submits the original form; cancellation submits nothing. Existing session success/error feedback produces toasts.
- **Tags and categories:** hardcoded `/admin/...` paths omitted the configured `/su/portal/v1/admin` prefix. Live tag edit reproduced a 404. Affected links and requests now use generated routes.
- **Unused tags:** the selected-delete callback did no work. It now submits selected IDs with confirmation and redirect feedback.
- **Tag deletion:** normal forms now redirect with feedback; AJAX callers retain JSON responses.
- **Tag merge:** the modal's payload and inventory differed from the controller's contract. Multi-source existing/new destination merges now work transactionally, retain all content-type links, handle overlapping links, and reject merging into a source. The modal loads all inventory pages. The original single-source API remains supported.
- **Tag pagination:** changing the client page now updates the displayed rows; server page links expose records beyond the initially loaded batch.

## Other empty or inactive surfaces detected

- Live Seerah administration showed 172 events, all drafts and zero published at inspection time. An empty public list therefore cannot establish a failed sync. Separate Seerah work already present in this workspace is outside this patch.
- The dashboard contains an unimplemented chart body (`Chart Ready` / `Integrate Chart.js`) and a period handler that only logs. Some filter, export, compare, and storage upgrade controls have no completed action. These remain outstanding.
- Dashboard activity, storage, and analytics fallback zeros are not reliable evidence that source tables are empty. Live settled counters showed 7 users, 28 articles, 5 media records, 6,236 Quran verses, and 7,806 people.
- The configured importer excludes several additional collections with documented upstream inventory limitations. This audit did not independently revalidate those upstream limitations.

## Verification

- Chrome: live empty collection reproduction; local Shah Waliullah and Nawawi content, Nawawi pagination and final narration; shared confirmation cancel/confirm in a no-write harness using the actual Blade components.
- Regression checks cover bookless visibility, pagination, unpublished records, import cache refresh, delete restrictions, narrator cleanup, tag form/AJAX behavior, and merge preservation.
- Final broad run: 113 tests passed; one new fixture violated the existing rule that published hadiths require Arabic. Corrected that fixture to unpublished and reran the entire hadith admin test file: 18 passed, 48 assertions. All 114 distinct tests therefore pass across those runs.
- Admin route smoke exercised 143 static GET routes; 96 parameterized routes were skipped by that smoke test, with targeted tests covering the changed parameterized actions.
- Production asset build succeeded with the existing large-chunk warning. Scoped PHP formatting and whitespace checks passed.

## Release follow-up

Deploy the changed application files and rebuilt assets through the normal release process, refresh compiled views, and invalidate the existing hadith browse cache once. Then verify the three Forty collection pages and admin confirmations on the deployed site. No data reimport or schema migration is needed for these fixes.
