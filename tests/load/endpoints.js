// Endpoint catalogue for the rasoolallah.com load tests.
//
// Weights approximate real traffic shape: the homepage and Quran reader dominate,
// listing pages next, JSON/search endpoints last (they are the expensive ones and
// are throttled server-side, so hammering them measures the throttle, not the app).
//
// Every path here is derived from routes/web.php. Numeric routes use ranges that
// are guaranteed valid (114 surahs, 30 juz, 604 pages, 60 hizb). Slug routes are
// discovered at runtime in setup() — see k6.js — because hardcoded slugs rot.

/** @param {number} n */
const int = (n) => Math.floor(Math.random() * n) + 1;
/** @param {Array} a */
export const pick = (a) => a[Math.floor(Math.random() * a.length)];

// group  → used for per-group latency metrics and threshold tuning
// weight → relative selection frequency
// path   → () => string, so parameterised routes vary per iteration
export const ENDPOINTS = [
  // --- Landing / navigation: cheap, Cloudflare-cacheable, highest volume ---
  { group: 'home',      weight: 20, path: () => '/' },
  { group: 'nav',       weight: 4,  path: () => '/quran' },
  { group: 'nav',       weight: 3,  path: () => '/hadith' },
  { group: 'nav',       weight: 3,  path: () => '/articles' },
  { group: 'nav',       weight: 2,  path: () => '/seerah' },
  { group: 'nav',       weight: 2,  path: () => '/media' },
  { group: 'nav',       weight: 2,  path: () => '/sahaba' },
  { group: 'nav',       weight: 1,  path: () => '/series' },
  { group: 'nav',       weight: 1,  path: () => '/topics' },
  { group: 'nav',       weight: 1,  path: () => '/genealogy' },

  // --- Quran reader: the heaviest read path, many rows + translations joined ---
  { group: 'quran',     weight: 14, path: () => `/quran/surah/${int(114)}` },
  { group: 'quran',     weight: 5,  path: () => `/quran/juz/${int(30)}` },
  { group: 'quran',     weight: 4,  path: () => `/quran/page/${int(604)}` },
  { group: 'quran',     weight: 2,  path: () => `/quran/hizb/${int(60)}` },
  { group: 'quran',     weight: 2,  path: () => '/quran/reciters' },
  { group: 'quran',     weight: 1,  path: () => '/quran/asbab-al-nuzul' },
  { group: 'quran',     weight: 1,  path: () => '/quran/live' },

  // --- JSON endpoints hit by the SPA-ish parts of the page ---
  { group: 'json',      weight: 6,  path: () => '/quran/surahs' },

  // --- Static/infra: proves nginx + Cloudflare are doing their job ---
  { group: 'static',    weight: 3,  path: () => '/robots.txt' },
  { group: 'static',    weight: 1,  path: () => '/sitemap.xml' },
];

// Throttled endpoints (Laravel `throttle:search`, nginx zone=api 30r/s).
// Kept in a separate pool so a normal load test does not spend its whole budget
// measuring the rate limiter. Included only when SEARCH=1.
export const SEARCH_ENDPOINTS = [
  { group: 'search', weight: 3, path: () => `/search?q=${pick(['محمد', 'الصلاة', 'بدر', 'الصبر', 'مكة'])}` },
  { group: 'search', weight: 2, path: () => `/hadith/search?q=${pick(['الصلاة', 'الصدق', 'الجهاد'])}` },
  { group: 'search', weight: 2, path: () => '/seerah/events?per_page=20' },
  { group: 'search', weight: 1, path: () => '/seerah/articles-feed' },
  { group: 'search', weight: 1, path: () => '/genealogy/people?per_page=20' },
];

// Slug-based routes. `discovered` is filled by setup() from sitemap.xml; if
// discovery finds nothing these are skipped rather than firing known-404s,
// because 404s make the error-rate threshold meaningless.
export const slugEndpoints = (discovered) => {
  const out = [];
  if (discovered.articles?.length) {
    out.push({ group: 'article', weight: 10, path: () => `/articles/${pick(discovered.articles)}` });
  }
  return out;
};

/** Build a flat weighted pool once, so per-iteration selection is O(1). */
export const buildPool = (endpointSets) => {
  const pool = [];
  for (const set of endpointSets) {
    for (const e of set) {
      for (let i = 0; i < e.weight; i++) pool.push(e);
    }
  }
  return pool;
};
