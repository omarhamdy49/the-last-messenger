/**
 * sunnah.com book-page extractor — the browser half of the Hadith pipeline.
 *
 * sunnah.com sits behind Cloudflare and returns 403 to non-browser clients, so
 * this must run in a real browser session (Chrome extension), not from Artisan.
 * It reads ONE book page and leaves the result on `window.__extract`, ready to
 * be transported to disk and then imported by `php artisan hadith:import`.
 *
 * Selectors are the verified set from specs/001-hadith-rebuild/contracts/
 * extraction-matrix.md. Two of them are easy to get wrong:
 *
 *  - Grade cells: English rows are [label, value] but Arabic rows are
 *    [value, label], because the Arabic table renders RTL in source order.
 *    We pick the cell that is NOT the label; indexing positionally writes
 *    "حكم :" into raw_grade on every Arabic row.
 *  - Permalinks: `.hadith_permalink` holds no <a> on book pages. Both urns are
 *    in the report handler: reportHadith(<arabic_urn>, 'h<english_urn>').
 *
 * GETTING THE RESULT TO DISK
 *
 * Neither origin can do both halves of the job: Chrome's Private Network Access
 * blocks an https://sunnah.com page from reaching 127.0.0.1, and a loopback page
 * cannot read sunnah.com because of CORS. `window.name`, however, survives a
 * cross-origin navigation in the same tab, so it carries the payload across:
 *
 *   1. on sunnah.com:  await extractSunnahBook('bukhari', '1')
 *                      window.name = window.__extract
 *   2. navigate the SAME tab to
 *                      http://127.0.0.1:8899/relay?collection=bukhari&book=1
 *      The relay page is same-origin with the sink, so it just POSTs.
 *   3. navigate back to sunnah.com for the next book.
 *
 * The sink writes book_<n>.json plus a manifest carrying expected-vs-actual
 * counts and a SHA-256. Compare that digest with the one returned here: equal
 * digests mean the transport was byte-exact.
 *
 * Usage (per book):
 *   await extractSunnahBook('bukhari', '1');   // -> {length, sha256, records}
 */
async function extractSunnahBook(collection, bookNumber) {
  const clean = (el) => (el ? el.textContent.replace(/ /g, ' ').trim() : null);
  // undefined matters: Array.find() returns it when no cell qualifies, and
  // a looser check pushed a grading entry carrying no raw_grade at all.
  const blank = (s) => s === null || s === undefined || s === '';

  const url = `https://sunnah.com/${collection}/${bookNumber}`;
  const res = await fetch(`/${collection}/${bookNumber}`);

  if (!res.ok) {
    throw new Error(`HTTP ${res.status} for ${url}`);
  }

  const doc = new DOMParser().parseFromString(await res.text(), 'text/html');

  // Chapters and hadiths are siblings in document order, so a hadith belongs to
  // the nearest chapter heading above it. There is no containment to rely on.
  const nodes = [...doc.querySelectorAll('.chapter, .actualHadithContainer')];
  const chapters = [];
  const records = [];
  let current = null;

  for (const node of nodes) {
    if (node.classList.contains('chapter')) {
      current = {
        number: clean(node.querySelector('.echapno')) || clean(node.querySelector('.achapno')),
        title: {
          en: clean(node.querySelector('.englishchapter')),
          ar: clean(node.querySelector('.arabicchapter')),
        },
        intro: {
          en: clean(node.querySelector('.echapintro')),
          ar: clean(node.querySelector('.achapintro')),
        },
        sort_order: chapters.length + 1,
      };
      chapters.push(current);
      continue;
    }

    const report = node.querySelector('.reportlink')?.getAttribute('onclick') ?? '';
    const urns = report.match(/reportHadith\(\s*(\d+)\s*,\s*'h?(\d+)'/);
    const share = node.querySelector('.sharelink')?.getAttribute('onclick') ?? '';

    const gradings = [];
    for (const row of node.querySelectorAll('.gradetable tr')) {
      for (const [cls, locale] of [['english_grade', 'en'], ['arabic_grade', 'ar']]) {
        const cells = [...row.querySelectorAll('td.' + cls)].map(clean);
        const value = cells.find((c) => !blank(c) && !/^(Grade|حكم)\s*:?\s*$/.test(c));
        if (!blank(value)) gradings.push({ locale, raw_grade: value });
      }
    }

    const arabicBody = clean(node.querySelector('.arabic_hadith_full'));
    const englishBody = clean(node.querySelector('.english_hadith_full'));
    const texts = {};

    // A locale with no body gets NO entry — never an empty string. English is
    // absent for ~7% of records and an empty body would render as a blank
    // translation panel that looks like a bug (FR-015).
    if (!blank(arabicBody)) {
      texts.ar = { body: arabicBody, sanad: clean(node.querySelector('.arabic_sanad')) };
    }
    if (!blank(englishBody)) {
      texts.en = { body: englishBody, narrated_by: clean(node.querySelector('.hadith_narrated')) };
    }

    records.push({
      // Two forms exist: /bukhari:1 (collection-wide) and /malik/1/1
      // (book-scoped, for collections that have no flat number at all).
      hadith_number: share.match(/'\/[^:'\/]+:([^']+)'/)?.[1] ?? null,
      sunnah_urn: urns ? Number(urns[1]) : null,
      sunnah_urn_en: urns ? Number(urns[2]) : null,
      // POSITION, not number. A chapter number does not identify a chapter:
      // Bukhari repeats one within a book 354 times, and 6 chapters carry no
      // number at all. Attributing by number silently linked 503 hadiths to the
      // wrong chapter — 460 of book 65's 499 among them.
      chapter_index: current ? chapters.length - 1 : null,
      chapter_number: current?.number ?? null,
      reference_rows: [...node.querySelectorAll('.hadith_reference tr')]
        .map((tr) => [...tr.children].map((td) => clean(td)?.replace(/^:\s*/, ''))),
      deprecated: node.querySelectorAll('.deprecated_reference').length > 0,
      texts,
      gradings,
    });
  }

  const payload = {
    source: {
      collection,
      book_number: String(bookNumber),
      url,
      extractor_version: '1.0.0',
    },
    book: {
      number: clean(doc.querySelector('.book_page_number')) ?? String(bookNumber),
      title: {
        en: clean(doc.querySelector('.book_page_english_name')),
        ar: clean(doc.querySelector('.book_page_arabic_name')),
      },
    },
    counts: { chapters: chapters.length, records: records.length },
    chapters,
    records,
  };

  // Compact, single line: every inner newline becomes a \n escape, so the
  // payload survives transport through a text channel without ambiguity.
  const json = JSON.stringify(payload);
  const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(json));
  const sha256 = [...new Uint8Array(digest)].map((b) => b.toString(16).padStart(2, '0')).join('');

  window.__extract = json;
  window.__extractSha = sha256;

  return { collection, book: String(bookNumber), length: json.length, sha256, records: records.length, chapters: chapters.length };
}

/**
 * Discover a collection's real book list. The index links an `about` page that
 * is NOT a book, while `introduction` and `letter` ARE books. The three forties
 * expose no book links at all — they are crawled per hadith instead.
 */
async function listSunnahBooks(collection) {
  const doc = new DOMParser().parseFromString(await (await fetch('/' + collection)).text(), 'text/html');
  const pattern = new RegExp('^/' + collection + '/([^/#?]+)$', 'i');

  return [...new Set([...doc.querySelectorAll('a[href]')]
    .map((a) => (a.getAttribute('href') || '').match(pattern)?.[1])
    .filter(Boolean))]
    .filter((book) => book !== 'about');
}
