// Load-test suite for rasoolallah.com — one script, five scenarios.
//
//   k6 run tests/load/k6.js --env SCENARIO=smoke
//   k6 run tests/load/k6.js --env SCENARIO=load
//   k6 run tests/load/k6.js --env SCENARIO=stress  --env TARGET=origin
//   k6 run tests/load/k6.js --env SCENARIO=spike
//   k6 run tests/load/k6.js --env SCENARIO=soak
//
// See README.md in this directory. Read the "Before you run this" section first —
// Cloudflare and the nginx rate limiter will both distort results if ignored.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.4/index.js';
import { ENDPOINTS, SEARCH_ENDPOINTS, slugEndpoints, buildPool, pick } from './endpoints.js';

// ---------------------------------------------------------------------------
// Configuration
// ---------------------------------------------------------------------------

const HOST      = __ENV.HOST     || 'rasoolallah.com';
const TARGET    = __ENV.TARGET   || 'cdn';         // cdn | origin
const ORIGIN_IP = __ENV.ORIGIN_IP || '51.195.100.16';
const SCENARIO  = __ENV.SCENARIO || 'smoke';
const WITH_SEARCH = __ENV.SEARCH === '1';
const BASE = `https://${HOST}`;

// Peak concurrency knob. Every scenario scales off this, so one number tunes the
// whole suite to whatever "huge traffic" means for your capacity target.
const PEAK = parseInt(__ENV.PEAK || '300', 10);

// ---------------------------------------------------------------------------
// Metrics
// ---------------------------------------------------------------------------

const latency  = {};   // group -> Trend
const GROUPS = ['home', 'nav', 'quran', 'json', 'static', 'article', 'search'];
for (const g of GROUPS) latency[g] = new Trend(`lat_${g}`, true);

const throttled = new Rate('throttled_429_503');   // rate limiter hits, NOT app failures
const serverErr = new Counter('server_errors_5xx');
const notFound  = new Counter('not_found_404');
const ttfb      = new Trend('ttfb', true);
const cfHit     = new Rate('cloudflare_cache_hit');

// ---------------------------------------------------------------------------
// Scenarios
// ---------------------------------------------------------------------------

const P = (frac) => Math.max(1, Math.round(PEAK * frac));

const ALL_SCENARIOS = {
  // Sanity: does every endpoint group actually work? Run this first, always.
  smoke: {
    executor: 'shared-iterations',
    vus: 2, iterations: 60, maxDuration: '3m',
    exec: 'browse', tags: { scenario: 'smoke' },
  },

  // Realistic sustained traffic with a warm-up ramp. This is the number you quote.
  load: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: '2m', target: P(0.25) },   // warm up: opcache, buffer pool, CF edge
      { duration: '5m', target: P(0.25) },
      { duration: '2m', target: P(0.6)  },
      { duration: '5m', target: P(0.6)  },
      { duration: '2m', target: 0 },
    ],
    gracefulRampDown: '30s',
    exec: 'browse', tags: { scenario: 'load' },
  },

  // Find the knee. Ramps past capacity until latency/errors break the thresholds.
  stress: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: '2m', target: P(0.5)  },
      { duration: '2m', target: P(1.0)  },
      { duration: '2m', target: P(1.5)  },
      { duration: '2m', target: P(2.0)  },
      { duration: '3m', target: P(2.0)  },
      { duration: '2m', target: 0 },
    ],
    gracefulRampDown: '1m',
    exec: 'browse', tags: { scenario: 'stress' },
  },

  // The "someone shared it on TV" case: near-instant jump to peak.
  spike: {
    executor: 'ramping-arrival-rate',
    startRate: 10, timeUnit: '1s',
    preAllocatedVUs: P(0.5), maxVUs: P(3),
    stages: [
      { duration: '1m',  target: 20      },   // baseline
      { duration: '15s', target: P(4)    },   // spike: 4x peak VUs worth of req/s
      { duration: '2m',  target: P(4)    },
      { duration: '30s', target: 20      },   // recovery — watch this window
      { duration: '2m',  target: 20      },
    ],
    exec: 'browse', tags: { scenario: 'spike' },
  },

  // Long, moderate. The only scenario that catches leaks — which is the point,
  // given the box has 3.7 GB and php-fpm workers recycle every 500 requests.
  soak: {
    executor: 'constant-vus',
    vus: P(0.3), duration: __ENV.SOAK_DURATION || '2h',
    exec: 'browse', tags: { scenario: 'soak' },
  },
};

// ---------------------------------------------------------------------------
// Thresholds — deliberately different per target. Through Cloudflare you are
// measuring the edge; against origin you are measuring the actual app.
// ---------------------------------------------------------------------------

const isOrigin = TARGET === 'origin';

const THRESHOLDS = {
  http_req_failed:   ['rate<0.01'],
  http_req_duration: [
    isOrigin ? 'p(95)<1500' : 'p(95)<800',
    isOrigin ? 'p(99)<3000' : 'p(99)<2000',
  ],
  server_errors_5xx: ['count<1'],          // any 5xx is a real bug, not capacity
  lat_home:   [isOrigin ? 'p(95)<1200' : 'p(95)<600'],
  lat_quran:  [isOrigin ? 'p(95)<2000' : 'p(95)<1200'],   // heaviest read path
  lat_json:   [isOrigin ? 'p(95)<1000' : 'p(95)<500'],
  lat_static: ['p(95)<300'],
  checks:     ['rate>0.99'],
};

// stress/spike are meant to break things — assert only on correctness, not speed.
if (SCENARIO === 'stress' || SCENARIO === 'spike') {
  delete THRESHOLDS.http_req_duration;
  delete THRESHOLDS.lat_home;
  delete THRESHOLDS.lat_quran;
  delete THRESHOLDS.lat_json;
  THRESHOLDS.http_req_failed = ['rate<0.10'];
  THRESHOLDS.checks = ['rate>0.90'];
}

export const options = {
  scenarios: SCENARIO === 'all'
    ? ALL_SCENARIOS
    : { [SCENARIO]: ALL_SCENARIOS[SCENARIO] || ALL_SCENARIOS.smoke },
  thresholds: THRESHOLDS,
  // Bypass Cloudflare and hit nginx directly. SNI/Host stay rasoolallah.com so
  // TLS and the vhost still match; only DNS resolution is overridden.
  hosts: isOrigin ? { [HOST]: ORIGIN_IP } : {},
  // Bodies are only needed for the content checks in browse(); dropping them
  // keeps the k6 process itself from becoming the bottleneck at high VU counts.
  discardResponseBodies: false,
  noConnectionReuse: false,
  userAgent: 'k6-loadtest/1.0 (+rasoolallah.com capacity testing)',
  summaryTrendStats: ['avg', 'min', 'med', 'p(90)', 'p(95)', 'p(99)', 'max'],
};

// ---------------------------------------------------------------------------
// Setup — discover real slugs so we test real pages, not 404s
// ---------------------------------------------------------------------------

export function setup() {
  console.log(`target=${TARGET} host=${HOST} scenario=${SCENARIO} peak=${PEAK}`);

  const discovered = { articles: [] };
  const res = http.get(`${BASE}/sitemap.xml`, { timeout: '30s' });

  if (res.status === 200 && res.body) {
    // Cheap regex beats an XML parser here; the sitemap is machine-generated.
    const locs = res.body.match(/<loc>([^<]+)<\/loc>/g) || [];
    discovered.articles = locs
      .map((l) => l.replace(/<\/?loc>/g, ''))
      .filter((u) => u.includes('/articles/'))
      .map((u) => u.split('/articles/')[1])
      .filter(Boolean)
      .slice(0, 200);
  }

  if (!discovered.articles.length) {
    console.warn('sitemap.xml gave no article slugs — article pages will be skipped');
  } else {
    console.log(`discovered ${discovered.articles.length} article slugs`);
  }

  // Fail fast rather than burning a 2h soak against a dead origin.
  const health = http.get(`${BASE}/`, { timeout: '30s' });
  if (health.status !== 200) {
    throw new Error(`origin not healthy: GET / returned ${health.status}`);
  }

  return discovered;
}

// ---------------------------------------------------------------------------
// The VU body
// ---------------------------------------------------------------------------

export function browse(discovered) {
  const sets = [ENDPOINTS, slugEndpoints(discovered)];
  if (WITH_SEARCH) sets.push(SEARCH_ENDPOINTS);
  const pool = buildPool(sets);

  const ep = pick(pool);
  const url = BASE + ep.path();

  const res = http.get(url, {
    tags: { group: ep.group },
    // 429/503 are the rate limiter working as designed, not an app failure.
    // Counting them in http_req_failed would hide real 5xx behind them.
    responseCallback: http.expectedStatuses(200, 301, 302, 304, 429, 503),
  });

  latency[ep.group]?.add(res.timings.duration);
  ttfb.add(res.timings.waiting);
  throttled.add(res.status === 429 || res.status === 503);
  if (res.status >= 500 && res.status !== 503) serverErr.add(1);
  if (res.status === 404) notFound.add(1);

  const cf = (res.headers['Cf-Cache-Status'] || '').toUpperCase();
  if (cf) cfHit.add(cf === 'HIT');

  check(res, {
    'status ok or throttled': (r) => [200, 301, 302, 304, 429, 503].includes(r.status),
    'no 5xx from app': (r) => r.status < 500 || r.status === 503,
    'body not empty': (r) => r.status !== 200 || (r.body && r.body.length > 0),
  }, { group: ep.group });

  // Real users read. Without think time you are testing a benchmark, not traffic.
  sleep(Math.random() * 3 + 1);
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

export function handleSummary(data) {
  const stamp = `${SCENARIO}-${TARGET}`;
  return {
    stdout: textSummary(data, { indent: '  ', enableColors: true }),
    [`tests/load/results/${stamp}.json`]: JSON.stringify(data, null, 2),
  };
}
