# Sunnah.com Parity Worklist

From `scripts/audit_sunnah_api_parity.php` (hadith text) and
`scripts/audit_sunnah_books_chapters.php` (structure) — both read-only, run live
against the Sunnah.com API.

**Books are clean.** Across all six collections the API serves, every book is present,
correctly numbered, and correctly titled in Arabic and English. Zero discrepancies.
Malik is excluded throughout: the API serves no data for it (`{"data":[],"total":0}`).

## 1. Chapters

| Collection | Missing locally | Number differs | Name differs | Per-book count differs |
|---|---:|---:|---:|---:|
| bukhari | 533 | 9 | 130 | — |
| muslim | 6 | 2 | — | — |
| abudawud | 7 | 0 | — | — |
| tirmidhi | 27 | 3 | 30 | — |
| nasai | 12 | 43 | 6 | — |
| ibnmajah | 22 | 0 | 2 | 3 |
| **total** | **607** | **57** | **168** | **3** |

### Number differences are largely a notation split, not wrong data

- nasai book 45: local `5, 6` vs API `5.00`
- nasai book 45: local `6, 7` vs API `6.00`
- nasai book 45: local `8, 9` vs API `8.00`

## 2. Hadith text

| Collection | Text mismatch | Text variant (near-identical) |
|---|---:|---:|
| bukhari | 3 | 12 |
| muslim | 3 | 0 |
| abudawud | 0 | 0 |
| tirmidhi | 104 | 0 |
| nasai | 10 | 0 |
| ibnmajah | 8 | 0 |

**These need scholarly judgement, not an automated fix.** A `text_variant` scores
near-identical (diacritics/spacing); a `text_mismatch` is a substantive wording difference.


### The mismatches are paired across languages — read that as misalignment, not bad text

Every affected hadith differs in **both** Arabic and English, in an exact 1:1 split:
tirmidhi 52 ar / 52 en, nasai 5 / 5, ibnmajah 4 / 4. If our text were merely a different
translation, only the English would diverge. Both diverging together points instead at
**52 Tirmidhi records whose number maps to a different hadith on sunnah.com** — a numbering
offset rather than corrupted content.

That changes the fix: the question to answer first is whether local hadith N corresponds to
sunnah.com's hadith N at all for those records. Realigning the mapping would resolve them
wholesale; editing text one by one would not, and would risk overwriting correct content.
Bukhari and Muslim differ only in English (3 each), which *is* consistent with an ordinary
translation difference and can be treated separately.

### Most divergent first

| Score | Collection | Hadith | Lang | Compare |
|---:|---|---|---|---|
| 8.42 | tirmidhi | 1756 | en | https://sunnah.com/tirmidhi:1756 |
| 8.64 | tirmidhi | 1526 | en | https://sunnah.com/tirmidhi:1526 |
| 8.82 | tirmidhi | 1599 | en | https://sunnah.com/tirmidhi:1599 |
| 8.95 | tirmidhi | 1424 | en | https://sunnah.com/tirmidhi:1424 |
| 10.89 | tirmidhi | 1637 | en | https://sunnah.com/tirmidhi:1637 |
| 12.55 | tirmidhi | 1767 | en | https://sunnah.com/tirmidhi:1767 |
| 12.84 | tirmidhi | 3758 | en | https://sunnah.com/tirmidhi:3758 |
| 14.12 | tirmidhi | 3367 | en | https://sunnah.com/tirmidhi:3367 |
| 15.93 | tirmidhi | 1579 | en | https://sunnah.com/tirmidhi:1579 |
| 16.09 | tirmidhi | 391 | en | https://sunnah.com/tirmidhi:391 |
| 16.67 | tirmidhi | 1515 | en | https://sunnah.com/tirmidhi:1515 |
| 17.65 | tirmidhi | 1618 | en | https://sunnah.com/tirmidhi:1618 |
| 17.71 | tirmidhi | 3367 | ar | https://sunnah.com/tirmidhi:3367 |
| 18.34 | tirmidhi | 1480 | en | https://sunnah.com/tirmidhi:1480 |
| 18.94 | tirmidhi | 391 | ar | https://sunnah.com/tirmidhi:391 |

## 3. Not audited

- **malik** — API serves nothing.
- **ahmed, adab, bulugh, darimi, mishkat, nawawi40, qudsi40, riyad, shahwaliullah40, shamail** — not yet run.
- 3 book requests failed transiently (2 bukhari, 1 abudawud); those books are unverified.
